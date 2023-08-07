const sag_module = __MODULE__;

console.log(performance.now());
try {
    sag_module.config = JSON.parse('{{CONFIG}}');
} catch {
    sag_module.config = {
        ajax: function (data, callback, settings) {
            sag_module.ajax('getProjectUsers')
                .then(response => {
                    callback(JSON.parse(response));
                })
                .catch(error => {
                    console.error(error);
                    callback({ data: [] });
                });
        }
    }
}

sag_module.handleCheckboxes = function (el) {
    const checked = $(el).prop('checked');
    sag_module.dt.rows(function (idx, data, node) {
        return data.bad.length > 0
    }, {
        search: 'applied'
    }).select(checked);
    $('.user-selector input').prop('checked', checked).trigger('change');
}

sag_module.openEmailUsersModal = function () {
    document.querySelector('#emailUsersModal form').reset();
    $('.collapse').collapse('hide');
    sag_module.populateDefaultEmailUserModal();
    $('#emailUsersModal').modal('show');
}

sag_module.populateDefaultEmailUserModal = function () {
    const emailBodyTemplate = `{{USER_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('emailBody').setContent(emailBodyTemplate);

    const emailSubjectTemplate = `{{USER_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#emailSubject').val(emailSubjectTemplate);

    const reminderBodyTemplate = `{{USER_REMINDER_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('reminderBody').setContent(reminderBodyTemplate);

    const reminderSubjectTemplate = `{{USER_REMINDER_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#reminderSubject').val(reminderSubjectTemplate);
}

sag_module.openEmailUserRightsHoldersModal = function () {
    document.querySelector('#emailUserRightsHoldersModal form').reset();
    $('.collapse').collapse('hide');
    sag_module.populateDefaultEmailUserRightsHoldersModal();
    $('#emailUserRightsHoldersModal').modal('show');
}

sag_module.populateDefaultEmailUserRightsHoldersModal = function () {
    const emailBodyTemplate = `{{USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('emailBody-UserRightsHolders').setContent(emailBodyTemplate);

    const emailSubjectTemplate = `{{USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#emailSubject-UserRightsHolders').val(emailSubjectTemplate);

    const reminderBodyTemplate = `{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('reminderBody-UserRightsHolders').setContent(reminderBodyTemplate);

    const reminderSubjectTemplate = `{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#reminderSubject-UserRightsHolders').val(reminderSubjectTemplate);
}

sag_module.openExpireUsersModal = function () {
    document.querySelector('#userExpirationModal form').reset();
    $('#userNotificationInfo').collapse('hide');
    userExpirationUserRightsHoldersToggle(false);
    const usersToExpire = sag_module.getSelectedUsers();
    let tableRows = "";
    usersToExpire.forEach(user => {
        tableRows +=
            `<tr><td><strong>${user.username}</strong></td><td>${user.name}</td><td>${user.email}</td></tr>`;
    })
    $('#userExpirationTable tbody').html(tableRows);
    sag_module.populateDefaultExpireUsersModal();
    $('#userExpirationModal').modal('show');
}

sag_module.populateDefaultExpireUsersModal = function () {
    const userEmailBodyTemplate = `{{USER_EXPIRATION_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('emailBody-userExpiration').setContent(userEmailBodyTemplate);

    const userEmailSubjectTemplate =
        `{{USER_EXPIRATION_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#emailSubject-userExpiration').val(userEmailSubjectTemplate);

    const userRightsHolderEmailBodyTemplate = `{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE}}`;
    tinymce.get('emailBody-userExpiration-UserRightsHolders').setContent(userRightsHolderEmailBodyTemplate);

    const userRightsHolderEmailSubjectTemplate = `{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE}}`;
    $('#emailSubject-userExpiration-UserRightsHolders').val(userRightsHolderEmailSubjectTemplate);
}

sag_module.getSelectedUsers = function () {
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

sag_module.expireUsers = async function () {
    const users = sag_module.getSelectedUsers();
    await sag_module.ajax('expireUsers', {
        users: users.map(userRow => userRow["username"]),
        delayDays: $('#delayDays-expiration').val()
    })
        .catch(function (error) {
            console.error(error);
            Swal.fire({
                title: sag_module.tt('error_2'),
                html: error.responseText,
                icon: 'error',
                confirmButtonText: sag_module.tt('ok'),
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

sag_module.sendEmailAlerts = function () {
    if (!sag_module.validateEmailForm()) {
        return;
    }

    let emailFormContents = $('#emailUsersForm').serializeObject();
    emailFormContents.emailBody = tinymce.get('emailBody').getContent();
    emailFormContents.reminderBody = tinymce.get('reminderBody').getContent();
    emailFormContents.alertType = 'users';
    emailFormContents.users = sag_module.getAlertUserInfo();

    sag_module.ajax('sendAlerts', { config: emailFormContents })
        .then(response => {
            const multiple = emailFormContents.users.length > 1;
            const title = multiple ? sag_module.tt('status_ui_66') : sag_module.tt('status_ui_67');
            Toast.fire({
                title: title,
                icon: 'success'
            });
        })
        .catch(error => {
            console.error(error);
            Toast.fire({
                title: sag_module.tt('status_ui_68'),
                icon: 'error'
            });
        });
}

sag_module.validateEmailForm = function () {
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

sag_module.getAlertUserInfo = function () {
    return $('#discrepancy-table').DataTable().rows({
        selected: true
    }).data().toArray().map((el) => {
        return {
            'sag_user': el.username,
            'sag_user_fullname': el.name,
            'sag_user_email': el.email,
            'sag_user_sag': { 'sag_id': el.sag, 'sag_name': el.sag_name },
            'sag_user_rights': el.bad
        };
    });
}


sag_module.sendEmailAlerts_UserRightsHolders = function () {
    if (!sag_module.validateEmailForm_UserRightsHolders()) {
        return;
    }

    let emailFormContents = $('#emailUserRightsHoldersForm').serializeObject();
    emailFormContents.emailBody = tinymce.get('emailBody-UserRightsHolders').getContent();
    emailFormContents.reminderBody = tinymce.get('reminderBody-UserRightsHolders').getContent();
    emailFormContents.alertType = 'userRightsHolders';
    emailFormContents.users = sag_module.getAlertUserInfo();
    emailFormContents.recipients = sag_module.getUserRightsHolderAlertRecipients('emailUserRightsHoldersForm');

    sag_module.ajax('sendAlerts', { config: emailFormContents })
        .then(response => {
            const multiple = emailFormContents.recipients.length > 1;
            const title = multiple ? sag_module.tt('status_ui_66') : sag_module.tt('status_ui_67');
            Toast.fire({
                title: title,
                icon: 'success'
            });
        })
        .catch(error => {
            console.error(error);
            Toast.fire({
                title: sag_module.tt('status_ui_68'),
                icon: 'error'
            });
        });
}

sag_module.validateEmailForm_UserRightsHolders = function () {
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

sag_module.expireUsersAndSendAlerts = function () {
    if (!sag_module.validateEmailForm_UserExpiration()) {
        return;
    }
    const users = sag_module.getAlertUserInfo();
    let formContents = $('#userExpirationForm').serializeObject();
    formContents.usersEmailBody = tinymce.get('emailBody-userExpiration').getContent();
    formContents.userRightsHoldersEmailBody = tinymce.get('emailBody-userExpiration-UserRightsHolders')
        .getContent();
    formContents.alertType = 'expiration';
    formContents.users = users;
    formContents.recipients = sag_module.getUserRightsHolderAlertRecipients('userExpirationForm');

    sag_module.expireUsers().then(() => {
        if (!formContents.sendUserNotification && !formContents[
            "sendNotification-userExpiration-UserRightsHolders"]) {
            const multiple = users.length > 1;
            const title = multiple ? sag_module.tt('status_ui_69') : sag_module.tt('status_ui_70');
            Toast.fire({
                title: title,
                icon: 'success'
            })
                .then(function () {
                    window.location.reload();
                });
        } else {
            sag_module.ajax('sendAlerts', { config: formContents })
                .then(response => {
                    const multiple = users.length > 1;
                    const title = multiple ? sag_module.tt('status_ui_66') : sag_module.tt('status_ui_67');
                    Toast.fire({
                        title: title,
                        icon: 'success'
                    });
                })
                .catch(error => {
                    console.error(error);
                    Toast.fire({
                        title: sag_module.tt('status_ui_68'),
                        icon: 'error'
                    });
                });
        }
    })
}

sag_module.validateEmailForm_UserExpiration = function () {
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

sag_module.getUserRightsHolderAlertRecipients = function (form_id) {
    return $(`#${form_id} .user-rights-holder-selector input:checked`).toArray().map(el => {
        return $(el).closest('tr').data('user')
    });
}

sag_module.previewEmail = async function ($emailContainer) {
    const id = $emailContainer.find('textarea.emailBody').prop('id');
    const content = tinymce.get(id).getContent();
    const replacedContent = await sag_module.replaceKeywordsPreview(content);
    $('#emailPreview div.modal-body').html(replacedContent);
    $emailContainer.closest('.modal').css('z-index', 1039);
    $('#emailPreview').modal('show');
    $('#emailPreview').on('hidden.bs.modal', function (event) {
        $emailContainer.closest('.modal').css('z-index', 1060);
    });
}

sag_module.replaceKeywordsPreview = async function (text) {
    const data = {
        'sag_user': 'robin123',
        'sag_user_fullname': 'Robin Jones',
        'sag_user_email': 'robin.jones@email.com',
        'sag_user_sag': { 'sag_id': 'sag_Default', 'sag_name': 'Default SAG' },
        'sag_user_rights': ['Project Design and Setup', 'User Rights', 'Create Records'],
        'sag_expiration_date': '1970-01-01'
    };

    return sag_module.ajax('replacePlaceholders', { text: text, data: data });
}

sag_module.previewEmailUserRightsHolders = async function ($emailContainer) {
    const id = $emailContainer.find('textarea.emailBody').prop('id');
    const content = tinymce.get(id).getContent();
    const replacedContent = await sag_module.replaceKeywordsPreviewUserRightsHolders(content);
    $('#emailPreview div.modal-body').html(replacedContent);
    $emailContainer.closest('.modal').css('z-index', 1039);
    $('#emailPreview').modal('show');
    $('#emailPreview').on('hidden.bs.modal', function (event) {
        $emailContainer.closest('.modal').css('z-index', 1060);
    });
}

sag_module.replaceKeywordsPreviewUserRightsHolders = async function (text) {

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
        "sag_sags": [
            { 'sag_id': 'sag_Default', 'sag_name': 'Default SAG' },
            { 'sag_id': 'sag_12345678', 'sag_name': 'Example SAG 1' },
            { 'sag_id': 'sag_87654321', 'sag_name': 'Example SAG 2' }
        ],
        "sag_rights": [
            ['Project Design and Setup', 'User Rights', 'Create Records'],
            ['Logging', 'Reports & Report Builder'],
            ['Data Export - Full Data Set', 'Data Viewing - View & Edit', 'Data Access Groups',
                'Stats & Charts', 'Survey Distribution Tools', 'File Repository'
            ]
        ],
        "sag_expiration_date": '1970-01-01'
    };

    return sag_module.ajax('replacePlaceholders', { text: text, data: data });
}

sag_module.handleDisplayUsersButton = function (allUsersVisible) {
    if (allUsersVisible) {
        $('#displayUsersButton').addClass('btn-outline-secondary').removeClass('btn-secondary');
    } else {
        $('#displayUsersButton').addClass('btn-secondary').removeClass('btn-outline-secondary');
    }
}

sag_module.handleActionButtons = function () {
    if ($('#discrepancy-table').DataTable().rows({
        selected: true
    }).count() > 0) {
        $('.buttonContainer button.action').prop('disabled', false);
    } else {
        $('.buttonContainer button.action').prop('disabled', true);
    }
}

sag_module.addTinyMCETranslations = function () {
    tinymce.util.I18n.add('en', {
        "Redo": sag_module.tt('tiny_mce_ui_1'),
        "Undo": sag_module.tt('tiny_mce_ui_2'),
        "Cut": sag_module.tt('tiny_mce_ui_3'),
        "Copy": sag_module.tt('tiny_mce_ui_4'),
        "Paste": sag_module.tt('tiny_mce_ui_5'),
        "Select all": sag_module.tt('tiny_mce_ui_6'),
        "New document": sag_module.tt('tiny_mce_ui_7'),
        "Ok": sag_module.tt('tiny_mce_ui_8'),
        "Cancel": sag_module.tt('tiny_mce_ui_9'),
        "Visual aids": sag_module.tt('tiny_mce_ui_10'),
        "Bold": sag_module.tt('tiny_mce_ui_11'),
        "Italic": sag_module.tt('tiny_mce_ui_12'),
        "Underline": sag_module.tt('tiny_mce_ui_13'),
        "Strikethrough": sag_module.tt('tiny_mce_ui_14'),
        "Superscript": sag_module.tt('tiny_mce_ui_15'),
        "Subscript": sag_module.tt('tiny_mce_ui_16'),
        "Clear formatting": sag_module.tt('tiny_mce_ui_17'),
        "Align left": sag_module.tt('tiny_mce_ui_18'),
        "Align center": sag_module.tt('tiny_mce_ui_19'),
        "Align right": sag_module.tt('tiny_mce_ui_20'),
        "Justify": sag_module.tt('tiny_mce_ui_21'),
        "Bullet list": sag_module.tt('tiny_mce_ui_22'),
        "Numbered list": sag_module.tt('tiny_mce_ui_23'),
        "Decrease indent": sag_module.tt('tiny_mce_ui_24'),
        "Increase indent": sag_module.tt('tiny_mce_ui_25'),
        "Close": sag_module.tt('tiny_mce_ui_26'),
        "Formats": sag_module.tt('tiny_mce_ui_27'),
        "Your browser doesn't support direct access to the clipboard. Please use the Ctrl+X\/C\/V keyboard shortcuts instead.": sag_module.tt('tiny_mce_ui_28'),
        "Headers": sag_module.tt('tiny_mce_ui_29'),
        "Header 1": sag_module.tt('tiny_mce_ui_30'),
        "Header 2": sag_module.tt('tiny_mce_ui_31'),
        "Header 3": sag_module.tt('tiny_mce_ui_32'),
        "Header 4": sag_module.tt('tiny_mce_ui_33'),
        "Header 5": sag_module.tt('tiny_mce_ui_34'),
        "Header 6": sag_module.tt('tiny_mce_ui_35'),
        "Headings": sag_module.tt('tiny_mce_ui_36'),
        "Heading 1": sag_module.tt('tiny_mce_ui_37'),
        "Heading 2": sag_module.tt('tiny_mce_ui_38'),
        "Heading 3": sag_module.tt('tiny_mce_ui_39'),
        "Heading 4": sag_module.tt('tiny_mce_ui_40'),
        "Heading 5": sag_module.tt('tiny_mce_ui_41'),
        "Heading 6": sag_module.tt('tiny_mce_ui_42'),
        "Preformatted": sag_module.tt('tiny_mce_ui_43'),
        "Div": sag_module.tt('tiny_mce_ui_44'),
        "Pre": sag_module.tt('tiny_mce_ui_45'),
        "Code": sag_module.tt('tiny_mce_ui_46'),
        "Paragraph": sag_module.tt('tiny_mce_ui_47'),
        "Blockquote": sag_module.tt('tiny_mce_ui_48'),
        "Inline": sag_module.tt('tiny_mce_ui_49'),
        "Blocks": sag_module.tt('tiny_mce_ui_50'),
        "Paste is now in plain text mode. Contents will now be pasted as plain text until you toggle this option off.": sag_module.tt('tiny_mce_ui_51'),
        "Font Family": sag_module.tt('tiny_mce_ui_52'),
        "Font Sizes": sag_module.tt('tiny_mce_ui_53'),
        "Class": sag_module.tt('tiny_mce_ui_54'),
        "Browse for an image": sag_module.tt('tiny_mce_ui_55'),
        "OR": sag_module.tt('tiny_mce_ui_56'),
        "Drop an image here": sag_module.tt('tiny_mce_ui_57'),
        "Upload": sag_module.tt('tiny_mce_ui_58'),
        "Block": sag_module.tt('tiny_mce_ui_59'),
        "Align": sag_module.tt('tiny_mce_ui_60'),
        "Default": sag_module.tt('tiny_mce_ui_61'),
        "Circle": sag_module.tt('tiny_mce_ui_62'),
        "Disc": sag_module.tt('tiny_mce_ui_63'),
        "Square": sag_module.tt('tiny_mce_ui_64'),
        "Lower Alpha": sag_module.tt('tiny_mce_ui_65'),
        "Lower Greek": sag_module.tt('tiny_mce_ui_66'),
        "Lower Roman": sag_module.tt('tiny_mce_ui_67'),
        "Upper Alpha": sag_module.tt('tiny_mce_ui_68'),
        "Upper Roman": sag_module.tt('tiny_mce_ui_69'),
        "Anchor": sag_module.tt('tiny_mce_ui_70'),
        "Name": sag_module.tt('tiny_mce_ui_71'),
        "Id": sag_module.tt('tiny_mce_ui_72'),
        "Id should start with a letter, followed only by letters, numbers, dashes, dots, colons or underscores.": sag_module.tt('tiny_mce_ui_73'),
        "You have unsaved changes are you sure you want to navigate away?": sag_module.tt('tiny_mce_ui_74'),
        "Restore last draft": sag_module.tt('tiny_mce_ui_75'),
        "Special character": sag_module.tt('tiny_mce_ui_76'),
        "Source code": sag_module.tt('tiny_mce_ui_77'),
        "Insert\/Edit code sample": sag_module.tt('tiny_mce_ui_78'),
        "Language": sag_module.tt('tiny_mce_ui_79'),
        "Code sample": sag_module.tt('tiny_mce_ui_80'),
        "Color": sag_module.tt('tiny_mce_ui_81'),
        "R": sag_module.tt('tiny_mce_ui_82'),
        "G": sag_module.tt('tiny_mce_ui_83'),
        "B": sag_module.tt('tiny_mce_ui_84'),
        "Left to right": sag_module.tt('tiny_mce_ui_85'),
        "Right to left": sag_module.tt('tiny_mce_ui_86'),
        "Emoticons": sag_module.tt('tiny_mce_ui_87'),
        "Document properties": sag_module.tt('tiny_mce_ui_88'),
        "Title": sag_module.tt('tiny_mce_ui_89'),
        "Keywords": sag_module.tt('tiny_mce_ui_90'),
        "Description": sag_module.tt('tiny_mce_ui_91'),
        "Robots": sag_module.tt('tiny_mce_ui_92'),
        "Author": sag_module.tt('tiny_mce_ui_93'),
        "Encoding": sag_module.tt('tiny_mce_ui_94'),
        "Fullscreen": sag_module.tt('tiny_mce_ui_95'),
        "Action": sag_module.tt('tiny_mce_ui_96'),
        "Shortcut": sag_module.tt('tiny_mce_ui_97'),
        "Help": sag_module.tt('tiny_mce_ui_98'),
        "Address": sag_module.tt('tiny_mce_ui_99'),
        "Focus to menubar": sag_module.tt('tiny_mce_ui_100'),
        "Focus to toolbar": sag_module.tt('tiny_mce_ui_101'),
        "Focus to element path": sag_module.tt('tiny_mce_ui_102'),
        "Focus to contextual toolbar": sag_module.tt('tiny_mce_ui_103'),
        "Insert link (if link plugin activated)": sag_module.tt('tiny_mce_ui_104'),
        "Save (if save plugin activated)": sag_module.tt('tiny_mce_ui_105'),
        "Find (if searchreplace plugin activated)": sag_module.tt('tiny_mce_ui_106'),
        "Plugins installed ({0}):": sag_module.tt('tiny_mce_ui_107'),
        "Premium plugins:": sag_module.tt('tiny_mce_ui_108'),
        "Learn more...": sag_module.tt('tiny_mce_ui_109'),
        "You are using {0}": sag_module.tt('tiny_mce_ui_110'),
        "Plugins": sag_module.tt('tiny_mce_ui_111'),
        "Handy Shortcuts": sag_module.tt('tiny_mce_ui_112'),
        "Horizontal line": sag_module.tt('tiny_mce_ui_113'),
        "Insert\/edit image": sag_module.tt('tiny_mce_ui_114'),
        "Image description": sag_module.tt('tiny_mce_ui_115'),
        "Source": sag_module.tt('tiny_mce_ui_116'),
        "Dimensions": sag_module.tt('tiny_mce_ui_117'),
        "Constrain proportions": sag_module.tt('tiny_mce_ui_118'),
        "General": sag_module.tt('tiny_mce_ui_119'),
        "Advanced": sag_module.tt('tiny_mce_ui_120'),
        "Style": sag_module.tt('tiny_mce_ui_121'),
        "Vertical space": sag_module.tt('tiny_mce_ui_122'),
        "Horizontal space": sag_module.tt('tiny_mce_ui_123'),
        "Border": sag_module.tt('tiny_mce_ui_124'),
        "Insert image": sag_module.tt('tiny_mce_ui_125'),
        "Image": sag_module.tt('tiny_mce_ui_126'),
        "Image list": sag_module.tt('tiny_mce_ui_127'),
        "Rotate counterclockwise": sag_module.tt('tiny_mce_ui_128'),
        "Rotate clockwise": sag_module.tt('tiny_mce_ui_129'),
        "Flip vertically": sag_module.tt('tiny_mce_ui_130'),
        "Flip horizontally": sag_module.tt('tiny_mce_ui_131'),
        "Edit image": sag_module.tt('tiny_mce_ui_132'),
        "Image options": sag_module.tt('tiny_mce_ui_133'),
        "Zoom in": sag_module.tt('tiny_mce_ui_134'),
        "Zoom out": sag_module.tt('tiny_mce_ui_135'),
        "Crop": sag_module.tt('tiny_mce_ui_136'),
        "Resize": sag_module.tt('tiny_mce_ui_137'),
        "Orientation": sag_module.tt('tiny_mce_ui_138'),
        "Brightness": sag_module.tt('tiny_mce_ui_139'),
        "Sharpen": sag_module.tt('tiny_mce_ui_140'),
        "Contrast": sag_module.tt('tiny_mce_ui_141'),
        "Color levels": sag_module.tt('tiny_mce_ui_142'),
        "Gamma": sag_module.tt('tiny_mce_ui_143'),
        "Invert": sag_module.tt('tiny_mce_ui_144'),
        "Apply": sag_module.tt('tiny_mce_ui_145'),
        "Back": sag_module.tt('tiny_mce_ui_146'),
        "Insert date\/time": sag_module.tt('tiny_mce_ui_147'),
        "Date\/time": sag_module.tt('tiny_mce_ui_148'),
        "Insert link": sag_module.tt('tiny_mce_ui_149'),
        "Insert\/edit link": sag_module.tt('tiny_mce_ui_150'),
        "Text to display": sag_module.tt('tiny_mce_ui_151'),
        "Url": sag_module.tt('tiny_mce_ui_152'),
        "Target": sag_module.tt('tiny_mce_ui_153'),
        "None": sag_module.tt('tiny_mce_ui_154'),
        "New window": sag_module.tt('tiny_mce_ui_155'),
        "Remove link": sag_module.tt('tiny_mce_ui_156'),
        "Anchors": sag_module.tt('tiny_mce_ui_157'),
        "Link": sag_module.tt('tiny_mce_ui_158'),
        "Paste or type a link": sag_module.tt('tiny_mce_ui_159'),
        "The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?": sag_module.tt('tiny_mce_ui_160'),
        "The URL you entered seems to be an external link. Do you want to add the required http:\/\/ prefix?": sag_module.tt('tiny_mce_ui_161'),
        "Link list": sag_module.tt('tiny_mce_ui_162'),
        "Insert video": sag_module.tt('tiny_mce_ui_163'),
        "Insert\/edit video": sag_module.tt('tiny_mce_ui_164'),
        "Insert\/edit media": sag_module.tt('tiny_mce_ui_165'),
        "Alternative source": sag_module.tt('tiny_mce_ui_166'),
        "Poster": sag_module.tt('tiny_mce_ui_167'),
        "Paste your embed code below:": sag_module.tt('tiny_mce_ui_168'),
        "Embed": sag_module.tt('tiny_mce_ui_169'),
        "Media": sag_module.tt('tiny_mce_ui_170'),
        "Nonbreaking space": sag_module.tt('tiny_mce_ui_171'),
        "Page break": sag_module.tt('tiny_mce_ui_172'),
        "Paste as text": sag_module.tt('tiny_mce_ui_173'),
        "Preview": sag_module.tt('tiny_mce_ui_174'),
        "Print": sag_module.tt('tiny_mce_ui_175'),
        "Save": sag_module.tt('tiny_mce_ui_176'),
        "Find": sag_module.tt('tiny_mce_ui_177'),
        "Replace with": sag_module.tt('tiny_mce_ui_178'),
        "Replace": sag_module.tt('tiny_mce_ui_179'),
        "Replace all": sag_module.tt('tiny_mce_ui_180'),
        "Prev": sag_module.tt('tiny_mce_ui_181'),
        "Next": sag_module.tt('tiny_mce_ui_182'),
        "Find and replace...": sag_module.tt('tiny_mce_ui_183'),
        "Could not find the specified string.": sag_module.tt('tiny_mce_ui_184'),
        "Match case": sag_module.tt('tiny_mce_ui_185'),
        "Whole words": sag_module.tt('tiny_mce_ui_186'),
        "Spellcheck": sag_module.tt('tiny_mce_ui_187'),
        "Ignore": sag_module.tt('tiny_mce_ui_188'),
        "Ignore all": sag_module.tt('tiny_mce_ui_189'),
        "Finish": sag_module.tt('tiny_mce_ui_190'),
        "Add to Dictionary": sag_module.tt('tiny_mce_ui_191'),
        "Insert table": sag_module.tt('tiny_mce_ui_192'),
        "Table properties": sag_module.tt('tiny_mce_ui_193'),
        "Delete table": sag_module.tt('tiny_mce_ui_194'),
        "Cell": sag_module.tt('tiny_mce_ui_195'),
        "Row": sag_module.tt('tiny_mce_ui_196'),
        "Column": sag_module.tt('tiny_mce_ui_197'),
        "Cell properties": sag_module.tt('tiny_mce_ui_198'),
        "Merge cells": sag_module.tt('tiny_mce_ui_199'),
        "Split cell": sag_module.tt('tiny_mce_ui_200'),
        "Insert row before": sag_module.tt('tiny_mce_ui_201'),
        "Insert row after": sag_module.tt('tiny_mce_ui_202'),
        "Delete row": sag_module.tt('tiny_mce_ui_203'),
        "Row properties": sag_module.tt('tiny_mce_ui_204'),
        "Cut row": sag_module.tt('tiny_mce_ui_205'),
        "Copy row": sag_module.tt('tiny_mce_ui_206'),
        "Paste row before": sag_module.tt('tiny_mce_ui_207'),
        "Paste row after": sag_module.tt('tiny_mce_ui_208'),
        "Insert column before": sag_module.tt('tiny_mce_ui_209'),
        "Insert column after": sag_module.tt('tiny_mce_ui_210'),
        "Delete column": sag_module.tt('tiny_mce_ui_211'),
        "Cols": sag_module.tt('tiny_mce_ui_212'),
        "Rows": sag_module.tt('tiny_mce_ui_213'),
        "Width": sag_module.tt('tiny_mce_ui_214'),
        "Height": sag_module.tt('tiny_mce_ui_215'),
        "Cell spacing": sag_module.tt('tiny_mce_ui_216'),
        "Cell padding": sag_module.tt('tiny_mce_ui_217'),
        "Caption": sag_module.tt('tiny_mce_ui_218'),
        "Left": sag_module.tt('tiny_mce_ui_219'),
        "Center": sag_module.tt('tiny_mce_ui_220'),
        "Right": sag_module.tt('tiny_mce_ui_221'),
        "Cell type": sag_module.tt('tiny_mce_ui_222'),
        "Scope": sag_module.tt('tiny_mce_ui_223'),
        "Alignment": sag_module.tt('tiny_mce_ui_224'),
        "H Align": sag_module.tt('tiny_mce_ui_225'),
        "V Align": sag_module.tt('tiny_mce_ui_226'),
        "Top": sag_module.tt('tiny_mce_ui_227'),
        "Middle": sag_module.tt('tiny_mce_ui_228'),
        "Bottom": sag_module.tt('tiny_mce_ui_229'),
        "Header cell": sag_module.tt('tiny_mce_ui_230'),
        "Row group": sag_module.tt('tiny_mce_ui_231'),
        "Column group": sag_module.tt('tiny_mce_ui_232'),
        "Row type": sag_module.tt('tiny_mce_ui_233'),
        "Header": sag_module.tt('tiny_mce_ui_234'),
        "Body": sag_module.tt('tiny_mce_ui_235'),
        "Footer": sag_module.tt('tiny_mce_ui_236'),
        "Border color": sag_module.tt('tiny_mce_ui_237'),
        "Insert template": sag_module.tt('tiny_mce_ui_238'),
        "Templates": sag_module.tt('tiny_mce_ui_239'),
        "Template": sag_module.tt('tiny_mce_ui_240'),
        "Text color": sag_module.tt('tiny_mce_ui_241'),
        "Background color": sag_module.tt('tiny_mce_ui_242'),
        "Custom...": sag_module.tt('tiny_mce_ui_243'),
        "Custom color": sag_module.tt('tiny_mce_ui_244'),
        "No color": sag_module.tt('tiny_mce_ui_245'),
        "Table of Contents": sag_module.tt('tiny_mce_ui_246'),
        "Show blocks": sag_module.tt('tiny_mce_ui_247'),
        "Show invisible characters": sag_module.tt('tiny_mce_ui_248'),
        "Words {0}": sag_module.tt('tiny_mce_ui_249'),
        "{0} words": sag_module.tt('tiny_mce_ui_250'),
        "File": sag_module.tt('tiny_mce_ui_251'),
        "Edit": sag_module.tt('tiny_mce_ui_252'),
        "Insert": sag_module.tt('tiny_mce_ui_253'),
        "View": sag_module.tt('tiny_mce_ui_254'),
        "Format": sag_module.tt('tiny_mce_ui_255'),
        "Table": sag_module.tt('tiny_mce_ui_256'),
        "Tools": sag_module.tt('tiny_mce_ui_257'),
        "Powered by {0}": sag_module.tt('tiny_mce_ui_258'),
        "Rich Text Area. Press ALT-F9 for menu. Press ALT-F10 for toolbar. Press ALT-0 for help": sag_module.tt('tiny_mce_ui_259'),
        "Find and replace": sag_module.tt('tiny_mce_ui_260'),
        "Open link in...": sag_module.tt('tiny_mce_ui_261'),
        "Current window": sag_module.tt('tiny_mce_ui_262'),
        "Link...": sag_module.tt('tiny_mce_ui_263'),
        "Fonts": sag_module.tt('tiny_mce_ui_264'),
        "Line height": sag_module.tt('tiny_mce_ui_265'),
        "Cut column": sag_module.tt('tiny_mce_ui_266'),
        "Copy column": sag_module.tt('tiny_mce_ui_267'),
        "Paste column before": sag_module.tt('tiny_mce_ui_268'),
        "Paste column after": sag_module.tt('tiny_mce_ui_269'),
        "Border width": sag_module.tt('tiny_mce_ui_270'),
        "Show caption": sag_module.tt('tiny_mce_ui_271'),
        "Border style": sag_module.tt('tiny_mce_ui_272'),
        "Select...": sag_module.tt('tiny_mce_ui_273'),
        "Solid": sag_module.tt('tiny_mce_ui_274'),
        "Dotted": sag_module.tt('tiny_mce_ui_275'),
        "Dashed": sag_module.tt('tiny_mce_ui_276'),
        "Double": sag_module.tt('tiny_mce_ui_277'),
        "Groove": sag_module.tt('tiny_mce_ui_278'),
        "Ridge": sag_module.tt('tiny_mce_ui_279'),
        "Inset": sag_module.tt('tiny_mce_ui_280'),
        "Outset": sag_module.tt('tiny_mce_ui_281'),
        "Hidden": sag_module.tt('tiny_mce_ui_282'),
        "Light Green": sag_module.tt('tiny_mce_ui_283'),
        "Light Yellow": sag_module.tt('tiny_mce_ui_284'),
        "Light Red": sag_module.tt('tiny_mce_ui_285'),
        "Light Purple": sag_module.tt('tiny_mce_ui_286'),
        "Light Blue": sag_module.tt('tiny_mce_ui_287'),
        "Green": sag_module.tt('tiny_mce_ui_288'),
        "Yellow": sag_module.tt('tiny_mce_ui_289'),
        "Red": sag_module.tt('tiny_mce_ui_290'),
        "Purple": sag_module.tt('tiny_mce_ui_291'),
        "Blue": sag_module.tt('tiny_mce_ui_292'),
        "Dark Turquoise": sag_module.tt('tiny_mce_ui_293'),
        "Orange": sag_module.tt('tiny_mce_ui_294'),
        "Dark Red": sag_module.tt('tiny_mce_ui_295'),
        "Dark Purple": sag_module.tt('tiny_mce_ui_296'),
        "Dark Blue": sag_module.tt('tiny_mce_ui_297'),
        "Light Gray": sag_module.tt('tiny_mce_ui_298'),
        "Medium Gray": sag_module.tt('tiny_mce_ui_299'),
        "Gray": sag_module.tt('tiny_mce_ui_300'),
        "Dark Gray": sag_module.tt('tiny_mce_ui_301'),
        "Navy Blue": sag_module.tt('tiny_mce_ui_302'),
        "Black": sag_module.tt('tiny_mce_ui_303'),
        "White": sag_module.tt('tiny_mce_ui_304'),
        "Remove color": sag_module.tt('tiny_mce_ui_305'),
        "Color Picker": sag_module.tt('tiny_mce_ui_306'),
    });
}

sag_module.initTinyMCE = function () {
    sag_module.addTinyMCETranslations();
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
        content_css: sag_module.getUrl('SecurityAccessGroups.css', false),
        relative_urls: false,
        convert_urls: false,
        convert_fonts_to_spans: true,
        extended_valid_elements: 'i[class]',
        paste_word_valid_elements: "b,strong,i,em,h1,h2,u,p,ol,ul,li,a[href],span,color," +
            "font-size,font-color,font-family,mark,table,tr,td",
        paste_retain_style_properties: "all",
        paste_postprocess: function (plugin, args) {
            args.node.innerHTML = cleanHTML(args.node.innerHTML);
        },
        remove_linebreaks: true,
        language: 'en'
    });
}

sag_module.initClipboard = function () {

    $('.dataPlaceholder').popover({
        placement: 'top',
        html: true,
        content: `<span class="text-danger">${sag_module.tt('status_ui_71')}</span>`,
        show: function () {
            $(this).fadeIn();
        },
        hide: function () {
            $(this).fadeOut();
        }
    });

    $('.userAlert').each((index, modal) => {
        const clipboard = new ClipboardJS('.dataPlaceholder', {
            text: function (trigger) {
                return $(trigger).text();
            },
            container: modal
        });
        clipboard.on('success', function (e) {
            $(e.trigger).popover('show');
            setTimeout(function () {
                $(e.trigger).popover('hide');
            }, 1000);
            e.clearSelection();
        });
    });
}

// Set up "OR" search
sag_module.setUpOrSearch = function (table) {
    $('.dataTables_filter input').off().on('input', function () {
        const searchTerm = $(this).val();
        if (searchTerm.includes('|')) {
            const newTerm = searchTerm.split('|').map(term => '(' + term.replaceAll('"', '').trim() + ')').filter(term => term && term != '()').join('|');
            table.search(newTerm, true, false, true).draw();
        } else {
            table.search(searchTerm, false, true, true).draw();
        }
        this.value = searchTerm;
    });
}

$(document).ready(function () {

    window.Toast = Swal.mixin({
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

    $('#sub-nav').removeClass('d-none');

    $(document).on('preInit.dt', function (e, settings) {
        $('#containerCard').show();
    });

    sag_module.dt = $('table.discrepancy-table').DataTable(Object.assign(sag_module.config, {
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
        stateSaveCallback: function (settings, data) {
            let checkboxStatus = {};
            $('#userFilter input').toArray().forEach(el => checkboxStatus[el.id] = el
                .checked);
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
            sag_module.handleDisplayUsersButton(allChecked);
            delete (data.checkboxStatus);
            return data;
        },
        columns: [{
            data: function (row, type, set, meta) {
                const hasDiscrepancy = row.bad.length > 0;
                row.expired = row.isExpired ? 'expired' : 'current';
                row.discrepant = hasDiscrepancy ? 'discrepant' :
                    'compliant';
                row.inputVal =
                    `<div data-discrepant="${hasDiscrepancy}" data-expired="${row.isExpired}">` +
                    (hasDiscrepancy ?
                        `<input style="display:block; margin: 0 auto;" ` +
                        `type="checkbox" onchange="sag_module.handleActionButtons()"></input>` :
                        "") +
                    `</div>`;
                if (type === 'filter') {
                    return [row.expired, row.discrepant].join(' ');
                }
                return row.inputVal;
            },
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).css('vertical-align', 'middle !important;');
                $(td).addClass('user-selector');
            }
        }, {
            title: sag_module.tt('status_ui_59'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return !row.isExpired ? `<strong>${row.username}</strong>` :
                        row.username;
                } else {
                    return row.username;
                }
            },
        }, {
            title: sag_module.tt('status_ui_60'),
            data: 'name'
        },
        {
            title: sag_module.tt('status_ui_61'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return `<a href="mailto:${row.email}">${row.email}</a>`;
                } else {
                    return row.email;
                }
            }
        },
        {
            title: sag_module.tt('status_ui_62'),
            data: 'expiration'
        },
        {
            title: sag_module.tt('status_ui_63'),
            data: function (row, type, set, meta) {
                if (row.sag) {
                    return `<strong>${row.sag_name}</strong> <span>(<span class="user-select-all">` +
                        `${row.sag}</span>)</span>`;
                } else {
                    return `<span class="text-secondary">${sag_module.tt('status_ui_72')}</span>`;
                }
            }
        },
        {
            title: sag_module.tt('status_ui_64'),
            data: function (row, type, set, meta) {
                const hasDiscrepancy = row.bad.length > 0;
                if (hasDiscrepancy) {
                    let rows = '';
                    for (const rightI in row.bad) {
                        const right = row.bad[rightI];
                        rows +=
                            `<tr style='cursor: default;'><td style="background-color: white !important;"><span>${right}</span></td></tr>`;
                    }
                    return `<a class="${row.isExpired ? "text-secondary" : "text-primary"}" ` +
                        `style="text-decoration: underline; cursor: pointer;" data-bs-toggle="modal" data-toggle="modal" ` +
                        `data-target="#modal-${row.username}" data-bs-target="#modal-${row.username}">${row.bad.length} ` +
                        (row.bad.length > 1 ? sag_module.tt('status_ui_73') : sag_module.tt('status_ui_74')) +
                        `</a>` +
                        `<div class="modal fade" id="modal-${row.username}" tabindex="-1" aria-hidden="true">` +
                        `<div class="modal-dialog modal-dialog-scrollable">` +
                        `<div class="modal-content">` +
                        `<div class="modal-header bg-dark text-light">` +
                        `<h5 class="m-0">` +
                        sag_module.tt('status_ui_75', [row.name, row.username]) +
                        `</h5>` +
                        `</div>` +
                        `<div class="modal-body">` +
                        `<div class="d-flex justify-content-center">` +
                        `<table class="table table-sm table-hover table-borderless mb-0 table-default">` +
                        `<tbody>${rows}</tbody>` +
                        `</table>` +
                        `</div>` +
                        `</div>` +
                        `</div>` +
                        `</div>` +
                        `</div>`;
                } else {
                    return "<i class='fa-sharp fa-check mr-1 text-success'></i>" + sag_module.tt('status_ui_72');
                }
            }
        },
        {
            title: sag_module.tt('status_ui_65'),
            data: function (row, type, set, meta) {
                if (row.project_role) {
                    return `<strong>${row.project_role_name}</strong> <span>(<span class="user-select-all">` +
                        row.project_role +
                        `</span>)</span>`;
                } else {
                    return `<span class="text-secondary">${sag_module.tt('status_ui_72')}</span>`;
                }
            }
        }
        ],
        createdRow: function (row, data, dataIndex) {
            let rowClass = data.bad.length > 0 ? 'table-danger-light' :
                'table-success-light';
            rowClass = data.isExpired ? 'table-expired' : rowClass;
            $(row).attr('data-user', data.username);
            $(row).attr('data-email', data.email);
            $(row).attr('data-name', data.name);
            $(row).addClass(rowClass);
            $(row).find('td').addClass(rowClass);
        },
        drawCallback: function (settings) {
            const api = this.api();
            api.rows({
                page: 'current'
            }).every(function (rowIdx, tableLoop, rowLoop) {
                const row = api.row(rowIdx);
                const rowNode = row.node();
                const checkbox = $(rowNode).find('input[type="checkbox"]');
                checkbox.prop('checked', row.selected());
            });
        },
        columnDefs: [{
            targets: [4, 5, 6, 7],
            className: 'text-center'
        }, {
            targets: '_all',
            className: 'align-middle SAG'
        }],
        dom: "lftip",
        initComplete: function () {
            $('table.discrepancy-table').addClass('table');
            this.api().columns.adjust().draw();
            sag_module.initTinyMCE();
            sag_module.initClipboard();
            sag_module.setUpOrSearch(this.api());
            console.log(performance.now());
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, sag_module.tt('alerts_37')]
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_status_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_status_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_status_empty_table'),
            info: sag_module.tt('dt_status_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_status_info_empty'),
            lengthMenu: sag_module.tt('dt_status_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_status_loading_records'),
            zeroRecords: sag_module.tt('dt_status_zero_records'),
            select: {
                rows: {
                    _: sag_module.tt('dt_status_select_rows_other'),
                    0: sag_module.tt('dt_status_select_rows_zero'),
                    1: sag_module.tt('dt_status_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_status_paginate_first'),
                last: sag_module.tt('dt_status_paginate_last'),
                next: sag_module.tt('dt_status_paginate_next'),
                previous: sag_module.tt('dt_status_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_status_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_status_aria_sort_descending')
            }
        }
    }));

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
            searchTerm += '(' + [discrepant, nonDiscrepant].filter(el => el).join('|') +
                ') ';
        } else {
            searchTerm += 'none';
        }

        const allChecked = expiredChecked && nonExpiredChecked && discrepantChecked &&
            nonDiscrepantChecked;
        sag_module.handleDisplayUsersButton(allChecked);
        sag_module.dt.columns(0).search(searchTerm, true).draw();
        sag_module.handleActionButtons();
    });
});