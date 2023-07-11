<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

require_once $module->framework->getSafePath('classes/Alerts.php');

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}
$sendAlertsUrl            = $module->framework->getUrl('ajax/sendAlerts.php');
$replaceSmartVariablesUrl = $module->framework->getUrl('ajax/replaceSmartVariables.php');
?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/sl-1.6.2/sr-1.2.2/datatables.min.css"
    rel="stylesheet" />

<script src="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/sl-1.6.2/sr-1.2.2/datatables.min.js">
</script>

<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>


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
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>From address</td>
                                    <td>This is the email address that the alert will come from. You can optionally
                                        specify a display name.</td>
                                </tr>
                                <tr>
                                    <td>Subject</td>
                                    <td>This is the subject of the alert email. You can use placeholders and/or smart
                                        variables in the subject (see below).</td>
                                </tr>
                                <tr>
                                    <td>Body</td>
                                    <td>This is some formatted text that will make up the body of the email. You can use
                                        placeholders and/or smart variables in the body (see below).</td>
                                </tr>
                                <tr>
                                    <td>Placeholders</td>
                                    <td>These are text strings that will be replaced with the actual values when the
                                        email is sent. A description of the available placeholders appears in the Alert
                                        Project Users popup.</td>
                                </tr>
                                <tr>
                                    <td>Reminder</td>
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
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-header bg-danger text-light font-weight-bold">
                        Expire Project Users
                    </div>
                    <div class="card-body bg-light">
                        <h5 class="card-title">From address</h5>
                        This is the email address that the alert will come from. You can optionally specify a display
                        name.
                        <h5 class="card-title mt-2">Subject</h5>
                        This is the subject of the alert email. You can use placeholders and/or smart variables in the
                        subject (see below).
                        <h5 class="card-title mt-2">Body</h5>
                        This is some formatted text that will make up the body of the email. You can use placeholders
                        and/or smart variables in the body (see below).
                        <h5 class="card-title mt-2">Placeholders</h5>
                        These are text strings that will be replaced with the actual values when the email is sent. A
                        description of the available placeholders appears in the Alert Project Users popup.
                        <h5 class="card-title mt-2">Reminder</h5>
                        The option exists to schedule a reminder to be sent after a defined period of days. Aside from
                        the number of days, the reminder is configured in the same way as the initial alert.
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
        <br><br>
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
            <button type="button" class="btn btn-xs btn-primary action" onclick="openEmailUsersModal();" disabled><i
                    class="fa-sharp fa-regular fa-envelope"></i> Email User(s)</button>
            <button type="button" class="btn btn-xs btn-warning action" onclick="openEmailUserRightsHoldersModal();"
                disabled><i class="fa-kit fa-sharp-regular-envelope-circle-exclamation"></i> Email User Rights
                Holders</button>
            <button type="button" class="btn btn-xs btn-danger action" onclick="openExpireUsersModal();" disabled><i
                    class="fa-regular fa-user-xmark fa-fw"></i> Expire User(s)</button>
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
                                type="checkbox" onchange="handleCheckboxes(this);"></input>
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
    <?php
    $project_id    = $module->framework->getProjectId();
    $adminUsername = $module->framework->getUser()->getUsername();

    $userSql       = 'SELECT COUNT(username) FROM redcap_user_rights WHERE project_id = ?';
    $usersResult   = $module->framework->query($userSql, [ $project_id ]);
    $usersCount    = intval($usersResult->fetch_assoc()["COUNT(username)"]);
    $userThreshold = 5000;
    if ( $usersCount <= $userThreshold ) {
        $userData = json_encode($module->getUsersWithBadRights2($project_id));
    }
    ?>
    <script>
    console.log(performance.now());
    console.time('dt');
    const usersCount = <?= $usersCount ?>;
    const doAjax = usersCount > <?= $userThreshold ?>;
    const config = doAjax ? {
        ajax: {
            url: '<?= $module->framework->getUrl("ajax/projectUsers.php") ?>',
            type: 'POST'
        },
    } : {
        data: JSON.parse('<?= $userData ?>')
    };
    var Toast = Swal.mixin({
        toast: true,
        position: 'middle',
        iconColor: 'white',
        customClass: {
            popup: 'colored-toast'
        },
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });

    function handleCheckboxes(el) {
        const dt = $('#discrepancy-table').DataTable();
        const checked = $(el).prop('checked');
        dt.rows(function(idx, data, node) {
            return data.bad.length > 0
        }, {
            search: 'applied'
        }).select(checked);
        $('.user-selector input').prop('checked', checked).trigger('change');
    }

    function openEmailUsersModal() {
        document.querySelector('#emailUsersModal form').reset();
        $('.collapse').collapse('hide');
        populateDefaultEmailUserModal();
        $('#emailUsersModal').modal('show');
    }

    function populateDefaultEmailUserModal() {
        const emailBodyTemplate = `<?= $module->getSystemSetting('user-email-body-template') ?? "" ?>`;
        tinymce.get('emailBody').setContent(emailBodyTemplate);

        const emailSubjectTemplate = `<?= $module->getSystemSetting('user-email-subject-template') ?? "" ?>`;
        $('#emailSubject').val(emailSubjectTemplate);

        const reminderBodyTemplate = `<?= $module->getSystemSetting('user-reminder-email-body-template') ?? "" ?>`;
        tinymce.get('reminderBody').setContent(reminderBodyTemplate);

        const reminderSubjectTemplate =
            `<?= $module->getSystemSetting('user-reminder-email-subject-template') ?? "" ?>`;
        $('#reminderSubject').val(reminderSubjectTemplate);
    }

    function openEmailUserRightsHoldersModal() {
        document.querySelector('#emailUserRightsHoldersModal form').reset();
        $('.collapse').collapse('hide');
        populateDefaultEmailUserRightsHoldersModal();
        $('#emailUserRightsHoldersModal').modal('show');
    }

    function populateDefaultEmailUserRightsHoldersModal() {
        const emailBodyTemplate = `<?= $module->getSystemSetting('user-rights-holders-email-body-template') ?? "" ?>`;
        tinymce.get('emailBody-UserRightsHolders').setContent(emailBodyTemplate);

        const emailSubjectTemplate =
            `<?= $module->getSystemSetting('user-rights-holders-email-subject-template') ?? "" ?>`;
        $('#emailSubject-UserRightsHolders').val(emailSubjectTemplate);

        const reminderBodyTemplate =
            `<?= $module->getSystemSetting('user-rights-holders-reminder-email-body-template') ?? "" ?>`;
        tinymce.get('reminderBody-UserRightsHolders').setContent(reminderBodyTemplate);

        const reminderSubjectTemplate =
            `<?= $module->getSystemSetting('user-rights-holders-reminder-email-subject-template') ?? "" ?>`;
        $('#reminderSubject-UserRightsHolders').val(reminderSubjectTemplate);
    }

    function openExpireUsersModal() {
        document.querySelector('#userExpirationModal form').reset();
        $('#userNotificationInfo').collapse('hide');
        userExpirationUserRightsHoldersToggle(false);
        const usersToExpire = getSelectedUsers();
        let tableRows = "";
        usersToExpire.forEach(user => {
            tableRows +=
                `<tr><td><strong>${user.username}</strong></td><td>${user.name}</td><td>${user.email}</td></tr>`;
        })
        $('#userExpirationTable tbody').html(tableRows);
        populateDefaultExpireUsersModal();
        $('#userExpirationModal').modal('show');
    }

    function populateDefaultExpireUsersModal() {
        const userEmailBodyTemplate = `<?= $module->getSystemSetting('user-expiration-email-body-template') ?? "" ?>`;
        tinymce.get('emailBody-userExpiration').setContent(userEmailBodyTemplate);

        const userEmailSubjectTemplate =
            `<?= $module->getSystemSetting('user-expiration-email-subject-template') ?? "" ?>`;
        $('#emailSubject-userExpiration').val(userEmailSubjectTemplate);

        const userRightsHolderEmailBodyTemplate = `<?=
                $module->getSystemSetting('user-expiration-user-rights-holders-reminder-email-body-template') ?? ""
                ?>`;
        tinymce.get('emailBody-userExpiration-UserRightsHolders').setContent(userRightsHolderEmailBodyTemplate);

        const userRightsHolderEmailSubjectTemplate = `<?=
                $module->getSystemSetting('user-expiration-user-rights-holders-reminder-email-subject-template') ??
                "" ?>`;
        $('#emailSubject-userExpiration-UserRightsHolders').val(userRightsHolderEmailSubjectTemplate);
    }

    function getSelectedUsers() {
        return $('#discrepancy-table').DataTable().rows({
            selected: true
        }).data().toArray().map((el) => {
            return {
                username: el.username,
                name: el.name,
                email: el.email
            };
        });
    }

    async function expireUsers() {
        const users = getSelectedUsers();
        await $.post("<?= $module->framework->getUrl('ajax/expireUsers.php') ?>", {
                users: users.map(userRow => userRow["username"]),
                delayDays: $('#delayDays-expiration').val(),
            })
            .fail(function(error) {
                console.error(error.responseText);
                Swal.fire({
                        title: 'Error',
                        html: error.responseText,
                        icon: 'error',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                        buttonsStyling: false
                    })
                    .then(function() {
                        window.location.reload();
                    });
            });
        return true;
    }

    function sendEmailAlerts() {
        if (!validateEmailForm()) {
            return;
        }

        let emailFormContents = $('#emailUsersForm').serializeObject();
        emailFormContents.emailBody = tinymce.get('emailBody').getContent();
        emailFormContents.reminderBody = tinymce.get('reminderBody').getContent();
        emailFormContents.alertType = 'users';
        emailFormContents.users = getAlertUserInfo();

        $.post("<?= $sendAlertsUrl ?>", emailFormContents)
            .done(response => {
                const multiple = emailFormContents.users.length > 1;
                Toast.fire({
                    title: 'The alert' + (multiple ? "s were " : " was ") +
                        'successfully sent.',
                    icon: 'success'
                });
            })
            .fail(error => {
                Toast.fire({
                    title: 'There was an error sending the alert.',
                    icon: 'error'
                });
            });
    }

    function validateEmailForm() {
        let valid = true;
        if ($('#emailSubject').val().trim() == "") {
            $('#emailSubject').addClass('is-invalid');
            valid = false;
        } else {
            $('#emailSubject').removeClass('is-invalid');
        }
        let emailBody = tinymce.get('emailBody').getContent({
            format: 'text'
        }).trim();
        if (emailBody == '') {
            $('#emailBody').siblings('label').addClass('is-invalid');
            $('#emailBody').parent().addClass('is-invalid');
            valid = false;
        } else {
            $('#emailBody').siblings('label').removeClass('is-invalid');
            $('#emailBody').parent().removeClass('is-invalid');
        }

        if ($('#sendReminder').is(':checked')) {
            if ($('#reminderSubject').val().trim() == "") {
                $('#reminderSubject').addClass('is-invalid');
                valid = false;
            } else {
                $('#reminderSubject').removeClass('is-invalid');
            }

            let delayDays = $('input[name="delayDays"]').val().trim();
            if (delayDays == "" || !isInteger(delayDays) || delayDays < 1) {
                $('input[name="delayDays"]').addClass('is-invalid');
                valid = false;
            } else {
                $('input[name="delayDays"]').removeClass('is-invalid');
            }
            let reminderBody = tinymce.get('reminderBody').getContent({
                format: 'text'
            }).trim();
            if (reminderBody == '') {
                $('#reminderBody').siblings('label').addClass('is-invalid');
                $('#reminderBody').parent().addClass('is-invalid');
                valid = false;
            } else {
                $('#reminderBody').siblings('label').removeClass('is-invalid');
                $('#reminderBody').parent().removeClass('is-invalid');
            }
        }
        return valid;
    }

    function getAlertUserInfo() {
        return $('#discrepancy-table').DataTable().rows({
            selected: true
        }).data().toArray().map((el) => {
            return {
                'sag_user': el.username,
                'sag_user_fullname': el.name,
                'sag_user_email': el.email,
                'sag_user_rights': el.bad
            };
        });
    }


    function sendEmailAlerts_UserRightsHolders() {
        if (!validateEmailForm_UserRightsHolders()) {
            return;
        }

        let emailFormContents = $('#emailUserRightsHoldersForm').serializeObject();
        emailFormContents.emailBody = tinymce.get('emailBody-UserRightsHolders').getContent();
        emailFormContents.reminderBody = tinymce.get('reminderBody-UserRightsHolders').getContent();
        emailFormContents.alertType = 'userRightsHolders';
        emailFormContents.users = getAlertUserInfo();
        emailFormContents.recipients = getUserRightsHolderAlertRecipients('emailUserRightsHoldersForm');

        $.post("<?= $sendAlertsUrl ?>", emailFormContents)
            .done(response => {
                const multiple = emailFormContents.recipients.length > 1;
                Toast.fire({
                    title: 'The alert' + (multiple ? "s were " : " was ") +
                        'successfully sent.',
                    icon: 'success'
                });
            })
            .fail(error => {
                Toast.fire({
                    title: 'There was an error sending the alert.',
                    icon: 'error'
                });
            });
    }

    function validateEmailForm_UserRightsHolders() {
        let valid = true;
        if ($('#emailSubject-UserRightsHolders').val().trim() == "") {
            $('#emailSubject-UserRightsHolders').addClass('is-invalid');
            valid = false;
        } else {
            $('#emailSubject-UserRightsHolders').removeClass('is-invalid');
        }
        let emailBody = tinymce.get('emailBody-UserRightsHolders').getContent({
            format: 'text'
        }).trim();
        if (emailBody == '') {
            $('#emailBody-UserRightsHolders').siblings('label').addClass('is-invalid');
            $('#emailBody-UserRightsHolders').parent().addClass('is-invalid');
            valid = false;
        } else {
            $('#emailBody-UserRightsHolders').siblings('label').removeClass('is-invalid');
            $('#emailBody-UserRightsHolders').parent().removeClass('is-invalid');
        }

        if ($('#sendReminder-UserRightsHolders').is(':checked')) {
            if ($('#reminderSubject-UserRightsHolders').val().trim() == "") {
                $('#reminderSubject-UserRightsHolders').addClass('is-invalid');
                valid = false;
            } else {
                $('#reminderSubject-UserRightsHolders').removeClass('is-invalid');
            }

            let delayDays = $('input[name="delayDays-UserRightsHolders"]').val().trim();
            if (delayDays == "" || !isInteger(delayDays) || delayDays < 1) {
                $('input[name="delayDays-UserRightsHolders"]').addClass('is-invalid');
                valid = false;
            } else {
                $('input[name="delayDays-UserRightsHolders"]').removeClass('is-invalid');
            }
            let reminderBody = tinymce.get('reminderBody-UserRightsHolders').getContent({
                format: 'text'
            }).trim();
            if (reminderBody == '') {
                $('#reminderBody-UserRightsHolders').siblings('label').addClass('is-invalid');
                $('#reminderBody-UserRightsHolders').parent().addClass('is-invalid');
                valid = false;
            } else {
                $('#reminderBody-UserRightsHolders').siblings('label').removeClass('is-invalid');
                $('#reminderBody-UserRightsHolders').parent().removeClass('is-invalid');
            }
        }

        const anyChecked = $('#recipientTable_UserRightsHolders .user-rights-holder-selector input').toArray().some(
            el => $(el).is(':checked'));
        if (!anyChecked) {
            $('#recipientTable_UserRightsHolders').addClass('is-invalid');
            valid = false;
        } else {

            $('#recipientTable_UserRightsHolders').removeClass('is-invalid');
        }

        return valid;
    }

    function expireUsersAndSendAlerts() {
        if (!validateEmailForm_UserExpiration()) {
            return;
        }
        const users = getAlertUserInfo();
        let formContents = $('#userExpirationForm').serializeObject();
        formContents.usersEmailBody = tinymce.get('emailBody-userExpiration').getContent();
        formContents.userRightsHoldersEmailBody = tinymce.get('emailBody-userExpiration-UserRightsHolders')
            .getContent();
        formContents.alertType = 'expiration';
        formContents.users = users;
        formContents.recipients = getUserRightsHolderAlertRecipients('userExpirationForm');

        expireUsers().then(() => {
            if (!formContents.sendUserNotification && !formContents[
                    "sendNotification-userExpiration-UserRightsHolders"]) {
                Toast.fire({
                        title: 'The user' + (users.length > 1 ? "s were " : " was ") +
                            'successfully expired.',
                        icon: 'success'
                    })
                    .then(function() {
                        window.location.reload();
                    });
            } else {
                $.post("<?= $sendAlertsUrl ?>", formContents)
                    .done(response => {
                        Toast.fire({
                            title: 'The user' + (users.length > 1 ? "s were " : " was ") +
                                'successfully expired.',
                            icon: 'success'
                        });
                    })
                    .fail(error => {
                        Toast.fire({
                            title: 'There was an error.',
                            icon: 'error'
                        });
                    });
            }
        })
    }

    function validateEmailForm_UserExpiration() {
        let valid = true;
        let delayDays = $('#delayDays-expiration').val().trim();
        if (delayDays == "" || !isInteger(delayDays) || delayDays < 0) {
            $('#delayDays-expiration').addClass('is-invalid');
            valid = false;
        } else {
            $('#delayDays-expiration').removeClass('is-invalid');
        }

        if ($('#sendUserNotification').is(':checked')) {
            let userEmailSubject = $('#emailSubject-userExpiration').val().trim();
            if (userEmailSubject == '') {
                $('#emailSubject-userExpiration').addClass('is-invalid');
                valid = false;
            } else {
                $('#emailSubject-userExpiration').removeClass('is-invalid');
            }

            let userEmailBody = tinymce.get('emailBody-userExpiration').getContent({
                format: 'text'
            }).trim();
            if (userEmailBody == '') {
                $('#emailBody-userExpiration').siblings('label').addClass('is-invalid');
                $('#emailBody-userExpiration').parent().addClass('is-invalid');
                valid = false;
            } else {
                $('#emailBody-userExpiration').siblings('label').removeClass('is-invalid');
                $('#emailBody-userExpiration').parent().removeClass('is-invalid');
            }
        }

        if ($('#sendNotification-userExpiration-UserRightsHolders').is(':checked')) {
            let userRightsHolderEmailSubject = $('#emailSubject-userExpiration-UserRightsHolders').val().trim();
            if (userRightsHolderEmailSubject == '') {
                $('#emailSubject-userExpiration-UserRightsHolders').addClass('is-invalid');
                valid = false;
            } else {
                $('#emailSubject-userExpiration-UserRightsHolders').removeClass('is-invalid');
            }

            let userRightsHolderEmailBody = tinymce.get('emailBody-userExpiration-UserRightsHolders').getContent({
                format: 'text'
            }).trim();
            if (userRightsHolderEmailBody == '') {
                $('#emailBody-userExpiration-UserRightsHolders').siblings('label').addClass('is-invalid');
                $('#emailBody-userExpiration-UserRightsHolders').parent().addClass('is-invalid');
                valid = false;
            } else {
                $('#emailBody-userExpiration-UserRightsHolders').siblings('label').removeClass('is-invalid');
                $('#emailBody-userExpiration-UserRightsHolders').parent().removeClass('is-invalid');
            }

            const anyChecked = $('#recipientTable_userExpiration_UserRightsHolders .user-rights-holder-selector input')
                .toArray().some(el => $(el).is(':checked'));
            if (!anyChecked) {
                $('#recipientTable_userExpiration_UserRightsHolders').addClass('is-invalid');
                valid = false;
            } else {

                $('#recipientTable_userExpiration_UserRightsHolders').removeClass('is-invalid');
            }
        }

        return valid;
    }

    function getUserRightsHolderAlertRecipients(form_id) {
        return $(`#${form_id} .user-rights-holder-selector input:checked`).toArray().map(el => {
            return $(el).closest('tr').data('user')
        });
    }

    async function previewEmail($emailContainer) {
        const id = $emailContainer.find('textarea.emailBody').prop('id');
        const content = tinymce.get(id).getContent();
        const replacedContent = await replaceKeywordsPreview(content);
        $('#emailPreview div.modal-body').html(replacedContent);
        $emailContainer.closest('.modal').css('z-index', 1039);
        $('#emailPreview').modal('show');
        $('#emailPreview').on('hidden.bs.modal', function(event) {
            $emailContainer.closest('.modal').css('z-index', 1050);
        });
    }

    async function replaceKeywordsPreview(text) {
        const data = {
            'sag_user': 'robin123',
            'sag_user_fullname': 'Robin Jones',
            'sag_user_email': 'robin.jones@email.com',
            'sag_user_rights': ['Project Design and Setup', 'User Rights', 'Create Records']
        };

        return $.post('<?= $replaceSmartVariablesUrl ?>', {
            text: text,
            data: data
        });
    }

    async function previewEmailUserRightsHolders($emailContainer) {
        const id = $emailContainer.find('textarea.emailBody').prop('id');
        const content = tinymce.get(id).getContent();
        const replacedContent = await replaceKeywordsPreviewUserRightsHolders(content);
        $('#emailPreview div.modal-body').html(replacedContent);
        $emailContainer.closest('.modal').css('z-index', 1039);
        $('#emailPreview').modal('show');
        $('#emailPreview').on('hidden.bs.modal', function(event) {
            $emailContainer.closest('.modal').css('z-index', 1050);
        });
    }

    async function replaceKeywordsPreviewUserRightsHolders(text) {

        const data = {
            "sag_users": [
                'robin123',
                'alex456',
                'drew789'
            ],
            "sag_fullnames": [
                'Robin Jones',
                'Alex Thomas',
                'Drew Jackson'
            ],
            "sag_emails": [
                'robin.jones@email.com',
                'alex.thomas@email.com',
                'drew.jackson@email.com'
            ],
            "sag_rights": [
                ['Project Design and Setup', 'User Rights', 'Create Records'],
                ['Logging', 'Reports & Report Builder'],
                ['Data Export - Full Data Set', 'Data Viewing - View & Edit', 'Data Access Groups',
                    'Stats & Charts', 'Survey Distribution Tools', 'File Repository'
                ]
            ]
        };

        return $.post('<?= $replaceSmartVariablesUrl ?>', {
            text: text,
            data: data
        });
    }

    async function previewEmailUserExpiration($emailContainer) {
        const id = $emailContainer.find('textarea.emailBody').prop('id');
        const content = tinymce.get(id).getContent();
        const replacedContent = await replaceKeywordsPreviewUserExpiration(content);
        $('#emailPreview div.modal-body').html(replacedContent);
        $emailContainer.closest('.modal').css('z-index', 1039);
        $('#emailPreview').modal('show');
        $('#emailPreview').on('hidden.bs.modal', function(event) {
            $emailContainer.closest('.modal').css('z-index', 1050);
        });
    }

    async function replaceKeywordsPreviewUserExpiration(text) {
        const data = {
            'sag_user': 'robin123',
            'sag_user_fullname': 'Robin Jones',
            'sag_user_email': 'robin.jones@email.com',
            'sag_user_rights': ['Project Design and Setup', 'User Rights', 'Create Records']
        };

        return $.post('<?= $replaceSmartVariablesUrl ?>', {
            text: text,
            data: data
        });
    }

    function handleDisplayUsersButton(allUsersVisible) {
        if (allUsersVisible) {
            $('#displayUsersButton').addClass('btn-outline-secondary').removeClass('btn-secondary');
        } else {
            $('#displayUsersButton').addClass('btn-secondary').removeClass('btn-outline-secondary');
        }
    }

    window.handleActionButtons = function() {
        if ($('#discrepancy-table').DataTable().rows({
                selected: true
            }).count() > 0) {
            $('.buttonContainer button.action').prop('disabled', false);
        } else {
            $('.buttonContainer button.action').prop('disabled', true);
        }
    }

    function initTinyMCE() {
        tinymce.init({
            entity_encoding: "raw",
            default_link_target: '_blank',
            selector: ".richtext",
            height: 350,
            branding: false,
            statusbar: true,
            menubar: true,
            elementpath: false,
            plugins: [
                'paste autolink lists link searchreplace code fullscreen table directionality hr'
            ],
            toolbar1: 'formatselect | hr | bold italic underline link | fontsizeselect | ' +
                'alignleft aligncenter alignright alignjustify | undo redo',
            toolbar2: 'bullist numlist | outdent indent | table tableprops tablecellprops | ' +
                'forecolor backcolor | searchreplace code removeformat | fullscreen',
            contextmenu: "copy paste | link inserttable | cell row column deletetable",
            content_css: "<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>",
            relative_urls: false,
            convert_urls: false,
            convert_fonts_to_spans: true,
            extended_valid_elements: 'i[class]',
            paste_word_valid_elements: "b,strong,i,em,h1,h2,u,p,ol,ul,li,a[href],span,color," +
                "font-size,font-color,font-family,mark,table,tr,td",
            paste_retain_style_properties: "all",
            paste_postprocess: function(plugin, args) {
                args.node.innerHTML = cleanHTML(args.node.innerHTML);
            },
            remove_linebreaks: true
        });
    }

    function initClipboard() {
        $('.dataPlaceholder').popover({
            placement: 'top',
            html: true,
            content: '<span class="text-danger">Copied!</span>',
            show: function() {
                $(this).fadeIn();
            },
            hide: function() {
                $(this).fadeOut();
            }
        });

        const clipboard = new ClipboardJS('.dataPlaceholder', {
            text: function(trigger) {
                return $(trigger).text();
            }
        });
        clipboard.on('success', function(e) {
            $(e.trigger).popover('show');
            setTimeout(function() {
                $(e.trigger).popover('hide');
            }, 1000);
            e.clearSelection();
        });
    }

    $(document).ready(function() {
        console.timeLog('dt', 'document ready');
        $('#sub-nav').removeClass('d-none');

        $(document).on('preInit.dt', function(e, settings) {
            $('#containerCard').show();
        });
        $(document).on('preXhr.dt', function(e, settings, json) {
            console.timeLog('dt', 'ajax start')
        });
        $(document).on('xhr.dt', function(e, settings, json) {
            console.timeLog('dt', 'ajax end')
        });

        const dt = $('table.discrepancy-table').DataTable(Object.assign(config, {

            select: {
                style: 'multi',
                selector: 'td:first-child input[type="checkbox"]'
            },
            deferRender: true,
            sort: false,
            filter: true,
            paging: true,
            info: true,
            scrollY: '75vh',
            scrollCollapse: true,
            stateSave: true,
            stateDuration: 60 * 60 * 24 * 365,
            stateSaveCallback: function(settings, data) {
                let checkboxStatus = {};
                $('#userFilter input').toArray().forEach(el => checkboxStatus[el.id] = el
                    .checked);
                data.checkboxStatus = checkboxStatus;
                localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data))
            },
            stateLoadCallback: function(settings) {
                const dataString = localStorage.getItem('DataTables_' + settings.sInstance);
                if (!dataString) return settings;
                const data = JSON.parse(dataString);
                if (!data.checkboxStatus) return settings;
                let allChecked = true;
                for (let id in data.checkboxStatus) {
                    const thisChecked = data.checkboxStatus[id];
                    allChecked = allChecked && thisChecked;
                    document.getElementById(id).checked = thisChecked;
                }
                handleDisplayUsersButton(allChecked);
                delete(data.checkboxStatus);
                return data;
            },
            columns: [{
                    data: function(row, type, set, meta) {
                        const hasDiscrepancy = row.bad.length > 0;
                        row.expired = row.isExpired ? 'expired' : 'current';
                        row.discrepant = hasDiscrepancy ? 'discrepant' :
                            'compliant';
                        row.inputVal =
                            `<div data-discrepant="${hasDiscrepancy}" data-expired="${row.isExpired}">` +
                            (hasDiscrepancy ?
                                `<input style="display:block; margin: 0 auto;" ` +
                                `type="checkbox" onchange="window.handleActionButtons()"></input>` :
                                "") +
                            `</div>`;
                        if (type === 'filter') {
                            return [row.expired, row.discrepant].join(' ');
                        }
                        return row.inputVal;
                    },
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).css('vertical-align', 'middle !important;');
                        $(td).addClass('user-selector');
                    }
                }, {
                    title: 'Username',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return !row.isExpired ? `<strong>${row.username}</strong>` :
                                row.username;
                        } else {
                            return row.username;
                        }
                    },
                }, {
                    title: 'Name',
                    data: 'name'
                },
                {
                    title: 'Email',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return `<a href="mailto:${row.email}">${row.email}</a>`;
                        } else {
                            return row.email;
                        }
                    }
                },
                {
                    title: 'Expiration',
                    data: 'expiration'
                },
                {
                    title: 'Security Access Group',
                    data: function(row, type, set, meta) {
                        if (row.sag) {
                            return `<strong>${row.sag_name}</strong> <span>(<span class="user-select-all">` +
                                `${row.sag}</span>)</span>`;
                        } else {
                            return `<span class="text-secondary">None</span>`;
                        }
                    }
                },
                {
                    title: 'Noncompliant Rights',
                    data: function(row, type, set, meta) {
                        const hasDiscrepancy = row.bad.length > 0;
                        if (hasDiscrepancy) {
                            let rows = '';
                            for (rightI in row.bad) {
                                const right = row.bad[rightI];
                                rows +=
                                    `<tr style='cursor: default;'><td><span>${right}</span></td></tr>`;
                            }
                            return `<a class="${row.isExpired ? "text-secondary" : "text-primary"}" ` +
                                `style="text-decoration: underline; cursor: pointer;" data-toggle="modal" ` +
                                `data-target="#modal-${row.username}">${row.bad.length} ` +
                                (row.bad.length > 1 ? " Rights" : " Right") +
                                `</a>` +
                                `<div class="modal fade" id="modal-${row.username}" tabindex="-1" aria-hidden="true">` +
                                `<div class="modal-dialog modal-dialog-scrollable">` +
                                `<div class="modal-content">` +
                                `<div class="modal-header bg-dark text-light">` +
                                `<h5 class="m-0">` +
                                `Noncompliant Rights for ${row.name} (${row.username})` +
                                `</h5>` +
                                `</div>` +
                                `<div class="modal-body">` +
                                `<div class="d-flex justify-content-center">` +
                                `<table class="table table-sm table-hover table-borderless mb-0">` +
                                `<tbody>${rows}</tbody>` +
                                `</table>` +
                                `</div>` +
                                `</div>` +
                                `</div>` +
                                `</div>` +
                                `</div>`;
                        } else {
                            return "<i class='fa-sharp fa-check mr-1 text-success'></i>None";
                        }
                    }
                },
                {
                    title: 'Project Role',
                    data: function(row, type, set, meta) {
                        if (row.project_role) {
                            return `<strong>${row.project_role_name}</strong> <span>(<span class="user-select-all">` +
                                row.project_role +
                                `</span>)</span>`;
                        } else {
                            return `<span class="text-secondary">None</span>`;
                        }
                    }
                }
            ],
            createdRow: function(row, data, dataIndex) {
                let rowClass = data.bad.length > 0 ? 'table-danger-light' :
                    'table-success-light';
                rowClass = data.isExpired ? 'text-secondary bg-light' : rowClass;
                $(row).attr('data-user', data.username);
                $(row).attr('data-email', data.email);
                $(row).attr('data-name', data.name);
                $(row).addClass(rowClass);
            },
            drawCallback: function(settings) {
                const api = this.api();
                api.rows({
                    page: 'current'
                }).every(function(rowIdx, tableLoop, rowLoop) {
                    const data = this.data();
                    const row = api.row(rowIdx);
                    const rowNode = row.node();
                    const checkbox = $(rowNode).find('input[type="checkbox"]');
                    checkbox.prop('checked', row.selected());
                });
            },
            columnDefs: [{
                targets: [4, 5, 6, 7],
                createdCell: function(td) {
                    $(td).addClass('align-middle text-center');
                }
            }, {
                targets: '_all',
                createdCell: function(td) {
                    $(td).addClass('align-middle');
                }
            }],
            dom: "lftip",
            initComplete: function() {
                $('table.discrepancy-table').addClass('table');
                this.api().columns.adjust().draw();
                console.timeLog('dt', 'dt init complete');
                initTinyMCE();
                initClipboard();
                console.timeEnd('dt');
                console.log(performance.now());
            },
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Users...",
                infoFiltered: " - filtered from _MAX_ total users",
                emptyTable: "No users found in this project",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "Showing 0 to 0 of 0 users",
                lengthMenu: "Show _MENU_ users",
                loadingRecords: "Loading...",
                zeroRecords: "No matching users found",
                select: {
                    rows: {
                        _: '%d users selected',
                        0: '',
                        1: 'One user selected'
                    }
                }
            }
        }));

        $('#userFilter label').click(function(e) {
            e.stopPropagation()
        });
        $('#userFilter input').change(function(e) {
            let searchTerm = "";
            // Expired/Current
            const expiredChecked = $('#expiredUsers').is(':checked');
            const nonExpiredChecked = $('#nonExpiredUsers').is(':checked');
            const expired = expiredChecked ? 'expired' : undefined;
            const nonExpired = nonExpiredChecked ? 'current' : undefined;
            if (expiredChecked || nonExpiredChecked) {
                searchTerm += '(' + [expired, nonExpired].filter(el => el).join('|') + ') ';
            } else {
                searchTerm += 'none ';
            }

            // Discrepant/Compliant
            const discrepantChecked = $('#discrepantUsers').is(':checked');
            const nonDiscrepantChecked = $('#nonDiscrepantUsers').is(':checked');
            const discrepant = discrepantChecked ? 'discrepant' : undefined;
            const nonDiscrepant = nonDiscrepantChecked ? 'compliant' : undefined;
            if (discrepantChecked || nonDiscrepantChecked) {
                searchTerm += '(' + [discrepant, nonDiscrepant].filter(el => el).join('|') +
                    ') ';
            } else {
                searchTerm += 'none';
            }

            const allChecked = expiredChecked && nonExpiredChecked && discrepantChecked &&
                nonDiscrepantChecked;
            handleDisplayUsersButton(allChecked);
            dt.columns(0).search(searchTerm, true).draw();
            window.handleActionButtons();
        });
    });
    </script>
</div>
<?php
$Alerts = new Alerts($module);
$Alerts->getUserEmailModal($adminUsername);
$Alerts->getUserRightsHoldersEmailModal($adminUsername);
$Alerts->getUserExpirationModal($adminUsername);
$Alerts->getEmailPreviewModal();
?>