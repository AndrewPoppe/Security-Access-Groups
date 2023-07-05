<?php

namespace YaleREDCap\SecurityAccessGroups;

require_once 'classes/APIHandler.php';
require_once 'classes/Alerts.php';
require_once 'classes/RightsChecker.php';
require_once 'classes/RoleEditForm.php';
use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

/**
 * @property Framework $framework
 * @see Framework
 */
class SecurityAccessGroups extends AbstractExternalModule
{

    public string $defaultRoleId = "role_Default";
    public string $defaultRoleName = "Default Role";
    private array $defaultRights = [];


    public function __construct()
    {
        parent::__construct();
        $this->defaultRights = $this->getSystemRoleRightsById($this->defaultRoleId);
    }

    public function redcap_every_page_before_render()
    {
        // Only run on the pages we're interested in
        if (
            $_SERVER["REQUEST_METHOD"] !== "POST" ||
            !in_array(PAGE, [
                "UserRights/edit_user.php",
                "UserRights/assign_user.php",
                "UserRights/import_export_users.php",
                "UserRights/import_export_roles.php",
                "api/index.php"
            ], true)
        ) {
            return;
        }

        // API
        if ( PAGE === "api/index.php" ) {
            $API = new APIHandler($this, $_POST);
            if ( !$API->shouldProcess() ) {
                return;
            }

            $API->handleRequest();
            if ( !$API->shouldAllowImport() ) {
                $bad_rights = $API->getBadRights();
                http_response_code(401);
                echo json_encode($bad_rights);
                $this->exitAfterHook();
                return;
            } else {
                [ $action, $project_id, $user, $original_rights ] = $API->getApiRequestInfo();
                $this->logApi($action, $project_id, $user, $original_rights);
            }
            return;
        }

        try {
            $username = $this->framework->getUser()->getUsername() ?? "";
        } catch ( \Throwable $e ) {
            $this->framework->log('Error', [ "error" => $e->getMessage() ]);
        }

        // Edit User or Role
        if (
            PAGE === "UserRights/edit_user.php" &&
            isset($_POST['submit-action']) &&
            in_array($_POST['submit-action'], [ "edit_role", "edit_user", "add_user" ])
        ) {
            $this->framework->log('attempt to edit user or role directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->framework->exitAfterHook();
            return;
        }

        // Assign User to Role
        if ( PAGE === "UserRights/assign_user.php" ) {
            $this->log('attempt to assign user role directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }

        // Upload Users via CSV
        if ( PAGE === "UserRights/import_export_users.php" ) {
            $this->log('attempt to upload users directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }

        // Upload Roles or Mappings via CSV
        if ( PAGE === "UserRights/import_export_roles.php" ) {
            $this->log('attempt to upload roles or role mappings directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }
    }

    // CRON job
    public function sendReminders($cronInfo = array())
    {
        try {
            $Alerts            = new Alerts($this);
            $enabledSystemwide = $this->framework->getSystemSetting('enabled');
            $prefix            = $this->getModuleDirectoryPrefix();

            if ( $enabledSystemwide == true ) {
                $all_project_ids = $this->getAllProjectIds();
                $project_ids     = array_filter($all_project_ids, function ($project_id) use ($prefix) {
                    return $this->isModuleEnabled($prefix, $project_id);
                });
            } else {
                $project_ids = $this->getProjectsWithModuleEnabled();
            }

            foreach ( $project_ids as $localProjectId ) {
                // Specifying project id just to prevent reminders being sent
                // for projects that no longer have the module enabled.
                $Alerts->sendUserReminders($localProjectId);
            }

            return "The \"{$cronInfo['cron_name']}\" cron job completed successfully.";
        } catch ( \Exception $e ) {
            $this->log("Error sending reminders", [ "error" => $e->getMessage() ]);
            return "The \"{$cronInfo['cron_name']}\" cron job failed: " . $e->getMessage();
        }
    }

    public function getAllProjectIds()
    {
        try {
            $query       = "select project_id from redcap_projects
            where created_by is not null
            and completed_time is null
            and date_deleted is null";
            $result      = $this->framework->query($query, []);
            $project_ids = [];
            while ( $row = $result->fetch_assoc() ) {
                $project_ids[] = intval($row["project_id"]);
            }
            return $project_ids;
        } catch ( \Exception $e ) {
            $this->log("Error fetching all projects", [ "error" => $e->getMessage() ]);
        }
    }

    public function redcap_user_rights($project_id)
    {

        ?>
<script>
$(function() {

    <?php if ( isset($_SESSION['SUR_imported']) ) { ?>
    window.import_type = '<?= $_SESSION['SUR_imported'] ?>';
    window.import_errors = JSON.parse('<?= $_SESSION['SUR_bad_rights'] ?>');
    <?php
                    unset($_SESSION['SUR_imported']);
                    unset($_SESSION['SUR_bad_rights']);
                } ?>

    function createRightsTable(bad_rights) {
        return `<table class="table table-sm table-borderless table-hover w-50 mt-4 mx-auto" style="font-size:13px; cursor: default;"><tbody><tr><td>${bad_rights.join('</td></tr><tr><td>')}</td></tr></tbody></table>`;
    }

    function fixLinks() {
        $('#importUserForm').attr('action', "<?= $this->getUrl("ajax/import_export_users.php") ?>");
        $('#importUsersForm2').attr('action', "<?= $this->getUrl("ajax/import_export_users.php") ?>");
        $('#importRoleForm').attr('action', "<?= $this->getUrl("ajax/import_export_roles.php") ?>");
        $('#importRolesForm2').attr('action', "<?= $this->getUrl("ajax/import_export_roles.php") ?>");
        $('#importUserRoleForm').attr('action',
            "<?= $this->getUrl("ajax/import_export_roles.php?action=uploadMapping") ?>");
        $('#importUserRoleForm2').attr('action',
            "<?= $this->getUrl("ajax/import_export_roles.php?action=uploadMapping") ?>");
    }

    function checkImportErrors() {
        if (window.import_type) {
            let title = "You can't do that.";
            let text = "";
            if (window.import_type == "users") {
                title = "You cannot import those users.";
                text =
                    `The following users included in the provided import file cannot have the following permissions granted to them due to their current SAG assignment:<br><table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>User</th><th>SAG</th><th>Permissions</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const users = Object.keys(window.import_errors);
                users.forEach((user) => {
                    text +=
                        `<tr style="border-top: 1px solid #666;"><td><strong>${user}</strong></td><td>${window.import_errors[user].SAG}</td><td>${window.import_errors[user].rights.join('<br>')}</td></tr>`;
                });
                text += `</tbody></table>`;
            } else if (window.import_type == "roles") {
                title = "You cannot import those roles.";
                text =
                    `The following roles have users assigned to them, and the following permissions cannot be granted for those users due to their current SAG assignment:<br><table style="margin-top: 20px; width: 100%; table-layout: fixed;"><thead style="border-bottom: 2px solid #666;"><tr><th>User Role</th><th>User</th><th>SAG</th><th COLSPAN=2>Permissions</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const roles = Object.keys(window.import_errors);
                roles.forEach((role) => {
                    const users = Object.keys(window.import_errors[role]);
                    users.forEach((user, index) => {
                        const theseRights = window.import_errors[role][user];
                        text +=
                            `<tr style='border-top: 1px solid black;'><td><strong>${role}</strong></td><td><strong>${user}</strong></td><td>${theseRights.SAG}</td><td COLSPAN=2>${theseRights.rights.join('<br>')}</td></tr>`;
                    });
                })
                text += `</tbody></table>`;
            } else if (window.import_type == "roleassignments") {
                title = "You cannot assign those users to those roles.";
                text =
                    `The following permissions cannot be granted for the following users due to their current SAG assignment:<br><table style="margin-top: 20px; width: 100%; table-layout: fixed;"><thead style="border-bottom: 2px solid #666;"><tr><th>User Role</th><th>User</th><th>SAG</th><th COLSPAN=2>Permissions</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const roles = Object.keys(window.import_errors);
                roles.forEach((role) => {
                    const users = Object.keys(window.import_errors[role]);
                    users.forEach((user, index) => {
                        const theseRights = window.import_errors[role][user];
                        text +=
                            `<tr style='border-top: 1px solid black;'><td><strong>${role}</strong></td><td><strong>${user}</strong></td><td>${theseRights.SAG}</td><td COLSPAN=2>${theseRights.rights.join('<br>')}</td></tr>`;
                    });
                })
                text += `</tbody></table>`;
            }
            Swal.fire({
                icon: 'error',
                title: title,
                html: text,
                width: '900px'
            });
        }
    }

    window.saveUserFormAjax = function() {
        showProgress(1);
        const permissions = $('form#user_rights_form').serializeObject();
        console.log(permissions);
        $.post('<?= $this->getUrl("ajax/edit_user.php?pid=$project_id") ?>', permissions, function(data) {
            showProgress(0, 0);
            try {
                const result = JSON.parse(data);
                if (!result.error || !result.bad_rights) {
                    return;
                }
                let title = "You can't do that.";
                let text = "";
                let users = Object.keys(result.bad_rights);
                if (!result.role) {
                    title = `You cannot grant those user rights to user "${users[0]}"`;
                    text =
                        `The user is currently assigned to the SAG: "<strong>${result.bad_rights[users[0]].SAG}</strong>"<br>The following permissions you are attempting to grant cannot be granted to users in that SAG:${createRightsTable(result.bad_rights[users[0]].rights)}`;
                } else {
                    title = `You cannot grant those rights to the role<br>"${result.role}"`;
                    text =
                        `The following users are assigned to that role, and the following permissions cannot be granted to them because of their current SAG assignment:<br><table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>User</th><th>SAG</th><th>Permissions</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                    users.forEach((user) => {
                        text +=
                            `<tr style="border-top: 1px solid #666;"><td><strong>${user}</strong></td><td>${result.bad_rights[user].SAG}</td><td>${result.bad_rights[user].rights.join('<br>')}</td></tr>`;
                    });
                    text += `</tbody></table>`;
                }
                Swal.fire({
                    icon: 'error',
                    title: title,
                    html: text,
                    width: '900px'
                });
                return;
            } catch (error) {
                if ($('#editUserPopup').hasClass('ui-dialog-content')) $('#editUserPopup').dialog(
                    'destroy');
                $('#user_rights_roles_table_parent').html(data);
                simpleDialogAlt($('#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                enablePageJS();
                if ($('#copy_role_success').length) {
                    setTimeout(function() {
                        openAddUserPopup('', $('#copy_role_success').val());
                    }, 1500);
                }
            }
            fixLinks();
        });
    }

    window.assignUserRole = function(username, role_id) {
        showProgress(1);
        checkIfuserRights(username, role_id, function(data) {
            if (data == 1) {
                console.log(username, role_id);
                $.post('<?= $this->getUrl("ajax/assign_user.php?pid=$project_id") ?>', {
                    username: username,
                    role_id: role_id,
                    notify_email_role: ($('#notify_email_role').prop('checked') ? 1 : 0),
                    group_id: $('#user_dag').val()
                }, function(data) {
                    showProgress(0, 0);
                    if (data == '') {
                        alert(woops);
                        return;
                    }
                    try {
                        const result = JSON.parse(data);
                        if (!result.error || !result.bad_rights) {
                            return;
                        }
                        let users = Object.keys(result.bad_rights);
                        const title =
                            `You cannot assign user "${username}" to user role "${result.role}"`;
                        const text =
                            `The user is currently assigned to the SAG: "<strong>${result.bad_rights[users[0]].SAG}</strong>"<br>The following permissions allowed in user role "${result.role}" cannot be granted to users in that SAG:${createRightsTable(result.bad_rights[users[0]].rights)}`;

                        Swal.fire({
                            icon: 'error',
                            title: title,
                            html: text,
                            width: '750px'
                        });
                        return;
                    } catch (error) {
                        $('#user_rights_roles_table_parent').html(data);
                        showProgress(0, 0);
                        simpleDialogAlt($(
                            '#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                        enablePageJS();
                        setTimeout(function() {
                            if (role_id == '0') {
                                simpleDialog(lang.rights_215, lang.global_03 + lang
                                    .colon + ' ' + lang.rights_214);
                            }
                        }, 3200);
                    }
                    fixLinks();
                });
            } else {
                showProgress(0, 0);
                setTimeout(function() {
                    simpleDialog(lang.rights_317, lang.global_03 + lang.colon + ' ' + lang
                        .rights_316);
                }, 500);
            }
            fixLinks();
        });
    }

    window.setExpiration = function() {
        $('#tooltipExpirationBtn').button('disable');
        $('#tooltipExpiration').prop('disabled', true);
        $('#tooltipExpirationCancel').hide();
        $('#tooltipExpirationProgress').show();
        $.post("<?= $this->getUrl('ajax/set_user_expiration.php?pid=' . $this->getProjectId()) ?>", {
                username: $('#tooltipExpirationHiddenUsername').val(),
                expiration: $('#tooltipExpiration').val()
            },
            function(data) {
                console.log(data);
                if (data == '0') {
                    alert(woops);
                    return;
                }
                try {
                    const result = JSON.parse(data);
                    if (!result.error || !result.bad_rights) {
                        return;
                    }
                    const users = Object.keys(result.bad_rights);
                    const title = `You cannot grant those user rights to user "${users[0]}"`;
                    const text =
                        `The user is currently assigned to the SAG: "<strong>${result.bad_rights[users[0]].SAG}</strong>"<br>The following permissions you are attempting to grant cannot be granted to users in that SAG:${createRightsTable(result.bad_rights[users[0]].rights)}`;

                    Swal.fire({
                        icon: 'error',
                        title: title,
                        html: text,
                        width: '750px'
                    });
                    return;
                } catch (error) {
                    $('#user_rights_roles_table_parent').html(data);
                    enablePageJS();
                } finally {
                    setTimeout(function() {
                        $('#tooltipExpiration').prop('disabled', false);
                        $('#tooltipExpirationBtn').button('enable');
                        $('#tooltipExpirationCancel').show();
                        $('#tooltipExpirationProgress').hide();
                        $('#userClickExpiration').hide();
                    }, 400);
                }
            });
    }
    fixLinks();
    checkImportErrors();
});
</script>
<?php
    }

    public function redcap_module_project_enable($version, $project_id)
    {
        $this->log('Module Enabled');
    }

    public function redcap_module_link_check_display($project_id, $link)
    {
        if ( empty($project_id) || $this->getUser()->isSuperUser() ) {
            return $link;
        }

        return null;
    }

    public function getCurrentRightsFormatted(string $username, $project_id)
    {
        $current_rights      = $this->getCurrentRights($username, $project_id);
        $current_data_export = $this->convertExportRightsStringToArray($current_rights["data_export_instruments"]);
        $current_data_entry  = $this->convertDataEntryRightsStringToArray($current_rights["data_entry"]);
        $current_rights      = array_merge($current_rights, $current_data_export, $current_data_entry);
        unset($current_rights["data_export_instruments"]);
        unset($current_rights["data_entry"]);
        unset($current_rights["data_export_tool"]);
        unset($current_rights["external_module_config"]);
        return $current_rights;
    }


    private function getBasicProjectUsers($project_id)
    {
        $sql = 'select rights.username,
        info.user_firstname,
        info.user_lastname,
        user_email,
        expiration,
        rights.role_id,
        roles.unique_role_name,
        roles.role_name,
        em.value as system_role
        from redcap_user_rights rights
        left join redcap_user_roles roles
        on rights.role_id = roles.role_id
        left join redcap_user_information info
        on rights.username = info.username
        LEFT JOIN redcap_external_module_settings em ON em.key = concat(rights.username,\'-role\')
        where rights.project_id = ?';
        try {
            $result = $this->framework->query($sql, [ $project_id ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $this->framework->escape($row);
            }
            return $users;
        } catch ( \Throwable $e ) {
            $this->framework->log('Error getting project users', [ 'error' => $e->getMessage() ]);
            return [];
        }
    }
    public function getUsersWithBadRights($project_id)
    {
        $users      = $this->getBasicProjectUsers($project_id);
        $roles      = $this->getAllSystemRoles(true);
        $bad_rights = [];
        foreach ( $users as $user ) {
            $expiration            = $user["expiration"];
            $isExpired             = $expiration != "" && strtotime($expiration) < strtotime("today");
            $username              = $user["username"];
            $acceptable_rights     = $roles[$user["system_role"]]["permissions"];
            $current_rights        = $this->getCurrentRightsFormatted($username, $project_id);
            $bad                   = $this->checkProposedRights($acceptable_rights, $current_rights);
            $systemRoleName        = $roles[$user["system_role"]]["role_name"];
            $projectRoleUniqueName = $user["unique_role_name"];
            $projectRoleName       = $user["role_name"];
            $bad_rights[]          = [
                "username"          => $username,
                "name"              => $user["user_firstname"] . " " . $user["user_lastname"],
                "email"             => $user["user_email"],
                "expiration"        => $expiration == "" ? "never" : $expiration,
                "isExpired"         => $isExpired,
                "system_role"       => $user["system_role"],
                "system_role_name"  => $systemRoleName,
                "project_role"      => $projectRoleUniqueName,
                "project_role_name" => $projectRoleName,
                "acceptable"        => $acceptable_rights,
                "current"           => $current_rights,
                "bad"               => $bad
            ];
        }
        return $bad_rights;
    }

    public function getUsersWithBadRights2($project_id)
    {
        $users            = $this->getBasicProjectUsers($project_id);
        $roles            = $this->getAllSystemRoles(true);
        $allCurrentRights = $this->getAllCurrentRights($project_id);
        $badRights        = [];
        foreach ( $users as $user ) {
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $systemRole            = $user['system_role'] ?? $this->defaultRoleId;
            $systemRole            = array_key_exists($systemRole, $roles) ? $systemRole : $this->defaultRoleId;
            $acceptableRights      = $roles[$systemRole]['permissions'];
            $currentRights         = $allCurrentRights[$username];
            $bad                   = $this->checkProposedRights2($acceptableRights, $currentRights);
            $systemRoleName        = $roles[$systemRole]['role_name'];
            $projectRoleUniqueName = $user['unique_role_name'];
            $projectRoleName       = $user['role_name'];
            $badRights[]           = [
                'username'          => $username,
                'name'              => $user['user_firstname'] . ' ' . $user['user_lastname'],
                'email'             => $user['user_email'],
                'expiration'        => $expiration == '' ? 'never' : $expiration,
                'isExpired'         => $isExpired,
                'system_role'       => $systemRole,
                'system_role_name'  => $systemRoleName,
                'project_role'      => $projectRoleUniqueName,
                'project_role_name' => $projectRoleName,
                'acceptable'        => $acceptableRights,
                'current'           => $currentRights,
                'bad'               => $bad
            ];
        }
        return $badRights;
    }

    private function logApiUser($project_id, $user, array $original_rights)
    {
        foreach ( $original_rights as $these_original_rights ) {
            $username    = $these_original_rights["username"];
            $oldRights   = $these_original_rights["rights"] ?? [];
            $newUser     = empty($oldRights);
            $newRights   = $this->getCurrentRights($username, $project_id);
            $changes     = json_encode(array_diff_assoc($newRights, $oldRights), JSON_PRETTY_PRINT);
            $changes     = $changes === "[]" ? "None" : $changes;
            $data_values = "user = '" . $username . "'\nchanges = " . $changes;
            if ( $newUser ) {
                $event       = "INSERT";
                $description = "Add user";
            } else {
                $event       = "UPDATE";
                $description = "Edit user";
            }
            $logTable     = $this->framework->getProject($project_id)->getLogTable();
            $sql          = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params       = [ $project_id, $user, $username, $event ];
            $result       = $this->framework->query($sql, $params);
            $log_event_id = intval($result->fetch_assoc()["log_event_id"]);
            if ( $log_event_id != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    $event,
                    $username,
                    $data_values,
                    $description,
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRole($project_id, $user, array $original_rights)
    {
        $newRights = \UserRights::getRoles($project_id);
        foreach ( $newRights as $role_id => $role ) {
            $oldRights   = $original_rights[$role_id] ?? [];
            $newRole     = empty($oldRights);
            $role_label  = $role["role_name"];
            $changes     = json_encode(array_diff_assoc($role, $oldRights), JSON_PRETTY_PRINT);
            $changes     = $changes === "[]" ? "None" : $changes;
            $data_values = "role = '" . $role_label . "'\nchanges = " . $changes;
            $logTable    = $this->framework->getProject($project_id)->getLogTable();

            if ( $newRole ) {
                $description      = 'Add role';
                $event            = 'INSERT';
                $orig_data_values = "role = '" . $role_label . "'";
                $object_type      = "redcap_user_rights";
                $sql              = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params           = [ $project_id, $user, $orig_data_values ];
            } else {
                $description = "Edit role";
                $event       = "update";
                $object_type = "redcap_user_roles";
                $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params      = [ $project_id, $user, $role_id ];
            }

            $result       = $this->framework->query($sql, $params);
            $log_event_id = intval($result->fetch_assoc()["log_event_id"]);
            if ( $log_event_id != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
            } else {
                \Logging::logEvent(
                    '',
                    $object_type,
                    $event,
                    $role_id,
                    $data_values,
                    $description,
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRoleMapping($project_id, $user, array $original_rights)
    {
        foreach ( $original_rights as $mapping ) {
            $username         = $mapping["username"];
            $unique_role_name = $mapping["unique_role_name"];
            $role_id          = $this->getRoleIdFromUniqueRoleName($unique_role_name);
            $role_label       = $this->getRoleLabel($role_id);

            $logTable     = $this->framework->getProject($project_id)->getLogTable();
            $sql          = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params       = [ $project_id, $user, $username ];
            $result       = $this->framework->query($sql, $params);
            $log_event_id = intval($result->fetch_assoc()["log_event_id"]);

            $data_values = "user = '" . $username . "'\nrole = '" . $role_label . "'\nunique_role_name = '" . $unique_role_name . "'";

            if ( $log_event_id != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    'INSERT',
                    $username,
                    $data_values,
                    'Assign user to role',
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApi(string $action, $project_id, $user, array $original_rights)
    {
        ob_start(function ($str) use ($action, $project_id, $user, $original_rights) {

            if ( strpos($str, '{"error":') === 0 ) {
                $this->log('api_failed');
                return $str;
            }
            if ( $action === "user" ) {
                $this->logApiUser($project_id, $user, $original_rights);
            } elseif ( $action === "userRole" ) {
                $this->logApiUserRole($project_id, $user, $original_rights);
            } elseif ( $action === "userRoleMapping" ) {
                $this->logApiUserRoleMapping($project_id, $user, $original_rights);
            }

            return $str;
        }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE);
    }

    public function getUserInfo(string $username) : ?array
    {
        $sql = "SELECT username
        , user_email
        , user_firstname
        , user_lastname
        , super_user
        , account_manager
        , access_system_config
        , access_system_upgrade
        , access_external_module_install
        , admin_rights
        , access_admin_dashboards
        , user_creation
        , user_lastlogin
        , user_suspended_time
        , user_expiration
        , user_sponsor
        , allow_create_db
        FROM redcap_user_information
        WHERE username = ?";
        try {
            $result = $this->framework->query($sql, [ $username ]);
            return $this->framework->escape($result->fetch_assoc());
        } catch ( \Throwable $e ) {
            $this->log("Error getting user info", [ "username" => $username, "error" => $e->getMessage(), "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function getAllUserInfo($includeSystemRole = false) : ?array
    {
        $sql = "SELECT username
        , user_email
        , user_firstname
        , user_lastname
        , super_user
        , account_manager
        , access_system_config
        , access_system_upgrade
        , access_external_module_install
        , admin_rights
        , access_admin_dashboards
        , user_creation
        , user_lastlogin
        , user_suspended_time
        , user_expiration
        , user_sponsor
        , allow_create_db";
        if ( $includeSystemRole ) {
            $sql .= ", em.value as system_role";
        }
        $sql .= " FROM redcap_user_information u";
        if ( $includeSystemRole ) {
            $sql .= " LEFT JOIN redcap_external_module_settings em ON em.key = concat(u.username,'-role')";
        }
        try {
            $result   = $this->framework->query($sql, []);
            $userinfo = [];
            while ( $row = $result->fetch_assoc() ) {
                $userinfo[] = $this->framework->escape($row);
            }
            return $userinfo;
        } catch ( \Throwable $e ) {
            $this->log("Error getting all user info", [ "error" => $e->getMessage(), "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function getAllRights()
    {
        $sql    = "SHOW COLUMNS FROM redcap_user_rights";
        $result = $this->framework->query($sql, []);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( !in_array($row["Field"], [ "project_id", "username", "expiration", "role_id", "group_id", "api_token", "data_access_group" ], true) ) {
                $rights[$row["Field"]] = $this->framework->escape($row["Field"]);
            }
        }
        return $rights;
    }

    public function getAcceptableRights(string $username)
    {
        $systemRoleId = $this->getUserSystemRole($username);
        $systemRole   = $this->getSystemRoleRightsById($systemRoleId);
        $roleRights   = json_decode($systemRole["permissions"], true);
        return $roleRights;
    }

    // E.g., from ["export-form-form1"=>"1", "export-form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertExportRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, "export-form-", 0, strlen("export-form-")) === 0 ) {
                $formName = str_replace("export-form-", "", $key);
                $result .= "[" . $formName . "," . $value . "]";
            }
        }
        return $result;
    }

    // E.g., from ["form-form1"=>"1", "form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertDataEntryRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, "form-", 0, strlen("form-")) === 0 && substr_compare($key, "form-editresp-", 0, strlen("form-editresp-")) !== 0 ) {
                $formName = str_replace("form-", "", $key);

                if ( $fullRightsArray["form-editresp-" . $formName] === "on" ) {
                    $value = "3";
                }

                $result .= "[" . $formName . "," . $value . "]";
            }
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["export-form-form1"=>"1", "export-form-form2"=>"1"] 
    private function convertExportRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            $result["export-form-" . $key] = $value;
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["form-form1"=>"1", "form-form2"=>"1"] 
    private function convertDataEntryRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            if ( $value == 3 ) {
                $result["form-" . $key]          = 2;
                $result["form-editresp-" . $key] = "on";
            } else {
                $result["form-" . $key] = $value;
            }
        }
        return $result;
    }


    public function checkProposedRights(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights();
    }

    private function checkProposedRights2(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights2();
    }

    public function isUserExpired($username, $project_id)
    {
        $sql    = "SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?";
        $result = $this->framework->query($sql, [ $username, $project_id ]);
        $row    = $result->fetch_assoc();
        return !is_null($row["expiration"]) && strtotime($row["expiration"]) < strtotime("today");
    }

    public function getRoleIdFromUniqueRoleName($uniqueRoleName)
    {
        $sql    = "SELECT role_id FROM redcap_user_roles WHERE unique_role_name = ?";
        $result = $this->framework->query($sql, [ $uniqueRoleName ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["role_id"]);
    }

    public function getUniqueRoleNameFromRoleId($role_id)
    {
        $sql    = "SELECT unique_role_name FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $role_id ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["unique_role_name"]);
    }

    public function getUsersInRole($project_id, $role_id)
    {
        if ( empty($role_id) ) {
            return [];
        }
        $sql    = "select * from redcap_user_rights where project_id = ? and role_id = ?";
        $result = $this->framework->query($sql, [ $project_id, $role_id ]);
        $users  = [];
        while ( $row = $result->fetch_assoc() ) {
            $users[] = $row["username"];
        }
        return $this->framework->escape($users);
    }

    public function getRoleLabel($role_id)
    {
        $sql    = "SELECT role_name FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $role_id ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["role_name"]);
    }

    public function getRoleRightsRaw($role_id)
    {
        $sql    = "SELECT * FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $role_id ]);
        return $this->framework->escape($result->fetch_assoc());
    }

    public function getRoleRights($role_id, $pid = null)
    {
        $project_id  = $pid ?? $this->getProjectId();
        $roles       = \UserRights::getRoles($project_id);
        $this_role   = $roles[$role_id];
        $role_rights = array_filter($this_role, function ($value, $key) {
            $off           = $value === "0";
            $null          = is_null($value);
            $unset         = isset($value) && is_null($value);
            $excluded      = in_array($key, [ "role_name", "unique_role_name", "project_id", "data_entry", "data_export_instruments" ], true);
            $also_excluded = !in_array($key, $this->getAllRights(), true);
            return !$off && !$unset && !$excluded && !$also_excluded && !$null;
        }, ARRAY_FILTER_USE_BOTH);
        return $role_rights;
    }

    public function getModuleDirectoryPrefix()
    {
        return strrev(preg_replace("/^.*v_/", "", strrev($this->framework->getModuleDirectoryName()), 1));
    }

    private function setUserSystemRole($username, $role_id)
    {
        $setting = $username . "-role";
        $this->setSystemSetting($setting, $role_id);
    }

    public function getUserSystemRole($username)
    {
        $setting = $username . "-role";
        $role    = $this->getSystemSetting($setting);
        if ( empty($role) || !$this->systemRoleExists($role) ) {
            $role = $this->defaultRoleId;
            $this->setUserSystemRole($username, $role);
        }
        return $role;
    }

    private function convertDataQualityResolution($rights)
    {
        // 0: no access
        // 1: view only
        // 4: open queries only
        // 2: respond only to opened queries
        // 5: open and respond to queries
        // 3: open, close, and respond to queries
        $value = $rights["data_quality_resolution"];
        if ( $value ) {
            $rights["data_quality_resolution_view"]    = intval($value) > 0 ? 1 : 0;
            $rights["data_quality_resolution_open"]    = in_array(intval($value), [ 3, 4, 5 ], true) ? 1 : 0;
            $rights["data_quality_resolution_respond"] = in_array(intval($value), [ 2, 3, 5 ], true) ? 1 : 0;
            $rights["data_quality_resolution_close"]   = intval($value) === 3 ? 1 : 0;
        }
        return $rights;
    }

    private function convertPermissions(string $permissions)
    {
        $rights = json_decode($permissions, true);
        $rights = $this->convertDataQualityResolution($rights);
        foreach ( $rights as $key => $value ) {
            if ( $value === "on" ) {
                $rights[$key] = 1;
            }
        }

        return json_encode($rights);
    }

    public function throttleSaveSystemRole(string $role_id, string $role_name, string $permissions)
    {
        if ( !$this->throttle("message = ?", 'role', 3, 1) ) {
            $this->saveSystemRole($role_id, $role_name, $permissions);
        } else {
            $this->log('saveSystemRole Throttled', [ "role_id" => $role_id, "role_name" => $role_name, "user" => $this->getUser()->getUsername() ]);
        }
    }

    /**
     * @param string $role_id
     * @param string $role_name
     * @param string $permissions - json-encoded string of user rights
     *
     * @return [type]
     */
    public function saveSystemRole(string $role_id, string $role_name, string $permissions)
    {
        try {
            $permissions_converted = $this->convertPermissions($permissions);
            $this->log("role", [
                "role_id"     => $role_id,
                "role_name"   => $role_name,
                "permissions" => $permissions_converted,
                "user"        => $this->getUser()->getUsername()
            ]);
        } catch ( \Throwable $e ) {
            $this->log('Error saving system role', [
                "error"       => $e->getMessage(),
                "role_id"     => $role_id,
                "role_name"   => $role_name,
                "permissions" => $permissions_converted,
                "user"        => $this->getUser()->getUsername()
            ]);
        }
    }

    public function throttleUpdateSystemRole(string $role_id, string $role_name, string $permissions)
    {
        if ( !$this->throttle("message = 'updated system role'", [], 3, 1) ) {
            $this->updateSystemRole($role_id, $role_name, $permissions);
        } else {
            $this->log('updateSystemRole Throttled', [ "role_id" => $role_id, "role_name" => $role_name, "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function updateSystemRole(string $role_id, string $role_name, string $permissions)
    {
        try {
            $permissions_converted = $this->convertPermissions($permissions);
            $sql1                  = "SELECT log_id WHERE message = 'role' AND role_id = ? AND project_id IS NULL";
            $result1               = $this->framework->queryLogs($sql1, [ $role_id ]);
            $log_id                = intval($result1->fetch_assoc()["log_id"]);
            if ( $log_id === 0 ) {
                throw new \Exception('No role found with the specified id');
            }
            $params = [ "role_name" => $role_name, "permissions" => $permissions_converted ];
            foreach ( $params as $name => $value ) {
                $sql = "UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?";
                $this->framework->query($sql, [ $value, $log_id, $name ]);
            }
            $this->log('updated system role', [ 'role_id' => $role_id, 'role_name' => $role_name, 'permissions' => $permissions_converted, "user" => $this->getUser()->getUsername() ]);
        } catch ( \Throwable $e ) {
            $this->log('Error updating system role', [
                'error'                 => $e->getMessage(),
                'role_id'               => $role_id,
                'role_name'             => $role_name,
                'permissions_orig'      => $permissions,
                'permissions_converted' => $permissions_converted,
                "user"                  => $this->getUser()->getUsername()
            ]);
        }
    }

    public function throttleDeleteSystemRole($role_id)
    {
        if ( !$this->throttle("message = 'deleted system role'", [], 2, 1) ) {
            $this->deleteSystemRole($role_id);
        } else {
            $this->log('deleteSystemRole Throttled', [ "role_id" => $role_id, "user" => $this->getUser()->getUsername() ]);
        }
    }

    private function deleteSystemRole($role_id)
    {
        try {
            $result = $this->removeLogs("message = 'role' AND role_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ", [ $role_id ]);
            $this->log('deleted system role', [ "user" => $this->getUser()->getUsername(), "role_id" => $role_id ]);
            return $result;
        } catch ( \Throwable $e ) {
            $this->log('Error deleting system role', [ "error" => $e->getMessage(), "user" => $this->getUser()->getUsername(), "role_id" => $role_id ]);
        }
    }

    public function getAllSystemRoles($parsePermissions = false)
    {
        $sql    = 'SELECT MAX(log_id) AS \'log_id\' WHERE message = \'role\' AND (project_id IS NULL OR project_id IS NOT NULL) GROUP BY role_id';
        $result = $this->framework->queryLogs($sql, []);
        $roles  = [];
        while ( $row = $result->fetch_assoc() ) {
            $logId             = $row['log_id'];
            $sql2              = 'SELECT role_id, role_name, permissions WHERE (project_id IS NULL OR project_id IS NOT NULL) AND log_id = ?';
            $result2           = $this->framework->queryLogs($sql2, [ $logId ]);
            $role              = $result2->fetch_assoc();
            $role['role_name'] = $this->framework->escape($role['role_name']);
            if ( $parsePermissions ) {
                $role['permissions']     = json_decode($role['permissions'], true);
                $roles[$role['role_id']] = $role;
            } else {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    private function setDefaultSystemRole()
    {
        $rights                   = $this->getDefaultRights();
        $rights['role_id']        = $this->defaultRoleId;
        $rights['role_name_edit'] = $this->defaultRoleName;
        $rights['dataViewing']    = '3';
        $rights['dataExport']     = '3';
        $this->saveSystemRole($this->defaultRoleId, $this->defaultRoleName, json_encode($rights));
        return $rights;
    }

    public function getSystemRoleRightsById($role_id)
    {
        if ( empty($role_id) ) {
            $role_id = $this->defaultRoleId;
        }
        $sql    = "SELECT role_id, role_name, permissions WHERE message = 'role' AND role_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ORDER BY log_id DESC LIMIT 1";
        $result = $this->framework->queryLogs($sql, [ $role_id ]);
        $rights = $result->fetch_assoc();
        if ( empty($rights) ) {
            $role_id2 = $this->defaultRoleId;
            $result2  = $this->framework->queryLogs($sql, [ $role_id2 ]);
            $rights   = $result2->fetch_assoc();

            if ( empty($rights) ) {
                $rights = $this->setDefaultSystemRole();
            }
        }
        return $rights;
    }

    public function systemRoleExists($role_id)
    {
        if ( empty($role_id) ) {
            return false;
        }
        foreach ( $this->getAllSystemRoles() as $role ) {
            if ( $role_id == $role["role_id"] ) {
                return true;
            }
        }
        return false;
    }

    public function generateNewRoleId()
    {
        $new_role_id = "role_" . substr(md5(uniqid()), 0, 13);

        if ( $this->systemRoleExists($new_role_id) ) {
            return $this->generateNewRoleId();
        } else {
            return $new_role_id;
        }
    }

    public function getDisplayTextForRights(bool $allRights = false)
    {
        global $lang;
        $rights = [
            'design'                         => $lang['rights_135'],
            'user_rights'                    => $lang['app_05'],
            'data_access_groups'             => $lang['global_22'],
            'dataViewing'                    => $lang['rights_373'],
            'dataExport'                     => $lang['rights_428'],
            'alerts'                         => $lang['global_154'],
            'reports'                        => $lang['rights_96'],
            'graphical'                      => $lang['report_builder_78'],
            'participants'                   => $lang['app_24'],
            'calendar'                       => $lang['app_08'] . " " . $lang['rights_357'],
            'data_import_tool'               => $lang['app_01'],
            'data_comparison_tool'           => $lang['app_02'],
            'data_logging'                   => $lang['app_07'],
            'file_repository'                => $lang['app_04'],
            'double_data'                    => $lang['rights_50'],
            'lock_record_customize'          => $lang['app_11'],
            'lock_record'                    => $lang['rights_97'],
            'randomization'                  => $lang['app_21'],
            'data_quality_design'            => $lang['dataqueries_38'],
            'data_quality_execute'           => $lang['dataqueries_39'],
            'data_quality_resolution'        => $lang['dataqueries_137'],
            'api'                            => $lang['setup_77'],
            'mobile_app'                     => $lang['global_118'],
            'realtime_webservice_mapping'    => "CDP/DDP" . " " . $lang['ws_19'],
            'realtime_webservice_adjudicate' => "CDP/DDP" . " " . $lang['ws_20'],
            'dts'                            => $lang['rights_132'],
            'mycap_participants'             => $lang['rights_437'],
            'record_create'                  => $lang['rights_99'],
            'record_rename'                  => $lang['rights_100'],
            'record_delete'                  => $lang['rights_101']

        ];
        if ( $allRights === true ) {
            $rights['random_setup']                    = $lang['app_21'] . " - " . $lang['rights_142'];
            $rights['random_dashboard']                = $lang['app_21'] . " - " . $lang['rights_143'];
            $rights['random_perform']                  = $lang['app_21'] . " - " . $lang['rights_144'];
            $rights['data_quality_resolution_view']    = 'Data Quality Resolution - View Queries';
            $rights['data_quality_resolution_open']    = 'Data Quality Resolution - Open Queries';
            $rights['data_quality_resolution_respond'] = 'Data Quality Resolution - Respond to Queries';
            $rights['data_quality_resolution_close']   = 'Data Quality Resolution - Close Queries';
            $rights['api_export']                      = $lang['rights_139'];
            $rights['api_import']                      = $lang['rights_314'];
            $rights['mobile_app_download_data']        = $lang['rights_306'];
            $rights['lock_record_multiform']           = $lang['rights_370'];
        }
        return $rights;
    }

    public function getDisplayTextForRight(string $right, string $key = "")
    {
        $rights = $this->getDisplayTextForRights(true);
        return $rights[$right] ?? $rights[$key] ?? $right;
    }

    public function convertRightName($rightName)
    {

        $conversions = [
            "stats_and_charts"           => "graphical",
            "manage_survey_participants" => "participants",
            "logging"                    => "data_logging",
            "data_quality_create"        => "data_quality_design",
            "lock_records_all_forms"     => "lock_record_multiform",
            "lock_records"               => "lock_record",
            "lock_records_customization" => "lock_record_customize"
        ];

        return $conversions[$rightName] ?? $rightName;
    }

    public function filterPermissions($rawArray)
    {
        $allRights                         = $this->getAllRights();
        $dataEntryString                   = $this->convertDataEntryRightsArrayToString($rawArray);
        $dataExportString                  = $this->convertExportRightsArrayToString($rawArray);
        $result                            = array_intersect_key($rawArray, $allRights);
        $result["data_export_instruments"] = $dataExportString;
        $result["data_entry"]              = $dataEntryString;
        return $result;
    }

    public function getDefaultRights()
    {
        $allRights = $this->getAllRights();
        if ( isset($allRights["data_export_tool"]) ) {
            $allRights["data_export_tool"] = 2;
        }
        if ( isset($allRights["data_import_tool"]) ) {
            $allRights["data_import_tool"] = 0;
        }
        if ( isset($allRights["data_comparison_tool"]) ) {
            $allRights["data_comparison_tool"] = 0;
        }
        if ( isset($allRights["data_logging"]) ) {
            $allRights["data_logging"] = 0;
        }
        if ( isset($allRights["file_repository"]) ) {
            $allRights["file_repository"] = 1;
        }
        if ( isset($allRights["double_data"]) ) {
            $allRights["double_data"] = 0;
        }
        if ( isset($allRights["user_rights"]) ) {
            $allRights["user_rights"] = 0;
        }
        if ( isset($allRights["lock_record"]) ) {
            $allRights["lock_record"] = 0;
        }
        if ( isset($allRights["lock_record_multiform"]) ) {
            $allRights["lock_record_multiform"] = 0;
        }
        if ( isset($allRights["lock_record_customize"]) ) {
            $allRights["lock_record_customize"] = 0;
        }
        if ( isset($allRights["data_access_groups"]) ) {
            $allRights["data_access_groups"] = 0;
        }
        if ( isset($allRights["graphical"]) ) {
            $allRights["graphical"] = 1;
        }
        if ( isset($allRights["reports"]) ) {
            $allRights["reports"] = 1;
        }
        if ( isset($allRights["design"]) ) {
            $allRights["design"] = 0;
        }
        if ( isset($allRights["alerts"]) ) {
            $allRights["alerts"] = 0;
        }
        if ( isset($allRights["dts"]) ) {
            $allRights["dts"] = 0;
        }
        if ( isset($allRights["calendar"]) ) {
            $allRights["calendar"] = 1;
        }
        if ( isset($allRights["record_create"]) ) {
            $allRights["record_create"] = 1;
        }
        if ( isset($allRights["record_rename"]) ) {
            $allRights["record_rename"] = 0;
        }
        if ( isset($allRights["record_delete"]) ) {
            $allRights["record_delete"] = 0;
        }
        if ( isset($allRights["participants"]) ) {
            $allRights["participants"] = 1;
        }
        if ( isset($allRights["data_quality_design"]) ) {
            $allRights["data_quality_design"] = 0;
        }
        if ( isset($allRights["data_quality_execute"]) ) {
            $allRights["data_quality_execute"] = 0;
        }
        if ( isset($allRights["data_quality_resolution"]) ) {
            $allRights["data_quality_resolution"] = 1;
        }
        if ( isset($allRights["api_export"]) ) {
            $allRights["api_export"] = 0;
        }
        if ( isset($allRights["api_import"]) ) {
            $allRights["api_import"] = 0;
        }
        if ( isset($allRights["mobile_app"]) ) {
            $allRights["mobile_app"] = 0;
        }
        if ( isset($allRights["mobile_app_download_data"]) ) {
            $allRights["mobile_app_download_data"] = 0;
        }
        if ( isset($allRights["random_setup"]) ) {
            $allRights["random_setup"] = 0;
        }
        if ( isset($allRights["random_dashboard"]) ) {
            $allRights["random_dashboard"] = 0;
        }
        if ( isset($allRights["random_perform"]) ) {
            $allRights["random_perform"] = 1;
        }
        if ( isset($allRights["realtime_webservice_mapping"]) ) {
            $allRights["realtime_webservice_mapping"] = 0;
        }
        if ( isset($allRights["realtime_webservice_adjudicate"]) ) {
            $allRights["realtime_webservice_adjudicate"] = 0;
        }
        if ( isset($allRights["mycap_participants"]) ) {
            $allRights["mycap_participants"] = 1;
        }
        return $allRights;
    }

    public function getCurrentRights(string $username, $project_id)
    {
        $result = $this->framework->query("SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?", [ $username, $project_id ]);
        $rights = $result->fetch_assoc();
        if ( !empty($rights["role_id"]) ) {
            $result2 = $this->framework->query("SELECT * FROM redcap_user_roles WHERE role_id = ?", [ $rights["role_id"] ]);
            $rights  = $result2->fetch_assoc();
        }
        unset($rights["api_token"], $rights["expiration"]);
        return $this->framework->escape($rights);
    }

    private function getAllCurrentRights($project_id)
    {
        $result = $this->framework->query('SELECT r.*,
        data_entry LIKE "%,3]%" data_entry3,
        data_entry LIKE "%,2]%" data_entry2,
        data_entry LIKE "%,1]%" data_entry1,
        data_export_instruments LIKE "%,3]%" data_export3,
        data_export_instruments LIKE "%,2]%" data_export2,
        data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights r WHERE project_id = ? AND role_id IS NULL', [ $project_id ]);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        $result2 = $this->framework->query('SELECT user.username, role.*,
        role.data_entry LIKE "%,3]%" data_entry3,
        role.data_entry LIKE "%,2]%" data_entry2,
        role.data_entry LIKE "%,1]%" data_entry1,
        role.data_export_instruments LIKE "%,3]%" data_export3,
        role.data_export_instruments LIKE "%,2]%" data_export2,
        role.data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights user
        LEFT JOIN redcap_user_roles role
        ON user.role_id = role.role_id
        WHERE user.project_id = ? AND user.role_id IS NOT NULL', [ $project_id ]);
        while ( $row = $result2->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        return $this->framework->escape($rights);
    }

    public function getUserRightsHolders($project_id)
    {
        try {
            $sql    = 'SELECT rights.username username,
            CONCAT(info.user_firstname, " ", info.user_lastname) fullname,
            info.user_email email
            from redcap_user_rights rights
            left join redcap_user_information info
            on rights.username = info.username
            where project_id = ?
            and user_rights = 1';
            $result = $this->framework->query($sql, [ $project_id ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $row;
            }
            return $this->framework->escape($users);
        } catch ( \Throwable $e ) {
            $this->log('Error fetching user rights holders', [ "error" => $e->getMessage() ]);
        }
    }

    public function updateLog($logId, array $params)
    {
        $sql = "UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?";
        foreach ( $params as $name => $value ) {
            try {
                $this->framework->query($sql, [ $value, $logId, $name ]);
            } catch ( \Throwable $e ) {
                $this->framework->log("Error updating log parameter", [ "error" => $e->getMessage() ]);
                return false;
            }
        }
        return true;
    }

    public function getRoleEditForm(array $rights, bool $newRole, $roleName = "", $roleId = "")
    {
        $roleEditForm = new RoleEditForm(
            $this,
            $rights,
            $newRole,
            $roleName,
            $roleId
        );
        $roleEditForm->getForm();
    }

}