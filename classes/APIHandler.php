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

    private function logApiUser()
    {
        foreach ( $this->originalRights as $theseOriginalRights ) {
            $username   = $theseOriginalRights['username'];
            $sagUser    = new SAGUser($this->module, $username);
            $oldRights  = $theseOriginalRights['rights'] ?? [];
            $newUser    = empty($oldRights);
            $newRights  = $sagUser->getCurrentRights($this->projectId);
            $changes    = json_encode(array_diff_assoc($newRights, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === '[]' ? 'None' : $changes;
            $dataValues = "user = '" . $username . "'\nchanges = " . $changes;
            if ( $newUser ) {
                $event       = 'INSERT';
                $description = 'Add user';
            } else {
                $event       = 'UPDATE';
                $description = 'Edit user';
            }
            $logTable   = $this->module->framework->getProject($this->projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $this->projectId, $this->user, $username, $event ];
            $result     = $this->module->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);
            if ( $logEventId != 0 ) {
                $this->module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    $event,
                    $username,
                    $dataValues,
                    $description,
                    '',
                    '',
                    '',
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRole()
    {
        $newRights = \UserRights::getRoles($this->projectId);
        foreach ( $newRights as $role_id => $role ) {
            $oldRights  = $this->originalRights[$role_id] ?? [];
            $newRole    = empty($oldRights);
            $roleLabel  = $role['role_name'];
            $changes    = json_encode(array_diff_assoc($role, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === '[]' ? 'None' : $changes;
            $dataValues = "role = '" . $roleLabel . "'\nchanges = " . $changes;
            $logTable   = $this->module->framework->getProject($this->projectId)->getLogTable();

            if ( $newRole ) {
                $description    = 'Add role';
                $event          = 'INSERT';
                $origDataValues = "role = '" . $roleLabel . "'";
                $objectType     = 'redcap_user_rights';
                $sql            = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params         = [ $this->projectId, $this->user, $origDataValues ];
            } else {
                $description = 'Edit role';
                $event       = 'update';
                $objectType  = 'redcap_user_roles';
                $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params      = [ $this->projectId, $this->user, $role_id ];
            }

            $result     = $this->module->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);
            if ( $logEventId != 0 ) {
                $this->module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    $objectType,
                    $event,
                    $role_id,
                    $dataValues,
                    $description,
                    '',
                    '',
                    '',
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRoleMapping()
    {
        foreach ( $this->originalRights as $mapping ) {
            $username       = $mapping['username'];
            $uniqueRoleName = $mapping['unique_role_name'];
            $role           = new Role($this->module, null, $uniqueRoleName);
            $roleLabel      = $role->getRoleName();

            $logTable   = $this->module->framework->getProject($this->projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $this->projectId, $this->user, $username ];
            $result     = $this->module->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);

            $dataValues = "user = '" . $username . "'\nrole = '" . $roleLabel . "'\nunique_role_name = '" . $uniqueRoleName . "'";

            if ( $logEventId != 0 ) {
                $this->module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    'INSERT',
                    $username,
                    $dataValues,
                    'Assign user to role',
                    '',
                    '',
                    '',
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    public function logApi()
    {
        ob_start(function ($str) {

            if ( strpos($str, '{"error":') === 0 ) {
                $this->module->framework->log('api_failed');
                return $str;
            }
            if ( $this->action === 'user' ) {
                $this->logApiUser();
            } elseif ( $this->action === 'userRole' ) {
                $this->logApiUserRole();
            } elseif ( $this->action === 'userRoleMapping' ) {
                $this->logApiUserRoleMapping();
            }
            return $str;
        }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE);
    }
}