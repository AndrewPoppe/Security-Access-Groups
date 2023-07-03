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
        $dirty_text = htmlspecialchars_decode($text);
        return \REDCap::filterHtml($dirty_text);
    }

    public function replaceText()
    {
        $replaced_text = \Piping::pipeSpecialTags(
            $this->cleanerText,
            $this->module->getProjectId(),
            null,
            null,
            null,
            $username = $this->data["sag_user"],
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
        $replaced_text = $this->replacePlaceholders($replaced_text);
        return $replaced_text;
    }

    private function makeList($array)
    {
        return '<ul><li>' . implode('</li><li>', $array) . '</li></ul>';
    }

    public function replacePlaceholders($text)
    {
        $text = $this->replace_sag_user($text);
        $text = $this->replace_sag_user_fullname($text);
        $text = $this->replace_sag_user_email($text);
        $text = $this->replace_sag_rights($text);
        $text = $this->replace_sag_project_title($text);
        $text = $this->replace_sag_users($text);
        $text = $this->replace_sag_user_fullnames($text);
        $text = $this->replace_sag_user_emails($text);
        $text = $this->replace_sag_users_table($text);
        $text = $this->replace_sag_users_table_full($text);
        $text = $this->replace_sag_expiration_date($text);

        return $text;
    }

    private function replace_sag_user($text)
    {
        $placeholder = '[sag-user]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $username = $this->data["sag_user"] ?? "";
        return str_replace($placeholder, $username, $text);
    }

    private function replace_sag_user_fullname($text)
    {
        $placeholder = '[sag-user-fullname]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $fullname = $this->data["sag_user_fullname"] ?? "";
        return str_replace($placeholder, $fullname, $text);
    }

    private function replace_sag_user_email($text)
    {
        $placeholder = '[sag-user-email]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $email             = $this->data["sag_user_email"] ?? "";
        $email_replacement = '<a href="mailto:' . $email . '">' . $email . '</a>';
        return str_replace($placeholder, $email_replacement, $text);
    }

    private function replace_sag_rights($text)
    {
        $placeholder = '[sag-rights]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $rights             = $this->data["sag_user_rights"] ?? [];
        $rights_replacement = $this->makeList($rights);
        return str_replace($placeholder, $rights_replacement, $text);
    }

    private function replace_sag_project_title($text)
    {
        $placeholder = '[sag-project-title]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $title = $this->module->getProject()->getTitle() ?? "";
        return str_replace($placeholder, $title, $text);
    }

    private function replace_sag_users($text)
    {
        $placeholder = '[sag-users]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $users             = $this->data["sag_users"] ?? [];
        $users_replacement = $this->makeList($users);
        return str_replace($placeholder, $users_replacement, $text);
    }

    private function replace_sag_user_fullnames($text)
    {
        $placeholder = '[sag-user-fullnames]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $fullnames             = $this->data["sag_fullnames"] ?? [];
        $fullnames_replacement = $this->makeList($fullnames);
        return str_replace($placeholder, $fullnames_replacement, $text);
    }

    private function replace_sag_user_emails($text)
    {
        $placeholder = '[sag-user-emails]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $emails             = $this->data["sag_emails"] ?? [];
        $emails             = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $emails_replacement = $this->makeList($emails);
        return str_replace($placeholder, $emails_replacement, $text);
    }

    private function replace_sag_users_table($text)
    {
        $placeholder = '[sag-users-table]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $users     = $this->data["sag_users"] ?? [];
        $fullnames = $this->data["sag_fullnames"] ?? [];
        $emails    = $this->data["sag_emails"] ?? [];
        $emails    = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);

        $table = "<table class='sag_users' style='border: 1px solid #666; border-collapse: collapse; width: 100%;'><thead><tr><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>Name</th><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>REDCap Username</th><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>Email Address</th></tr></thead><tbody>";
        foreach ( $users as $index => $username ) {
            $bg       = $index % 2 == 0 ? "transparent" : "#f2f2f2";
            $fullname = $fullnames[$index] ?? "";
            $email    = $emails[$index] ?? "";
            $table .= "<tr style='background-color:" . $bg . ";'><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$fullname</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$username</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$email</td></tr>";
        }
        $table .= "</tbody></table>";

        return str_replace($placeholder, $table, $text);
    }

    private function replace_sag_users_table_full($text)
    {
        $placeholder = '[sag-users-table-full]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }

        $users     = $this->data["sag_users"] ?? [];
        $fullnames = $this->data["sag_fullnames"] ?? [];
        $emails    = $this->data["sag_emails"] ?? [];
        $emails    = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $rights    = $this->data["sag_rights"] ?? [];

        $table = "<table class='sag_users' style='border: 1px solid #666; border-collapse: collapse; width: 100%;'><thead><tr><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>Name</th><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>REDCap Username</th><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>Email Address</th><th style='text-align: left;padding: 8px;border: 1px solid #666;background-color: #f2f2f2;'>Noncompliant Rights</th></tr></thead><tbody>";
        foreach ( $users as $index => $username ) {
            $bg           = $index % 2 == 0 ? "transparent" : "#f2f2f2";
            $fullname     = $fullnames[$index] ?? "";
            $email        = $emails[$index] ?? "";
            $these_rights = $rights[$index] ?? [];
            $rights_list  = $this->makeList($these_rights);
            $table .= "<tr style='background-color:" . $bg . ";'><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$fullname</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$username</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$email</td><td style='text-align: left;padding: 8px;border: 1px solid #666;'>$rights_list</td></tr>";
        }
        $table .= "</tbody></table>";

        return str_replace($placeholder, $table, $text);
    }

    private function replace_sag_expiration_date($text)
    {
        $placeholder = '[sag-expiration-date]';
        if ( strpos($text, $placeholder) === false ) {
            return $text;
        }
        $expiration_date = $this->data["sag_expiration_date"] ?? "";
        return str_replace($placeholder, $expiration_date, $text);
    }
}