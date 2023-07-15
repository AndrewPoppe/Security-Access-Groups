<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "userlist";

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
            <li class="<?= $tab === "userlist" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=userlist') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li class="<?= $tab === "sags" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=sags') ?>"
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
    <div class="clear"></div>

    <?php if ( $tab == "userlist" ) {
        $sags = $module->getAllSags();
        ?>

        <p style='margin:20px 0;max-width:1000px;font-size:14px;'>This table shows all users in the REDCap system and their
            current SAG assignment. Use the <strong>Edit Users</strong> button to change a user's SAG assignment. You may
            export the current list of SAG assignments or import a CSV file of assignments using the buttons below.
        </p>

        <!-- Modals -->
        <div id="loading" class="modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body p-4 text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div class="mt-2">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="hidden">
            <input type="file" accept="text/csv" class="form-control-file" id="importUsersFile">
            <table aria-label="template table" id="templateTable">
                <thead>
                    <tr>
                        <th>username</th>
                        <th>sag_id</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $sags as $index => $sag ) {
                        echo "<tr><td>example_user_",
                            (intval($index) + 1),
                            "</td><td>",
                            \REDCap::escapeHtml($sag["sag_id"]),
                            "</td></tr>";
                    } ?>
                </tbody>
            </table>
        </div>
        <!-- Users Table -->
        <div class="card card-body bg-light" style="min-width:700px;">
            <div class="toolbar2 d-flex flex-row justify-content-between mb-2">
                <div class="d-flex">
                    <button class="btn btn-danger btn-xs mr-1 editUsersButton" style="width: 8em;" data-editing="false"
                        onclick="toggleEditMode(event);">
                        <i class="fa-sharp fa-user-pen"></i>
                        <span>Edit Users</span>
                    </button>
                    <div class="d-flex dropdown">
                        <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-sharp fa-file-excel mr-1"></i>
                            <span>Import or Export User Assignments</span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" onclick="handleCsvExport();"><i
                                        class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success">
                                    </i>Export User Assignments</a></li>
                            <li><a class="dropdown-item" onclick="importCsv();"><i
                                        class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import User
                                    Assignments</a></li>
                            <li><a class="dropdown-item" onclick="downloadTemplate();"><i
                                        class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                                    Template</a></li>
                        </ul>
                    </div>

                </div>
            </div>
            <table aria-label='Users Table' id='SUR-System-Table' class="compact cell-border border">
                <thead>
                    <tr>
                        <th data-id="username" class="py-3">Username</th>
                        <th data-id="name" class="py-3">Name</th>
                        <th data-id="email" class="py-3">Email</th>
                        <th data-id="sag" class="py-3">SAG Name</th>
                        <th data-id="sag_id" class="py-3">SAG ID</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <?php
        $basicSags = [];
        foreach ( $sags as $sag ) {
            $basicSags[$sag["sag_id"]] = $sag["sag_name"];
        }
        $basicSagsJson = json_encode($basicSags);

        $js = file_get_contents($module->framework->getSafePath('js/system-settings-userlist.js'));
        $js = str_replace('{{SAGS_JSON}}', $basicSagsJson, $js);
        $js = str_replace('{{IMPORT_CSV_USERS_URL}}', $module->framework->getUrl('ajax/importCsvUsers.php'), $js);
        $js = str_replace('{{ASSIGN_SAG_URL}}', $module->framework->getUrl('ajax/assignSag.php'), $js);
        $js = str_replace('{{USERS_URL}}', $module->framework->getUrl('ajax/users.php'), $js);
        $js = str_replace('{{DEFAULT_SAG_ID}}', $module->defaultSagId, $js);
        echo '<script type="text/javascript">' . $js . '</script>';


    } elseif ( $tab == "sags" ) {
        $displayTextForUserRights    = $module->getDisplayTextForRights();
        $allDisplayTextForUserRights = $module->getDisplayTextForRights(true);

        ?>

        <p style='margin:20px 0;max-width:1000px;font-size:14px;'>This table shows all the SAGs that currently exist in the
            system. A SAG must be created here before it can be assigned to a user. The current list of SAGs can be exported
            as a CSV file, and a CSV file can be imported to update existing SAGs or to create new SAGs.
        </p>

        <!-- Modal -->
        <div class="modal" id="edit_sag_popup" data-backdrop="static" data-keyboard="false"
            aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>


        <!-- Controls Container -->
        <div class="container ml-0 mt-2 mb-3 px-0"
            style="background-color: #eee; max-width: 550px; border: 1px solid #ccc;">
            <div class="d-flex flex-row justify-content-end my-1">
                <div class="dropdown">
                    <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-sharp fa-file-excel"></i>
                        <span>Import or Export SAGs</span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" onclick="exportRawCsv();"><i
                                    class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-info"></i>Export SAGs
                                (raw)</a></li>
                        <li><a class="dropdown-item" onclick="exportCsv();"><i
                                    class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success"></i>Export SAGs
                                (labels)</a></li>
                        <li><a class="dropdown-item" onclick="importCsv();"><i
                                    class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import SAGs</a>
                        </li>
                        <li><a class="dropdown-item" onclick="exportRawCsv(false);"><i
                                    class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                                Template</a></li>
                    </ul>
                </div>
                <div class="hidden">
                    <input type="file" accept="text/csv" class="form-control-file" id="importSagsFile">
                </div>
            </div>
            <div class="row ml-2">
                <span><strong>Create new Security Access Group:</strong></span>
            </div>
            <div class="row ml-2 mb-2 mt-1 justify-content-start">
                <div class="col-6 px-0">
                    <input id="newSagName" class="form-control form-control-sm" type="text"
                        placeholder="Enter new SAG name">
                </div>
                <div class="col ml-1 px-0 justify-content-start">
                    <button class="btn btn-success btn-sm" id="addSagButton" onclick="addNewSag();"
                        title="Add a New Security Access Group">
                        <i class="fa-kit fa-solid-tag-circle-plus fa-fw"></i>
                        <span>Create SAG</span>
                    </button>
                </div>
            </div>
        </div>


        <!-- SAG Table -->
        <div class=" clear">
        </div>
        <div id="sagTableWrapper" style="display: none; width: 100%;">
            <table aria-label="SAGs Table" id="sagTable" class="sagTable cell-border" style="width: 100%">
                <thead>
                    <tr style="vertical-align: bottom; text-align: center;">
                        <th>Order</th>
                        <th data-key="sag_name">SAG Name</th>
                        <th data-key="sag_id">SAG ID</th>
                        <?php foreach ( $allDisplayTextForUserRights as $key => $text ) {
                            echo "<th data-key='",
                                \REDCap::escapeHtml($key),
                                "' class='dt-head-center'>",
                                \REDCap::escapeHtml($text),
                                "</th>";
                        } ?>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <?php
        $js = file_get_contents($module->framework->getSafePath('js/system-settings-sags.js'));
        $js = str_replace('{{DELETE_SAG_URL}}', $module->framework->getUrl('ajax/deleteSag.php'), $js);
        $js = str_replace('{{EDIT_SAG_URL}}', $module->framework->getUrl('ajax/editSag.php'), $js);
        $js = str_replace('{{USER_RIGHTS_ERROR_MESSAGE}}', $lang['rights_358'], $js);
        $js = str_replace('{{EDIT_SAG_FALSE_URL}}', $module->framework->getUrl('ajax/editSag.php?newSag=false'), $js);
        $js = str_replace('{{EDIT_SAG_TRUE_URL}}', $module->framework->getUrl('ajax/editSag.php?newSag=true'), $js);
        $js = str_replace('{{IMPORT_CSV_SAGS_URL}}', $module->framework->getUrl('ajax/importCsvSags.php'), $js);
        $js = str_replace('{{SAGS_URL}}', $module->framework->getUrl('ajax/sags.php'), $js);
        echo '<script type="text/javascript">', $js, '</script>';
    }
    ?>
</div> <!-- End SAG_Container -->