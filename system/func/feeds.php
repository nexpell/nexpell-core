<?php
/**
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 *                  Webspell-RM      /                        /   /                                          *
 *                  -----------__---/__---__------__----__---/---/-----__---- _  _ -                         *
 *                   | /| /  /___) /   ) (_ `   /   ) /___) /   / __  /     /  /  /                          *
 *                  _|/_|/__(___ _(___/_(__)___/___/_(___ _/___/_____/_____/__/__/_                          *
 *                               Free Content / Management System                                            *
 *                                           /                                                               *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @version         webspell-rm                                                                              *
 *                                                                                                           *
 * @copyright       2018-2023 by webspell-rm.de                                                              *
 * @support         For Support, Plugins, Templates and the Full Script visit webspell-rm.de                 *
 * @website         <https://www.webspell-rm.de>                                                             *
 * @forum           <https://www.webspell-rm.de/forum.html>                                                  *
 * @wiki            <https://www.webspell-rm.de/wiki.html>                                                   *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @license         Script runs under the GNU GENERAL PUBLIC LICENCE                                         *
 *                  It's NOT allowed to remove this copyright-tag                                            *
 *                  <http://www.fsf.org/licensing/licenses/gpl.html>                                         *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @author          Code based on WebSPELL Clanpackage (Michael Gruber - webspell.at)                        *
 * @copyright       2005-2011 by webspell.org / webspell.info                                                *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
*/

function generate_rss2()
{
    global $hp_url, $hp_title, $rss_default_language;

    // Sprachdatei laden
    $_language = new \webspell\Language();
    $_language->setLanguage($rss_default_language);
    $_language->readModule('feeds');

    // Letztes Veröffentlichungsdatum bestimmen
    $result = safe_query(
        "SELECT `date` FROM `plugins_news_manager` WHERE `displayed` = 1 ORDER BY `date` DESC LIMIT 1"
    );

    if (mysqli_num_rows($result)) {
        $data = mysqli_fetch_assoc($result);
        $updated = $data['date'];
    } else {
        $updated = time();
    }

    // XML-Header und Metadaten
    $xmlstring = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xmlstring .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
    $xmlstring .= '    <channel>' . PHP_EOL;
    $xmlstring .= '        <title>' . htmlspecialchars($hp_title . ' ' . $_language->module['news_feed']) . '</title>' . PHP_EOL;
    $xmlstring .= '        <link>' . $hp_url . '</link>' . PHP_EOL;
    $xmlstring .= '        <atom:link href="' . $hp_url . '/tmp/rss.xml" rel="self" type="application/rss+xml" />' . PHP_EOL;
    $xmlstring .= '        <description>' . htmlspecialchars($_language->module['latest_news_from'] . ' ' . $hp_url) . '</description>' . PHP_EOL;
    $xmlstring .= '        <language>' . $rss_default_language . '-' . $rss_default_language . '</language>' . PHP_EOL;
    $xmlstring .= '        <pubDate>' . date('D, d M Y H:i:s O', $updated) . '</pubDate>' . PHP_EOL;

    // Neuste 10 News-Einträge laden
    $news_result = safe_query(
        "SELECT * FROM `plugins_news_manager` WHERE `displayed` = 1 ORDER BY `date` DESC LIMIT 10"
    );

    while ($news = mysqli_fetch_array($news_result)) {
        $title = htmlspecialchars($news['headline']);
        $content = $news['content'];
        $poster = (int) $news['poster'];

        $author_email = getemail($poster);
        $author_name = getfirstname($poster) . ' ' . getlastname($poster);
        $news_link = $hp_url . '/index.php?site=news_contents&newsID=' . $news['newsID'];

        $xmlstring .= '        <item>' . PHP_EOL;
        $xmlstring .= '            <title>' . $title . '</title>' . PHP_EOL;
        $xmlstring .= '            <description><![CDATA[' . $content . ']]></description>' . PHP_EOL;
        $xmlstring .= '            <author>' . $author_email . ' (' . $author_name . ')</author>' . PHP_EOL;
        $xmlstring .= '            <guid><![CDATA[' . $news_link . ']]></guid>' . PHP_EOL;
        $xmlstring .= '            <link><![CDATA[' . $news_link . ']]></link>' . PHP_EOL;
        $xmlstring .= '        </item>' . PHP_EOL;
    }

    // RSS-Feed abschließen
    $xmlstring .= '    </channel>' . PHP_EOL;
    $xmlstring .= '</rss>';

    // Datei speichern
    $rss_xml = fopen('./tmp/rss.xml', 'w');
    fwrite($rss_xml, $xmlstring);
    fclose($rss_xml);
}
