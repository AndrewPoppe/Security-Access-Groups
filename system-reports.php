<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->isSuperUser() ) {
    exit();
}
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

?>
<link href="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.5/b-2.4.1/b-html5-2.4.1/datatables.min.css"
    rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.5/b-2.4.1/b-html5-2.4.1/datatables.min.js"></script>

<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/light.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-light.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/fontawesome/js/fontawesome.min.js') ?>"></script>

<link href="<?= $module->framework->getUrl('lib/Select2/select2.min.css') ?>" rel="stylesheet" />
<script defer src="<?= $module->framework->getUrl('lib/Select2/select2.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>
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
            <li>
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
            <li class="active">
                <a href="<?= $module->framework->getUrl('system-reports.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-memo"></i>
                    <?= $module->framework->tt('cc_user_3') ?>
                </a>
            </li>
        </ul>
    </div>
    <div class="clear"></div>



    <div style='margin-top: 20px; margin-bottom: 0px; max-width:1000px;font-size:14px;'>
        <?= $module->framework->tt('cc_reports_1') ?>
    </div>
    <button class="btn btn-link btn-xs p-0" data-target=".collapse-group-1" aria-controls="helpLinkContainer help"
        data-toggle="collapse"
        onclick="this.textContent=this.textContent.trim() === sag_module.tt('cc_reports_2') ? sag_module.tt('cc_reports_3') : sag_module.tt('cc_reports_2');">
        <?= $module->framework->tt('cc_reports_2') ?>
    </button>
    <div id="help" class="collapse collapse-group-1 mb-2">
        <strong><i class="fa-light fa-users fa-fw text-danger"></i>
            <?= $module->framework->tt('cc_reports_4') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_5') ?>
        <br>
        <br>
        <strong><i class="fa-solid fa-users fa-fw text-danger"></i>
            <?= $module->framework->tt('cc_reports_6') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_7') ?>
        <br>
        <br>
        <strong><i class="fa-sharp fa-light fa-rectangle-history-circle-user fa-fw text-successrc">
            </i>
            <?= $module->framework->tt('cc_reports_8') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_9') ?>
        <br>
        <br>
        <strong><i class="fa-sharp fa-solid fa-rectangle-history-circle-user fa-fw text-successrc"></i>
            <?= $module->framework->tt('cc_reports_10') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_11') ?>
        <br>
        <br>
        <strong><i class="fa-sharp fa-light fa-rectangle-list fa-fw text-info"></i>
            <?= $module->framework->tt('cc_reports_12') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_13') ?>
        <br>
        <br>
        <strong><i class="fa-sharp fa-solid fa-rectangle-list fa-fw text-info"></i>
            <?= $module->framework->tt('cc_reports_14') ?>
        </strong>
        <br>
        <?= $module->framework->tt('cc_reports_15') ?>
    </div>

    <!-- Controls Container -->
    <div class="dropdown mt-2">
        <button type="button" class="btn btn-primary btn-xs border dropdown-toggle mr-2" data-toggle="dropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-sharp fa-file-excel"></i>
            <span>
                <?= $module->framework->tt('cc_reports_16') ?>
            </span>
            <span class="sr-only">
                <?= $module->framework->tt('cc_user_7') ?>
            </span>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" onclick="sag_module.showUserTable(false);"><i
                        class="fa-light fa-users fa-fw text-danger"></i>
                    <?= $module->framework->tt('cc_reports_4') ?>
                </a></li>
            <li><a class="dropdown-item" onclick="sag_module.showUserTable(true);"><i
                        class="fa-solid fa-users fa-fw text-danger"></i>
                    <?= $module->framework->tt('cc_reports_6') ?>
                </a></li>
            <li><a class="dropdown-item" onclick="sag_module.showProjectTable(false);"><i
                        class="fa-sharp fa-light fa-rectangle-history-circle-user fa-fw text-successrc"></i>
                    <?= $module->framework->tt('cc_reports_8') ?>
                </a></li>
            <li><a class="dropdown-item" onclick="sag_module.showProjectTable(true);"><i
                        class="fa-sharp fa-solid fa-rectangle-history-circle-user fa-fw text-successrc"></i>
                    <?= $module->framework->tt('cc_reports_10') ?>
                </a></li>
            <li><a class="dropdown-item" onclick="sag_module.showUserAndProjectTable(false);"><i
                        class="fa-sharp fa-light fa-rectangle-list fa-fw text-info"></i>
                    <?= $module->framework->tt('cc_reports_12') ?>
                </a></li>
            <li><a class="dropdown-item" onclick="sag_module.showUserAndProjectTable(true);"><i
                        class="fa-sharp fa-solid fa-rectangle-list fa-fw text-info"></i>
                    <?= $module->framework->tt('cc_reports_14') ?>
                </a></li>
        </ul>
    </div>
    <!-- SAG Table -->
    <div class=" clear">
    </div>
    <div id="projectTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
        <h5 id="projectTableTitle"></h5>
        <table aria-label="Projects Table" id="SAG-System-Table" class="sagTable projectTable cell-border"
            style="width: 100%">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control projectTableSelect tableSelect"
                                            id="usersSelectProject" multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control projectTableSelect tableSelect"
                                            id="sagsSelectProject" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control projectTableSelect tableSelect"
                                            id="projectsSelectProject" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control projectTableSelect tableSelect"
                                            id="rightsSelectProject" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <!--0-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_17') ?>
                    </th>
                    <!--1-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_18') ?>
                    </th>
                    <!--2-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_19') ?>
                    </th>
                    <!--3-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_20') ?>
                    </th>
                    <!--4-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_21') ?>
                    </th>
                    <!--5-->
                    <th scope="col">PID for CSV</th>
                    <!--6-->
                    <th scope="col">Project Title for CSV</th>
                    <!--7-->
                    <th scope="col">Project Status for CSV</th>
                    <!--8-->
                    <th scope="col">Usernames for CSV</th>
                    <!--9-->
                    <th scope="col">SAG IDs for CSV</th>
                    <!--10-->
                    <th scope="col">SAG Names for CSV</th>
                    <!--11-->
                    <th scope="col">Rights for CSV</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="userTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
        <h5 id="userTableTitle"></h5>
        <table aria-label="Users Table" id="SAG-System-Table" class="sagTable userTable cell-border"
            style="width: 100%">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control userTableSelect tableSelect"
                                            id="projectsSelectUser" multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control userTableSelect tableSelect"
                                            id="sagsSelectUser" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control userTableSelect tableSelect"
                                            id="usersSelectUser" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control userTableSelect tableSelect"
                                            id="rightsSelectUser" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <!--0-->
                    <th scope="col">
                        <?= $module->framework->tt('user') ?>
                    </th>
                    <!--1-->
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_60') ?>
                    </th>
                    <!--2-->
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_61') ?>
                    </th>
                    <!--3-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_20') ?>
                    </th>
                    <!--4-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_22') ?>
                    </th>
                    <!--5-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_23') ?>
                    </th>
                    <!--6-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_21') ?>
                    </th>
                    <!--7-->
                    <th scope="col">Username for CSV</th>
                    <!--8-->
                    <th scope="col">PIDs for CSV</th>
                    <!--9-->
                    <th scope="col">Project Titles for CSV</th>
                    <!--10-->
                    <th scope="col">Project Statuses for CSV</th>
                    <!--11-->
                    <th scope="col">Rights for CSV</th>
                    <!--12-->
                    <th scope="col">SAG ID for CSV</th>
                    <!--13-->
                    <th scope="col">SAG Name for CSV</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="allTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
        <h5 id="allTableTitle"></h5>
        <table aria-label="Users and Projects Table" id="SAG-System-Table" class="sagTable allTable cell-border"
            style="width: 100%">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control allTableSelect tableSelect"
                                            id="projectsSelectAll" multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control allTableSelect tableSelect"
                                            id="sagsSelectAll" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control allTableSelect tableSelect"
                                            id="usersSelectAll" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control allTableSelect tableSelect"
                                            id="rightsSelectAll" multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <!--0-->
                    <th scope="col">
                        <?= $module->framework->tt('user') ?>
                    </th>
                    <!--1-->
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_60') ?>
                    </th>
                    <!--2-->
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_61') ?>
                    </th>
                    <!--3-->
                    <th scope="col">
                        <?= $module->framework->tt('status_ui_63') ?>
                    </th>
                    <!--4-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_17') ?>
                    </th>
                    <!--5-->
                    <th scope="col">
                        <?= $module->framework->tt('cc_reports_21') ?>
                    </th>
                    <!--6-->
                    <th scope="col">Username for CSV</th>
                    <!--7-->
                    <th scope="col">PID for CSV</th>
                    <!--8-->
                    <th scope="col">Project Title for CSV</th>
                    <!--9-->
                    <th scope="col">Project Status for CSV</th>
                    <!--10-->
                    <th scope="col">Rights for CSV</th>
                    <!--11-->
                    <th scope="col">SAG ID for CSV</th>
                    <!--12-->
                    <th scope="col">SAG Name for CSV</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <style>
    div.dt-buttons {
        float: right;
    }

    .select2-search__field {
        width: 100% !important;
    }

    div.dataTables_filter {
        margin-top: 4px;
        margin-right: 10px;
    }
    </style>
</div> <!-- End SAG_Container -->
<?php
echo $module->framework->initializeJavascriptModuleObject();
$module->framework->tt_transferToJavascriptModuleObject();
$js = file_get_contents($module->framework->getSafePath('js/system-reports.js'));
$js = str_replace('{{MODULE_DIRECTORY_PREFIX}}', $module->getModuleDirectoryPrefix(), $js);
$js = str_replace('__MODULE__', $module->framework->getJavascriptModuleObjectName(), $js);
echo '<script type="text/javascript">' . $js . '</script>';
require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php';