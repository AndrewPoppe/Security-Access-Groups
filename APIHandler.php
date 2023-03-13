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
                $this->module->log('Processing API Role Mapping Import');
                $this->checkApiUserRoleMapping();
                break;
            case "userRole":
                $this->module->log('Processing API Role Import');
                $this->checkUserRoles();
                break;
            case "user":
                $this->module->log('Processing API User Import');
                $this->checkUsers();
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
        try {
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
        } catch (\Throwable $e) {
            $this->module->log('Error Processing API User Role Mapping Import', ["error" => $e->getMessage()]);
        }
    }

    private function checkUserRoles()
    {
        try {
            $bad_rights = [];
            foreach ($this->data as $this_role) {
                $role_label = $this_role["role_label"];
                $role_id = $this->module->getRoleIdFromUniqueRoleName($this_role["unique_role_name"]);
                $usersInRole = $this->module->getUsersInRole($this->project_id, $role_id);
                if (isset($this_role['forms']) && $this_role['forms'] != '') {
                    foreach ($this_role['forms'] as $this_form => $this_right) {
                        $this_role['form-' . $this_form] = $this_right;
                    }
                    unset($this_role['forms']);
                }
                if (isset($this_role['forms_export']) && $this_role['forms_export'] != '') {
                    foreach ($this_role['forms_export'] as $this_form => $this_right) {
                        $this_role['export-form-' . $this_form] = $this_right;
                    }
                    unset($this_role['forms_export']);
                }
                $this_role = array_filter($this_role, function ($value, $key) {
                    return ($value != 0);
                }, ARRAY_FILTER_USE_BOTH);

                $these_bad_rights = [];
                foreach ($usersInRole as $username) {
                    $acceptable_rights = $this->module->getAcceptableRights($username);
                    $user_bad_rights = $this->module->checkProposedRights($acceptable_rights, $this_role);
                    if (!empty($user_bad_rights)) {
                        $these_bad_rights[$username] = $user_bad_rights;
                    }
                }
                if (!empty($these_bad_rights)) {
                    $bad_rights[$role_label] = $these_bad_rights;
                }
            }
            $this->bad_rights = $bad_rights;
        } catch (\Throwable $e) {
            $this->module->log('Error Processing API User Role Import', ["error" => $e->getMessage()]);
        }
    }

    private function checkUsers()
    {
        try {
            $bad_rights = [];
            foreach ($this->data as $this_user) {
                $username = $this_user['username'];
                if (isset($this_user['forms']) && $this_user['forms'] != '') {
                    foreach ($this_user['forms'] as $this_form => $this_right) {
                        $this_user['form-' . $this_form] = $this_right;
                    }
                    unset($this_user['forms']);
                }
                if (isset($this_user['forms_export']) && $this_user['forms_export'] != '') {
                    foreach ($this_user['forms_export'] as $this_form => $this_right) {
                        $this_user['export-form-' . $this_form] = $this_right;
                    }
                    unset($this_user['forms_export']);
                }
                $this_user = array_filter($this_user, function ($value, $key) {
                    return ($value != 0);
                }, ARRAY_FILTER_USE_BOTH);

                $acceptable_rights = $this->module->getAcceptableRights($username);
                $these_bad_rights = $this->module->checkProposedRights($acceptable_rights, $this_user);
                if (!empty($these_bad_rights)) {
                    $bad_rights[$username] = $these_bad_rights;
                }
            }
            $this->bad_rights = $bad_rights;
        } catch (\Throwable $e) {
            $this->module->log('Error Processing API User Import', ["error" => $e->getMessage()]);
        }
    }
}
