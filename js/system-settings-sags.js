const sag_module = __MODULE__;
console.log(performance.now());
console.time('dt');

sag_module.openSagEditor = function (sag_id = "", sag_name = "", newSag = false) {
    const deleteSagButtonCallback = function () {
        Swal.fire({
            title: sag_module.tt('cc_sags_11'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: sag_module.tt('cc_sags_12'),
            cancelButtonText: sag_module.tt('cancel'),
            customClass: {
                confirmButton: 'btn btn-danger m-1',
                cancelButton: 'btn btn-secondary m-1'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                sag_module.ajax('deleteSag', { sag_id: sag_id })
                    .then((response) => {
                        const result = JSON.parse(response);
                        if (result.status != 'error') {
                            Toast.fire({
                                title: sag_module.tt('cc_sags_13'),
                                icon: 'success'
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                title: sag_module.tt('error_2'),
                                html: result.message,
                                icon: 'error',
                                confirmButtonText: sag_module.tt('ok'),
                                customClass: {
                                    confirmButton: 'btn btn-primary',
                                },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            }
        });
    };
    const copySagButtonCallback = function () {
        const data = $("#SAG_Setting").serializeObject();
        Swal.fire({
            title: sag_module.tt('cc_sags_14'),
            input: 'text',
            inputValue: `${data["sag_name_edit"]} ${sag_module.tt('cc_sags_15')}}`,
            showCancelButton: true,
            confirmButtonText: sag_module.tt('cc_sags_16'),
            cancelButtonText: sag_module.tt('cancel'),
            customClass: {
                confirmButton: 'btn btn-info m-1',
                cancelButton: 'btn btn-secondary m-1'
            },
            buttonsStyling: false
        })
            .then(function (result) {
                if (result.isConfirmed) {
                    const sag_name = result.value;
                    data.sag_name_edit = sag_name;
                    data.newSag = '1';
                    data.subaction = 'save';
                    sag_module.ajax('editSag', data)
                        .then((response) => {
                            const result = JSON.parse(response);
                            if (result.status != 'error') {
                                Toast.fire({
                                    icon: "success",
                                    title: sag_module.tt('cc_sags_17')
                                }).then(function () {
                                    window.location.reload();
                                });
                            } else {
                                Toast.fire({
                                    icon: "error",
                                    title: sag_module.tt('cc_sags_18')
                                });
                            }
                        })
                        .catch((error) => {
                            console.error(error);
                        });
                }
            })
    };
    const saveSagChangesButtonCallback = function () {
        $('input[name="sag_name_edit"]').blur();
        const sag_name_edit = $('input[name="sag_name_edit"]').val();
        if (sag_name_edit != '') {
            const data = $("#SAG_Setting").serializeObject();
            data.sag_id = sag_id;
            data.newSag = '0';
            data.subaction = 'save';
            sag_module.ajax('editSag', data)
                .then((response) => {
                    const result = JSON.parse(response);
                    if (result.status != 'error') {
                        Toast.fire({
                            icon: "success",
                            title: sag_module.tt('cc_sags_19', sag_name_edit)
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Toast.fire({
                            icon: "error",
                            title: sag_module.tt('cc_sags_20', sag_name_edit)
                        });
                    }
                })
                .catch((error) => {
                    console.error(error);
                });
        }
    };
    const saveNewSagButtonCallback = function () {
        $('input[name="sag_name_edit"]').blur();
        const sag_name_edit = $('input[name="sag_name_edit"]').val();
        if (sag_name_edit != '') {
            const data = $("#SAG_Setting").serializeObject();
            data.newSag = '1';
            data.subaction = 'save';
            sag_module.ajax('editSag', data)
                .then((response) => {
                    const result = JSON.parse(response);
                    if (result.status != 'error') {
                        Toast.fire({
                            icon: "success",
                            title: sag_module.tt('cc_sags_21')
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Toast.fire({
                            icon: "error",
                            title: sag_module.tt('cc_sags_22')
                        });
                    }
                })
                .catch((error) => {
                    console.error(error);
                });
        }
    };

    sag_module.ajax('editSag', { subaction: 'get', sag_id: sag_id, sag_name: sag_name, newSag: newSag })
        .then((response) => {
            const form = JSON.parse(response).form;
            $("#edit_sag_popup").html(form);
            $("#edit_sag_popup").on('shown.bs.modal', function (event) {
                $('input[name="sag_name_edit"]').blur(function () {
                    $(this).val($(this).val().trim());
                    if ($(this).val() == '') {
                        Swal.fire({
                            title: '{{USER_RIGHTS_ERROR_MESSAGE}}',
                            icon: 'error',
                            confirmButtonText: sag_module.tt('ok'),
                            customClass: {
                                confirmButton: 'btn btn-primary',
                            },
                            buttonsStyling: false
                        })
                            .then(() => {
                                $('input[name=sag_name_edit]').focus();
                            })
                    }
                });
                $('#SAG_Save').click(sag_id == "" ? saveNewSagButtonCallback :
                    saveSagChangesButtonCallback);
                if ($('#SAG_Copy')) $('#SAG_Copy').click(copySagButtonCallback);
                if ($('#SAG_Delete')) $('#SAG_Delete').click(deleteSagButtonCallback);
            })
            $("#edit_sag_popup").modal('show');
        })
        .catch(function (error) {
            console.error(error);
        });
}

sag_module.editSag = function (sag_id, sag_name) {
    sag_module.openSagEditor(sag_id, sag_name, false);
}

sag_module.addNewSag = function () {
    $('#addSagButton').blur();
    const newSagName = $('#newSagName').val().trim();
    if (newSagName == "") {
        Toast.fire({
            title: sag_module.tt('cc_sags_23'),
            icon: "error",
            showConfirmButton: false,
            didClose: () => {
                $("#newSagName").focus()
            }
        });
    } else {
        sag_module.openSagEditor("", newSagName, true);
    }
}

sag_module.formatNow = function () {
    const d = new Date();
    return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) +
        '-' + (d.getDate()).toString().padStart(2, 0);
}

sag_module.join = function (a, separator, boundary, escapeChar, reBoundary) {
    let s = '';
    for (let i = 0, ien = a.length; i < ien; i++) {
        if (i > 0) {
            s += separator;
        }
        s += boundary ?
            boundary + ('' + a[i]).replace(reBoundary, escapeChar + boundary) + boundary :
            a[i];
    }
    return s;
};

sag_module.exportRawCsv = function (includeData = true) {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = (includeData ?
        'SecurityAccessGroups_Raw_' + sag_module.formatNow() :
        'SecurityAccessGroups_ImportTemplate') + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const rowSelector = includeData ? undefined : -1;
    const data = sag_module.dt.buttons.exportData({
        format: {
            header: function (html, col, node) {
                const key = $(node).data('key');
                return key;
            },
            body: function (html, row, col, node) {
                if (col === 0) {
                    return;
                } else if (col === 1) {
                    return $(html).text();
                } else if (col === 2) {
                    return html;
                } else {
                    const value = $(node).data('value');
                    return value == '' ? 0 : value;
                }
            }
        },
        customizeData: function (data) {
            data.header.shift();
            data.body.forEach(row => row.shift());
            return data;
        },
        rows: rowSelector,
        columns: 'export:name'
    });

    const header = sag_module.join(data.header, separator, boundary, escapeChar, reBoundary) + newLine;
    const footer = data.footer ? newLine + sag_module.join(data.footer, separator, boundary, escapeChar, reBoundary) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(sag_module.join(data.body[i], separator, boundary, escapeChar, reBoundary));
    }

    const result = {
        str: header + body.join(newLine) + footer,
        rows: body.length
    };

    $.fn.dataTable.fileSave(new Blob([result.str], {
        type: 'text/csv' + charset
    }),
        filename,
        true);
}

sag_module.exportCsv = function () {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = 'SecurityAccessGroups_Labels_' + sag_module.formatNow() + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const data = sag_module.dt.buttons.exportData({
        format: {
            body: function (html, row, col, node) {
                if (col === 0) {
                    return;
                } else if (col === 1) {
                    return $(html).text();
                } else if (col === 2) {
                    return html;
                } else {
                    let result = $(node).text();
                    if (result == '') {
                        const value = $(node).data('value');
                        result = value == '' ? 0 : value;
                    } else {
                        result = String(html).replace(/<br>|<br\/>/g, '\n').replace(/&amp;/g, '&');
                    }
                    return result;
                }
            }
        },
        customizeData: function (data) {
            data.header.shift();
            data.body.forEach(row => row.shift());
            return data;
        },
        columns: 'export:name'
    });

    const header = sag_module.join(data.header, separator, boundary, escapeChar, reBoundary) + newLine;
    const footer = data.footer ? newLine + sag_module.join(data.footer, separator, boundary, escapeChar, reBoundary) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(sag_module.join(data.body[i], separator, boundary, escapeChar, reBoundary));
    }

    const result = {
        str: header + body.join(newLine) + footer,
        rows: body.length
    };

    $.fn.dataTable.fileSave(new Blob([result.str], {
        type: 'text/csv' + charset
    }),
        filename,
        true);
}

sag_module.importCsv = function () {
    $('#importSagsFile').click();
}

sag_module.handleFiles = function () {
    if (this.files.length !== 1) {
        return;
    }
    const file = this.files[0];
    this.value = null;

    if (file.type !== "text/csv" && file.name.toLowerCase().indexOf('.csv') === -1) {
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        window.csv_file_contents = e.target.result;

        sag_module.ajax('importCsvSags', { data: e.target.result })
            .then((response) => {
                const result = JSON.parse(response);
                if (result.status != 'error') {
                    $(result.table).modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: sag_module.tt('cc_user_20'),
                        html: result.message,
                        showConfirmButton: false
                    });
                }
            })
            .catch((error) => {
                console.error(error);
            });
    };
    reader.readAsText(file);
}

sag_module.confirmImport = function () {
    $('.modal').modal('hide');
    if (!window.csv_file_contents || window.csv_file_contents === "") {
        return;
    }

    sag_module.ajax('importCsvSags', { data: window.csv_file_contents, confirm: true })
        .then((response) => {
            const result = JSON.parse(response);
            if (result.status != 'error') {
                Swal.fire({
                    icon: 'success',
                    html: sag_module.tt('cc_user_24'),
                    confirmButtonText: sag_module.tt('ok'),
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    buttonsStyling: false
                })
                    .then(() => {
                        window.location.reload();
                    });
            } else {
                Toast.fire({
                    icon: 'error',
                    html: sag_module.tt('cc_user_20')
                });
            }
        })
        .catch((error) => {
            Toast.fire({
                icon: 'error',
                html: sag_module.tt('cc_user_20')
            });
            console.error(error);
        });
}

sag_module.hover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").addClass("highlight"); // shade only the hovered row
}

sag_module.dehover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").removeClass("highlight"); // shade only the hovered row
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

    const importFileElement = document.getElementById("importSagsFile");
    importFileElement.addEventListener("change", sag_module.handleFiles);

    const shieldcheck = '<i class="fa-solid fa-shield-check fa-xl" style="color: green;"></i>';
    const check = '<i class="fa-solid fa-check fa-xl" style="color: green;"></i>';
    const x = '<i class="fa-regular fa-xmark" style="color: #D00000;"></i>';
    sag_module.dt = $('#sagTable').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getSags')
                .then((response) => {
                    callback(JSON.parse(response));
                })
                .catch((error) => {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        deferRender: true,
        searching: false,
        info: false,
        paging: false,
        ordering: true,
        fixedHeader: false,
        fixedColumns: true,
        scrollX: true,
        scrollY: '75vh',
        scrollCollapse: true,
        rowReorder: {
            dataSrc: 'index',
            snapX: 0,
            selector: '.dt-rowReorder-grab'
        },
        initComplete: function () {
            $('#sagTableWrapper').show();
            const table = this.api();


            const theseSettingsString = localStorage.getItem('DataTables_sagOrder');
            if (theseSettingsString) {
                const theseSettings = JSON.parse(theseSettingsString);
                table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                    const thisSagId = table.cell(rowLoop, 2).data();
                    const desiredIndex = theseSettings.indexOf(thisSagId);
                    table.cell(rowLoop, 0).data(desiredIndex);
                });
                table.order([0, 'asc']).draw();
            } else {
                const order = table.column(2).data().toArray();
                localStorage.setItem('DataTables_sagOrder', JSON.stringify(order));
            }

            table.on('draw', function () {
                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = sag_module.hover;
                    row.onmouseleave = sag_module.dehover;
                });
            });

            table.on('row-reordered', function (e, diff, edit) {
                setTimeout(() => {
                    const order = table.column(2).data().toArray();
                    localStorage.setItem('DataTables_sagOrder', JSON.stringify(
                        order));
                }, 0);
            });

            table.rows().every(function () {
                const rowNode = this.node();
                const rowIndex = this.index();
                $(rowNode).attr('data-dt-row', rowIndex);
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = sag_module.hover;
                row.onmouseleave = sag_module.dehover;
            });

            setTimeout(() => {
                table.stateRestore();
                table.columns.adjust().draw();
            }, 0);

            console.log(performance.now());
            console.timeEnd('dt');
        },

        columns: [{
            data: 'index',
            orderable: true,
            visible: false
        },
        {
            className: '',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const iclass =
                        "fa-solid  fa-grip-dots-vertical mr-2 dt-rowReorder-grab text-secondary";
                    const aclass = "SagLink text-primary text-decoration-none font-weight-bold";
                    return `<div style="display: flex; align-items: center; white-space: nowrap;">` +
                        `<i class="${iclass}"></i>` +
                        `<a class="${aclass}" onclick="sag_module.editSag('${row.sag_id}')">${row.sag_name}</a>` +
                        `</div>`;
                } else {
                    return row.sag_name;
                }
            }
        },
        {
            className: 'sag-id-column user-select-all',
            data: 'sag_id'
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.design ? check : x;
                } else {
                    return row.permissions.design;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.user_rights ? check : x;
                } else {
                    return row.permissions.user_rights;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_access_groups ? check : x;
                } else {
                    return row.permissions.data_access_groups;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    switch (String(row.permissions.dataViewing)) {
                        case '3':
                            return sag_module.tt('cc_sags_25');
                        case '2':
                            return sag_module.tt('cc_sags_26');
                        case '1':
                            return sag_module.tt('cc_sags_27');
                        default:
                            return x;
                    }
                } else {
                    return row.permissions.dataViewing;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    switch (String(row.permissions.dataExport)) {
                        case '3':
                            return sag_module.tt('cc_sags_28');
                        case '2':
                            return sag_module.tt('cc_sags_29');
                        case '1':
                            return sag_module.tt('cc_sags_30');
                        default:
                            return x;
                    }
                } else {
                    return row.permissions.dataExport;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.alerts ? check : x;
                } else {
                    return row.permissions.alerts;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.reports ? check : x;
                } else {
                    return row.permissions.reports;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.graphical ? check : x;
                } else {
                    return row.permissions.graphical;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.participants ? check : x;
                } else {
                    return row.permissions.participants;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.calendar ? check : x;
                } else {
                    return row.permissions.calendar;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_import_tool ? check : x;
                } else {
                    return row.permissions.data_import_tool;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_comparison_tool ? check : x;
                } else {
                    return row.permissions.data_comparison_tool;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_logging ? check : x;
                } else {
                    return row.permissions.data_logging;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.file_repository ? check : x;
                } else {
                    return row.permissions.file_repository;
                }
            }
        },
        {
            className: 'dt-center dt-body-nowrap',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const reviewer = row.permissions.double_data_reviewer ? sag_module.tt('cc_sags_31') : null;
                    const person = row.permissions.double_data_person ? sag_module.tt('cc_sags_32') : null;
                    const cellValue = [reviewer, person].filter(el => el).join("<br/>");
                    return cellValue || x;
                } else {
                    return row.permissions.double_data;
                }
            },
            name: 'double_data'
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.lock_record_customize ? check : x;
                } else {
                    return row.permissions.lock_record_customize;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    switch (String(row.permissions.lock_record)) {
                        case '2':
                            return shieldcheck;
                        case '1':
                            return check;
                        default:
                            return x;
                    }
                } else {
                    return row.permissions.lock_record;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const random_setup = row.permissions.random_setup ? sag_module.tt('cc_sags_33') : null;
                    const random_dashboard = row.permissions.random_dashboard ?
                        sag_module.tt('cc_sags_34') : null;
                    const random_perform = row.permissions.random_perform ?
                        sag_module.tt('cc_sags_35') : null;
                    const cellValue = [random_setup, random_dashboard, random_perform]
                        .filter(el => el).join("<br/>");
                    return cellValue || x;
                } else {
                    return row.permissions.randomization;
                }
            },
            name: 'randomization'
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_quality_design ? check : x;
                } else {
                    return row.permissions.data_quality_design;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.data_quality_execute ? check : x;
                } else {
                    return row.permissions.data_quality_execute;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const view = row.permissions.data_quality_resolution_view ?
                        sag_module.tt('cc_sags_36') : null;
                    const respond = row.permissions.data_quality_resolution_respond ?
                        sag_module.tt('cc_sags_37') : null;
                    const open = row.permissions.data_quality_resolution_open ?
                        sag_module.tt('cc_sags_38') : null;
                    const close = row.permissions.data_quality_resolution_close ?
                        sag_module.tt('cc_sags_39') : null;
                    const cellValue = [view, respond, open, close].filter(el => el)
                        .join("<br/>");
                    return cellValue || x;
                } else {
                    return row.permissions.data_quality_resolution;
                }
            },
            name: 'data_quality_resolution'
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const api_export = row.permissions.api_export ? sag_module.tt('cc_sags_40') : null;
                    const api_import = row.permissions.api_import ? sag_module.tt('cc_sags_41') : null;
                    const cellValue = [api_export, api_import].filter(el => el).join(
                        "<br/>");
                    return cellValue || x;
                } else {
                    return row.permissions.api;
                }
            },
            name: 'api'
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.mobile_app ? check : x;
                } else {
                    return row.permissions.mobile_app;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.realtime_webservice_mapping ? check : x;
                } else {
                    return row.permissions.realtime_webservice_mapping;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.realtime_webservice_adjudicate ? check : x;
                } else {
                    return row.permissions.realtime_webservice_adjudicate;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.dts ? check : x;
                } else {
                    return row.permissions.dts;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.mycap_participants ? check : x;
                } else {
                    return row.permissions.mycap_participants;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.record_create ? check : x;
                } else {
                    return row.permissions.record_create;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.record_rename ? check : x;
                } else {
                    return row.permissions.record_rename;
                }
            }
        },
        {
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.permissions.record_delete ? check : x;
                } else {
                    return row.permissions.record_delete;
                }
            }
        },
        {
            data: 'permissions.random_setup',
            visible: false
        },
        {
            data: 'permissions.random_dashboard',
            visible: false
        },
        {
            data: 'permissions.random_perform',
            visible: false
        },
        {
            data: 'permissions.data_quality_resolution_view',
            visible: false
        },
        {
            data: 'permissions.data_quality_resolution_open',
            visible: false
        },
        {
            data: 'permissions.data_quality_resolution_respond',
            visible: false
        },
        {
            data: 'permissions.data_quality_resolution_close',
            visible: false
        },
        {
            data: 'permissions.double_data_reviewer',
            visible: false
        },
        {
            data: 'permissions.double_data_person',
            visible: false
        },
        {
            data: 'permissions.api_export',
            visible: false
        },
        {
            data: 'permissions.api_import',
            visible: false
        },
        {
            data: 'permissions.mobile_app_download_data',
            visible: false
        },
        {
            data: 'permissions.lock_record_multiform',
            visible: false
        }
        ],
        columnDefs: [{
            targets: "_all",
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).data('value', cellData ?? 0);
            },
            name: 'export',
            orderable: false,
            className: 'SAG'
        }]
    });

    $('#newSagName').keyup(function (event) {
        if (event.which === 13) {
            $('#addSagButton').click();
        }
    });
});
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}