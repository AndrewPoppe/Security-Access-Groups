<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->isSuperUser() ) {
    exit();
}
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

?>
<link href="<?= $module->framework->getUrl('lib/DataTables/datatables.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/DataTables/datatables.min.js') ?>"></script>

<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/fontawesome.min.js') ?>"></script>

<link href="<?= $module->framework->getUrl('lib/Select2/select2.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/Select2/select2.min.js') ?>"></script>
<script src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>

<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />


<h4 style='color:#900; margin: 0 0 10px;'>
    <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>Security Access Groups</span>
</h4>
<p style='max-width:1000px; margin-bottom:0;font-size:14px;'>Security Access Groups (SAGs) are used to restrict which
    user rights a REDCap user can be granted in a project. SAGs do not define the rights a user will have in a given
    project; rather, they define the set of allowable rights the user is able to be granted. If a user is assigned to a
    SAG that does not allow the Project Design right, then that user cannot have that user right granted in a project.
    The Security Access Groups module must be enabled in a project for the SAG to have an effect.</p>
<div class="SAG_Container" style="min-width: 900px;">
    <div id="sub-nav" class="mr-4 mb-0 ml-0" style="min-width: 900px;">
        <ul>
            <li>
                <a href="<?= $module->framework->getUrl('system-settings-userlist.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li class="active">
                <a href="<?= $module->framework->getUrl('system-settings-sags.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-user-tag"></i>
                    Security Access Groups
                </a>
            </li>
            <li>
                <a href="<?= $module->framework->getUrl('system-reports.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-memo"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
    <div class='clear'></div>

    <?php
    $allDisplayTextForUserRights = $module->framework->escape($module->getDisplayTextForRights(true));
    $headers                     = '';
    foreach ( $allDisplayTextForUserRights as $dataKey => $value ) {
        $headers .= "<th data-key='" . $dataKey . "' class='dt-head-center'>" . $value . "</th>";
    }

    $sagsHtml = file_get_contents($module->framework->getSafePath('html/system-settings-sags.html'));
    $sagsHtml = str_replace('{{HEADERS}}', $headers, $sagsHtml);
    echo $sagsHtml;
    echo $module->framework->initializeJavascriptModuleObject();

    $js = file_get_contents($module->framework->getSafePath('js/system-settings-sags.js'));
    $js = str_replace('{{USER_RIGHTS_ERROR_MESSAGE}}', $lang['rights_358'], $js);
    $js = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);

    echo '<script type="text/javascript">', $js, '</script>';
    ?>
</div> <!-- End SAG_Container -->