<?php

namespace YaleREDCap\SecurityAccessGroups;

class TextReplacer
{

    private $module;
    public $text;
    public $cleanerText;
    private $data;

    public function __construct(SecurityAccessGroups $module, string $text, array $data)
    {
        $this->module      = $module;
        $this->text        = $text;
        $this->cleanerText = $this->cleanText($text);
        $this->data        = $module->framework->escape($data);
    }

    private function cleanText(string $text) : string
    {
        $dirtyText = htmlspecialchars_decode($text);
        return \REDCap::filterHtml($dirtyText);
    }

    public function replaceText()
    {
        $replacedText = \Piping::pipeSpecialTags(
            $this->cleanerText,
            $this->module->getProjectId(),
            null,
            null,
            null,
            $this->data['sag_user'],
            false,
            null,
            null,
            false,
            false,
            false,
            false,
            null,
            false,
            false
        );
        $replacedText = $this->replacePlaceholders($replacedText);
        return $replacedText;
    }

    private function makeList($array)
    {
        return '<ul><li>' . implode('</li><li>', $array) . '</li></ul>';
    }

    public function replacePlaceholders($text)
    {
        $text = $this->replaceSagUserSag($text);
        $text = $this->replaceSagUser($text);
        $text = $this->replaceSagUserFullname($text);
        $text = $this->replaceSagUserEmail($text);
        $text = $this->replaceSagRights($text);
        $text = $this->replaceSagProjectTitle($text);
        $text = $this->replaceSagUserSags($text);
        $text = $this->replaceSagUsers($text);
        $text = $this->replaceSagUserFullnames($text);
        $text = $this->replaceSagUserEmails($text);
        $text = $this->replaceSagUsersTable($text);
        $text = $this->replaceSagUsersTableFull($text);
        $text = $this->replaceSagExpirationDate($text);

        return $text;
    }

    private function replaceSagUserSag($text)
    {
        $placeholder = '[sag-user-sag]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $sag             = $this->data['sag_user_sag'] ?? '';
        $replacementText = '<strong>' . $sag['sag_name'] . '</strong> (' . $sag['sag_id'] . ')';
        return str_replace($placeholder, $replacementText, $text);
    }

    private function replaceSagUser($text)
    {
        $placeholder = '[sag-user]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $username = $this->data['sag_user'] ?? '';
        return str_replace($placeholder, $username, $text);
    }

    private function replaceSagUserFullname($text)
    {
        $placeholder = '[sag-user-fullname]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $fullname = $this->data['sag_user_fullname'] ?? '';
        return str_replace($placeholder, $fullname, $text);
    }

    private function replaceSagUserEmail($text)
    {
        $placeholder = '[sag-user-email]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $email            = $this->data['sag_user_email'] ?? '';
        $emailReplacement = '<a href="mailto:' . $email . '">' . $email . '</a>';
        return str_replace($placeholder, $emailReplacement, $text);
    }

    private function replaceSagRights($text)
    {
        $placeholder = '[sag-rights]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $rights            = $this->data['sag_user_rights'] ?? [];
        $rightsReplacement = $this->makeList($rights);
        return str_replace($placeholder, $rightsReplacement, $text);
    }

    private function replaceSagProjectTitle($text)
    {
        $placeholder = '[sag-project-title]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $title = $this->module->getProject()->getTitle() ?? '';
        return str_replace($placeholder, $title, $text);
    }

    private function replaceSagUserSags($text)
    {
        $placeholder = '[sag-user-sags]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $sags     = $this->data['sag_sags'] ?? [];
        $sagsHtml = [];
        foreach ( $sags as $sag ) {
            $sagsHtml[] = '<strong>' . $sag['sag_name'] . '</strong> (' . $sag['sag_id'] . ')';
        }
        $sagsReplacement = $this->makeList($sagsHtml);
        return str_replace($placeholder, $sagsReplacement, $text);
    }

    private function replaceSagUsers($text)
    {
        $placeholder = '[sag-users]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $users            = $this->data['sag_users'] ?? [];
        $usersReplacement = $this->makeList($users);
        return str_replace($placeholder, $usersReplacement, $text);
    }

    private function replaceSagUserFullnames($text)
    {
        $placeholder = '[sag-user-fullnames]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $fullnames            = $this->data['sag_fullnames'] ?? [];
        $fullnamesReplacement = $this->makeList($fullnames);
        return str_replace($placeholder, $fullnamesReplacement, $text);
    }

    private function replaceSagUserEmails($text)
    {
        $placeholder = '[sag-user-emails]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $emails            = $this->data['sag_emails'] ?? [];
        $emails            = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $emailsReplacement = $this->makeList($emails);
        return str_replace($placeholder, $emailsReplacement, $text);
    }

    private function replaceSagUsersTable($text)
    {
        $placeholder = '[sag-users-table]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $users     = $this->data['sag_users'] ?? [];
        $fullnames = $this->data['sag_fullnames'] ?? [];
        $emails    = $this->data['sag_emails'] ?? [];
        $emails    = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $sags      = $this->data['sag_sags'] ?? [];
        $thStyle   = "style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'";
        $table     = "<table class='sag_users' style='border: 1px solid #666; border-collapse: collapse; width: 100%;'><thead><tr>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_60') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_97') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_107') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_108') . "</th>" .
            "</tr></thead><tbody>";
        foreach ( $users as $index => $username ) {
            $bg          = $index % 2 == 0 ? 'transparent' : '#f2f2f2';
            $fullname    = $fullnames[$index] ?? '';
            $email       = $emails[$index] ?? '';
            $sag         = $sags[$index] ?? '';
            $sagForTable = '<strong>' . $sag['sag_name'] . '</strong> (' . $sag['sag_id'] . ')';
            $table .= "<tr style='background-color:" . $bg . ";'><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$fullname</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$username</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$email</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$sagForTable</td></tr>";
        }
        $table .= '</tbody></table>';

        return str_replace($placeholder, $table, $text);
    }

    private function replaceSagUsersTableFull($text)
    {
        $placeholder = '[sag-users-table-full]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $users     = $this->data['sag_users'] ?? [];
        $fullnames = $this->data['sag_fullnames'] ?? [];
        $emails    = $this->data['sag_emails'] ?? [];
        $emails    = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $sags      = $this->data['sag_sags'] ?? [];
        $rights    = $this->data['sag_rights'] ?? [];
        $thStyle   = "style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'";
        $table     = "<table class='sag_users' style='border: 1px solid #666; border-collapse: collapse; width: 100%;'><thead><tr>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_60') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_97') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_107') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('status_ui_108') . "</th>" .
            "<th " . $thStyle . ">" . $this->module->framework->tt('cc_reports_21') . "</th>" .
            "</tr></thead><tbody>";
        foreach ( $users as $index => $username ) {
            $bg          = $index % 2 == 0 ? 'transparent' : '#f2f2f2';
            $fullname    = $fullnames[$index] ?? '';
            $email       = $emails[$index] ?? '';
            $sag         = $sags[$index] ?? '';
            $sagForTable = '<strong>' . $sag['sag_name'] . '</strong> (' . $sag['sag_id'] . ')';
            $theseRights = $rights[$index] ?? [];
            $rightsList  = $this->makeList($theseRights);
            $table .= "<tr style='background-color:" . $bg . ";'><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$fullname</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$username</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$email</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$sagForTable</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$rightsList</td></tr>";
        }
        $table .= '</tbody></table>';

        return str_replace($placeholder, $table, $text);
    }

    private function replaceSagExpirationDate($text)
    {
        $placeholder = '[sag-expiration-date]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $expirationDate = $this->data['sag_expiration_date'] ?? '';
        return str_replace($placeholder, $expirationDate, $text);
    }
}