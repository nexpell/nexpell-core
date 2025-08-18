<?php
namespace nexpell;


class SeoUrlHandler {


public static function route(?string $uri = null): void 
{
    $uri = $uri ?? $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));

    if (isset($segments[0]) && preg_match('/^[a-z]{2}$/i', $segments[0])) {
        // Sprachsegment
        $_GET['lang'] = strtolower($segments[0]);
        $_GET['site'] = $segments[1] ?? 'index';

        // action nur setzen, wenn nicht nur Parameter folgt
        $_GET['action'] = (isset($segments[2]) && !preg_match('/id$/i', $segments[2])) ? $segments[2] : null;

        // Parameter ab Segment 2 oder 3
        $start = ($_GET['action'] === null) ? 2 : 3;
        for ($i = $start; $i < count($segments); $i += 2) {
            $key = $segments[$i] ?? null;
            $val = $segments[$i + 1] ?? null;
            if ($key === null || $val === null) continue;

            if (strtolower($key) === 'index.php' || strtolower($val) === 'index.php') continue;

            if (preg_match('/^([a-z]+)id$/i', $key, $matches)) {
                $key = $matches[1] . 'ID';
            }

            $_GET[$key] = is_numeric($val) ? (int)$val : $val;
        }

    } else {
        // klassische query-Parameter
        parse_str(parse_url($uri, PHP_URL_QUERY) ?: '', $queryParams);
        foreach ($queryParams as $k => $v) {
            $_GET[$k] = $v;
        }
        $_GET['lang'] = $_GET['lang'] ?? 'de';
    }
}











    /**
     * Wandelt einen Query-String in eine SEO-URL um
     */
public static function convertToSeoUrl(string $url): string
{
    if (!defined('USE_SEO_URLS') || !USE_SEO_URLS) {
        return $url; // SEO deaktiviert
    }

    $parsed = parse_url($url);
    parse_str($parsed['query'] ?? '', $query);

    $lang = $query['lang'] ?? 'de';
    $site = $query['site'] ?? 'index';
    $action = $query['action'] ?? null;

    $segments = [$lang, $site];
    if ($action) {
        $segments[] = $action;
    }

    unset($query['lang'], $query['site'], $query['action']);

    foreach ($query as $key => $value) {
        if ($value === null || strtolower($value) === 'index.php') continue;

        switch (strtolower($key)) {
            case 'postid':
            case 'threadid':
            case 'userid':
            case 'pagenr':
                $value = (int)$value;
                break;
        }

        $segments[] = strtolower($key);
        $segments[] = $value;
    }

    $seoUrl = '/' . implode('/', $segments);

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
    $params['site'] = $segments[1] ?? 'index';

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







public static function buildPluginUrl(string $type, int $id, string $lang = 'de', $db = null): string
{
    switch ($type) {
        case 'plugins_articles':
            $url = "index.php?lang={$lang}&site=articles&action=watch&id={$id}";
            break;

        case 'plugins_forum_threads':
            $threadTitle = self::getThreadTitle($id); // DB-Methode, die den Thread-Titel holt
            $slug = $threadTitle ? self::slugify($threadTitle) : "thread{$id}";
            $url = "index.php?lang={$lang}&site=forum&action=showthread&threadID={$id}&title={$slug}";
            break;

        case 'plugins_forum_posts':
            $threadId = self::getThreadIdByPost($id);
            $postTitle = self::getPostTitle($id); // DB-Methode, die Post-Titel holt
            $slug = $postTitle ? self::slugify($postTitle) : "post{$id}";

            if ($threadId > 0) {
                $url = "index.php?lang={$lang}&site=forum&action=showthread&threadID={$threadId}#{$slug}";
            } else {
                $url = "index.php?lang={$lang}&site=forum&action=showpost&postID={$id}&title={$slug}";
            }
            break;

        case 'plugins_news':
            $newsTitle = self::getNewsTitle($id);
            $slug = $newsTitle ? self::slugify($newsTitle) : "news{$id}";
            $url = "index.php?lang={$lang}&site=news&action=show&id={$id}&title={$slug}";
            break;

        case 'plugins_gallery':
            $url = "index.php?lang={$lang}&site=gallery&picID={$id}";
            break;

        case 'plugins_downloads':
            $downloadTitle = self::getDownloadTitle($id);
            $slug = $downloadTitle ? self::slugify($downloadTitle) : "download{$id}";
            $url = "index.php?lang={$lang}&site=downloads&action=show&id={$id}&title={$slug}";
            break;

        case 'plugins_userlist':
            $userName = self::getUserName($id);
            $slug = $userName ? self::slugify($userName) : "user{$id}";
            $url = "index.php?lang={$lang}&site=user&id={$id}&name={$slug}";
            break;

        case 'plugins_team':
            $memberName = self::getTeamMemberName($id);
            $slug = $memberName ? self::slugify($memberName) : "member{$id}";
            $url = "index.php?lang={$lang}&site=team&action=member&id={$id}&name={$slug}";
            break;

        case 'plugins_calendar':
            $eventTitle = self::getEventTitle($id);
            $slug = $eventTitle ? self::slugify($eventTitle) : "event{$id}";
            $url = "index.php?lang={$lang}&site=calendar&action=show&id={$id}&title={$slug}";
            break;

        default:
            $url = "index.php?lang={$lang}&site=plugin&plugin={$type}&id={$id}";
            break;
    }

    return self::convertToSeoUrl($url);
}

/**
 * Wandelt einen Titel in einen URL-tauglichen Slug um
 */
private static function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text); // Leerzeichen zu Bindestrichen
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Umlaute & Sonderzeichen
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    return $text ?: 'item';
}



/**
 * Hilfsmethode: Thread-ID anhand der Post-ID ermitteln
 */
protected static function getThreadIdByPost(int $postID): int
{
    global $_database;
    $sql = "SELECT threadID FROM plugins_forum_posts WHERE postID = ?";
    $stmt = $_database->prepare($sql);
    $stmt->bind_param('i', $postID);
    $stmt->execute();
    $stmt->bind_result($threadID);
    $stmt->fetch();
    $stmt->close();
    return $threadID ?? 0;
}


private static function getPostTitle(int $postId): ?string
{
    $db = $GLOBALS['db'] ?? null; // DB-Objekt holen
    if (!$db) return null; // kein DB-Objekt vorhanden

    $query = $db->prepare("SELECT title FROM " . PREFIX . "plugins_forum_posts WHERE id = ?");
    $query->execute([$postId]);
    $row = $query->fetch();

    return $row ? $row['title'] : null;
}

private static function getThreadTitle(int $threadId): ?string
{
    global $db;

    $query = $db->prepare("SELECT title FROM " . PREFIX . "plugins_forum_threads WHERE id = ?");
    $query->execute([$threadId]);
    $row = $query->fetch();

    return $row ? $row['title'] : null;
}

// Beispiel für News
private static function getNewsTitle(int $newsId): ?string
{
    global $db;

    $query = $db->prepare("SELECT title FROM " . PREFIX . "plugins_news WHERE id = ?");
    $query->execute([$newsId]);
    $row = $query->fetch();

    return $row ? $row['title'] : null;
}


}



