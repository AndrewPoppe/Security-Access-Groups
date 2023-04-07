<?php

namespace YaleREDCap\SystemUserRights;

use YaleREDCap\SystemUserRights\SystemUserRights;

class TextReplacer
{

    private $module;
    public $text;
    private $data;

    public function __construct(SystemUserRights $module, string $text, array $data)
    {
        $this->module = $module;
        $this->text = $text;
        $this->data = $data;
    }

    public function replaceText()
    {
        $replaced_text = \Piping::pipeSpecialTags($this->text, $this->module->getProjectId());
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

        return $text;
    }

    private function replace_sag_user($text)
    {
        $placeholder = '[sag-user]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $username = $this->data["sag_user"] ?? "";
        return str_replace($placeholder, $username, $text);
    }

    private function replace_sag_user_fullname($text)
    {
        $placeholder = '[sag-user-fullname]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $fullname = $this->data["sag_user_fullname"] ?? "";
        return str_replace($placeholder, $fullname, $text);
    }

    private function replace_sag_user_email($text)
    {
        $placeholder = '[sag-user-email]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $email = $this->data["sag_user_email"] ?? "";
        $email_replacement = '<a href="mailto:' . $email . '">' . $email . '</a>';
        return str_replace($placeholder, $email_replacement, $text);
    }

    private function replace_sag_rights($text)
    {
        $placeholder = '[sag-rights]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $rights = $this->data["sag_rights"] ?? [];
        $rights_replacement = $this->makeList($rights);
        return str_replace($placeholder, $rights_replacement, $text);
    }

    private function replace_sag_project_title($text)
    {
        $placeholder = '[sag-project-title]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $title = $this->module->getProject()->getTitle() ?? "";
        return str_replace($placeholder, $title, $text);
    }

    private function replace_sag_users($text)
    {
        $placeholder = '[sag-users]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $users = $this->data["sag_users"] ?? [];
        $users_replacement = $this->makeList($users);
        return str_replace($placeholder, $users_replacement, $text);
    }

    private function replace_sag_user_fullnames($text)
    {
        $placeholder = '[sag-user-fullnames]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }
        $fullnames = $this->data["sag_fullnames"] ?? [];
        $fullnames_replacement = $this->makeList($fullnames);
        return str_replace($placeholder, $fullnames_replacement, $text);
    }

    private function replace_sag_user_emails($text)
    {
        $placeholder = '[sag-user-emails]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }

        $emails = $this->data["sag_emails"] ?? [];
        $emails = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $emails_replacement = $this->makeList($emails);
        return str_replace($placeholder, $emails_replacement, $text);
    }

    private function replace_sag_users_table($text)
    {
        $placeholder = '[sag-users-table]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }

        $users = $this->data["sag_users"] ?? [];
        $fullnames = $this->data["sag_fullnames"] ?? [];
        $emails = $this->data["sag_emails"] ?? [];
        $emails = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);

        $table = "<table class='sag_users'><thead><tr><th>Name</th><th>REDCap Username</th><th>Email Address</th></tr></thead><tbody>";
        foreach ($users as $index => $username) {
            $fullname = $fullnames[$index] ?? "";
            $email = $emails[$index] ?? "";
            $table .= "<tr><td>$fullname</td><td>$username</td><td>$email</td></tr>";
        }
        $table .= "</tbody></table>";
        $table .= $this->getSagUsersTableCss();

        return str_replace($placeholder, $table, $text);
    }

    private function replace_sag_users_table_full($text)
    {
        $placeholder = '[sag-users-table-full]';
        if (!str_contains($text, $placeholder)) {
            return $text;
        }

        $users = $this->data["sag_users"] ?? [];
        $fullnames = $this->data["sag_fullnames"] ?? [];
        $emails = $this->data["sag_emails"] ?? [];
        $emails = array_map(function ($email) {
            return "<a href='mailto:$email'>$email</a>";
        }, $emails);
        $rights = $this->data["sag_rights"] ?? [];

        $table = "<table class='sag_users'><thead><tr><th>Name</th><th>REDCap Username</th><th>Email Address</th><th>Noncompliant Rights</th></tr></thead><tbody>";
        foreach ($users as $index => $username) {
            $fullname = $fullnames[$index] ?? "";
            $email = $emails[$index] ?? "";
            $these_rights = $rights[$index] ?? [];
            $rights_list = $this->makeList($these_rights);
            $table .= "<tr><td>$fullname</td><td>$username</td><td>$email</td><td>$rights_list</td></tr>";
        }
        $table .= "</tbody></table>";
        $table .= $this->getSagUsersTableCss();

        return str_replace($placeholder, $table, $text);
    }

    private function getSagUsersTableCss()
    {
        $style = <<<EOF
        <style>
            table.sag_users {
                border: 1px solid #666;
                border-collapse: collapse;
                width: 100%;
            }
          
            table.sag_users th,
            table.sag_users td {
                text-align: left;
                padding: 8px;
                border: 1px solid #666;
            }
          
            table.sag_users th {
                background-color: #f2f2f2;
            }
          
            table.sag_users tr:nth-child(even) {
                background-color: #f2f2f2;
            }
        </style>
        EOF;
        return $style;
    }
}
