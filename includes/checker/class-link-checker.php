<?php
/**
 * Link Checker - Prüft Backlinks auf Verfügbarkeit und Attribute
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade_Link_Checker {

    /**
     * User Agents für Rotation
     */
    private array $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/120.0.0.0 Safari/537.36',
    ];

    /**
     * Timeout in Sekunden
     */
    private int $timeout = 15;

    /**
     * Link prüfen
     *
     * @param string $page_url Die Seite, auf der der Backlink sein sollte
     * @param string $target_url Die URL, die verlinkt sein sollte
     * @return array Check-Ergebnis
     */
    public function check(string $page_url, string $target_url): array {
        $start_time = microtime(true);

        $result = [
            'status' => 'offline',
            'http_code' => 0,
            'response_time' => 0,
            'is_nofollow' => false,
            'is_noindex' => false,
            'is_sponsored' => false,
            'redirect_url' => null,
            'error_message' => null,
            'link_found' => false,
            'anchor_text' => null,
        ];

        // HTTP-Request ausführen
        $response = $this->make_request($page_url);

        $result['response_time'] = (int) ((microtime(true) - $start_time) * 1000);

        if (is_wp_error($response)) {
            $result['error_message'] = $response->get_error_message();
            return $result;
        }

        $result['http_code'] = wp_remote_retrieve_response_code($response);

        // Redirect prüfen
        $final_url = $this->get_final_url($response);
        if ($final_url && $final_url !== $page_url) {
            $result['redirect_url'] = $final_url;
        }

        // Bei HTTP-Fehler abbrechen
        if ($result['http_code'] >= 400) {
            $result['status'] = 'offline';
            $result['error_message'] = sprintf('HTTP %d', $result['http_code']);
            return $result;
        }

        // HTML parsen
        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            $result['error_message'] = 'Leere Antwort vom Server';
            return $result;
        }

        // noindex im Meta prüfen
        $result['is_noindex'] = $this->check_noindex($body);

        // Backlink suchen und prüfen
        $link_result = $this->find_link($body, $target_url);

        if ($link_result['found']) {
            $result['link_found'] = true;
            $result['is_nofollow'] = $link_result['is_nofollow'];
            $result['is_sponsored'] = $link_result['is_sponsored'];
            $result['anchor_text'] = $link_result['anchor_text'];

            // Status bestimmen
            if ($link_result['is_nofollow'] || $result['is_noindex']) {
                $result['status'] = 'warning';
            } else {
                $result['status'] = 'online';
            }
        } else {
            $result['status'] = 'offline';
            $result['error_message'] = 'Link zur Zielseite nicht gefunden';
        }

        return $result;
    }

    /**
     * HTTP-Request ausführen
     */
    private function make_request(string $url): array|WP_Error {
        $args = [
            'timeout' => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => $this->get_random_user_agent(),
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache',
            ],
            'sslverify' => true,
        ];

        return wp_remote_get($url, $args);
    }

    /**
     * Zufälligen User-Agent wählen
     */
    private function get_random_user_agent(): string {
        return $this->user_agents[array_rand($this->user_agents)];
    }

    /**
     * Finale URL nach Redirects ermitteln
     */
    private function get_final_url(array $response): ?string {
        $headers = wp_remote_retrieve_headers($response);

        if (isset($headers['location'])) {
            return is_array($headers['location'])
                ? end($headers['location'])
                : $headers['location'];
        }

        return null;
    }

    /**
     * Auf noindex prüfen
     */
    private function check_noindex(string $html): bool {
        // Meta robots noindex
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]*>/i', $html)) {
            return true;
        }

        // Alternative Schreibweise
        if (preg_match('/<meta[^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]+name=["\']robots["\'][^>]*>/i', $html)) {
            return true;
        }

        return false;
    }

    /**
     * Backlink im HTML finden
     */
    private function find_link(string $html, string $target_url): array {
        $result = [
            'found' => false,
            'is_nofollow' => false,
            'is_sponsored' => false,
            'is_ugc' => false,
            'anchor_text' => null,
        ];

        // URL-Varianten erstellen (mit/ohne www, http/https)
        $url_patterns = $this->get_url_patterns($target_url);

        // DOMDocument für sauberes Parsing (PHP 8.2+ kompatibel)
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if (empty($href)) {
                continue;
            }

            // Prüfen ob die URL matcht
            foreach ($url_patterns as $pattern) {
                if (stripos($href, $pattern) !== false) {
                    $result['found'] = true;
                    $result['anchor_text'] = trim($link->textContent);

                    // rel-Attribute prüfen
                    $rel = strtolower($link->getAttribute('rel'));
                    $result['is_nofollow'] = strpos($rel, 'nofollow') !== false;
                    $result['is_sponsored'] = strpos($rel, 'sponsored') !== false;
                    $result['is_ugc'] = strpos($rel, 'ugc') !== false;

                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * URL-Varianten für Matching erstellen
     */
    private function get_url_patterns(string $url): array {
        $patterns = [];

        // Original-URL
        $patterns[] = $url;

        // Ohne Protokoll
        $no_protocol = preg_replace('#^https?://#', '', $url);
        $patterns[] = $no_protocol;

        // Mit/ohne www
        if (strpos($no_protocol, 'www.') === 0) {
            $patterns[] = substr($no_protocol, 4);
        } else {
            $patterns[] = 'www.' . $no_protocol;
        }

        // Mit anderem Protokoll
        if (strpos($url, 'https://') === 0) {
            $patterns[] = 'http://' . $no_protocol;
        } else {
            $patterns[] = 'https://' . $no_protocol;
        }

        // Ohne trailing slash
        $patterns = array_map(function($p) {
            return rtrim($p, '/');
        }, $patterns);

        // Mit trailing slash
        foreach ($patterns as $p) {
            $patterns[] = $p . '/';
        }

        return array_unique($patterns);
    }

    /**
     * Batch-Check mehrerer Links
     */
    public function check_batch(array $links, int $delay_ms = 3000): array {
        $results = [];

        foreach ($links as $link) {
            $results[$link->id] = $this->check($link->partner_url, $link->target_url);

            // Verzögerung zwischen Requests
            if ($delay_ms > 0) {
                usleep($delay_ms * 1000);
            }
        }

        return $results;
    }
}
