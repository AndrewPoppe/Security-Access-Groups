<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/import_export_users.php', APP_PATH_DOCROOT);

if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content']) ) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {
    $csv_content = filter_input(INPUT_POST, 'csv_content');
    $data        = csvToArray(removeBOMfromUTF8($csv_content));
    $bad_rights  = [];
    foreach ( $data as $key => $this_user ) {
        $username = $this_user['username'];
        if ( isset($this_user['forms']) && $this_user['forms'] != '' ) {
            foreach ( explode(",", $this_user['forms']) as $this_pair ) {
                list( $this_form, $this_right )  = explode(":", $this_pair, 2);
                $this_user['form-' . $this_form] = $this_right;
            }
            unset($this_user['forms']);
        }
        if ( isset($this_user['forms_export']) && $this_user['forms_export'] != '' ) {
            foreach ( explode(",", $this_user['forms_export']) as $this_pair ) {
                list( $this_form, $this_right )         = explode(":", $this_pair, 2);
                $this_user['export-form-' . $this_form] = $this_right;
            }
            unset($this_user['forms_export']);
        }
        $this_user = array_filter($this_user, function ($value, $key) {
            return ($value != 0);
        }, ARRAY_FILTER_USE_BOTH);

        $acceptable_rights = $module->getAcceptableRights($username);
        $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $this_user);

        // We ignore expired users, unless the request unexpires them
        $userExpired         = $module->isUserExpired($username, $module->framework->getProjectId());
        $requestedExpiration = $this_user["expiration"];
        $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
        if ( $userExpired && !$requestedUnexpired ) {
            $ignore = true;
        }

        if ( !empty($these_bad_rights) && !$ignore ) {
            $bad_rights[$username] = $these_bad_rights;
        }
    }

    if ( empty($bad_rights) ) {
        require $scriptPath;
    } else {
        $_SESSION['SUR_imported']   = 'users';
        $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
        redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
    }
}