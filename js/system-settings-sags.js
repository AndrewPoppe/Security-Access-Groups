function openSagEditor(url, sag_id = "", sag_name = "") {
    const deleteSagButtonCallback = function () {
        Swal.fire({
            title: 'Are you sure you want to delete this SAG?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete SAG',
            customClass: {
                confirmButton: 'btn btn-danger m-1',
                cancelButton: 'btn btn-secondary m-1'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                $.post("{{DELETE_SAG_URL}}", {
                    sag_id: sag_id
                })
                    .done(function (response) {
                        Toast.fire({
                            title: 'The SAG was deleted',
                            icon: 'success'
                        })
                            .then(function () {
                                window.location.reload();
                            });
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
                        });
                    })
                    .always(function () { });
            }
        });
    };
    const copySagButtonCallback = function () {
        const data = $("#SAG_Setting").serializeObject();
        Swal.fire({
            title: 'What would you like the new SAG to be called?',
            input: 'text',
            inputValue: `${data["sag_name_edit"]} Copy`,
            showCancelButton: true,
            confirmButtonText: 'Copy SAG',
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
                    data.newSag = 1;
                    $.post('{{EDIT_SAG_URL}}', data)
                        .done(function (result) {
                            Toast.fire({
                                icon: 'success',
                                title: 'The SAG was copied'
                            })
                                .then(function () {
                                    window.location.reload();
                                });
                        })
                        .fail(function (result) {
                            console.error(result.responseText);
                        })
                        .always(function () { });
                }
            })
    };
    const saveSagChangesButtonCallback = function () {
        $('input[name="sag_name_edit"]').blur();
        const sag_name_edit = $('input[name="sag_name_edit"]').val();
        if (sag_name_edit != '') {
            const data = $("#SAG_Setting").serializeObject();
            data.sag_id = sag_id;
            $.post(url, data)
                .done(function (response) {
                    Toast.fire({
                        icon: "success",
                        title: `SAG "${sag_name_edit}" Successfully Saved`
                    }).then(function () {
                        window.location.reload();
                    })
                })
                .fail(function (error) {
                    console.error(error.responseText);
                });
        }
    };
    const saveNewSagButtonCallback = function () {
        $('input[name="sag_name_edit"]').blur();
        const sag_name_edit = $('input[name="sag_name_edit"]').val();
        if (sag_name_edit != '') {
            const data = $("#SAG_Setting").serializeObject();
            $.post(url, data)
                .done(function (response) {
                    Toast.fire({
                        icon: "success",
                        title: `SAG Successfully Created`
                    }).then(function () {
                        window.location.reload();
                    })
                })
                .fail(function (error) {
                    console.error(error.responseText);
                });
        }
    };

    $.get(url, {
        sag_id: sag_id,
        sag_name: sag_name
    })
        .done(function (response) {
            $("#edit_sag_popup").html(response);
            $("#edit_sag_popup").on('shown.bs.modal', function (event) {
                $('input[name="sag_name_edit"]').blur(function () {
                    $(this).val($(this).val().trim());
                    if ($(this).val() == '') {
                        Swal.fire({
                            title: '{{USER_RIGHTS_ERROR_MESSAGE}}',
                            icon: 'error',
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
        .fail(function (error) {
            console.error(error.responseText)
        });
}

function editSag(sag_id, sag_name) {
    const url = "{{EDIT_SAG_FALSE_URL}}";
    openSagEditor(url, sag_id, sag_name);
}

function addNewSag() {
    $('#addSagButton').blur();
    const url = "{{EDIT_SAG_TRUE_URL}}";
    const newSagName = $('#newSagName').val().trim();
    if (newSagName == "") {
        Toast.fire({
            title: "You must specify a SAG name",
            icon: "error",
            showConfirmButton: false,
            didClose: () => {
                $("#newSagName").focus()
            }
        });
    } else {
        openSagEditor(url, "", newSagName);
    }
}

function formatNow() {
    const d = new Date();
    return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) +
        '-' + (d.getDate()).toString().padStart(2, 0);
}

function join(a) {
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

function exportRawCsv(includeData = true) {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = (includeData ?
        'SecurityAccessGroups_Raw_' + formatNow() :
        'SecurityAccessGroups_ImportTemplate') + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const rowSelector = includeData ? undefined : -1;
    const data = $('#sagTable').DataTable().buttons.exportData({
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

    const header = join(data.header) + newLine;
    const footer = data.footer ? newLine + join(data.footer) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(join(data.body[i]));
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

function exportCsv() {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = 'SecurityAccessGroups_Labels_' + formatNow() + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const data = $('#sagTable').DataTable().buttons.exportData({
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

    const header = join(data.header) + newLine;
    const footer = data.footer ? newLine + join(data.footer) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(join(data.body[i]));
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

function importCsv() {
    $('#importSagsFile').click();
}

function handleFiles() {
    if (this.files.length !== 1) {
        return;
    }
    const file = this.files[0];

    if (!(file.type === "text/csv")) {
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        window.csv_file_contents = e.target.result;
        $.post("{{IMPORT_CSV_SAGS_URL}}", {
            data: e.target.result
        })
            .done((response) => {
                $(response).modal('show');
            })
            .fail((error) => {
                const errorText = JSON.parse(error.responseText) ?? {};
                const message = errorText.error;
                Swal.fire({
                    icon: 'error',
                    title: "Error importing CSV",
                    html: message,
                    showConfirmButton: false
                });
            });
    };
    reader.readAsText(file);
}

function confirmImport() {
    $('.modal').modal('hide');
    if (!window.csv_file_contents || window.csv_file_contents === "") {
        return;
    }
    $.post("{{IMPORT_CSV_SAGS_URL}}", {
        data: window.csv_file_contents,
        confirm: true
    })
        .done((response) => {
            if (response) {
                Swal.fire({
                    icon: 'success',
                    html: "Successfully imported Security Access Group definitions.",
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
                    html: "Error importing CSV"
                });
            }
        })
        .fail((error) => {
            Toast.fire({
                icon: 'error',
                html: "Error importing CSV"
            });
            console.error(error.responseText);
        })
}

function hover() {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").addClass("highlight"); // shade only the hovered row
}

function dehover() {
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
    importFileElement.addEventListener("change", handleFiles, false);

    const shieldcheck = '<i class="fa-solid fa-shield-check fa-xl" style="color: green;"></i>';
    const check = '<i class="fa-solid fa-check fa-xl" style="color: green;"></i>';
    const x = '<i class="fa-regular fa-xmark" style="color: #D00000;"></i>';
    $('#sagTable').DataTable({
        ajax: {
            url: '{{SAGS_URL}}',
            method: 'POST'
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
                    row.onmouseenter = hover;
                    row.onmouseleave = dehover;
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
                row.onmouseenter = hover;
                row.onmouseleave = dehover;
            });

            setTimeout(() => {
                table.stateRestore();
                table.columns.adjust().draw();
            }, 0);
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
                    const aclass = "SagLink text-primary";
                    return `<div style="display: flex; align-items: center; white-space: nowrap;">` +
                        `<i class="${iclass}"></i>` +
                        `<a class="${aclass}" onclick="editSag('${row.sag_id}')">${row.sag_name}</a>` +
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
                            return 'View & Edit Forms and Survey Responses';
                        case '2':
                            return 'View & Edit Forms';
                        case '1':
                            return 'Read Only';
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
                            return 'Full Data Set';
                        case '2':
                            return 'Remove Identifiers';
                        case '1':
                            return 'De-Identified';
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
            className: 'dt-center',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    switch (String(row.permissions.double_data)) {
                        case '1':
                            return "Person #1";
                        case '2':
                            return "Person #2";
                        default:
                            return 'Reviewer';
                    }
                } else {
                    return row.permissions.double_data;
                }
            }
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
                    const random_setup = row.permissions.random_setup ? "Setup" : null;
                    const random_dashboard = row.permissions.random_dashboard ?
                        "Dashboard" : null;
                    const random_perform = row.permissions.random_perform ?
                        "Randomize" : null;
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
                        "View" : null;
                    const respond = row.permissions.data_quality_resolution_respond ?
                        "Respond" : null;
                    const open = row.permissions.data_quality_resolution_open ?
                        "Open" : null;
                    const close = row.permissions.data_quality_resolution_close ?
                        "Close" : null;
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
                    const api_export = row.permissions.api_export ? "Export" : null;
                    const api_import = row.permissions.api_import ? "Import" : null;
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
            orderable: false
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