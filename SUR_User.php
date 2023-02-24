<?php

namespace YaleREDCap\SystemUserRights;

use \ExternalModules\User;
use YaleREDCap\SystemUserRights\SystemUserRights;

class SUR_User
{
    public string $username;
    public User $user;
    public SystemUserRights $module;
    public array $userinfo;
    function __construct(SystemUserRights $module, string $username, array $userinfo = null)
    {
        $this->module = $module;
        $this->username = $username;
        $this->user = $this->module->getUser($this->username);
        $this->userinfo = $userinfo ?? $module->getUserInfo($this->username) ?? [];
    }

    public function getTrainingStatus()
    {

        return 2;
    }
}
