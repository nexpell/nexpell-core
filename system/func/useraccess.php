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

class UserPermissions {

    // Überprüft, ob der Benutzer einer bestimmten Gruppe angehört
    private function isUserInGroup($userID, $group) {
        return (
            mysqli_num_rows(
                safe_query(
                    "SELECT userID FROM `users_groups` WHERE `userID` = " . (int)$userID . " AND `$group` = 1"
                )
            ) > 0
        );
    }

    // Überprüft, ob der Benutzer ein Administrator ist
    public function isAnyAdmin($userID) {
        $groups = ['super', 'forum', 'files', 'page', 'feedback', 'news', 'news_writer', 'polls', 'clanwars', 'user', 'cash', 'gallery'];
        foreach ($groups as $group) {
            if ($this->isUserInGroup($userID, $group)) {
                return true;
            }
        }
        return false;
    }

    // Überprüft, ob der Benutzer ein Superadmin ist
    public function isSuperAdmin($userID) {
        return $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Foren-Administrator ist
    public function isForumAdmin($userID) {
        return $this->isUserInGroup($userID, 'forum') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Datei-Administrator ist
    public function isFilesAdmin($userID) {
        return $this->isUserInGroup($userID, 'files') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Seiten-Administrator ist
    public function isPageAdmin($userID) {
        return $this->isUserInGroup($userID, 'page') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Feedback-Administrator ist
    public function isFeedbackAdmin($userID) {
        return $this->isUserInGroup($userID, 'feedback') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Nachrichten-Administrator ist
    public function isNewsAdmin($userID) {
        return $this->isUserInGroup($userID, 'news') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Nachrichten-Schreiber ist
    public function isNewsWriter($userID) {
        return $this->isUserInGroup($userID, 'news_writer') || $this->isUserInGroup($userID, 'super') || $this->isUserInGroup($userID, 'news');
    }

    // Überprüft, ob der Benutzer ein Umfragen-Administrator ist
    public function isPollsAdmin($userID) {
        return $this->isUserInGroup($userID, 'polls') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Clanwars-Administrator ist
    public function isClanWarsAdmin($userID) {
        return $this->isUserInGroup($userID, 'clanwars') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer Moderator für ein bestimmtes Board ist
    public function isModerator($userID, $boardID) {
        if (empty($userID) || empty($boardID)) {
            return false;
        }

        if (!$this->isAnyModerator($userID)) {
            return false;
        }

        return (
            mysqli_num_rows(
                safe_query(
                    "SELECT userID FROM `plugins_forum_moderators` WHERE `userID` = " . (int)$userID . " AND `boardID` = " . (int)$boardID
                )
            ) > 0
        );
    }

    // Überprüft, ob der Benutzer irgendein Moderator ist
    public function isAnyModerator($userID) {
        return $this->isUserInGroup($userID, 'moderator');
    }

    // Überprüft, ob der Benutzer ein Benutzer-Administrator ist
    public function isUserAdmin($userID) {
        return $this->isUserInGroup($userID, 'user') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Cash-Administrator ist
    public function isCashAdmin($userID) {
        return $this->isUserInGroup($userID, 'cash') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer ein Gallery-Administrator ist
    public function isGalleryAdmin($userID) {
        return $this->isUserInGroup($userID, 'gallery') || $this->isUserInGroup($userID, 'super');
    }

    // Überprüft, ob der Benutzer Mitglied eines Clans ist
    public function isClanMember($userID) {
        if (mysqli_num_rows(
            safe_query(
                "SELECT userID FROM `plugins_squads_members` WHERE `userID` = " . (int)$userID
            )
        ) > 0) {
            return true;
        } else {
            return $this->isSuperAdmin($userID);
        }
    }

    // Überprüft, ob der Benutzer Mitglied eines JoinUs-Clans ist
    public function isJoinUsMember($userID) {
        if (mysqli_num_rows(
            safe_query(
                "SELECT userID FROM `plugins_squads_members` WHERE `userID` = " . (int)$userID
            )
        ) > 0) {
            return true;
        } else {
            return $this->isSuperAdmin($userID);
        }
    }

    // Überprüft, ob der Benutzer gebannt ist
    public function isBanned($userID) {
        return (
            mysqli_num_rows(
                safe_query(
                    "SELECT userID FROM `users` WHERE `userID` = " . (int)$userID . " AND (`banned` = 'perm' OR `banned` IS NOT NULL)"
                )
            ) > 0
        );
    }

    // Überprüft, ob der Benutzer den angegebenen Kommentar gepostet hat
    public function isCommentPoster($userID, $commID) {
        if (empty($userID) || empty($commID)) {
            return false;
        }

        return (
            mysqli_num_rows(
                safe_query(
                    "SELECT commentID FROM `plugins_comments` WHERE `commentID` = " . (int)$commID . " AND `userID` = " . (int)$userID
                )
            ) > 0
        );
    }

    // Überprüft, ob der Benutzer den angegebenen Forumspost gepostet hat
    public function isForumPoster($userID, $postID) {
        return (
            mysqli_num_rows(
                safe_query(
                    "SELECT postID FROM `plugins_forum_posts` WHERE `postID` = " . (int)$postID . " AND `poster` = " . (int)$userID
                )
            ) > 0
        );
    }

    // Überprüft, ob der angegebene Post der erste Post in einem Thema ist
    public function isTopicPost($topicID, $postID) {
        $ds = mysqli_fetch_array(
            safe_query(
                "SELECT postID FROM `plugins_forum_posts` WHERE `topicID` = " . (int)$topicID . " ORDER BY `date` ASC LIMIT 0,1"
            )
        );
        return $ds['postID'] == $postID;
    }

    // Überprüft, ob der Benutzer in der angegebenen Benutzergruppe ist
    public function isInUserGroup($usergrp, $userID) {
        if ($usergrp == 'user' && !empty($userID)) {
            return true;
        }

        if (!$this->userGroupExists($usergrp)) {
            return false;
        }

        if (mysqli_num_rows(safe_query(
            "SELECT userID FROM `user_forum_groups` WHERE `$usergrp` = 1 AND `userID` = " . (int)$userID
        )) > 0) {
            return true;
        }

        return $this->isForumAdmin($userID);
    }

    // Überprüft, ob die Benutzergruppe existiert
    private function userGroupExists($usergrp) {
        // Deine Logik, um zu überprüfen, ob die Gruppe existiert (z. B. eine Abfrage zur Überprüfung der Gruppennamen)
        return true; // Beispiel, du kannst das nach Bedarf anpassen
    }
}
