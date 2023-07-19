<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the SAG
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $data    = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $sagId   = $data['sag_id'] ?? $module->generateNewSagId();
    $sagName = $data['sag_name_edit'];
    $newSag  = $data['newSag'];
    if ( $newSag == 1 ) {
        $module->throttleSaveSag($sagId, $sagName, json_encode($data));
    } else {
        $module->throttleUpdateSag($sagId, $sagName, json_encode($data));
    }
    echo $sagId;
    exit;
}

// We're asking for the add/edit SAG form contents
if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
    $newSag  = filter_input(INPUT_GET, 'newSag', FILTER_VALIDATE_BOOLEAN);
    $sagId   = filter_input(INPUT_GET, 'sag_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $sagName = filter_input(INPUT_GET, 'sag_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ( $newSag === true ) {
        $rights = $module->getDefaultRights();
        $newSag = true;
    } else {
        $thisRole = $module->getSagRightsById($sagId);
        $rights   = json_decode($thisRole['permissions'], true);
        $sagName  = $thisRole['sag_name'];
        $newSag   = false;
    }
    $SagEditForm = new SagEditForm(
        $module,
        $rights,
        $newSag,
        $sagName,
        $sagId
    );
    $SagEditForm->getForm();
    exit;
}