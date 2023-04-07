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

    public function replacePlaceholders($text)
    {
        $functions = get_class_methods($this);

        foreach ($functions as $func) {
            if (str_starts_with($func, "replace_sag_")) {
                $text = $this->$func($text);
            }
        }

        return $text;
    }

    private function replace_sag_user($text)
    {
        $username = $this->data["sag_user"] ?? "";
        return str_replace('[sag-user]', $username, $text);
    }

    private function replace_sag_user_fullname($text)
    {
        $fullname = $this->data["sag_user_fullname"] ?? "";
        return str_replace('[sag-user-fullname]', $fullname, $text);
    }

    private function replace_sag_user_email($text)
    {
        $email = $this->data["sag_user_email"];
        $email_replacement = '<a href="mailto:' . $email . '">' . $email . '</a>';
        return str_replace('[sag-user-email]', $email_replacement, $text);
    }

    private function replace_sag_rights($text)
    {
        $rights = $this->data["sag_rights"] ?? [];
        $rights_replacement = '<ul><li>' . implode('</li><li>', $rights) . '</li></ul>';
        return str_replace('[sag-rights]', $rights_replacement, $text);
    }
}
