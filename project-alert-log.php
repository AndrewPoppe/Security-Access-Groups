<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.js"></script>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</noscript>
<script defer src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/duotone.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />


<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>Security Access Groups</span>
    </div>
    <div style="width:950px;max-width:950px;font-size:14px;" class="d-none d-md-block mt-3 mb-2">
        Security Access Groups (SAGs) are used to restrict which user rights a REDCap user can be granted in a project.
        SAGs do not define the rights a user will have in a given project; rather, they define the set of allowable
        rights the user is able to be granted. SAGs are defined at the system level and are used in any project that has
        this module enabled.
    </div>
    <div class="clearfix">
        <div id="sub-nav" class="mr-4 mb-0 ml-0" style="max-width: 1100px;">
            <ul>
                <li>
                    <a href="<?= $module->framework->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li class="active">
                    <a href="<?= $module->framework->getUrl('project-alert-log.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-envelopes-bulk"></i>
                        Alert Log
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="mt-4">
        This page shows all alerts sent by the SAG module as well as all currently scheduled reminders.
    </div>
    <div class="alertLogWrapper mt-4 mr-3 card card-body bg-light" style="width: 1100px; display: none;">
        <table aria-label="alert log table" id="alertLogTable" class="border" style="width:100%;">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="8" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="row px-3 pt-2">
                                        <div class="col pl-0 pr-1"><input id="mindatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="Begin time">
                                        </div>
                                        <div class="col p-0">
                                            <input id="maxdatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="End time">
                                        </div>
                                    </div>
                                    <div class="row pl-4 py-2">
                                        <div class="col">
                                            <button class="btn btn-xs btn-success"
                                                onclick="module.showPastAlerts()">View past
                                                alerts</button>
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-xs btn-primaryrc"
                                                onclick="module.showFutureAlerts()">View
                                                future alerts</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4 px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select class="" id="alertTypeSelect" multiple="multiple">
                                            <option value="users">User</option>
                                            <option value="userRightsHolders">User Rights Holder</option>
                                            <option value="expiration">Expiration</option>
                                        </select>
                                    </div>
                                    <div class="row ">
                                        <select class="form-control" id="notificationTypeSelect" multiple="multiple">
                                            <option value="false">Original alert</option>
                                            <option value="true">Reminder</option>
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
                    <th scope="col">Alert ID</th>
                    <th scope="col">Send Time</th>
                    <th scope="col">Alert Type</th>
                    <th scope="col">Reminder</th>
                    <th scope="col">View Alert</th>
                    <th scope="col">User(s)</th>
                    <th scope="col">Recipient</th>
                    <th scope="col">Status</th>
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
                <h4 class="modal-title" id="alertPreviewModalLabel">Alert Preview</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
$js = file_get_contents($module->framework->getSafePath('js/project-alert-log.js'));
$js = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);
echo '<script type="text/javascript">' . $js . '</script>';
?>