<?php
namespace nexpell;

class SeoUrlHandler {


    public static function route(?string $uri = null): void 
    {
        $uri = $uri ?? $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));

        if (isset($segments[0]) && preg_match('/^[a-z]{2}$/i', $segments[0])) {
            $_GET['lang'] = strtolower($segments[0]);
            $_GET['site'] = $segments[1] ?? 'start';
            $_GET['action'] = $segments[2] ?? null;

            // Ab Index 3 key/value-Paare parsen
            for ($i = 3; $i < count($segments); $i += 2) {
                $key = strtolower($segments[$i]);
                $val = $segments[$i + 1] ?? null;
                if ($val === null) continue;

                // index.php als Wert ignorieren, da das falsch ist
                if (strtolower($val) === 'index.php') continue;

                switch ($key) {
                    case 'postid':
                        $_GET['postID'] = (int)$val;
                        break;
                    case 'threadid':
                        $_GET['threadID'] = (int)$val;
                        break;
                    case 'userid':
                        $_GET['userID'] = (int)$val;
                        break;
                    case 'pagenr':  // Nutze 'pagenr' konsequent als URL-Key
                        $_GET['page'] = (int)$val;
                        break;
                    default:
                        $_GET[$key] = $val;
                        break;
                }
            }
        }
    }

    /**
     * Wandelt einen Query-String in eine SEO-URL um
     */
    public static function convertToSeoUrl(string $url)
    {
        if (!defined('USE_SEO_URLS') || !USE_SEO_URLS) {
            // SEO deaktiviert: Original-URL zurückgeben
            return $url;
        }

        // URL parsen (z.B. "index.php?site=forum&action=thread&id=4&pagenr=1")
        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $query);

        // Sprache, Site, Action auslesen oder Default setzen
        $lang = $query['lang'] ?? 'de';
        $site = $query['site'] ?? 'start';
        $action = $query['action'] ?? null;

        $segments = [$lang, $site];
        if ($action) {
            $segments[] = $action;
        }

        // Unbekannte Keys ausschließen
        unset($query['lang'], $query['site'], $query['action']);

        // Restliche key/value-Paare als Pfadsegmente anhängen
        foreach ($query as $key => $value) {
            $segments[] = strtolower($key);
            $segments[] = $value;
        }

        // SEO-URL zusammenbauen
        $seoUrl = '/' . implode('/', $segments);

        // Falls die ursprüngliche URL ein Fragment (Anker) hatte, anhängen
        if (isset($parsed['fragment'])) {
            $seoUrl .= '#' . $parsed['fragment'];
        }

        return $seoUrl;
    }

    /**
     * Liest SEO-URL und schreibt $_GET-Werte
     */
    public static function parseSeoUrl()
    {

        $uriPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = explode('/', $uriPath);

        $params = [];

        // Sprache
        $params['lang'] = $segments[0] ?? 'de';

        // Site
        $params['site'] = $segments[1] ?? 'start';

        // Action
        if (isset($segments[2]) && !is_numeric($segments[2])) {
            $params['action'] = $segments[2];
            $startIndex = 3;
        } else {
            $startIndex = 2;
        }

        // Rest als Key/Value-Paare
        for ($i = $startIndex; $i < count($segments); $i += 2) {
            $key = strtolower($segments[$i] ?? '');
            $val = $segments[$i + 1] ?? null;

            if ($key === '' || $val === null) continue;

            switch ($key) {
                case 'postid':
                    $params['postID'] = $val;
                    if ($params['action'] === 'quote') {
                        $params['id'] = $val;
                    } elseif (!isset($params['postID'])) {
                        $params['id'] = $val;
                    }
                    break;

                case 'threadid':
                    $params['threadID'] = $val;
                    if (!isset($params['id']) && $params['action'] !== 'quote') {
                        $params['id'] = $val;
                    }
                    break;

                default:
                    $params[$key] = $val;
                    break;
            }
        }

        // $_GET füllen
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        return $params;
    }

}
