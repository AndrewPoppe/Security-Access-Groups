<?php

namespace YaleREDCap\SystemUserRights;

$scriptPath = $module->getSafePath('UserRights/import_export_users.php', APP_PATH_DOCROOT);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content'])) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if (isset($_POST['csv_content']) && $_POST['csv_content'] != '') {
    $csv_content = filter_input(INPUT_POST, 'csv_content');
    $data = csvToArray(removeBOMfromUTF8($csv_content));
    foreach ($data as $key => $this_user) {
        if (isset($this_user['forms']) && $this_user['forms'] != '') {
            foreach (explode(",", $this_user['forms']) as $this_pair) {
                list($this_form, $this_right) = explode(":", $this_pair, 2);
                $data[$key]['form-' . $this_form] = $this_right;
            }
            unset($data[$key]['forms']);
        }
        if (isset($this_user['forms_export']) && $this_user['forms_export'] != '') {
            foreach (explode(",", $this_user['forms_export']) as $this_pair) {
                list($this_form, $this_right) = explode(":", $this_pair, 2);
                $data[$key]['export-form-' . $this_form] = $this_right;
            }
            unset($data[$key]['forms_export']);
        }
    }
    var_dump($data);
    var_dump(\UserRights::getPrivileges($module->getProjectId(), "carol"));
    var_dump($module->getAllRights());
    exit;
}
