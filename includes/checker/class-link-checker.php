<?php
/**
 * Link Checker - Checks backlinks for availability and attributes
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Linktrade_Link_Checker
 */
class Linktrade_Link_Checker {

	/**
	 * User agents for rotation
	 *
	 * @var array
	 */
	private $user_agents = array(
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
	);

	/**
	 * Timeout in seconds
	 *
	 * @var int
	 */
	private $timeout = 15;

	/**
	 * Check a link
	 *
	 * @param string $page_url   The page where the backlink should be.
	 * @param string $target_url The URL that should be linked.
	 * @return array Check result.
	 */
	public function check( $page_url, $target_url ) {
		$start_time = microtime( true );

		$result = array(
			'status'        => 'offline',
			'http_code'     => 0,
			'response_time' => 0,
			'is_nofollow'   => false,
			'is_noindex'    => false,
			'is_sponsored'  => false,
			'redirect_url'  => null,
			'error_message' => null,
			'link_found'    => false,
			'anchor_text'   => null,
			// True when we could not read the page at all (blocked, challenged).
			// The caller must then keep the previous status: an unreadable page
			// is not a statement about the link.
			'unreadable'    => false,
			// True when the link is there but points to another page of the target site.
			'moved'         => false,
		);

		// Make HTTP request. Transport errors, rate limits and server errors get
		// one retry: a single hiccup must never look like a removed link.
		$response = $this->make_request( $page_url );

		if ( $this->is_temporary_failure( $response ) ) {
			sleep( 2 );
			$retry = $this->make_request( $page_url );
			if ( ! $this->is_temporary_failure( $retry ) ) {
				$response = $retry;
			}
		}

		$result['response_time'] = (int) ( ( microtime( true ) - $start_time ) * 1000 );

		// Transport errors (timeout, DNS, TLS) are no statement about the link.
		if ( is_wp_error( $response ) ) {
			$result['unreadable']    = true;
			$result['error_message'] = $response->get_error_message();
			return $result;
		}

		$result['http_code'] = wp_remote_retrieve_response_code( $response );

		// Check for redirect.
		// A switch from http to https, to or from www, or a trailing slash is
		// the same page. Only a different host or path is a redirect worth showing.
		$final_url = $this->get_final_url( $response );
		if ( $final_url && ( $this->get_host( $final_url ) !== $this->get_host( $page_url ) || $this->get_path_key( $final_url ) !== $this->get_path_key( $page_url ) ) ) {
			$result['redirect_url'] = $final_url;
		}

		// Only "not found" (404) and "gone" (410) say that the page no longer
		// exists. Every other error code means we were not allowed to read the
		// page (401, 403, 429, and the 400, 406, 418, 451 that firewalls answer
		// with) or the server could not deliver it (5xx). Then we cannot tell
		// whether the link is still there, and "offline" would be a false alarm.
		if ( (int) $result['http_code'] >= 400 && ! in_array( (int) $result['http_code'], array( 404, 410 ), true ) ) {
			$result['status']        = 'warning';
			$result['unreadable']    = true;
			$result['error_message'] = sprintf(
				/* translators: %d: HTTP status code */
				__( 'Page could not be read (HTTP %d), please verify by hand', 'linktrade-monitor' ),
				$result['http_code']
			);
			return $result;
		}

		// Abort on HTTP error.
		if ( $result['http_code'] >= 400 ) {
			$result['status']        = 'offline';
			$result['error_message'] = sprintf( 'HTTP %d', $result['http_code'] );
			return $result;
		}

		// Parse HTML.
		$body = wp_remote_retrieve_body( $response );

		// Bot protection that answers with HTTP 200 and a JS challenge instead
		// of the page (Cloudflare "Just a moment…", DDoS-Guard and friends).
		if ( $this->is_bot_challenge( $body ) ) {
			$result['status']        = 'warning';
			$result['unreadable']    = true;
			$result['error_message'] = __( 'Bot protection instead of page content, please verify by hand', 'linktrade-monitor' );
			return $result;
		}

		if ( '' === trim( (string) $body ) ) {
			$result['unreadable']    = true;
			$result['error_message'] = __( 'Empty response from server', 'linktrade-monitor' );
			return $result;
		}

		// Without the PHP DOM extension the page cannot be parsed. Say so
		// instead of ending in a fatal error.
		if ( ! class_exists( 'DOMDocument' ) ) {
			$result['unreadable']    = true;
			$result['error_message'] = __( 'The PHP extension "dom" is missing on this server, links cannot be checked', 'linktrade-monitor' );
			return $result;
		}

		// What the page tells search engines, from meta tags and header.
		$robots               = $this->robots_directives( $body, $response );
		$result['is_noindex'] = $robots['noindex'];

		// Find and check backlink.
		$link_result = $this->find_link( $body, $target_url );

		if ( $link_result['found'] ) {
			$result['link_found'] = true;
			// rel="ugc" and a page-wide nofollow devalue the link the same way
			// rel="nofollow" does.
			$result['is_nofollow']  = $link_result['is_nofollow'] || $link_result['is_ugc'] || $robots['nofollow'];
			$result['is_sponsored'] = $link_result['is_sponsored'];
			$result['anchor_text']  = $link_result['anchor_text'];

			// Determine status.
			if ( ! empty( $link_result['target_mismatch'] ) ) {
				// The link is still there but points somewhere else on the same
				// domain (moved page, umlaut/encoding difference, tracking
				// parameter). That is not a lost link, so no false alarm is raised.
				$result['status']        = 'warning';
				$result['moved']         = true;
				$result['redirect_url']  = $link_result['actual_url'];
				$result['error_message'] = sprintf(
					/* translators: %s: URL that is actually linked */
					__( 'Link found, but it points to %s', 'linktrade-monitor' ),
					$link_result['actual_url']
				);
			} elseif ( $result['is_nofollow'] || $result['is_sponsored'] || $result['is_noindex'] ) {
				$result['status'] = 'warning';
			} else {
				$result['status'] = 'online';
			}
		} elseif ( ! empty( $link_result['no_links'] ) ) {
			$result['unreadable']    = true;
			$result['error_message'] = __( 'The page source contains no links at all, it is probably built by JavaScript. Please verify by hand', 'linktrade-monitor' );
		} elseif ( strlen( $body ) >= 3 * MB_IN_BYTES ) {
			// The page was cut off at the size limit: the link may be in the rest.
			$result['unreadable']    = true;
			$result['error_message'] = __( 'Page is too large to be checked completely, please verify by hand', 'linktrade-monitor' );
		} else {
			$result['status']        = 'offline';
			$result['error_message'] = __( 'Link to target page not found', 'linktrade-monitor' );
		}

		return $result;
	}

	/**
	 * Make HTTP request
	 *
	 * @param string $url URL to fetch.
	 * @return array|WP_Error Response or error.
	 */
	private function make_request( $url ) {
		$args = array(
			'timeout'             => $this->timeout,
			'redirection'         => 5,
			'httpversion'         => '1.1',
			'user-agent'          => $this->get_random_user_agent(),
			'headers'             => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8',
				'Accept-Encoding' => 'gzip, deflate',
				'Connection'      => 'keep-alive',
				'Cache-Control'   => 'no-cache',
			),
			'sslverify'           => true,
			// A page is never larger than this in any useful sense; the rest is cut off.
			'limit_response_size' => 3 * MB_IN_BYTES,
		);

		// Only plain web addresses are fetched, and only through the safe
		// variant: it refuses private and loopback addresses, also after a
		// redirect.
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		if ( 'http' !== $scheme && 'https' !== $scheme ) {
			return new WP_Error( 'linktrade_invalid_url', __( 'Only http and https addresses can be checked', 'linktrade-monitor' ) );
		}

		return wp_safe_remote_get( $url, $args );
	}

	/**
	 * Is this response a temporary failure that deserves a retry?
	 *
	 * @param array|WP_Error $response Response.
	 * @return bool True for transport errors, 429 and 5xx.
	 */
	private function is_temporary_failure( $response ) {
		if ( is_wp_error( $response ) ) {
			return true;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 401/403 are included on purpose: rate limits on WordPress.com and
		// several CDNs answer with 403, not 429. Without a retry a single
		// throttled request looks like a blocked page and flips the status.
		return ( 401 === $code || 403 === $code || 429 === $code || $code >= 500 );
	}

	/**
	 * Get random user agent
	 *
	 * @return string User agent string.
	 */
	private function get_random_user_agent() {
		return $this->user_agents[ array_rand( $this->user_agents ) ];
	}

	/**
	 * Get final URL after redirects
	 *
	 * @param array $response HTTP response.
	 * @return string|null Final URL or null.
	 */
	private function get_final_url( $response ) {
		// WP_Http follows redirects, so the final response usually has no
		// Location header. The real end URL sits on the Requests object.
		if ( isset( $response['http_response'] ) && is_object( $response['http_response'] )
			&& method_exists( $response['http_response'], 'get_response_object' ) ) {
			$requests_response = $response['http_response']->get_response_object();
			if ( ! empty( $requests_response->url ) ) {
				return $requests_response->url;
			}
		}

		// Fallback for redirects that were not followed.
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( ! empty( $location ) ) {
			return is_array( $location ) ? end( $location ) : $location;
		}

		return null;
	}

	/**
	 * Does the response body look like a bot-protection interstitial?
	 *
	 * Only markers that a real article would not contain are used. A page that
	 * merely says "just a moment" in its text is a normal page.
	 *
	 * @param string $html HTML content.
	 * @return bool True when it is a challenge page, not the real page.
	 */
	private function is_bot_challenge( $html ) {
		// Real pages are longer than a challenge screen; keep the check cheap.
		if ( strlen( $html ) > 120000 ) {
			return false;
		}

		// Technical markers of the challenge scripts themselves.
		foreach ( array( 'cf-browser-verification', 'cf_chl_opt', '__cf_chl', 'ddos-guard/js-challenge' ) as $marker ) {
			if ( false !== stripos( $html, $marker ) ) {
				return true;
			}
		}

		// Wording counts only in the page title.
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $match ) ) {
			$title = strtolower( trim( wp_strip_all_tags( $match[1] ) ) );
			foreach ( array( 'just a moment', 'attention required', 'checking your browser', 'ddos-guard', 'access denied' ) as $phrase ) {
				if ( 0 === strpos( $title, $phrase ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Read what the page tells search engines: noindex and nofollow, from the
	 * meta tags (robots and googlebot) and from the X-Robots-Tag header.
	 * "none" means both.
	 *
	 * @param string $html     HTML content.
	 * @param array  $response HTTP response.
	 * @return array noindex and nofollow as booleans.
	 */
	private function robots_directives( $html, $response ) {
		$tokens = array();

		// Commented-out markup and script contents are not instructions.
		$markup = preg_replace( array( '/<!--.*?-->/s', '#<script\b.*?</script>#is' ), '', $html );
		if ( null === $markup ) {
			$markup = $html;
		}

		if ( preg_match_all( '/<meta\b[^>]*>/i', $markup, $tags ) ) {
			foreach ( $tags[0] as $tag ) {
				if ( ! preg_match( '/(?<![\w-])name\s*=\s*["\']?\s*(robots|googlebot)(?![\w-])/i', $tag ) ) {
					continue;
				}
				if ( preg_match( '/\bcontent\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $content ) ) {
					$value  = strtolower( implode( '', array_slice( $content, 1 ) ) );
					$tokens = array_merge( $tokens, preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY ) );
				}
			}
		}

		$header       = wp_remote_retrieve_header( $response, 'x-robots-tag' );
		$header_lines = array();
		foreach ( (array) $header as $line ) {
			// One header line can address several crawlers: "googlebot: noindex, bingbot: nofollow".
			$header_lines = array_merge( $header_lines, preg_split( '/,\s*(?=[a-z0-9_-]+\s*:)/i', (string) $line ) );
		}
		foreach ( $header_lines as $line ) {
			$line = strtolower( (string) $line );
			// "googlebot: noindex" addresses one crawler. Rules for other named
			// crawlers (bingbot, otherbot) are not rules for everybody.
			if ( preg_match( '/^\s*([a-z0-9_-]+)\s*:(.*)$/', $line, $match ) && ! in_array( $match[1], array( 'noindex', 'nofollow', 'none', 'unavailable_after', 'max-snippet', 'max-image-preview', 'max-video-preview' ), true ) ) {
				if ( 'googlebot' !== $match[1] ) {
					continue;
				}
				$line = $match[2];
			}
			$tokens = array_merge( $tokens, preg_split( '/[\s,]+/', $line, -1, PREG_SPLIT_NO_EMPTY ) );
		}

		return array(
			'noindex'  => in_array( 'noindex', $tokens, true ) || in_array( 'none', $tokens, true ),
			'nofollow' => in_array( 'nofollow', $tokens, true ) || in_array( 'none', $tokens, true ),
		);
	}

	/**
	 * Find backlink in HTML
	 *
	 * @param string $html       HTML content.
	 * @param string $target_url Target URL to find.
	 * @return array Result with found status and attributes.
	 */
	private function find_link( $html, $target_url ) {
		$result = array(
			'found'           => false,
			'is_nofollow'     => false,
			'is_sponsored'    => false,
			'is_ugc'          => false,
			'anchor_text'     => null,
			'target_mismatch' => false,
			'actual_url'      => null,
			'no_links'        => false,
		);

		$target_host = $this->get_host( $target_url );

		// Without a target host we cannot match anything safely.
		if ( empty( $target_host ) ) {
			return $result;
		}

		$target_key = $this->get_path_key( $target_url );

		$previous_libxml = libxml_use_internal_errors( true );
		$dom             = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml );

		$links = $dom->getElementsByTagName( 'a' );

		// A page without a single link in its source is built in the browser by
		// JavaScript (or is no real page). The link cannot be seen this way,
		// which is not the same as "removed".
		if ( 0 === $links->length ) {
			$result['no_links'] = true;
			return $result;
		}

		// Second-best hit: same host, but a different path.
		$host_match = null;

		// Exact hit that carries nofollow, sponsored or ugc.
		$flagged_match = null;

		foreach ( $links as $link ) {
			$href = trim( $link->getAttribute( 'href' ) );

			if ( empty( $href ) ) {
				continue;
			}

			// Host gate first. A link only counts when it points to our exact
			// domain. Without this, a link to "notexample.org" would match the
			// target "example.org" as a substring and be counted as our backlink.
			if ( $this->get_host( $href ) !== $target_host ) {
				continue;
			}

			// Same host: is it the exact target page? Path compared as a whole,
			// so "/page" does not match "/page-2" or "/page/sub".
			$exact = ( $this->get_path_key( $href ) === $target_key );

			if ( $exact ) {
				$rel   = preg_split( '/\s+/', strtolower( $link->getAttribute( 'rel' ) ), -1, PREG_SPLIT_NO_EMPTY );
				$match = array(
					'found'           => true,
					'is_nofollow'     => in_array( 'nofollow', $rel, true ),
					'is_sponsored'    => in_array( 'sponsored', $rel, true ),
					'is_ugc'          => in_array( 'ugc', $rel, true ),
					'anchor_text'     => trim( $link->textContent ),
					'target_mismatch' => false,
					'actual_url'      => null,
				);

				// A clean link wins. A page can link to the target twice, for
				// example a nofollow link in a comment and a normal one in the text.
				if ( ! $match['is_nofollow'] && ! $match['is_sponsored'] && ! $match['is_ugc'] ) {
					return $match;
				}
				if ( null === $flagged_match ) {
					$flagged_match = $match;
				}
				continue;
			}

			// Same host, different path: remember as second-best.
			if ( null === $host_match ) {
				$host_match = $link;
			}
		}

		if ( null !== $flagged_match ) {
			return $flagged_match;
		}

		// No exact hit, but the domain is still linked: the target page moved
		// or the stored URL is spelled differently (umlauts, encoding).
		if ( null !== $host_match ) {
			$rel                       = preg_split( '/\s+/', strtolower( $host_match->getAttribute( 'rel' ) ), -1, PREG_SPLIT_NO_EMPTY );
			$result['found']           = true;
			$result['target_mismatch'] = true;
			$result['actual_url']      = $host_match->getAttribute( 'href' );
			$result['anchor_text']     = trim( $host_match->textContent );
			$result['is_nofollow']     = in_array( 'nofollow', $rel, true );
			$result['is_sponsored']    = in_array( 'sponsored', $rel, true );
			$result['is_ugc']          = in_array( 'ugc', $rel, true );
		}

		return $result;
	}

	/**
	 * Hostname of a URL, without www and lowercased
	 *
	 * @param string $url URL.
	 * @return string Host or empty string.
	 */
	private function get_host( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( empty( $host ) ) {
			return '';
		}

		$host = function_exists( 'mb_strtolower' ) ? mb_strtolower( $host ) : strtolower( $host );

		// A domain with umlauts can be written as "müller.de" or as
		// "xn--mller-kva.de". Both are the same domain: compare the encoded form.
		if ( preg_match( '/[^\x20-\x7e]/', $host ) && class_exists( '\\WpOrg\\Requests\\IdnaEncoder' ) ) {
			try {
				$host = strtolower( (string) \WpOrg\Requests\IdnaEncoder::encode( $host ) );
			} catch ( Exception $e ) {
				// Not encodable: compare as written.
				unset( $e );
			}
		}

		return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}

	/**
	 * The part of a URL that identifies the page: path without trailing slash,
	 * decoded, plus the query if there is one. Protocol, www and fragment do
	 * not matter.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function get_path_key( $url ) {
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );

		// Compared without regard to case, as before 1.4.0.
		$path = strtolower( rtrim( rawurldecode( $path ), '/' ) );

		// Tracking parameters are not part of the page.
		if ( '' !== $query ) {
			parse_str( $query, $args );
			foreach ( array_keys( $args ) as $key ) {
				if ( 0 === strpos( $key, 'utm_' ) || in_array( $key, array( 'fbclid', 'gclid', 'ref' ), true ) ) {
					unset( $args[ $key ] );
				}
			}
			ksort( $args );
			$query = http_build_query( $args );
		}

		return $path . ( '' !== $query ? '?' . $query : '' );
	}
}
