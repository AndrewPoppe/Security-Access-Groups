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
    public function __construct(SecurityAccessGroups $module, array $post)
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

    private function handleFormsViewing($rights)
    {
        if ( isset($rights['forms']) && $rights['forms'] != '' ) {
            foreach ( $rights['forms'] as $thisForm => $thisRight ) {
                $rights['form-' . $thisForm] = $thisRight;
            }
            unset($rights['forms']);
        }
        return $rights;
    }

    private function handleFormsExport($rights)
    {
        if ( isset($rights['forms_export']) && $rights['forms_export'] != '' ) {
            foreach ( $rights['forms_export'] as $thisForm => $thisRight ) {
                $rights['export-form-' . $thisForm] = $thisRight;
            }
            unset($rights['forms_export']);
        }
        return $rights;
    }

    private function filterRights($rights)
    {
        return array_filter($rights, function ($value) {
            return $value != 0;
        });
    }

    private function checkUsersInRole($usersInRole, $thisRole)
    {
        $theseBadRights = [];
        foreach ( $usersInRole as $username ) {
            $acceptableRights = $this->module->getAcceptableRights($username);
            $userBadRights    = $this->module->checkProposedRights($acceptableRights, $thisRole);
            // We ignore expired users
            $userExpired = $this->module->isUserExpired($username, $this->project_id);
            if ( !empty($userBadRights) && !$userExpired ) {
                $theseBadRights[$username] = $userBadRights;
            }
        }
        return $theseBadRights;
    }

    private function checkUserRoles()
    {
        try {
            $badRights = [];
            foreach ( $this->data as $thisRole ) {
                $roleLabel   = $thisRole["role_label"];
                $roleId      = $this->module->getRoleIdFromUniqueRoleName($thisRole["unique_role_name"]);
                $usersInRole = $this->module->getUsersInRole($this->project_id, $roleId);
                $thisRole    = $this->handleFormsViewing($thisRole);
                $thisRole    = $this->handleFormsExport($thisRole);
                $thisRole    = $this->filterRights($thisRole);

                $theseBadRights = $this->checkUsersInRole($usersInRole, $thisRole);
                if ( !empty($theseBadRights) ) {
                    $badRights[$roleLabel] = $theseBadRights;
                }

                $this->original_rights = \UserRights::getRoles($this->project_id);
            }
            $this->bad_rights = $badRights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Role Import', [ "error" => $e->getMessage() ]);
        }
    }

    private function checkUsers()
    {
        try {
            $badRights = [];
            foreach ( $this->data as $thisUser ) {
                $username = $thisUser['username'];
                $thisUser = $this->handleFormsViewing($thisUser);
                $thisUser = $this->handleFormsExport($thisUser);
                $thisUser = $this->filterRights($thisUser);

                $acceptableRights = $this->module->getAcceptableRights($username);
                $theseBadRights   = $this->module->checkProposedRights($acceptableRights, $thisUser);

                // We ignore expired users, unless the request unexpires them
                $userExpired            = $this->module->isUserExpired($username, $this->project_id);
                $requestedExpiration    = urldecode($thisUser['expiration']);
                $expirationDateInFuture = strtotime($requestedExpiration) >= strtotime('today');
                $requestedUnexpired     = empty($requestedExpiration) || $expirationDateInFuture;
                if ( $userExpired && !$requestedUnexpired ) {
                    $ignore = true;
                }

                if ( !empty($theseBadRights) && !$ignore ) {
                    $badRights[$username] = $theseBadRights;
                } else {
                    $this->original_rights[] = [
                        "username" => $username,
                        "rights"   => $this->module->getCurrentRights($username, $this->project_id)
                    ];
                }
            }
            $this->bad_rights = $badRights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Import', [ "error" => $e->getMessage() ]);
        }
    }

    public function getApiRequestInfo()
    {
        return [ $this->action, $this->project_id, $this->user, $this->original_rights ];
    }
}