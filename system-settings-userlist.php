<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    exit();
}

?>
<link href="<?= $module->framework->getUrl('lib/DataTables/datatables.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/DataTables/datatables.min.js') ?>"></script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

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
            <li class="active">
                <a href="<?= $module->framework->getUrl('system-settings-userlist.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li>
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
    $sags    = $module->getAllSags();
    $sagRows = '';
    foreach ( $sags as $index => $sag ) {
        $sagId   = \REDCap::escapeHtml($sag['sag_id']);
        $sagRows .= '<tr><td>example_user_' . (intval($index) + 1) . '</td><td>' . $sagId . '</td></tr>';
    }
    $userListHtml = file_get_contents($module->framework->getSafePath('html/system-settings-userlist.html'));
    $userListHtml = str_replace('{{SAG_ROWS}}', $sagRows, $userListHtml);
    echo $userListHtml;
    echo $module->framework->initializeJavascriptModuleObject();

    $basicSags = [];
    foreach ( $sags as $sag ) {
        $basicSags[$sag["sag_id"]] = $sag["sag_name"];
    }
    $basicSagsJson = json_encode($basicSags);
    $js            = file_get_contents($module->framework->getSafePath('js/system-settings-userlist.js'));
    $js            = str_replace('{{SAGS_JSON}}', $basicSagsJson, $js);
    $js            = str_replace('{{USERS_URL}}', $module->framework->getUrl('ajax/users.php'), $js);
    $js            = str_replace('{{DEFAULT_SAG_ID}}', $module->defaultSagId, $js);
    $js            = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);

    echo '<script type="text/javascript">' . $js . '</script>';
    ?>
</div> <!-- End SAG_Container -->