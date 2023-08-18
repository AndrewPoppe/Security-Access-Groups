<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->isSuperUser() ) {
    http_response_code(401);
    exit;
}
?>
<script defer src="<?= $module->framework->getUrl('lib/DataTables/datatables.min.js') ?>"></script>
<link rel="stylesheet" href="<?= $module->framework->getUrl('lib/DataTables/datatables.min.css') ?>">
<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />

<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/fontawesome.min.js') ?>"></script>

<script defer src="<?= $module->framework->getUrl('lib/Clipboard/clipboard.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>


<!-- Modal -->
<div class="modal" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title">
                    <?= $module->framework->tt('module_name') . ' - ' . $module->framework->tt('status_ui_1') ?>
                </h5>
                <button type="button" class="btn-close btn-secondary align-self-center" data-bs-dismiss="modal"
                    data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= $module->framework->tt('status_ui_3') ?>
                        </h5>
                        <p>
                            <?= $module->framework->tt('status_ui_4') ?>
                        </p>
                        <p>
                            <?= $module->framework->tt('status_ui_5') ?>
                        </p>
                        <p>
                            <?= $module->framework->tt('status_ui_6') ?>
                        <ul>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-primary text-light p-1 font-weight-bold"><?= $module->framework->tt('status_ui_7') ?></span>:
                                <?= $module->framework->tt('status_ui_10') ?>
                            </li>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-warning text-body p-1 font-weight-bold"><?= $module->framework->tt('status_ui_8') ?></span>:
                                <?= $module->framework->tt('status_ui_11') ?>
                            </li>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-danger text-light p-1 font-weight-bold"><?= $module->framework->tt('status_ui_9') ?></span>:
                                <?= $module->framework->tt('status_ui_12') ?>
                            </li>
                        </ul>
                        </p>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-primary text-light font-weight-bold">
                        <?= $module->framework->tt('status_ui_7') ?>
                    </div>
                    <div class="card-body bg-light">
                        <p>
                            <?= $module->framework->tt('status_ui_13') ?>
                        </p>
                        <table class="table" aria-label="Options for sending alerts to users">
                            <thead>
                                <tr>
                                    <th>
                                        <?= $module->framework->tt('status_ui_14') ?>
                                    </th>
                                    <th>
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </th>
                                    <th>
                                        <?= $module->framework->tt('status_ui_16') ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_17') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_18') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_20') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-dark">
                                        <?= $module->framework->tt('status_ui_19') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_29') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_21') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_25') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_22') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_26') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_23') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-secondary">
                                        <?= $module->framework->tt('status_ui_30') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_27') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_24') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-dark">
                                        <?= $module->framework->tt('status_ui_19') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_28') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-warning text-body font-weight-bold">
                        <?= $module->framework->tt('status_ui_8') ?>
                    </div>
                    <div class="card-body bg-light">
                        <ul>
                            <li>
                                <?= $module->framework->tt('status_ui_31') ?>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_32') ?>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_33') ?>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_34') ?>
                            </li>
                        </ul>
                        <table class="table" aria-label="Options for sending alerts to user rights holders">
                            <thead>
                                <tr>
                                    <th>
                                        <?= $module->framework->tt('status_ui_14') ?>
                                    </th>
                                    <th>
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </th>
                                    <th>
                                        <?= $module->framework->tt('status_ui_16') ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_17') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_18') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_20') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-dark">
                                        <?= $module->framework->tt('status_ui_19') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_29') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_21') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_25') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_22') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_26') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_23') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-secondary">
                                        <?= $module->framework->tt('status_ui_30') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_27') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_35') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-danger">
                                        <?= $module->framework->tt('status_ui_15') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_36') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $module->framework->tt('status_ui_24') ?>
                                    </td>
                                    <td style="font-size: smaller;" class="text-dark">
                                        <?= $module->framework->tt('status_ui_19') ?>
                                    </td>
                                    <td>
                                        <?= $module->framework->tt('status_ui_28') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-danger text-light font-weight-bold">
                        <?= $module->framework->tt('status_ui_9') ?>
                    </div>
                    <div class="card-body bg-light">
                        <ul>
                            <li>
                                <?= $module->framework->tt('status_ui_37') ?>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_38') ?>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_39') ?>
                                <ul>
                                    <li>
                                        <?= $module->framework->tt('status_ui_40') ?>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_41') ?>
                                <ul>
                                    <li>
                                        <?= $module->framework->tt('status_ui_42') ?>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <?= $module->framework->tt('status_ui_43') ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="SAG-Container">
    <div class="projhdr">
        <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>
            <?= $module->framework->tt('module_name') ?>
        </span>
    </div>
    <div style="width:950px;max-width:950px;font-size:14px;" class="d-none d-md-block mt-3 mb-2">
        <?= $module->framework->tt('status_ui_44') ?>
    </div>
    <div class="clearfix">
        <div id="sub-nav" class="mr-4 mb-0 ml-0" style="max-width: 1100px;">
            <ul>
                <li class="active">
                    <a href="<?= $module->framework->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        <?= $module->framework->tt('status_ui_2') ?>
                    </a>
                </li>
                <li>
                    <a href="<?= $module->framework->getUrl('project-alert-log.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-envelopes-bulk"></i>
                        <?= $module->framework->tt('status_ui_45') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="mt-4">
        <?= $module->framework->tt('status_ui_46') ?>
        <ol>
            <li>
                <?= $module->framework->tt('status_ui_47') ?>
            </li>
            <li>
                <?= $module->framework->tt('status_ui_48') ?>
            </li>
        </ol>
        <?= $module->framework->tt('status_ui_49') ?>
        <i class="fa-solid fa-circle-info fa-lg align-self-center text-info" style="font-size: 16.25px;"></i>
        <?= $module->framework->tt('status_ui_50') ?>
    </div>
    <div id="containerCard" class="container mt-4 mx-0 card card-body bg-light" style="width: 1100px; display: none;">
        <div class="buttonContainer mb-2">
            <div class="btn-group">
                <button id="displayUsersButton" type="button" class="btn btn-xs btn-outline-secondary dropdown-toggle"
                    data-toggle="dropdown" aria-expanded="false">
                    <i class="fa-sharp fa-regular fa-eye"></i>
                    <?= $module->framework->tt('status_ui_51') ?>
                </button>
                <div class="dropdown-menu" id="userFilter">
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="expiredUsers" checked>
                        <label class="form-check-label" for="expiredUsers">
                            <?= $module->framework->tt('status_ui_52') ?>
                        </label>
                    </div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="nonExpiredUsers" checked>
                        <label class="form-check-label" for="nonExpiredUsers">
                            <?= $module->framework->tt('status_ui_53') ?>
                        </label>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="discrepantUsers" checked>
                        <label class="form-check-label" for="discrepantUsers">
                            <?= $module->framework->tt('status_ui_54') ?>
                        </label>
                    </div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="nonDiscrepantUsers" checked>
                        <label class="form-check-label" for="nonDiscrepantUsers">
                            <?= $module->framework->tt('status_ui_55') ?>
                        </label>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-primary action" onclick="sag_module.openEmailUsersModal();"
                disabled><i class="fa-sharp fa-regular fa-envelope"></i>
                <?= $module->framework->tt('status_ui_56') ?>
            </button>
            <button type="button" class="btn btn-xs btn-warning action"
                onclick="sag_module.openEmailUserRightsHoldersModal();" disabled><i
                    class="fa-kit fa-sharp-regular-envelope-circle-exclamation"></i>
                <?= $module->framework->tt('status_ui_57') ?>
            </button>
            <button type="button" class="btn btn-xs btn-danger action" onclick="sag_module.openExpireUsersModal();"
                disabled><i class="fa-regular fa-user-xmark fa-fw"></i>
                <?= $module->framework->tt('status_ui_58') ?>
            </button>
            <div class="btn-group" role="group">
                <i class="fa-solid fa-circle-info fa-lg align-self-center text-info infoButton" style="cursor:pointer;"
                    onclick="$('#infoModal').modal('show');">
                </i>
            </div>
        </div>
        <div id="table-container" class="container mx-0 px-0" style="background-color: #fafafa;">
            <table aria-label="discrepancy table" id="discrepancy-table" class="discrepancy-table bg-white">
                <thead class="text-center" style="background-color:#ececec">
                    <tr>
                        <th style="vertical-align: middle !important;"><input style="display:block; margin: 0 auto;"
                                type="checkbox" onchange="sag_module.handleCheckboxes(this);"></input>
                        </th>
                        <th>
                            <?= $module->framework->tt('status_ui_59') ?>
                        </th>
                        <th>
                            <?= $module->framework->tt('status_ui_60') ?>
                        </th>
                        <th>
                            <?= $module->framework->tt('status_ui_61') ?>
                        </th>
                        <th class="dt-head-center">
                            <?= $module->framework->tt('status_ui_62') ?>
                        </th>
                        <th class="dt-head-center">
                            <?= $module->framework->tt('status_ui_63') ?>
                        </th>
                        <th class="dt-head-center">
                            <?= $module->framework->tt('status_ui_64') ?>
                        </th>
                        <th class="dt-head-center">
                            <?= $module->framework->tt('status_ui_65') ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$project_id = $module->framework->getProjectId();

$userSql       = 'SELECT COUNT(username) FROM redcap_user_rights WHERE project_id = ?';
$usersResult   = $module->framework->query($userSql, [ $project_id ]);
$usersCount    = intval($usersResult->fetch_assoc()["COUNT(username)"]);
$userThreshold = 5000;
if ( $usersCount <= $userThreshold ) {
    $sagProject = new SAGProject($module, $project_id);
    $userData   = $sagProject->getUsersWithBadRights();
    $config     = json_encode([
        'data' => $module->framework->escape($userData)
    ]);
} else {
    $config = '';
}
echo $module->framework->initializeJavascriptModuleObject();
$module->framework->tt_transferToJavascriptModuleObject();
$js = file_get_contents($module->framework->getSafePath('js/project-status.js'));
$js = str_replace('{{CONFIG}}', $config, $js);
$js = str_replace('{{USER_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-email-body-template') ?? "", $js);
$js = str_replace('{{USER_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-email-subject-template') ?? "", $js);
$js = str_replace('{{USER_REMINDER_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-reminder-email-body-template') ?? "", $js);
$js = str_replace('{{USER_REMINDER_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-reminder-email-subject-template') ?? "", $js);
$js = str_replace('{{USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-rights-holders-email-body-template') ?? "", $js);
$js = str_replace('{{USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-rights-holders-email-subject-template') ?? "", $js);
$js = str_replace('{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-rights-holders-reminder-email-body-template') ?? "", $js);
$js = str_replace('{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-rights-holders-reminder-email-subject-template') ?? "", $js);
$js = str_replace('{{USER_EXPIRATION_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-expiration-email-body-template') ?? "", $js);
$js = str_replace('{{USER_EXPIRATION_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-expiration-email-subject-template') ?? "", $js);
$js = str_replace('{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE}}', $module->getSystemSetting('user-expiration-user-rights-holders-email-body-template') ?? "", $js);
$js = str_replace('{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE}}', $module->getSystemSetting('user-expiration-user-rights-holders-email-subject-template') ?? "", $js);
$js = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);
echo '<script type="text/javascript">' . $js . '</script>';

$Alerts = new Alerts($module);
$Alerts->getUserEmailModal();
$Alerts->getUserRightsHoldersEmailModal();
$Alerts->getUserExpirationModal();
$Alerts->getEmailPreviewModal();
?>