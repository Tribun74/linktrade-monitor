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
        );

        // Make HTTP request.
        $response = $this->make_request( $page_url );

        $result['response_time'] = (int) ( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            $result['error_message'] = $response->get_error_message();
            return $result;
        }

        $result['http_code'] = wp_remote_retrieve_response_code( $response );

        // Check for redirect.
        $final_url = $this->get_final_url( $response );
        if ( $final_url && $final_url !== $page_url ) {
            $result['redirect_url'] = $final_url;
        }

        // Abort on HTTP error.
        if ( $result['http_code'] >= 400 ) {
            $result['status']        = 'offline';
            $result['error_message'] = sprintf( 'HTTP %d', $result['http_code'] );
            return $result;
        }

        // Parse HTML.
        $body = wp_remote_retrieve_body( $response );

        if ( empty( $body ) ) {
            $result['error_message'] = __( 'Empty response from server', 'linktrade-monitor' );
            return $result;
        }

        // Check noindex in meta.
        $result['is_noindex'] = $this->check_noindex( $body );

        // Find and check backlink.
        $link_result = $this->find_link( $body, $target_url );

        if ( $link_result['found'] ) {
            $result['link_found']   = true;
            $result['is_nofollow']  = $link_result['is_nofollow'];
            $result['is_sponsored'] = $link_result['is_sponsored'];
            $result['anchor_text']  = $link_result['anchor_text'];

            // Determine status.
            if ( $link_result['is_nofollow'] || $result['is_noindex'] ) {
                $result['status'] = 'warning';
            } else {
                $result['status'] = 'online';
            }
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
            'timeout'     => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => $this->get_random_user_agent(),
            'headers'     => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection'      => 'keep-alive',
                'Cache-Control'   => 'no-cache',
            ),
            'sslverify'   => true,
        );

        return wp_remote_get( $url, $args );
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
        $headers = wp_remote_retrieve_headers( $response );

        if ( isset( $headers['location'] ) ) {
            return is_array( $headers['location'] )
                ? end( $headers['location'] )
                : $headers['location'];
        }

        return null;
    }

    /**
     * Check for noindex
     *
     * @param string $html HTML content.
     * @return bool True if noindex found.
     */
    private function check_noindex( $html ) {
        // Meta robots noindex.
        if ( preg_match( '/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]*>/i', $html ) ) {
            return true;
        }

        // Alternative format.
        if ( preg_match( '/<meta[^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]+name=["\']robots["\'][^>]*>/i', $html ) ) {
            return true;
        }

        return false;
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
            'found'        => false,
            'is_nofollow'  => false,
            'is_sponsored' => false,
            'is_ugc'       => false,
            'anchor_text'  => null,
        );

        // Create URL patterns (with/without www, http/https).
        $url_patterns = $this->get_url_patterns( $target_url );

        // Use DOMDocument for clean parsing.
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        $links = $dom->getElementsByTagName( 'a' );

        foreach ( $links as $link ) {
            $href = $link->getAttribute( 'href' );

            if ( empty( $href ) ) {
                continue;
            }

            // Check if URL matches.
            foreach ( $url_patterns as $pattern ) {
                if ( false !== stripos( $href, $pattern ) ) {
                    $result['found']       = true;
                    $result['anchor_text'] = trim( $link->textContent );

                    // Check rel attributes.
                    $rel                   = strtolower( $link->getAttribute( 'rel' ) );
                    $result['is_nofollow'] = false !== strpos( $rel, 'nofollow' );
                    $result['is_sponsored'] = false !== strpos( $rel, 'sponsored' );
                    $result['is_ugc']      = false !== strpos( $rel, 'ugc' );

                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Get URL patterns for matching
     *
     * @param string $url Original URL.
     * @return array URL patterns.
     */
    private function get_url_patterns( $url ) {
        $patterns = array();

        // Original URL.
        $patterns[] = $url;

        // Without protocol.
        $no_protocol = preg_replace( '#^https?://#', '', $url );
        $patterns[]  = $no_protocol;

        // With/without www.
        if ( 0 === strpos( $no_protocol, 'www.' ) ) {
            $patterns[] = substr( $no_protocol, 4 );
        } else {
            $patterns[] = 'www.' . $no_protocol;
        }

        // With other protocol.
        if ( 0 === strpos( $url, 'https://' ) ) {
            $patterns[] = 'http://' . $no_protocol;
        } else {
            $patterns[] = 'https://' . $no_protocol;
        }

        // Without trailing slash.
        $patterns = array_map(
            function ( $p ) {
                return rtrim( $p, '/' );
            },
            $patterns
        );

        // With trailing slash.
        $with_slash = array();
        foreach ( $patterns as $p ) {
            $with_slash[] = $p . '/';
        }
        $patterns = array_merge( $patterns, $with_slash );

        return array_unique( $patterns );
    }
}
