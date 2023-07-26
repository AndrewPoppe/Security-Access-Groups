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
                <h5 class="modal-title">Security Access Groups - Alerts and Actions</h5>
                <button type="button" class="btn-close btn-secondary align-self-center" data-bs-dismiss="modal"
                    data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">Introduction</h5>
                        <p>If there are users in this project with non-compliant rights (i.e., user rights that are
                            disallowed by their currently-assigned SAG), then there are several options that can be
                            taken from this page.</p>
                        <p>To take an action, you first have to select one or more users using the checkboxes in the
                            table.</p>
                        <p>The available actions that can be taken are listed here and explained more fully below:
                        <ul>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-primary text-light p-1 font-weight-bold">Alert Project Users</span>:
                                This lets the user(s) know that their own user rights are currently not in compliance
                                with their assigned SAG. There is also the option to schedule a reminder alert after a
                                period of time.
                            </li>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-warning text-body p-1 font-weight-bold">Alert Project User Rights
                                    Holders</span>: This alert is sent to one or more users in the project who have the
                                permission to change user rights. There is also the option to schedule a reminder alert
                                after a period of time.
                            </li>
                            <li class="my-1" style="line-height: 1.3rem;">
                                <span class="bg-danger text-light p-1 font-weight-bold">Expire Project Users</span>: Set
                                the expiration date of the user in this project. Alerts can be sent to the users and/or
                                user rights holders when the expiration is scheduled.
                            </li>
                        </ul>
                        </p>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-primary text-light font-weight-bold">Alert Project Users</div>
                    <div class="card-body bg-light">
                        <p>This lets the user(s) know that their own user rights are currently not in compliance
                            with their assigned SAG. The options for the alert itself are similar to other alerts in
                            REDCap:</p>
                        <table class="table" aria-label="Options for sending alerts to users">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>From address</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is the email address that the alert will come from.</td>
                                </tr>
                                <tr>
                                    <td>Display Name</td>
                                    <td style="font-size: smaller;" class="text-dark">Optional</td>
                                    <td>The display name that will appear next to the From address in the alert email
                                    </td>
                                </tr>
                                <tr>
                                    <td>Subject</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is the subject of the alert email. You can use placeholders and/or smart
                                        variables in the subject (see below).</td>
                                </tr>
                                <tr>
                                    <td>Body</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is some formatted text that will make up the body of the email. You can use
                                        placeholders and/or smart variables in the body (see below).</td>
                                </tr>
                                <tr>
                                    <td>Placeholders</td>
                                    <td style="font-size: smaller;" class="text-secondary">N/A</td>
                                    <td>These are text strings that will be replaced with the actual values when the
                                        email is sent. A description of the available placeholders appears in the Alert
                                        Project Users popup.</td>
                                </tr>
                                <tr>
                                    <td>Reminder</td>
                                    <td style="font-size: smaller;" class="text-dark">Optional</td>
                                    <td>The option exists to schedule a reminder to be sent after a defined period of
                                        days. Aside from the number of days, the reminder is configured in the same way
                                        as the initial alert.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-warning text-body font-weight-bold">
                        Alert Project User Rights Holders
                    </div>
                    <div class="card-body bg-light">
                        <ul>
                            <li>This alert is sent to one or more users in the project who have the permission to change
                                user
                                rights. The recipient(s) are selected from a list of all user rights holders in the
                                project.
                            </li>
                            <li>Be sure to verify that the intended recipients are in compliance with their own SAG to
                                prevent confusion.</li>
                            <li>Also, note that only one alert will be sent to each user rights holder,
                                so use placeholders to specify which project users the alert is regarding.</li>
                            <li>The options for setting up this alert are nearly identical with the Project Users Alert,
                                described above. One difference is that the placeholders are different, given the
                                possibility that the alert refers to multiple users.</li>
                        </ul>
                        <table class="table" aria-label="Options for sending alerts to user rights holders">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>From address</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is the email address that the alert will come from.</td>
                                </tr>
                                <tr>
                                    <td>Display Name</td>
                                    <td style="font-size: smaller;" class="text-dark">Optional</td>
                                    <td>The display name that will appear next to the From address in the alert email
                                    </td>
                                </tr>
                                <tr>
                                    <td>Subject</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is the subject of the alert email. You can use placeholders and/or smart
                                        variables in the subject (see below).</td>
                                </tr>
                                <tr>
                                    <td>Body</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>This is some formatted text that will make up the body of the email. You can use
                                        placeholders and/or smart variables in the body (see below).</td>
                                </tr>
                                <tr>
                                    <td>Placeholders</td>
                                    <td style="font-size: smaller;" class="text-secondary">N/A</td>
                                    <td>These are text strings that will be replaced with the actual values when the
                                        email is sent. A description of the available placeholders appears in the Alert
                                        Project User Rights Holders popup.</td>
                                </tr>
                                <tr>
                                    <td>Recipients</td>
                                    <td style="font-size: smaller;" class="text-danger">Required</td>
                                    <td>Select at least one recipient for this alert from the table of users that have
                                        User Rights permissions in this project.</td>
                                </tr>
                                <tr>
                                    <td>Reminder</td>
                                    <td style="font-size: smaller;" class="text-dark">Optional</td>
                                    <td>The option exists to schedule a reminder to be sent after a defined period of
                                        days. Aside from the number of days, the reminder is configured in the same way
                                        as the initial alert.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-danger text-light font-weight-bold">
                        Expire Project Users
                    </div>
                    <div class="card-body bg-light">
                        <ul>
                            <li>This allows an admin to expire the selected user or users in the project either
                                immediately or after a designated number of days</li>
                            <li>Users will have their expiration date set in the project based on this selection</li>
                            <li>An alert can be sent to the user(s) to notify them of the expiration
                                <ul>
                                    <li>The options for this alert are identical with those described in the Alert
                                        Project
                                        Users section above, with the exception that no reminder is available to be
                                        scheduled</li>
                                </ul>
                            </li>
                            <li>An alert can be sent to project user rights holders to notify them of the expiration
                                <ul>
                                    <li>The options for this alert are identical with those described in the Alert
                                        Project User Rights Holders section above, with the exception that no reminder
                                        is available to be scheduled</li>
                                </ul>
                            </li>
                            <li><strong>Note:</strong> these alerts are sent immediately regardless of when the
                                expiration is scheduled for.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                <li class="active">
                    <a href="<?= $module->framework->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li>
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
        Use this page to identify which users currently have user rights assigned
        to them that do not comply with their assigned SAG. This situation can arise
        <ol>
            <li>when the SAG module is first enabled in a project that already has users added to it</li>
            <li>when a user's SAG assignment is changed</li>
        </ol>
        Actions can be taken to correct these situations. Click the
        <i class="fa-solid fa-circle-info fa-lg align-self-center text-info" style="font-size: 16.25px;"></i> button
        below for more information.
    </div>
    <div id="containerCard" class="container mt-4 mx-0 card card-body bg-light" style="width: 1100px; display: none;">
        <div class="buttonContainer mb-2">
            <div class="btn-group">
                <button id="displayUsersButton" type="button" class="btn btn-xs btn-outline-secondary dropdown-toggle"
                    data-toggle="dropdown" aria-expanded="false">
                    <i class="fa-sharp fa-regular fa-eye"></i> Display Users
                </button>
                <div class="dropdown-menu" id="userFilter">
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="expiredUsers" checked>
                        <label class="form-check-label" for="expiredUsers">
                            Expired users
                        </label>
                    </div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="nonExpiredUsers" checked>
                        <label class="form-check-label" for="nonExpiredUsers">
                            Non-Expired users
                        </label>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="discrepantUsers" checked>
                        <label class="form-check-label" for="discrepantUsers">
                            Users with noncompliant rights
                        </label>
                    </div>
                    <div class="form-check pl-4 mr-2">
                        <input class="form-check-input" type="checkbox" value="1" id="nonDiscrepantUsers" checked>
                        <label class="form-check-label" for="nonDiscrepantUsers">
                            Users without noncompliant rights
                        </label>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-primary action" onclick="sag_module.openEmailUsersModal();"
                disabled><i class="fa-sharp fa-regular fa-envelope"></i> Email User(s)</button>
            <button type="button" class="btn btn-xs btn-warning action"
                onclick="sag_module.openEmailUserRightsHoldersModal();" disabled><i
                    class="fa-kit fa-sharp-regular-envelope-circle-exclamation"></i> Email User Rights
                Holders</button>
            <button type="button" class="btn btn-xs btn-danger action" onclick="sag_module.openExpireUsersModal();"
                disabled><i class="fa-regular fa-user-xmark fa-fw"></i> Expire User(s)</button>
            <div class="btn-group" role="group">
                <i class="fa-solid fa-circle-info fa-lg align-self-center text-info infoButton" style="cursor:pointer;"
                    onclick="$('#infoModal').modal('show');">
                </i>
            </div>
        </div>
        <div id="table-container" class="container mx-0 px-0" style="background-color: #fafafa;">
            <table aria-label="discrepancy table" id="discrepancy-table" class="discrepancy-table row-border bg-white">
                <thead class="text-center" style="background-color:#ececec">
                    <tr>
                        <th style="vertical-align: middle !important;"><input style="display:block; margin: 0 auto;"
                                type="checkbox" onchange="sag_module.handleCheckboxes(this);"></input>
                        </th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th class="dt-head-center">Expiration</th>
                        <th class="dt-head-center">Security Access Group</th>
                        <th class="dt-head-center">Noncompliant Rights</th>
                        <th class="dt-head-center">Project Role</th>
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
        'data' => $userData
    ]);
} else {
    $config = '';
}
echo $module->framework->initializeJavascriptModuleObject();
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