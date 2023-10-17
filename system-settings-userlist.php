<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->isSuperUser() ) {
    exit();
}

?>
<link href="<?= $module->framework->getUrl('lib/DataTables/datatables.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/DataTables/datatables.min.js') ?>"></script>

<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/fontawesome.min.js') ?>"></script>

<link href="<?= $module->framework->getUrl('lib/Select2/select2.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/Select2/select2.min.js') ?>"></script>
<script src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>

<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('css/SecurityAccessGroups.php') ?>' />


<h4 style='color:#900; margin: 0 0 10px;'>
    <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>
        <?= $module->framework->tt('module_name') ?>
    </span>
</h4>
<p style='max-width:1000px; margin-bottom:0;font-size:14px;'>
    <?= $module->framework->tt('status_ui_44') ?>
</p>
<div class="SAG_Container" style="min-width: 900px;">
    <div id="sub-nav" class="mr-4 mb-0 ml-0" style="min-width: 900px;">
        <ul>
            <li class="active">
                <a href="<?= $module->framework->getUrl('system-settings-userlist.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    <?= $module->framework->tt('cc_user_1') ?>
                </a>
            </li>
            <li>
                <a href="<?= $module->framework->getUrl('system-settings-sags.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-user-tag"></i>
                    <?= $module->framework->tt('cc_user_2') ?>
                </a>
            </li>
            <li>
                <a href="<?= $module->framework->getUrl('system-reports.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-memo"></i>
                    <?= $module->framework->tt('cc_user_3') ?>
                </a>
            </li>
        </ul>
    </div>
    <div class='clear'></div>

    <?php
    $sags      = $module->getAllSags();
    $sagRows   = '';
    $basicSags = [];
    foreach ( $sags as $index => $sag ) {
        $sagId             = \REDCap::escapeHtml($sag->sagId);
        $sagRows .= '<tr><td>example_user_' . (intval($index) + 1) . '</td><td>' . $sagId . '</td></tr>';
        $basicSags[$sagId] = $sag->sagName;
    }
    $userListHtml = file_get_contents($module->framework->getSafePath('html/system-settings-userlist.html'));
    $userListHtml = str_replace('{{SAG_ROWS}}', $sagRows, $userListHtml);
    $userListHtml = $module->replaceAllTranslations($userListHtml);
    echo $userListHtml;
    echo $module->framework->initializeJavascriptModuleObject();
    $module->framework->tt_transferToJavascriptModuleObject();

    $basicSagsJson = json_encode($basicSags);
    $js            = file_get_contents($module->framework->getSafePath('js/system-settings-userlist.js'));
    $js            = str_replace('{{SAGS_JSON}}', $basicSagsJson, $js);
    $js            = str_replace('{{DEFAULT_SAG_ID}}', $module->defaultSagId, $js);
    $js            = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);

    echo '<script type="text/javascript">' . $js . '</script>';
    ?>
</div> <!-- End SAG_Container -->