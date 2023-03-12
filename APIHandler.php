<?php

namespace YaleREDCap\SystemUserRights;

class APIHandler
{
    private $module;
    private $post;
    private $token;
    private $data;
    private $bad_rights = [];
    private $requestHandled = false;
    private $project_id;
    function __construct(SystemUserRights $module, array $post)
    {
        $this->module = $module;
        $this->post = $post;
        $this->token = $this->post["token"];
        $this->data = json_decode($this->post["data"] ?? "{}", true);
    }

    public function handleRequest()
    {
        switch ($this->post["content"]) {
            case "userRoleMapping":
                $this->checkApiUserRoleMapping();
                break;
            default:
                break;
        }

        $this->requestHandled = true;
    }

    public function shouldProcess()
    {
        $rights = $this->getUserRightsFromToken($_POST["token"]) ?? [];
        $this->project_id = $rights["project_id"];
        $prefix = $this->module->getModuleDirectoryPrefix();
        $isModuleEnabledInProject = (bool) $this->module->isModuleEnabled($prefix, $this->project_id);
        $isApiUserRightsMethod = in_array($_POST["content"], ["user", "userRole", "userRoleMapping"], true);
        $dataImported = isset($_POST["data"]);


        return $isModuleEnabledInProject && $isApiUserRightsMethod && $dataImported;
    }

    public function getBadRights()
    {
        if (!$this->requestHandled) {
            $this->handleRequest();
        }
        return $this->bad_rights;
    }

    public function shouldAllowImport()
    {
        if (!$this->requestHandled) {
            $this->handleRequest();
        }
        return empty($this->bad_rights);
    }

    private function getUserRightsFromToken()
    {
        $sql = "SELECT * FROM redcap_user_rights WHERE api_token = ?";
        $rights = [];
        try {
            $result = $this->module->query($sql, [$this->token]);
            $rights = $result->fetch_assoc();
        } catch (\Throwable $e) {
            $this->module->log('Error getting user rights from API token', ["error" => $e->getMessage()]);
        } finally {
            return $rights;
        }
    }

    private function checkApiUserRoleMapping()
    {
        $bad_rights = [];
        foreach ($this->data as $this_assignment) {
            $username = $this_assignment["username"];
            $uniqueRoleName = $this_assignment["unique_role_name"];
            if ($uniqueRoleName == '') {
                continue;
            }
            $role_id = $this->module->getRoleIdFromUniqueRoleName($uniqueRoleName);
            $role_name = \ExternalModules\ExternalModules::getRoleName($this->project_id, $role_id);
            $role_rights = $this->module->getRoleRights($role_id, $this->project_id);
            $acceptable_rights = $this->module->getAcceptableRights($username);
            $these_bad_rights = $this->module->checkProposedRights($acceptable_rights, $role_rights);
            if (!empty($these_bad_rights)) {
                $bad_rights[$role_name] = $these_bad_rights;
            }
        }
        $this->bad_rights = $bad_rights;
    }
}
