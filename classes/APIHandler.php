<?php

namespace YaleREDCap\SecurityAccessGroups;

class APIHandler
{
    private $module;
    private $post;
    private $token;
    private $data;
    private $original_rights = [];
    private $bad_rights = [];
    private $requestHandled = false;
    private $project_id;
    private $user;
    private $action;
    function __construct(SecurityAccessGroups $module, array $post)
    {
        $this->module = $module;
        $this->post   = $post;
        $this->token  = $this->post["token"];
        $this->data   = json_decode($this->post["data"] ?? "{}", true);
        $this->action = htmlspecialchars($this->post["content"]);
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
        $rights           = $this->getUserRightsFromToken() ?? [];
        $this->project_id = $rights["project_id"];
        $this->user       = $rights["username"];

        $prefix                   = $this->module->getModuleDirectoryPrefix();
        $isModuleEnabledInProject = (bool) $this->module->isModuleEnabled($prefix, $this->project_id);
        $isApiUserRightsMethod    = in_array($this->action, [ "user", "userRole", "userRoleMapping" ], true);
        $dataImported             = isset($this->post["data"]);

        return $isModuleEnabledInProject && $isApiUserRightsMethod && $dataImported;
    }

    public function getBadRights()
    {
        if ( !$this->requestHandled ) {
            $this->handleRequest();
        }
        return $this->bad_rights;
    }

    public function shouldAllowImport()
    {
        if ( !$this->requestHandled ) {
            $this->handleRequest();
        }
        return empty($this->bad_rights);
    }

    private function getUserRightsFromToken()
    {
        $sql    = "SELECT * FROM redcap_user_rights WHERE api_token = ?";
        $rights = [];
        try {
            $result = $this->module->query($sql, [ $this->token ]);
            $rights = $result->fetch_assoc();
        } catch ( \Throwable $e ) {
            $this->module->log('Error getting user rights from API token', [ "error" => $e->getMessage() ]);
        } finally {
            return $this->module->framework->escape($rights);
        }
    }

    private function checkApiUserRoleMapping()
    {
        try {
            $bad_rights = [];
            foreach ( $this->data as $this_assignment ) {
                $username       = $this_assignment["username"];
                $uniqueRoleName = $this_assignment["unique_role_name"];
                if ( $uniqueRoleName == '' ) {
                    continue;
                }
                $role_id = $this->module->getRoleIdFromUniqueRoleName($uniqueRoleName);
                if ( empty($role_id) ) {
                    continue;
                }
                $role_name         = \ExternalModules\ExternalModules::getRoleName($this->project_id, $role_id);
                $role_rights       = $this->module->getRoleRights($role_id, $this->project_id);
                $acceptable_rights = $this->module->getAcceptableRights($username);
                $these_bad_rights  = $this->module->checkProposedRights($acceptable_rights, $role_rights);
                // We ignore expired users
                $userExpired = $this->module->isUserExpired($username, $this->project_id);
                if ( !empty($these_bad_rights) && !$userExpired ) {
                    $bad_rights[$role_name] = $these_bad_rights;
                }
            }
            $this->bad_rights      = $bad_rights;
            $this->original_rights = $this->data;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Role Mapping Import', [ "error" => $e->getMessage() ]);
        }
    }

    private function checkUserRoles()
    {
        try {
            $bad_rights = [];
            foreach ( $this->data as $this_role ) {
                $role_label  = $this_role["role_label"];
                $role_id     = $this->module->getRoleIdFromUniqueRoleName($this_role["unique_role_name"]);
                $usersInRole = $this->module->getUsersInRole($this->project_id, $role_id);
                if ( isset($this_role['forms']) && $this_role['forms'] != '' ) {
                    foreach ( $this_role['forms'] as $this_form => $this_right ) {
                        $this_role['form-' . $this_form] = $this_right;
                    }
                    unset($this_role['forms']);
                }
                if ( isset($this_role['forms_export']) && $this_role['forms_export'] != '' ) {
                    foreach ( $this_role['forms_export'] as $this_form => $this_right ) {
                        $this_role['export-form-' . $this_form] = $this_right;
                    }
                    unset($this_role['forms_export']);
                }
                $this_role = array_filter($this_role, function ($value, $key) {
                    return ($value != 0);
                }, ARRAY_FILTER_USE_BOTH);

                $these_bad_rights = [];
                foreach ( $usersInRole as $username ) {
                    $acceptable_rights = $this->module->getAcceptableRights($username);
                    $user_bad_rights   = $this->module->checkProposedRights($acceptable_rights, $this_role);
                    // We ignore expired users
                    $userExpired = $this->module->isUserExpired($username, $this->project_id);
                    if ( !empty($user_bad_rights) && !$userExpired ) {
                        $these_bad_rights[$username] = $user_bad_rights;
                    }
                }
                if ( !empty($these_bad_rights) ) {
                    $bad_rights[$role_label] = $these_bad_rights;
                }
                $this->original_rights = \UserRights::getRoles($this->project_id);
            }
            $this->bad_rights = $bad_rights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Role Import', [ "error" => $e->getMessage() ]);
        }
    }

    private function checkUsers()
    {
        try {
            $bad_rights = [];
            foreach ( $this->data as $this_user ) {
                $username = $this_user['username'];
                if ( isset($this_user['forms']) && $this_user['forms'] != '' ) {
                    foreach ( $this_user['forms'] as $this_form => $this_right ) {
                        $this_user['form-' . $this_form] = $this_right;
                    }
                    unset($this_user['forms']);
                }
                if ( isset($this_user['forms_export']) && $this_user['forms_export'] != '' ) {
                    foreach ( $this_user['forms_export'] as $this_form => $this_right ) {
                        $this_user['export-form-' . $this_form] = $this_right;
                    }
                    unset($this_user['forms_export']);
                }
                $this_user = array_filter($this_user, function ($value, $key) {
                    return ($value != 0);
                }, ARRAY_FILTER_USE_BOTH);

                $acceptable_rights = $this->module->getAcceptableRights($username);
                $these_bad_rights  = $this->module->checkProposedRights($acceptable_rights, $this_user);

                // We ignore expired users, unless the request unexpires them
                $userExpired         = $this->module->isUserExpired($username, $this->project_id);
                $requestedExpiration = urldecode($this_user["expiration"]);
                $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
                if ( $userExpired && !$requestedUnexpired ) {
                    $ignore = true;
                }

                if ( !empty($these_bad_rights) && !$ignore ) {
                    $bad_rights[$username] = $these_bad_rights;
                } else {
                    $this->original_rights[] = [
                        "username" => $username,
                        "rights"   => $this->module->getCurrentRights($username, $this->project_id)
                    ];
                    $this->module->framework->log('orig', [ 'rights' => json_encode($this->original_rights, JSON_PRETTY_PRINT) ]);
                }
            }
            $this->bad_rights = $bad_rights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Import', [ "error" => $e->getMessage() ]);
        }
    }

    function getApiRequestInfo()
    {
        return [ $this->action, $this->project_id, $this->user, $this->original_rights ];
    }
}