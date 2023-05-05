<?php

namespace YaleREDCap\SystemUserRights;

/** @var SystemUserRights $module */

require_once "Alerts.php";
$Alerts = new Alerts($module);

// TODO: Remove this
//$module->removeLogs("message = 'user alert reminder sent' AND (project_id IS NULL OR project_id IS NOT NULL)", []);

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css"
    rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>

<script defer src="<?= $module->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />

<!-- Modal -->
<div class="hidden">
    <div id="infoContainer" class="modal-body p-4 text-center" style="font-size:x-large;">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit metus, venenatis in congue sed, ultrices sed
        nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla at auctor volutpat, urna odio ornare nulla,
        a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis vel egestas. Integer eu purus vel dui
        egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices. Aenean consectetur efficitur leo, et
        euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum erat, vitae convallis ligula. Lorem ipsum
        dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec quam blandit, vel faucibus turpis
        convallis.
    </div>
</div>

<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-user-secret'></i>&nbsp;<span>System User Rights</span>
    </div>

    <?php
    $project_id       = $module->framework->getProjectId();
    $adminUsername    = $module->framework->getUser()->getUsername();
    $discrepantRights = $module->getUsersWithBadRights($project_id);

    if ( empty($discrepantRights) ) {
        exit();
    }
    ?>

    <div class="clearfix">
        <div id="sub-nav" class="d-none d-sm-block mr-4 mb-0 ml-0">
            <ul>
                <li class="active">
                    <a href="<?= $module->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li>
                    <a href="<?= $module->getUrl('project-alert-log.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-envelopes-bulk"></i>
                        Alert Log
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div>
        <p style="font-size:large;">Check User Rights</p>
        <p>Current users in the ...</p>
    </div>
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
                        Users with discrepant rights
                    </label>
                </div>
                <div class="form-check pl-4 mr-2">
                    <input class="form-check-input" type="checkbox" value="1" id="nonDiscrepantUsers" checked>
                    <label class="form-check-label" for="nonDiscrepantUsers">
                        Users without discrepant rights
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
                class="fa-solid fa-user-xmark fa-fw"></i> Expire User(s)</button>
        <div class="btn-group" role="group">
            <i class="fa-solid fa-circle-info fa-lg align-self-center text-info" style="cursor:pointer;"
                onclick="Swal.fire({html: $('#infoContainer').html(), icon: 'info', showConfirmButton: false});"></i>
        </div>
    </div>
    <div class="container ml-0 pl-0">
        <!-- <table class="table table-bordered discrepancy-table"> -->
        <table id="discrepancy-table" class="discrepancy-table row-border border hover">
            <thead class="text-center" style="background-color:#ececec">
                <tr>
                    <th style="vertical-align: middle !important;"><input style="display:block; margin: 0 auto;"
                            type="checkbox"
                            onchange="$('.user-selector input').prop('checked', $(this).prop('checked')).trigger('change');"></input>
                    </th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Expiration</th>
                    <th>System Role</th>
                    <th>Discrepant Rights</th>
                    <th>Project Role</th>
                    <th>Alert</th>
                    <th>Reminder</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] as $i ) {
                    foreach ( $discrepantRights as $user => $thisUsersRights ) {
                        $badRights      = $thisUsersRights["bad"];
                        $hasDiscrepancy = !empty($badRights);
                        $isExpired      = $thisUsersRights["expiration"] !== "never" && strtotime($thisUsersRights["expiration"]) < strtotime("today");
                        $rowClass       = $hasDiscrepancy ? "table-danger" : "bg-light"; //"table-success";
                        $rowClass       = $isExpired ? "text-secondary bg-light" : $rowClass; ?>
                        <tr data-user="<?= $user ?>" data-email="<?= $thisUsersRights["email"] ?>"
                            data-name="<?= $thisUsersRights["name"] ?>"
                            data-rights="<?= htmlspecialchars(json_encode($badRights)) ?>" class="<?= $rowClass ?>">
                            <td style="vertical-align: middle !important;" class="align-middle user-selector">
                                <?= '<div data-discrepant="' . $hasDiscrepancy . '" data-expired="' . $isExpired . '">' . ($hasDiscrepancy ? '<input style="display:block; margin: 0 auto;" type="checkbox" onchange="window.handleActionButtons()"></input>' : '') . '</div>' ?>
                            </td>
                            <td class="align-middle">
                                <?= $isExpired ? $user : "<strong>$user</strong>" ?>
                            </td>
                            <td class="align-middle">
                                <?= $thisUsersRights["name"] ?>
                            </td>
                            <td class="align-middle">
                                <?= $thisUsersRights["email"] ?>
                            </td>
                            <td class="align-middle text-center">
                                <?= $thisUsersRights["expiration"] ?>
                            </td>
                            <td class="align-middle text-center"><span class="user-select-all">
                                    <?= $thisUsersRights["system_role"] ?>
                                </span></td>
                            <td class="align-middle text-center <?= $hasDiscrepancy ? "" : "table-success" ?>">
                                <?php
                                if ( $hasDiscrepancy ) { ?>
                                    <a class="<?= $isExpired ? "text-secondary" : "text-primary" ?>"
                                        style="text-decoration: underline; cursor: pointer;" data-toggle="modal"
                                        data-target="#modal-<?= $user ?>"><?= sizeof($badRights) . (sizeof($badRights) > 1 ? " Rights" : " Right") ?></a>
                                    <div class="modal fade" id="modal-<?= $user ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header bg-dark text-light">
                                                    <h5 class="m-0">Discrepant Rights for
                                                        <?= $thisUsersRights["name"] . " (" . $user . ")" ?>
                                                    </h5>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="d-flex justify-content-center">
                                                        <table class="table table-sm table-hover table-borderless mb-0">
                                                            <tbody>
                                                                <?php foreach ( $badRights as $right ) {
                                                                    echo "<tr style='cursor: default;'><td><span>$right</span></td></tr>";
                                                                } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                } else {
                                    echo "<i class='fa-sharp fa-check mr-1 text-success'></i>None";
                                }
                                ?>
                            </td>
                            <td class="align-middle text-center">
                                <?= $thisUsersRights["project_role"] ?>
                            </td>
                            <td class="align-middle text-center">
                                <?= $Alerts->getUserEmailSentFormatted($project_id, $user); ?>
                            </td>
                            <td class="align-middle text-center">
                                <?= $Alerts->getUserReminderStatusFormatted($project_id, $user); ?>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
    <?php $Alerts->getUserEmailModal($project_id, $adminUsername); ?>
    <?php $Alerts->getUserRightsHoldersEmailModal($project_id, $adminUsername); ?>
    <?php $Alerts->getUserExpirationModal($project_id, $adminUsername); ?>
    <?php $Alerts->getUserExpirationSchedulerModal($project_id); ?>
    <?php $Alerts->getEmailPreviewModal(); ?>
    <script>
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

            const reminderSubjectTemplate = `<?= $module->getSystemSetting('user-reminder-email-subject-template') ?? "" ?>`;
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

            const emailSubjectTemplate = `<?= $module->getSystemSetting('user-rights-holders-email-subject-template') ?? "" ?>`;
            $('#emailSubject-UserRightsHolders').val(emailSubjectTemplate);

            const reminderBodyTemplate = `<?= $module->getSystemSetting('user-rights-holders-reminder-email-body-template') ?? "" ?>`;
            tinymce.get('reminderBody-UserRightsHolders').setContent(reminderBodyTemplate);

            const reminderSubjectTemplate = `<?= $module->getSystemSetting('user-rights-holders-reminder-email-subject-template') ?? "" ?>`;
            $('#reminderSubject-UserRightsHolders').val(reminderSubjectTemplate);
        }

        function openExpireUsersModal() {
            document.querySelector('#userExpirationModal form').reset();
            $('#userNotificationInfo').collapse('hide');
            userExpirationUserRightsHoldersToggle(false);
            const usersToExpire = getSelectedUsers();
            let tableRows = "";
            usersToExpire.forEach(user => {
                tableRows += `<tr><td><strong>${user.name}</strong> (${user.username}) - ${user.email}</td></tr>`;
            })
            $('#userExpirationTable tbody').html(tableRows);
            $('#userExpirationModal').modal('show');
        }

        function openScheduleExpireUsersModal() {
            document.querySelector('#userExpirationSchedulerModal form').reset();
            $('#userExpirationSchedulerModal').modal('show');
        }

        function getSelectedUsers() {
            return $('.user-selector').toArray().map((el) => {
                if ($(el).find('input').is(':checked')) {
                    const row = $(el).closest('tr');
                    return {
                        username: $(row).find('td').eq(1).text(),
                        name: $(row).find('td').eq(2).text(),
                        email: $(row).find('td').eq(3).text()
                    };
                }
            }).filter((el) => el);
        }

        async function expireUsers() {
            const users = getSelectedUsers();
            await $.post("<?= $module->getUrl('expireUsers.php') ?>", {
                users: users.map(userRow => userRow["username"])
            })
                .fail(function (error) {
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
                        .then(function () {
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

            console.log(emailFormContents);

            $.post("<?= $module->getUrl('sendAlerts.php') ?>", emailFormContents)
                .done(response => {
                    console.log(response);
                    Swal.fire({
                        html: response
                    });
                })
                .fail(error => {
                    console.error(error.responseText);
                })
                .always({

                });
        }

        // TODO: We're going to validate form contents server-side eventually, so this is temporary
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
            const users = [];
            $('.user-selector input:checked').each((i, el) => {
                const row = $(el).closest('tr');
                users[i] = {
                    'sag_user': $(row).data('user'),
                    'sag_user_fullname': $(row).data('name'),
                    'sag_user_email': $(row).data('email'),
                    'sag_user_rights': $(row).data('rights')
                }
            });
            return users;
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

            console.log(emailFormContents);

            $.post("<?= $module->getUrl('sendAlerts.php') ?>", emailFormContents)
                .done(response => {
                    console.log(response);
                    Swal.fire({
                        html: response
                    });
                })
                .fail(error => {
                    console.error(error.responseText);
                })
                .always({

                });
        }

        // TODO: We're going to validate form contents server-side eventually, so this is temporary
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

            const anyChecked = $('#recipientTable_UserRightsHolders .user-rights-holder-selector input').toArray().some(el => $(el).is(':checked'));
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
            formContents.userRightsHoldersEmailBody = tinymce.get('emailBody-userExpiration-UserRightsHolders').getContent();
            formContents.alertType = 'expiration';
            formContents.users = users;
            formContents.recipients = getUserRightsHolderAlertRecipients('userExpirationForm');

            console.log(formContents);

            expireUsers().then(() => {
                if (!formContents.sendUserNotification && !formContents["sendNotification-userExpiration-UserRightsHolders"]) {
                    Toast.fire({
                        title: 'The user' + (users.length > 1 ? "s were " : " was ") + 'successfully expired.',
                        icon: 'success'
                    })
                        .then(function () {
                            window.location.reload();
                        });
                } else {
                    $.post("<?= $module->getUrl('sendAlerts.php') ?>", formContents)
                        .done(response => {
                            console.log(response);
                            Swal.fire({
                                html: response
                            });
                        })
                        .fail(error => {
                            console.error(error.responseText);
                        })
                        .always({

                        });
                }
            })
        }

        // TODO: We're going to validate form contents server-side eventually, so this is temporary
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

                const anyChecked = $('#recipientTable_userExpiration_UserRightsHolders .user-rights-holder-selector input').toArray().some(el => $(el).is(':checked'));
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
            return $(`#${form_id} .user-rights-holder-selector input:checked`).toArray().map(el => $(el).closest('tr').data('user'));
        }

        async function previewEmail($emailContainer) {
            const id = $emailContainer.find('textarea.emailBody').prop('id');
            const content = tinymce.get(id).getContent();
            const replacedContent = await replaceKeywordsPreview(content);
            $('#emailPreview div.modal-body').html(replacedContent);
            $emailContainer.closest('.modal').css('z-index', 1039);
            $('#emailPreview').modal('show');
            $('#emailPreview').on('hidden.bs.modal', function (event) {
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

            return $.post('<?= $module->getUrl('replaceSmartVariables.php') ?>', {
                text: text,
                data: data
            });
        }

        async function previewEmailUserRightsHolders($emailContainer) {
            const id = $emailContainer.find('textarea.emailBody').prop('id');
            const content = tinymce.get(id).getContent();
            console.log(content);
            const replacedContent = await replaceKeywordsPreviewUserRightsHolders(content);
            console.log(replacedContent);
            $('#emailPreview div.modal-body').html(replacedContent);
            $emailContainer.closest('.modal').css('z-index', 1039);
            $('#emailPreview').modal('show');
            $('#emailPreview').on('hidden.bs.modal', function (event) {
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
                    ['Data Export - Full Data Set', 'Data Viewing - View & Edit', 'Data Access Groups', 'Stats & Charts', 'Survey Distribution Tools', 'File Repository']
                ]
            };

            return $.post('<?= $module->getUrl('replaceSmartVariables.php') ?>', {
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
            $('#emailPreview').on('hidden.bs.modal', function (event) {
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

            return $.post('<?= $module->getUrl('replaceSmartVariables.php') ?>', {
                text: text,
                data: data
            });
        }

        $(document).ready(function () {
            $('.dataPlaceholder').popover({
                placement: 'top',
                html: true,
                content: '<span class="text-danger">Copied!</span>',
                show: function () {
                    $(this).fadeIn();
                },
                hide: function () {
                    $(this).fadeOut();
                }
            });

            const clipboard = new ClipboardJS('.dataPlaceholder', {
                text: function (trigger) {
                    return $(trigger).text();
                }
            });
            clipboard.on('success', function (e) {
                $(e.trigger).popover('show');
                setTimeout(function () {
                    $(e.trigger).popover('hide');
                }, 1000);
                e.clearSelection();
            });
            tinymce.init({
                entity_encoding: "raw",
                default_link_target: '_blank',
                selector: ".richtext",
                height: 350,
                branding: false,
                statusbar: true,
                menubar: true,
                elementpath: false,
                plugins: ['paste autolink lists link searchreplace code fullscreen table directionality hr'],
                toolbar1: 'formatselect | hr | bold italic underline link | fontsizeselect | alignleft aligncenter alignright alignjustify | undo redo',
                toolbar2: 'bullist numlist | outdent indent | table tableprops tablecellprops | forecolor backcolor | searchreplace code removeformat | fullscreen',
                contextmenu: "copy paste | link inserttable | cell row column deletetable",
                content_css: "<?= $module->getUrl('SystemUserRights.css') ?>",
                relative_urls: false,
                convert_urls: false,
                convert_fonts_to_spans: true,
                extended_valid_elements: 'i[class]',
                paste_word_valid_elements: "b,strong,i,em,h1,h2,u,p,ol,ul,li,a[href],span,color,font-size,font-color,font-family,mark,table,tr,td",
                paste_retain_style_properties: "all",
                paste_postprocess: function (plugin, args) {
                    args.node.innerHTML = cleanHTML(args.node.innerHTML);
                },
                remove_linebreaks: true
            });

            function handleDisplayUsersButton(allUsersVisible) {
                if (allUsersVisible) {
                    $('#displayUsersButton').addClass('btn-outline-secondary').removeClass('btn-secondary');
                } else {
                    $('#displayUsersButton').addClass('btn-secondary').removeClass('btn-outline-secondary');
                }
            }

            window.handleActionButtons = function () {
                if ($('.user-selector input').is(':checked')) {
                    $('.buttonContainer button.action').prop('disabled', false);
                } else {
                    $('.buttonContainer button.action').prop('disabled', true);
                }
            }

            const dt = $('table.discrepancy-table').DataTable({
                sort: false,
                //filter: false,
                paging: false,
                info: false,
                scrollY: '75vh',
                scrollCollapse: true,
                stateSave: true,
                stateDuration: 60 * 60 * 24 * 365,
                stateSaveCallback: function (settings, data) {
                    let checkboxStatus = {};
                    $('#userFilter input').toArray().forEach(el => checkboxStatus[el.id] = el.checked);
                    data.checkboxStatus = checkboxStatus;
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data))
                },
                stateLoadCallback: function (settings) {
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
                    delete (data.checkboxStatus);
                    return data;
                },
                dom: "t",
                initComplete: function () {
                    $('table.discrepancy-table').addClass('table');
                },
                columnDefs: [{
                    targets: [0],
                    data: function (row, type, val, meta) {
                        if (type === 'set') {
                            row.expired = $(val).data('expired') ? 'expired' : 'current';
                            row.discrepant = $(val).data('discrepant') ? 'discrepant' : 'compliant';
                            row.inputVal = val;
                        } else if (type === 'display') {
                            return row.inputVal;
                        } else if (type === 'filter') {
                            return [row.expired, row.discrepant].join(' ');
                        }
                        return row.inputVal;
                    }
                }]
            });

            $('#userFilter label').click(function (e) {
                e.stopPropagation()
            });
            $('#userFilter input').change(function (e) {
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
                    searchTerm += '(' + [discrepant, nonDiscrepant].filter(el => el).join('|') + ') ';
                } else {
                    searchTerm += 'none';
                }

                const allChecked = expiredChecked && nonExpiredChecked && discrepantChecked && nonDiscrepantChecked;
                handleDisplayUsersButton(allChecked);
                dt.columns(0).search(searchTerm, true).draw();
                window.handleActionButtons();
            });
        });
    </script>
</div>