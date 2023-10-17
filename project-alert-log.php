<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->isSuperUser() ) {
    http_response_code(401);
    exit;
}

?>

<link rel="stylesheet" href="<?= $module->framework->getUrl('lib/Flatpickr/flatpickr.min.css') ?>">
<script defer src="<?= $module->framework->getUrl('lib/Flatpickr/flatpickr.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/duotone.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/fontawesome.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('css/SecurityAccessGroups.php') ?>' />


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
                <li>
                    <a href="<?= $module->framework->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        <?= $module->framework->tt('status_ui_2') ?>
                    </a>
                </li>
                <li class="active">
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
        <?= $module->framework->tt('alerts_1') ?>
    </div>
    <div class="alertLogWrapper mt-4 mr-3 card card-body bg-light" style="width: 1100px; display: none;">
        <table aria-label="alert log table" id="alertLogTable" class="border sagTable" style="width:100%;">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="8" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="row px-3 pt-2">
                                        <div class="col pl-0 pr-1"><input id="mindatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="<?= $module->framework->tt('alerts_2') ?>">
                                        </div>
                                        <div class="col p-0">
                                            <input id="maxdatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="<?= $module->framework->tt('alerts_3') ?>">
                                        </div>
                                    </div>
                                    <div class="row pl-4 py-2">
                                        <div class="col">
                                            <button class="btn btn-xs btn-success"
                                                onclick="sag_module.showPastAlerts()">
                                                <?= $module->framework->tt('alerts_4') ?>
                                            </button>
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-xs btn-primaryrc"
                                                onclick="sag_module.showFutureAlerts()">
                                                <?= $module->framework->tt('alerts_5') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4 px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select class="" id="alertTypeSelect" multiple="multiple">
                                            <option value="users">
                                                <?= $module->framework->tt('user') ?>
                                            </option>
                                            <option value="userRightsHolders">
                                                <?= $module->framework->tt('alerts_6') ?>
                                            </option>
                                            <option value="expiration">
                                                <?= $module->framework->tt('status_ui_62') ?>
                                            </option>
                                        </select>
                                    </div>
                                    <div class="row ">
                                        <select class="form-control" id="notificationTypeSelect" multiple="multiple">
                                            <option value="false">
                                                <?= $module->framework->tt('alerts_7') ?>
                                            </option>
                                            <option value="true">
                                                <?= $module->framework->tt('status_ui_24') ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4 px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select class="form-control" id="usersSelect" multiple="multiple">
                                        </select>
                                    </div>
                                    <div class="row">
                                        <select class="form-control" id="recipientSelect" multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_8') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_9') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_10') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_24') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_11') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_12') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_13') ?>
                    </th>
                    <th scope="col">
                        <?= $module->framework->tt('alerts_14') ?>
                    </th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class="modal fade" id="alertPreviewModal" tabindex="-1" aria-labelledby="alertPreviewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="alertPreviewModalLabel">
                    <?= $module->framework->tt('alerts_15') ?>
                </h4>
                <button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ...
            </div>
        </div>
    </div>
</div>
<?php
echo $module->framework->initializeJavascriptModuleObject();
$module->framework->tt_transferToJavascriptModuleObject();
$js = file_get_contents($module->framework->getSafePath('js/project-alert-log.js'));
$js = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);
echo '<script type="text/javascript">' . $js . '</script>';
?>