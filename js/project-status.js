const module = __MODULE__;

console.log(performance.now());
console.time('dt');
const config = JSON.parse('{{CONFIG}}');
function handleCheckboxes(el) {
    const dt = $('#discrepancy-table').DataTable();
    const checked = $(el).prop('checked');
    dt.rows(function (idx, data, node) {
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
    const emailBodyTemplate = `{{USER_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('emailBody').setContent(emailBodyTemplate);

    const emailSubjectTemplate = `{{USER_EMAIL_SUBJECT_TEMPLATE_URL}}`;
    $('#emailSubject').val(emailSubjectTemplate);

    const reminderBodyTemplate = `{{USER_REMINDER_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('reminderBody').setContent(reminderBodyTemplate);

    const reminderSubjectTemplate = `{{USER_REMINDER_EMAIL_SUBJECT_TEMPLATE_URL}}`;
    $('#reminderSubject').val(reminderSubjectTemplate);
}

function openEmailUserRightsHoldersModal() {
    document.querySelector('#emailUserRightsHoldersModal form').reset();
    $('.collapse').collapse('hide');
    populateDefaultEmailUserRightsHoldersModal();
    $('#emailUserRightsHoldersModal').modal('show');
}

function populateDefaultEmailUserRightsHoldersModal() {
    const emailBodyTemplate = `{{USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('emailBody-UserRightsHolders').setContent(emailBodyTemplate);

    const emailSubjectTemplate = `{{USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE_URL}}`;
    $('#emailSubject-UserRightsHolders').val(emailSubjectTemplate);

    const reminderBodyTemplate = `{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('reminderBody-UserRightsHolders').setContent(reminderBodyTemplate);

    const reminderSubjectTemplate = `{{USER_RIGHTS_HOLDERS_REMINDER_EMAIL_SUBJECT_TEMPLATE_URL}}`;
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
    const userEmailBodyTemplate = `{{USER_EXPIRATION_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('emailBody-userExpiration').setContent(userEmailBodyTemplate);

    const userEmailSubjectTemplate =
        `{{USER_EXPIRATION_EMAIL_SUBJECT_TEMPLATE_URL}}`;
    $('#emailSubject-userExpiration').val(userEmailSubjectTemplate);

    const userRightsHolderEmailBodyTemplate = `{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_BODY_TEMPLATE_URL}}`;
    tinymce.get('emailBody-userExpiration-UserRightsHolders').setContent(userRightsHolderEmailBodyTemplate);

    const userRightsHolderEmailSubjectTemplate = `{{USER_EXPIRATION_USER_RIGHTS_HOLDERS_EMAIL_SUBJECT_TEMPLATE_URL}}`;
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
    await $.post("{{EXPIRE_USERS_URL}}", {
        users: users.map(userRow => userRow["username"]),
        delayDays: $('#delayDays-expiration').val(),
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

    console.log('ok');
    module.ajax('sendAlerts', { config: emailFormContents })
        .then(response => {
            console.log('success', response);
            const multiple = emailFormContents.users.length > 1;
            Toast.fire({
                title: 'The alert' + (multiple ? "s were " : " was ") +
                    'successfully sent.',
                icon: 'success'
            });
        })
        .catch(error => {
            console.error(error);
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

    $.post("{{SEND_ALERTS_URL}}", emailFormContents)
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
                .then(function () {
                    window.location.reload();
                });
        } else {
            $.post("{{SEND_ALERTS_URL}}", formContents)
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

    return $.post('{{REPLACE_SMART_VARIABLES_URL}}', {
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
            ['Data Export - Full Data Set', 'Data Viewing - View & Edit', 'Data Access Groups',
                'Stats & Charts', 'Survey Distribution Tools', 'File Repository'
            ]
        ]
    };

    return $.post('{{REPLACE_SMART_VARIABLES_URL}}', {
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

function handleActionButtons() {
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
        content_css: "{{SAG_CSS_URL}}",
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
        remove_linebreaks: true
    });
}

function initClipboard() {
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




    console.timeLog('dt', 'document ready');
    $('#sub-nav').removeClass('d-none');

    $(document).on('preInit.dt', function (e, settings) {
        $('#containerCard').show();
    });
    $(document).on('preXhr.dt', function (e, settings, json) {
        console.timeLog('dt', 'ajax start')
    });
    $(document).on('xhr.dt', function (e, settings, json) {
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
            handleDisplayUsersButton(allChecked);
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
                        `type="checkbox" onchange="handleActionButtons()"></input>` :
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
            title: 'Username',
            data: function (row, type, set, meta) {
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
            data: function (row, type, set, meta) {
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
            data: function (row, type, set, meta) {
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
            data: function (row, type, set, meta) {
                const hasDiscrepancy = row.bad.length > 0;
                if (hasDiscrepancy) {
                    let rows = '';
                    for (const rightI in row.bad) {
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
            data: function (row, type, set, meta) {
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
        createdRow: function (row, data, dataIndex) {
            let rowClass = data.bad.length > 0 ? 'table-danger-light' :
                'table-success-light';
            rowClass = data.isExpired ? 'text-secondary bg-light' : rowClass;
            $(row).attr('data-user', data.username);
            $(row).attr('data-email', data.email);
            $(row).attr('data-name', data.name);
            $(row).addClass(rowClass);
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
            createdCell: function (td) {
                $(td).addClass('align-middle text-center');
            }
        }, {
            targets: '_all',
            createdCell: function (td) {
                $(td).addClass('align-middle');
            }
        }],
        dom: "lftip",
        initComplete: function () {
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
        handleDisplayUsersButton(allChecked);
        dt.columns(0).search(searchTerm, true).draw();
        handleActionButtons();
    });
});