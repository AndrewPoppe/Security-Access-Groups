<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/import_export_roles.php', APP_PATH_DOCROOT);

if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content']) ) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {

    $csv_content = filter_input(INPUT_POST, 'csv_content');
    $data        = csvToArray(removeBOMfromUTF8($csv_content));

    if ( $_GET['action'] == 'uploadMapping' ) {
        $bad_rights = [];
        foreach ( $data as $key => $this_assignment ) {
            $username       = $this_assignment["username"];
            $uniqueRoleName = $this_assignment["unique_role_name"];
            if ( $uniqueRoleName == '' ) {
                continue;
            }
            $role_id           = $module->getRoleIdFromUniqueRoleName($uniqueRoleName);
            $role_name         = \ExternalModules\ExternalModules::getRoleName($module->getProjectId(), $role_id);
            $role_rights       = $module->getRoleRights($role_id);
            $acceptable_rights = $module->getAcceptableRights($username);
            $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $role_rights);
            if ( !empty($these_bad_rights) ) {
                $bad_rights[$role_name] = $these_bad_rights;
            }
        }
        if ( empty($bad_rights) ) {
            require $scriptPath;
        } else {
            $_SESSION['SUR_imported']   = 'roleassignments';
            $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    } else {


        $bad_rights = [];
        foreach ( $data as $key => $this_role ) {
            $role_label  = $this_role["role_label"];
            $role_id     = $module->getRoleIdFromUniqueRoleName($this_role["unique_role_name"]);
            $usersInRole = $module->getUsersInRole($module->getProjectId(), $role_id);
            if ( isset($this_role['forms']) && $this_role['forms'] != '' ) {
                foreach ( explode(",", $this_role['forms']) as $this_pair ) {
                    list( $this_form, $this_right )  = explode(":", $this_pair, 2);
                    $this_role['form-' . $this_form] = $this_right;
                }
                unset($this_role['forms']);
            }
            if ( isset($this_role['forms_export']) && $this_role['forms_export'] != '' ) {
                foreach ( explode(",", $this_role['forms_export']) as $this_pair ) {
                    list( $this_form, $this_right )         = explode(":", $this_pair, 2);
                    $this_role['export-form-' . $this_form] = $this_right;
                }
                unset($this_role['forms_export']);
            }
            $this_role = array_filter($this_role, function ($value, $key) {
                return ($value != 0);
            }, ARRAY_FILTER_USE_BOTH);

            $these_bad_rights = [];
            foreach ( $usersInRole as $username ) {
                $acceptable_rights = $module->getAcceptableRights($username);
                $user_bad_rights   = $module->checkProposedRights($acceptable_rights, $this_role);
                if ( !empty($user_bad_rights) ) {
                    $these_bad_rights[$username] = $user_bad_rights;
                }
            }
            if ( !empty($these_bad_rights) ) {
                $bad_rights[$role_label] = $these_bad_rights;
            }
        }

        if ( empty($bad_rights) ) {
            require $scriptPath;
        } else {
            $_SESSION['SUR_imported']   = 'roles';
            $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    }
}