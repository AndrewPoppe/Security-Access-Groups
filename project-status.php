<?php

namespace YaleREDCap\SystemUserRights;

require_once "Alerts.php";

use YaleREDCap\SystemUserRights\Alerts;

$Alerts = new Alerts($module);
var_dump($Alerts->getAllAlertsIds());
var_dump($Alerts->getAllAlertsIds(18));
var_dump($Alerts->getAllAlertsIds(19));

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/015226af80.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />

<!-- Modal -->
<div class="hidden">
    <div id="infoContainer" class="modal-body p-4 text-center" style="font-size:x-large;">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit metus, venenatis in congue sed, ultrices sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla at auctor volutpat, urna odio ornare nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis vel egestas. Integer eu purus vel dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices. Aenean consectetur efficitur leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum erat, vitae convallis ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec quam blandit, vel faucibus turpis convallis.
    </div>
</div>

<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-user-secret'></i>&nbsp;<span>System User Rights</span>
    </div>

    <?php
    $project_id = $module->getProjectId();
    $adminUsername = $module->getUser()->getUsername();
    $discrepantRights = $module->getUsersWithBadRights($project_id);
    if (empty($discrepantRights)) {
        exit();
    }
    ?>
    <div>
        <p style="font-size:large;">Check User Rights</p>
        <p>Current users in the ...</p>
    </div>
    <div class="buttonContainer mb-2">
        <button type="button" class="btn btn-xs btn-primary" onclick="openEmailUsersModal();" disabled><i class="fa-sharp fa-regular fa-envelope"></i> Email User(s)</button>
        <button type="button" class="btn btn-xs btn-warning" onclick="openEmailUserRightsHoldersModal();" disabled><i class="fa-kit fa-sharp-regular-envelope-circle-exclamation"></i> Email User Rights Holders</button>
        <div class="btn-group dropdown" role="group">
            <button type="button" class="btn btn-danger btn-xs dropdown-toggle mr-1" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                <i class="fa-solid fa-user-xmark mr-1"></i>
                <span>Expire User(s)</span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" onclick="expireUsers();"><i class="fa-solid fa-user-xmark fa-fw mr-1 text-danger"></i>Expire User(s) now</a></li>
                <li><a class="dropdown-item" onclick="openExpireUsersModal();"><i class="fa-sharp fa-solid fa-calendar-days fa-fw mr-1 text-success"></i>Schedule Expiration of User(s)</a></li>
            </ul>
            <i class="fa-solid fa-circle-info fa-lg align-self-center text-info" style="cursor:pointer;" onclick="Swal.fire({html: $('#infoContainer').html(), icon: 'info', showConfirmButton: false});"></i>
        </div>
    </div>
    <div class="container ml-0 pl-0">
        <table class="table table-sm table-bordered discrepancy-table">
            <thead class="thead-dark">
                <tr>
                    <th style="vertical-align: middle !important;"><input style="display:block; margin: 0 auto;" type="checkbox" onchange="$('.user-selector input').prop('checked', $(this).prop('checked')).trigger('change');"></input></th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Expiration</th>
                    <th>System Role</th>
                    <th>Discrepant Rights</th>
                    <th>Project Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($discrepantRights as $user => $thisUsersRights) {
                    $badRights = $thisUsersRights["bad"];
                    $hasDiscrepancy = !empty($badRights);
                    $isExpired = $thisUsersRights["expiration"] !== "never" && strtotime($thisUsersRights["expiration"]) < strtotime("today");
                    $rowClass = $hasDiscrepancy ? "table-danger" : ""; //"table-success";
                    $rowClass = $isExpired ? "text-secondary bg-light" : $rowClass; ?>
                    <tr data-user="<?= $user ?>" data-email="<?= $thisUsersRights["email"] ?>" data-name="<?= $thisUsersRights["name"] ?>" data-rights="<?= htmlspecialchars(json_encode($badRights)) ?>" class="<?= $rowClass ?>">
                        <td style="vertical-align: middle !important;" class="align-middle user-selector"><?= $hasDiscrepancy ? '<input style="display:block; margin: 0 auto;" type="checkbox"></input>' : '' ?></td>
                        <td class="align-middle"><?= $isExpired ? $user : "<strong>$user</strong>" ?></td>
                        <td class="align-middle"><?= $thisUsersRights["name"] ?></td>
                        <td class="align-middle"><?= $thisUsersRights["email"] ?></td>
                        <td class="align-middle"><?= $thisUsersRights["expiration"] ?></td>
                        <td class="align-middle"><?= $thisUsersRights["system_role"] ?></td>
                        <td class="align-middle <?= $hasDiscrepancy ? "" : "table-success" ?>">
                            <?php
                            if ($hasDiscrepancy) { ?>
                                <a class="<?= $isExpired ? "text-secondary" : "text-primary" ?>" style="text-decoration: underline; cursor: pointer;" data-toggle="modal" data-target="#modal-<?= $user ?>"><?= sizeof($badRights) . (sizeof($badRights) > 1 ? " Rights" : " Right") ?></a>
                                <div class="modal fade" id="modal-<?= $user ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header bg-dark text-light">
                                                <h5 class="m-0">Discrepant Rights for <?= $thisUsersRights["name"] . " (" . $user . ")" ?></h5>
                                            </div>
                                            <div class="modal-body">
                                                <div class="d-flex justify-content-center">
                                                    <table class="table table-sm table-hover table-borderless mb-0">
                                                        <tbody>
                                                            <?php foreach ($badRights as $right) {
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
                        <td class="align-middle"><?= $thisUsersRights["project_role"] ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php $Alerts->getUserEmailModal($project_id, $adminUsername); ?>
    <?php $Alerts->getUserRightsHoldersEmailModal($project_id, $adminUsername); ?>
    <?php $Alerts->getUserExpirationSchedulerModal($project_id); ?>
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
            document.querySelector('#userExpirationSchedulerModal form').reset();
            $('#userExpirationSchedulerModal').modal('show');
        }

        function expireUsers() {
            const users = $('.user-selector').toArray().map((el) => {
                if ($(el).find('input').is(':checked')) {
                    const row = $(el).closest('tr');
                    return {
                        username: $(row).find('td').eq(1).text(),
                        name: $(row).find('td').eq(2).text(),
                        email: $(row).find('td').eq(3).text()
                    };
                }
            }).filter((el) => el);

            let table = "<table class='table'><thead><tr><th>Username</th><th>Name</th><th>Email</th></tr></thead><tbody>";
            users.forEach((userRow) => {
                table += `<tr><td>${userRow["username"]}</td><td>${userRow["name"]}</td><td>${userRow["email"]}</td></tr>`;
            });
            table += "</tbody></table>";

            Swal.fire({
                    title: `Are you sure you want to expire ${users.length > 1 ? "these users" : "this user"} in this project?`,
                    html: table,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: 'Expire User' + (users.length > 1 ? "s" : ""),
                    customClass: {
                        confirmButton: 'btn btn-danger order-2',
                        cancelButton: 'btn btn-secondary order-1 mr-2',
                        icon: "text-danger border-danger"
                    },
                    buttonsStyling: false
                })
                .then((response) => {
                    if (response.isConfirmed) {
                        $.post("<?= $module->getUrl('expireUsers.php') ?>", {
                                users: users.map(userRow => userRow["username"])
                            })
                            .done(function(response) {
                                Toast.fire({
                                        title: 'The user' + (users.length > 1 ? "s were " : " was ") + 'successfully expired.',
                                        icon: 'success'
                                    })
                                    .then(function() {
                                        window.location.reload();
                                    });
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
                            })
                    }
                })
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
                    'sag_rights': $(row).data('rights')
                }
            });
            return users;
        }

        async function previewEmail($emailContainer) {
            const id = $emailContainer.find('textarea.emailBody').prop('id');
            const content = tinymce.get(id).getContent();
            const replacedContent = await replaceKeywordsPreview(content);
            $('#emailPreview div.modal-body').html(replacedContent);
            $('#emailUsersModal').css('z-index', 1039);
            $('#emailPreview').modal('show');
            $('#emailPreview').on('hidden.bs.modal', function(event) {
                $('#emailUsersModal').css('z-index', 1050);
            });
        }

        async function replaceKeywordsPreview(text) {
            const data = {
                'sag_user': 'robin123',
                'sag_user_fullname': 'Robin Jones',
                'sag_user_email': 'robin.jones@email.com',
                'sag_rights': ['Project Design and Setup', 'User Rights', 'Create Records']
            };

            return $.post('<?= $module->getUrl('replaceSmartVariables.php') ?>', {
                text: text,
                data: data
            });
        }

        async function previewEmailUserRightsHolders($emailContainer) {
            const id = $emailContainer.find('textarea.emailBody').prop('id');
            const content = tinymce.get(id).getContent();
            const replacedContent = await replaceKeywordsPreviewUserRightsHolders(content);
            $('#emailPreview-UserRightsHolders div.modal-body').html(replacedContent);
            $('#emailUserRightsHoldersModal').css('z-index', 1039);
            $('#emailPreview-UserRightsHolders').modal('show');
            $('#emailPreview-UserRightsHolders').on('hidden.bs.modal', function(event) {
                $('#emailUserRightsHoldersModal').css('z-index', 1050);
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

        $(document).ready(function() {
            $('.user-selector input').change(function(event) {
                if ($('.user-selector input').is(':checked')) {
                    $('.buttonContainer button').prop('disabled', false);
                } else {
                    $('.buttonContainer button').prop('disabled', true);
                }
            });

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
                paste_postprocess: function(plugin, args) {
                    args.node.innerHTML = cleanHTML(args.node.innerHTML);
                },
                remove_linebreaks: true
            });
        });
    </script>
</div>