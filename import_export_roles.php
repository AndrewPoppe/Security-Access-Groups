<?php

namespace YaleREDCap\SystemUserRights;

$scriptPath = $module->getSafePath('UserRights/import_export_roles.php', APP_PATH_DOCROOT);
//$scriptPath .= $_GET['action'] == 'uploadMapping' ? "&action=uploadMapping" : "";

$module->log('thing', ['url' => $scriptPath]);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content'])) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if (isset($_POST['csv_content']) && $_POST['csv_content'] != '') {
    $csv_content = filter_input(INPUT_POST, 'csv_content');
    $data = csvToArray(removeBOMfromUTF8($csv_content));
    echo json_encode($data);
    exit;
}





$count = 0;
$errors = array();
$csv_content = $preview = "";
$commit = false;
if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name'])) {
    $csv_content = file_get_contents($_FILES['file']['tmp_name']);
} elseif (isset($_POST['csv_content']) && $_POST['csv_content'] != '') {
    if (!isset($_POST['notify_email']) || $_POST['notify_email'] == '') {
        $_POST['notify_email'] = 0;
    }
    $csv_content = $_POST['csv_content'];
    $commit = true;
}

if ($csv_content != "") {
    $data = csvToArray(removeBOMfromUTF8($csv_content));
    //echo json_encode($data);
    require $scriptPath;
    exit;
}
