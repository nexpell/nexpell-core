<?php

# Datei muss noch überprüft werden!!!


#userlist verwendiese funktion
function getusername($userID) {
    $erg = safe_query("SELECT username FROM users WHERE `userID` = " . (int)$userID);
    if(mysqli_num_rows($erg) == '1') {
        $ds = mysqli_fetch_array(safe_query("SELECT username FROM users WHERE `userID` = " . (int)$userID));
        return $ds['username'];
    } else {
        $ds = mysqli_fetch_array(safe_query("SELECT username FROM user_username WHERE `userID` = " . (int)$userID));
        return '<s>'.@$ds['username'].'</s>';
    }
}

function getuserpic($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT userpic,username FROM users WHERE `userID` = " . (int)$userID . ""));
    if (empty($ds['userpic'])) {
        return "svg-avatar.php?name=".@$ds['username']."G";
    }

    return $ds['userpic'];
}

function getavatar($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT avatar,username FROM users WHERE `userID` = " . (int)$userID . ""));
    if (empty($ds['avatar'])) {
        return "svg-avatar.php?name=".@$ds['username']."G";
    }

    return $ds['avatar'];
}



/*










function getuserid($username)
{
    $get = safe_query("SELECT userID FROM users WHERE `username` = '" . $username . "'");
    if(mysqli_num_rows($get) > 0) {
        $ds = mysqli_fetch_array($get);
        return $ds['userID'];
    } else {
        return '';
    }
}



function deleteduser($userID) {
    $erg = safe_query("SELECT username FROM users WHERE `userID` = " . (int)$userID);
    if(mysqli_num_rows($erg) == '1') {
        return '0';
    } else {
        return '1';
    }
}
function getuserdescription($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT userdescription FROM users WHERE `userID` = " . (int)$userID
        )
    );
    return htmlspecialchars($ds['userdescription']);
}

function getfirstname($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT firstname FROM users WHERE `userID` = " . (int)$userID
        )
    );
    return htmlspecialchars($ds['firstname']);
}

function getlastname($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT lastname FROM users WHERE `userID` = " . (int)$userID
        )
    );
    return htmlspecialchars($ds['lastname']);
}

function getbirthday($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT birthday FROM users WHERE `userID` = " . (int)$userID
        )
    );
    return getformatdate($ds['birthday']);
}

function gettown($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT town FROM users WHERE `userID` = " . (int)$userID));
    return htmlspecialchars($ds['town']);
}

function getemail($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT email FROM users WHERE `userID` = " . (int)$userID));
    if(isset($ds))
    return htmlspecialchars($ds['email']);
}

function getemailhide($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT email_hide FROM users WHERE `userID` = " . (int)$userID));
    return htmlspecialchars(@$ds['email_hide']);
}

function gethomepage($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT homepage FROM users WHERE `userID` = " . (int)$userID));
    if(isset($ds))
    return str_replace('https://', '', htmlspecialchars($ds['homepage']));
}

function getdiscord($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT discord FROM users WHERE `userID` = " . (int)$userID));
    return htmlspecialchars($ds['discord']);
}

function getcountries($selected = null)
{
    $countries = '';
    $ergebnis = safe_query("SELECT * FROM settings_countries WHERE `fav` = 1 ORDER BY `country`");
    $anz = mysqli_num_rows($ergebnis);
    while ($ds = mysqli_fetch_array($ergebnis)) {
        if ($ds['short'] == $selected) {
            $countries .= '<option value="' . $ds['short'] . '" selected="selected">' . $ds['country'] . '</option>';
        } else {
            $countries .= '<option value="' . $ds['short'] . '">' . $ds['country'] . '</option>';
        }
    }
    if ($anz) {
        $countries .= '<option value="">----------------------------------</option>';
    }
    $result = safe_query("SELECT * FROM settings_countries WHERE `fav`= 0 ORDER BY `country`");
    while ($dv = mysqli_fetch_array($result)) {
        if ($dv['short'] == $selected) {
            $countries .= '<option value="' . $dv['short'] . '" selected="selected">' . $dv['country'] . '</option>';
        } else {
            $countries .= '<option value="' . $dv['short'] . '">' . $dv['country'] . '</option>';
        }
    }
    return $countries;
}

function getcountry($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT country FROM users WHERE `userID` = " . (int)$userID));
    return htmlspecialchars($ds['country']);
}

function getuserlanguage($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT language FROM users WHERE `userID` = " . (int)$userID));
    return htmlspecialchars($ds['language']);
}

function getuserpic($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT userpic,username FROM users WHERE `userID` = " . (int)$userID . ""));
    if (empty($ds['userpic'])) {
        return "svg-avatar.php?name=".@$ds['username']."G";
    }

    return $ds['userpic'];
}

function getavatar($userID)
{
    $ds = mysqli_fetch_array(safe_query("SELECT avatar,username FROM users WHERE `userID` = " . (int)$userID . ""));
    if (empty($ds['avatar'])) {
        return "svg-avatar.php?name=".@$ds['username']."G";
    }

    return $ds['avatar'];
}

function getsignatur($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT usertext FROM users WHERE `userID` = " . (int)$userID
        )
    );
    #return strip_tags($ds['usertext']);
    if(isset($ds))
    return $ds['usertext'];
}

function getregistered($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT registerdate FROM users WHERE `userID` = " . (int)$userID
        )
    );
    if(isset($ds))
    return getformatdate($ds['registerdate']);
}

function getlastlogin($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT lastlogin FROM users WHERE `userID` = " . (int)$userID
        )
    );
    if(isset($ds))
    return getformatdate($ds['lastlogin']);
}

function usergroupexists($userID)
{
    return (
        mysqli_num_rows(
            safe_query(
                "SELECT userID FROM users_groups WHERE `userID` = " . (int)$userID
            )
        ) > 0
    );
}

function wantmail($userID)
{
    return (
        mysqli_num_rows(
            safe_query(
                "SELECT
                    userID
                FROM
                    user
                WHERE
                    `userID` = " . (int)$userID . " AND
                    `mailonpm` = 1"
            )
        ) > 0
    );
}

function getuserguestbookstatus($userID)
{
    $ds = mysqli_fetch_array(
        safe_query(
            "SELECT user_guestbook FROM users WHERE `userID` = " . (int)$userID
        )
    );
    return htmlspecialchars($ds['user_guestbook']);
}

function getusercomments($userID, $type)
{
    return mysqli_num_rows(
        safe_query(
            "SELECT
                commentID
            FROM
                `comments`
            WHERE
                `userID` = " . (int)$userID . " AND
                `type` = '" . $type . "'"
        )
    );
}

function getallusercomments($userID)
{
    return mysqli_num_rows(
        safe_query(
            "SELECT commentID FROM `comments` WHERE `userID` = " . (int)$userID
        )
    );
}

function RandPass($length, $type = 0)
{

    /* Randpass: Generates an random password
    Parameter:
    length - length of the password string
    type - there are 4 types: 0 - all chars, 1 - numeric only, 2 - upper chars only, 3 - lower chars only
    Example:
    echo RandPass(7, 1); => 0917432
    */
/*    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        if ($type == 0) {
            $rand = rand(1, 3);
        } else {
            $rand = $type;
        }
        switch ($rand) {
            case 1:
                $pass .= chr(rand(48, 57));
                break;
            case 2:
                $pass .= chr(rand(65, 90));
                break;
            case 3:
                $pass .= chr(rand(97, 122));
                break;
        }
    }
    return $pass;
}

function isonline($userID)
{
    $q = safe_query("SELECT site FROM whoisonline WHERE userID=" . (int)$userID);
    if (mysqli_num_rows($q) > 0) {
        $ds = mysqli_fetch_array($q);
        return '<strong>online</strong> @ <a href="index.php?site=' . $ds['site'] . '">' . $ds['site'] . '</a>';
    }

    return 'offline';
}

function getLanguageWeight($language)
{
    if (empty($language)) {
        return 1;
    }

    return $language;
}

function detectUserLanguage()
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        preg_match_all(
            "/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i",
            $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            $matches
        );
        if (count($matches)) {
            $languages_found = array_combine($matches[1], array_map("getLanguageWeight", $matches[4]));
            arsort($languages_found, SORT_NUMERIC);
            $path = $GLOBALS['_language']->getRootPath();
            foreach ($languages_found as $key => $val) {
                if (is_dir($path . $key)) {
                    return $key;
                }
            }
        }
    }
    return null;
}
*/