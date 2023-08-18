const sag_module = __MODULE__;

$(function () {

    function createRightsTable(bad_rights) {
        return `<table class="table table-sm table-borderless table-hover w-50 mt-4 mx-auto" style="font-size:13px; cursor: default;"><tbody><tr><td>${bad_rights.join('</td></tr><tr><td>')}</td></tr></tbody></table>`;
    }

    function fixLinks() {
        $('#importUserForm').attr('action', "{{IMPORT_EXPORT_USERS_URL}}");
        $('#importUsersForm2').attr('action', "{{IMPORT_EXPORT_USERS_URL}}");
        $('#importRoleForm').attr('action', "{{IMPORT_EXPORT_ROLES_URL}}");
        $('#importRolesForm2').attr('action', "{{IMPORT_EXPORT_ROLES_URL}}");
        $('#importUserRoleForm').attr('action', "{{IMPORT_EXPORT_MAPPINGS_URL}}");
        $('#importUserRoleForm2').attr('action', "{{IMPORT_EXPORT_MAPPINGS_URL}}");
    }

    function checkImportErrors() {
        if (window.import_type) {
            let title = "";
            let text = "";
            if (window.import_type == "users") {
                title = sag_module.tt('bad_user_import_2');
                text =
                    `${sag_module.tt('bad_user_import_1')}<br><table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>${sag_module.tt('user')}</th><th>${sag_module.tt('sag')}</th><th>${sag_module.tt('permissions_1')}</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const users = Object.keys(window.import_errors);
                users.forEach((user) => {
                    text +=
                        `<tr style="border-top: 1px solid #666;"><td><strong>${user}</strong></td><td>${window.import_errors[user].SAG}</td><td>${window.import_errors[user].rights.join('<br>')}</td></tr>`;
                });
                text += `</tbody></table>`;
            } else if (window.import_type == "roles") {
                title = sag_module.tt('bad_role_import_1');
                text =
                    `${sag_module.tt('bad_role_import_2')}:<br><table style="margin-top: 20px; width: 100%; table-layout: fixed;"><thead style="border-bottom: 2px solid #666;"><tr><th>${sag_module.tt('user_role')}</th><th>${sag_module.tt('user')}</th><th>${sag_module.tt('sag')}</th><th COLSPAN=2>${sag_module.tt('permissions_1')}</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const roles = Object.keys(window.import_errors);
                roles.forEach((role) => {
                    const users = Object.keys(window.import_errors[role]);
                    users.forEach((user, index) => {
                        const theseRights = window.import_errors[role][user];
                        text +=
                            `<tr style='border-top: 1px solid black;'><td><strong>${role}</strong></td><td><strong>${user}</strong></td><td>${theseRights.SAG}</td><td COLSPAN=2>${theseRights.rights.join('<br>')}</td></tr>`;
                    });
                })
                text += `</tbody></table>`;
            } else if (window.import_type == "roleassignments") {
                title = sag_module.tt('bad_role_assignment_import_1');
                text =
                    `${sag_module.tt('bad_role_assigment_import_2')}:<br><table style="margin-top: 20px; width: 100%; table-layout: fixed;"><thead style="border-bottom: 2px solid #666;"><tr><th>${sag_module.tt('user_role')}</th><th>${sag_module.tt('user')}</th><th>${sag_module.tt('sag')}</th><th COLSPAN=2>${sag_module.tt('permissions_1')}</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                const roles = Object.keys(window.import_errors);
                roles.forEach((role) => {
                    const users = Object.keys(window.import_errors[role]);
                    users.forEach((user, index) => {
                        const theseRights = window.import_errors[role][user];
                        text +=
                            `<tr style='border-top: 1px solid black;'><td><strong>${role}</strong></td><td><strong>${user}</strong></td><td>${theseRights.SAG}</td><td COLSPAN=2>${theseRights.rights.join('<br>')}</td></tr>`;
                    });
                })
                text += `</tbody></table>`;
            }
            Swal.fire({
                icon: 'error',
                title: title,
                html: text,
                width: '900px',
                confirmButtonText: sag_module.tt('ok')
            });
        }
    }

    window.saveUserFormAjax = function () {
        showProgress(1);
        const permissions = $('form#user_rights_form').serializeObject();
        $.post('{{EDIT_USER_URL}}', permissions)
            .done(function (data) {
                // Edit went through normally
                showProgress(0, 0);
                if ($('#editUserPopup').hasClass('ui-dialog-content')) {
                    $('#editUserPopup').dialog('destroy');
                }
                $('#user_rights_roles_table_parent').html(data);
                simpleDialogAlt($('#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                enablePageJS();
                if ($('#copy_role_success').length) {
                    setTimeout(function () {
                        openAddUserPopup('', $('#copy_role_success').val());
                    }, 1500);
                }
            })
            .fail(function (response) {
                // There was an issue
                showProgress(0, 0);
                if (response.status == 403) {
                    try {
                        const data = response.responseText;
                        const result = JSON.parse(data);
                        if (!result.error || !result.bad_rights) {
                            return;
                        }
                        let title;
                        let text = "";
                        let users = Object.keys(result.bad_rights);
                        if (!result.role) {
                            title = sag_module.tt('bad_user_1', users[0]);
                            text = sag_module.tt('bad_user_2', result.bad_rights[users[0]].SAG) +
                                '<br>' + sag_module.tt('bad_user_3') +
                                createRightsTable(result.bad_rights[users[0]].rights);
                        } else {
                            title = sag_module.tt('bad_role_1') + '<br>' + result.role;
                            text =
                                `${sag_module.tt('bad_role_2')}:<br><table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>${sag_module.tt('user')}</th><th>${sag_module.tt('sag')}</th><th>${sag_module.tt('permissions_1')}</th></tr></thead><tbody style="border-bottom: 1px solid black;">`;
                            users.forEach((user) => {
                                text +=
                                    `<tr style="border-top: 1px solid #666;"><td><strong>${user}</strong></td><td>${result.bad_rights[user].SAG}</td><td>${result.bad_rights[user].rights.join('<br>')}</td></tr>`;
                            });
                            text += `</tbody></table>`;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: title,
                            html: text,
                            width: '900px',
                            confirmButtonText: sag_module.tt('ok')
                        });
                        return;
                    } catch (error) {
                        console.error(error);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: sag_module.tt('error_1'),
                        html: response.responseText,
                        width: '900px',
                        confirmButtonText: sag_module.tt('ok')
                    });
                }
            })
            .always(function () {
                fixLinks();
            });
    }

    window.assignUserRole = function (username, role_id) {
        showProgress(1);
        checkIfuserRights(username, role_id, function (data) {
            if (data == 1) {
                $.post('{{ASSIGN_USER_URL}}', {
                    username: username,
                    role_id: role_id,
                    notify_email_role: ($('#notify_email_role').prop('checked') ? 1 : 0),
                    group_id: $('#user_dag').val()
                })
                    .done(function (data) {
                        showProgress(0, 0);
                        if (data == '') {
                            alert(woops);
                            return;
                        }
                        $('#user_rights_roles_table_parent').html(data);
                        showProgress(0, 0);
                        simpleDialogAlt($(
                            '#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                        enablePageJS();
                        setTimeout(function () {
                            if (role_id == '0') {
                                simpleDialog(lang.rights_215, lang.global_03 + lang
                                    .colon + ' ' + lang.rights_214);
                            }
                        }, 3200);
                    })
                    .fail(function (response) {
                        showProgress(0, 0);
                        if (response.status == 403) {
                            try {
                                const data = response.responseText;
                                const result = JSON.parse(data);
                                if (!result.error || !result.bad_rights) {
                                    return;
                                }
                                let users = Object.keys(result.bad_rights);
                                const title = sag_module.tt('bad_role_assignment_1', [username, result.role]);
                                const text = sag_module.tt('bad_user_2', result.bad_rights[users[0]].SAG) +
                                    '<br>' + sag_module.tt('bad_role_assignment_2', result.role) +
                                    createRightsTable(result.bad_rights[users[0]].rights);

                                Swal.fire({
                                    icon: 'error',
                                    title: title,
                                    html: text,
                                    width: '750px',
                                    confirmButtonText: sag_module.tt('ok')
                                });
                                return;
                            } catch (error) {
                                console.error(error);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: sag_module.tt('error_1'),
                                html: response.responseText,
                                width: '900px',
                                confirmButtonText: sag_module.tt('ok')
                            });
                        }
                    })
                    .always(function () {
                        fixLinks();
                    });
            } else {
                showProgress(0, 0);
                setTimeout(function () {
                    simpleDialog(lang.rights_317, lang.global_03 + lang.colon + ' ' + lang
                        .rights_316);
                }, 500);
            }
            fixLinks();
        });
    }

    window.setExpiration = function () {
        $('#tooltipExpirationBtn').button('disable');
        $('#tooltipExpiration').prop('disabled', true);
        $('#tooltipExpirationCancel').hide();
        $('#tooltipExpirationProgress').show();
        $.post("{{SET_USER_EXPIRATION_URL}}", {
            username: $('#tooltipExpirationHiddenUsername').val(),
            expiration: $('#tooltipExpiration').val()
        })
            .done(function (data) {
                if (data == '0') {
                    alert(woops);
                    return;
                }
                $('#user_rights_roles_table_parent').html(data);
                enablePageJS();
            })
            .fail(function (response) {
                if (response.status == 403) {
                    const data = response.responseText;
                    try {
                        const result = JSON.parse(data);
                        if (!result.error || !result.bad_rights) {
                            return;
                        }
                        const users = Object.keys(result.bad_rights);
                        const title = sag_module.tt('bad_user_1', users[0]);
                        const text = sag_module.tt('bad_user_2', result.bad_rights[users[0]].SAG) +
                            '<br>' + sag_module.tt('bad_user_3') +
                            createRightsTable(result.bad_rights[users[0]].rights);

                        Swal.fire({
                            icon: 'error',
                            title: title,
                            html: text,
                            width: '750px',
                            confirmButtonText: sag_module.tt('ok')
                        });
                        return;
                    } catch (error) {
                        console.error(error);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: sag_module.tt('error_1'),
                        html: response.responseText,
                        width: '900px',
                        confirmButtonText: sag_module.tt('ok')
                    });
                }
            })
            .always(function () {
                setTimeout(function () {
                    $('#tooltipExpiration').prop('disabled', false);
                    $('#tooltipExpirationBtn').button('enable');
                    $('#tooltipExpirationCancel').show();
                    $('#tooltipExpirationProgress').hide();
                    $('#userClickExpiration').hide();
                }, 400);
                fixLinks();
            });
    }

    fixLinks();
    checkImportErrors();
});