<?php

namespace YaleREDCap\SecurityAccessGroups;

class APIHandler
{
    private $module;
    private $post;
    private $token;
    private $data;
    private $originalRights = [];
    private $badRights = [];
    private $requestHandled = false;
    private $projectId;
    private $user;
    private $action;
    public function __construct(SecurityAccessGroups $module, array $post)
    {
        $this->module = $module;
        $this->post   = $post;
        $this->token  = $this->module->framework->sanitizeAPIToken($this->post['token']);
        $this->data   = json_decode($this->post['data'] ?? '{}', true);
        $this->action = htmlspecialchars($this->post['content']);
    }

    public function handleRequest()
    {
        switch ($this->post['content']) {
            case 'userRoleMapping':
                $this->module->log('Processing API Role Mapping Import');
                $this->checkApiUserRoleMapping();
                break;
            case 'userRole':
                $this->module->log('Processing API Role Import');
                $this->checkUserRoles();
                break;
            case 'user':
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
        $rights          = $this->getUserRightsFromToken() ?? [];
        $this->projectId = $rights['project_id'];
        $this->user      = $rights['username'];

        $prefix                   = $this->module->getModuleDirectoryPrefix();
        $isModuleEnabledInProject = (bool) $this->module->isModuleEnabled($prefix, $this->projectId);
        $isApiUserRightsMethod    = in_array($this->action, [ 'user', 'userRole', 'userRoleMapping' ], true);
        $dataImported             = isset($this->post['data']);

        return $isModuleEnabledInProject && $isApiUserRightsMethod && $dataImported;
    }

    public function getBadRights()
    {
        if ( !$this->requestHandled ) {
            $this->handleRequest();
        }
        return $this->badRights;
    }

    public function shouldAllowImport()
    {
        if ( !$this->requestHandled ) {
            $this->handleRequest();
        }
        return empty($this->badRights);
    }

    private function getUserRightsFromToken()
    {
        $sql    = "SELECT * FROM redcap_user_rights WHERE api_token = ?";
        $rights = [];
        try {
            $result = $this->module->query($sql, [ $this->token ]);
            $rights = $result->fetch_assoc();
        } catch ( \Throwable $e ) {
            $this->module->log('Error getting user rights from API token', [ 'error' => $e->getMessage() ]);
        } finally {
            return $this->module->framework->escape($rights);
        }
    }

    private function checkApiUserRoleMapping()
    {
        try {
            $badRights = [];
            foreach ( $this->data as $this_assignment ) {
                $username       = $this_assignment['username'];
                $sagUser        = new SAGUser($this->module, $username);
                $uniqueRoleName = $this_assignment['unique_role_name'];
                if ( $uniqueRoleName == '' ) {
                    continue;
                }
                $role   = new Role($this->module, null, $uniqueRoleName);
                $roleId = $role->getRoleId();
                if ( empty($roleId) ) {
                    continue;
                }
                $roleName         = $role->getRoleName();
                $roleRights       = $role->getRoleRights($this->projectId);
                $acceptableRights = $sagUser->getAcceptableRights();
                $theseBadRights   = $this->module->checkProposedRights($acceptableRights, $roleRights);
                // We ignore expired users
                $userExpired = $sagUser->isUserExpired($this->projectId);
                if ( !empty($theseBadRights) && !$userExpired ) {
                    $badRights[$roleName] = $theseBadRights;
                }
            }
            $this->badRights      = $badRights;
            $this->originalRights = $this->data;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Role Mapping Import', [ 'error' => $e->getMessage() ]);
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
            $sagUser          = new SAGUser($this->module, $username);
            $acceptableRights = $sagUser->getAcceptableRights();
            $userBadRights    = $this->module->checkProposedRights($acceptableRights, $thisRole);
            // We ignore expired users
            $userExpired = $sagUser->isUserExpired($this->projectId);
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
                $roleLabel   = $thisRole['role_label'];
                $role        = new Role($this->module, null, $thisRole['unique_role_name']);
                $usersInRole = $role->getUsersInRole($this->projectId);
                $thisRole    = $this->handleFormsViewing($thisRole);
                $thisRole    = $this->handleFormsExport($thisRole);
                $thisRole    = $this->filterRights($thisRole);

                $theseBadRights = $this->checkUsersInRole($usersInRole, $thisRole);
                if ( !empty($theseBadRights) ) {
                    $badRights[$roleLabel] = $theseBadRights;
                }

                $this->originalRights = \UserRights::getRoles($this->projectId);
            }
            $this->badRights = $badRights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Role Import', [ 'error' => $e->getMessage() ]);
        }
    }

    private function checkUsers()
    {
        try {
            $badRights = [];
            foreach ( $this->data as $thisUser ) {
                $username = $thisUser['username'];
                $sagUser  = new SAGUser($this->module, $username);
                $thisUser = $this->handleFormsViewing($thisUser);
                $thisUser = $this->handleFormsExport($thisUser);
                $thisUser = $this->filterRights($thisUser);

                $acceptableRights = $sagUser->getAcceptableRights();
                $theseBadRights   = $this->module->checkProposedRights($acceptableRights, $thisUser);

                // We ignore expired users, unless the request unexpires them
                $userExpired            = $sagUser->isUserExpired($this->projectId);
                $requestedExpiration    = urldecode($thisUser['expiration']);
                $expirationDateInFuture = strtotime($requestedExpiration) >= strtotime('today');
                $requestedUnexpired     = empty($requestedExpiration) || $expirationDateInFuture;
                if ( $userExpired && !$requestedUnexpired ) {
                    $ignore = true;
                }

                if ( !empty($theseBadRights) && !$ignore ) {
                    $badRights[$username] = $theseBadRights;
                } else {

                    $this->originalRights[] = [
                        'username' => $username,
                        'rights'   => $sagUser->getCurrentRights($this->projectId)
                    ];
                }
            }
            $this->badRights = $badRights;
        } catch ( \Throwable $e ) {
            $this->module->log('Error Processing API User Import', [ 'error' => $e->getMessage() ]);
        }
    }

    public function getApiRequestInfo()
    {
        return [ $this->action, $this->projectId, $this->user, $this->originalRights ];
    }
}