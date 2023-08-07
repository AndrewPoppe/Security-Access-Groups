const sag_module = __MODULE__;
console.log(performance.now());
console.time('dt');

sag_module.sags = JSON.parse('{{SAGS_JSON}}');

sag_module.formatNow = function () {
    const d = new Date();
    return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) + '-' + (d.getDate()).toString()
        .padStart(2, 0)
}

sag_module.toggleEditMode = function (event) {
    const button = $('button.editUsersButton');
    const editing = !$(button).data('editing');
    $('.sagSelect').attr('disabled', !editing);
    $(button).data('editing', editing);
    let style = 'none';
    if (editing) {
        $(button).find('span').text(sag_module.tt('cc_user_13'));
        $(button).addClass('btn-outline-danger');
        $(button).removeClass('btn-danger');
    } else {
        $(button).find('span').text(sag_module.tt('cc_user_14'));
        $(button).addClass('btn-danger');
        $(button).removeClass('btn-outline-danger');
        style = 'user-select:all; cursor: text; margin-left: 1px; margin-right: 1px;';
    }
    $('.sagSelect').select2({
        minimumResultsForSearch: 20,
        templateSelection: function (selection) {
            return $(
                `<div class="d-flex justify-content-between">` +
                `<strong>${selection.text}</strong>&nbsp;` +
                `<span class="text-secondary" style="${style}">${selection.id}</span>` +
                `</div>`
            );
        },
        templateResult: function (option) {
            return $(
                `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
            );
        }
    });
}

sag_module.handleCsvExport = function () {
    if (sag_module.dt.search() != '') {
        Swal.fire({
            title: sag_module.tt('cc_user_15'),
            text: sag_module.tt('cc_user_16'),
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: sag_module.tt('cc_user_17'),
            denyButtonText: sag_module.tt('cc_user_18'),
            cancelButtonText: sag_module.tt('cancel')
        }).then((result) => {
            if (result.isConfirmed) {
                sag_module.exportCsv(true);
            } else if (result.isDenied) {
                sag_module.exportCsv();
            }
        });
    } else {
        sag_module.exportCsv();
    }
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

sag_module.exportCsv = function (useFilter = false) {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = 'SecurityAccessGroups_Users_' + (useFilter ? 'FILTERED_' : '') + sag_module.formatNow() + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const useSearch = useFilter ? 'applied' : 'none';
    const allData = sag_module.dt.rows({
        search: useSearch,
        page: 'all'
    }).data();
    const data = sag_module.dt.buttons.exportData({
        format: {
            header: function (html, col, node) {
                return $(node).data('id');
            },
            body: function (html, row, col, node) {
                if (col === 0) {
                    return allData[row]["username"];
                } else if (col === 1) {
                    return allData[row]["user_firstname"] + " " + allData[row]["user_lastname"];
                } else if (col === 2) {
                    return allData[row]["user_email"];
                } else if (col === 3) {
                    return sag_module.sags[allData[row]["sag"]];
                } else if (col === 4) {
                    return allData[row]["sag"];
                }
            }
        },
        modifier: {
            page: 'all',
            search: useSearch

        }
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
    $('#loading').modal('hide');
}

sag_module.importCsv = function () {
    $('#importUsersFile').click();
}

sag_module.handleImportError = function (errorData) {
    let body = errorData.error.join('<br>') + "<div class='container'>";
    if (errorData.users.length) {
        body +=
            "<div class='row justify-content-center m-2'>" +
            `<table><thead><tr><th>${sag_module.tt('status_ui_59')}</th></tr></thead><tbody>`;
        errorData.users.forEach((user) => {
            body += `<tr><td>${user}</td></tr>`;
        });
        body += "</tbody></table></div>";
    }
    if (errorData.sags.length) {
        body +=
            "<div class='row justify-content-center m-2'>" +
            `<table><thead><tr><th>${sag_module.tt('cc_user_12')}</th></tr></thead><tbody>`;
        errorData.sags.forEach((sag) => {
            body += `<tr><td>${sag}</td></tr>`;
        });
        body += "</tbody></table></div>";
    }
    body += "</div>";
    Swal.fire({
        title: sag_module.tt('error_2'),
        html: body,
        icon: 'error',
        confirmButtonText: sag_module.tt('ok'),
    });
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

    Swal.fire({
        title: sag_module.tt('alerts_16'),
        didOpen: () => {
            Swal.showLoading();
        }
    });


    const reader = new FileReader();
    reader.onload = (e) => {
        sag_module.csv_file_contents = e.target.result;
        sag_module.ajax('importCsvUsers', { data: sag_module.csv_file_contents })
            .then((response) => {
                Swal.close();
                const result = JSON.parse(response);
                if (result.status != 'error') {
                    $(result.data).modal('show');
                } else {
                    sag_module.handleImportError(result.data);
                }
            })
            .catch((error) => {
                Swal.close();
                console.error(error);
            });
    };
    reader.readAsText(file);
}

sag_module.confirmImport = function () {
    $('.modal').modal('hide');
    if (!sag_module.csv_file_contents || sag_module.csv_file_contents === "") {
        return;
    }
    sag_module.ajax('importCsvUsers', { data: sag_module.csv_file_contents, confirm: true })
        .then((response) => {
            const result = JSON.parse(response);
            if (result.status != 'error') {
                sag_module.dt.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    html: sag_module.tt('cc_user_19'),
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    buttonsStyling: false,
                    confirmButtonText: sag_module.tt('ok'),
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

sag_module.downloadTemplate = function () {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = 'SecurityAccessGroups_Users_ImportTemplate' + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const data = $('#templateTable').DataTable().buttons.exportData();
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

sag_module.saveSag = function (selectNode) {
    const select = $(selectNode);
    const tr = $(selectNode).closest('tr');
    const user = tr.data('user');
    const newSag = select.val();

    let color = "#66ff99";
    sag_module.ajax('assignSag', { username: user, sag: newSag })
        .then((response) => {
            const result = JSON.parse(response);
            if (result.status != 'error') {
                select.closest('td').data('sag', newSag);
                select.closest('td').attr('data-sag', newSag);
                const rowIndex = sag_module.dt.row(select.closest('tr')).index();
                sag_module.dt.cell(rowIndex, 4).data(newSag);
            } else {
                color = "#ff3300";
                Toast.fire({
                    icon: 'error',
                    title: sag_module.tt('cc_user_21')
                });
                sag_module.dt.ajax.reload();
            }
            $(tr).find('td.SAG').css('cssText', `background-color:${color} !important`);
            setTimeout(() => {
                $(tr).find('td.SAG').css('cssText', 'transition:background-color 2s ease-out;');
                setTimeout(() => {
                    $(tr).find('td.SAG').css('cssText', '');
                }, 2000);
            }, 10);
        })
        .catch((error) => {
            console.error(error);
        });
}

sag_module.handleSelects = function () {
    const button = $('button.editUsersButton');
    const editing = $(button).data('editing');
    const style = editing ? 'user-select:all; cursor: text; margin-left: 1px; margin-right: 1px;' : 'none';

    $('.sagSelect').select2({
        minimumResultsForSearch: 20,
        templateSelection: function (selection) {
            return $(
                `<div class="d-flex justify-content-between">` +
                `<strong>${selection.text}</strong>&nbsp;` +
                `<span class="text-secondary" style="${style}">${selection.id}</span>` +
                `</div>`
            );
        },
        templateResult: function (option) {
            return $(
                `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
            );
        }
    });
    $('.sagSelect').attr('disabled', !editing);
}

// Set up "OR" search
sag_module.setUpOrSearch = function (table) {
    let searchTerm = '';
    $('.dataTables_filter input').off().on('input', function () {
        searchTerm = $(this).val().replaceAll(/[()]/g, '')
        if (searchTerm.includes('|')) {
            const newTerm = searchTerm.split('|').map(term => '(' + term.replaceAll('"', '').trim() + ')').filter(term => term && term != '()').join('|');
            table.search(newTerm, true, false, true).draw();
        } else {
            table.search(searchTerm, false, true, true).draw();
        }
        this.value = searchTerm;
    });
    table.on('search.dt', () => $('.dataTables_filter input').val(searchTerm));
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

    const importFileElement = document.getElementById("importUsersFile");
    importFileElement.addEventListener("change", sag_module.handleFiles, false);
    sag_module.dt = $('#SAG-System-Table').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getUsers')
                .then((response) => {
                    callback(JSON.parse(response));
                })
                .catch((error) => {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        drawCallback: function (settings) {
            sag_module.handleSelects();
        },
        deferRender: true,
        paging: true,
        pageLength: 10,
        info: true,
        columns: [{
            title: sag_module.tt('status_ui_59'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const root = `${app_path_webroot_full}redcap_v${redcap_version}`;
                    const href =
                        `${root}/ControlCenter/view_users.php?username=${row.username}`;
                    const attrs = `target="_blank" rel="noopener noreferrer"`;
                    return `<strong><a href="${href}" ${attrs}>${row.username}</a></strong>`;
                } else {
                    return row.username;
                }
            },
        }, {
            title: sag_module.tt('status_ui_60'),
            data: function (row, type, set, meta) {
                return row.user_firstname + ' ' + row.user_lastname;
            }
        }, {
            title: sag_module.tt('status_ui_61'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return `<a href="mailto:${row.user_email}">${row.user_email}</a>`;
                } else {
                    return row.user_email;
                }
            }
        }, {
            title: sag_module.tt('status_ui_63'),
            data: function (row, type, set, meta) {
                if (row.sag === null || sag_module.sags[row.sag] === undefined) {
                    row.sag = '{{DEFAULT_SAG_ID}}';
                }
                if (type === 'filter' || type === 'search') {
                    const sag_id = row.sag;
                    const sag_label = sag_module.sags[sag_id];
                    const result = sag_id + ' ' + sag_label;
                    return result;
                } else if (type === 'sort') {
                    return sag_module.sags[row.sag];
                } else {
                    let result =
                        `<select class="sagSelect" disabled="true" onchange="sag_module.saveSag(this)">`;
                    for (let sag_id in sag_module.sags) {
                        const sag_label = sag_module.sags[sag_id];
                        const selected = sag_id == row.sag ?
                            "selected" : "";
                        result +=
                            `<option value='${sag_id}' ${selected}>${sag_label}</option>`;
                    }
                    result += `</select>`;
                    return result;
                }
            }
        },
        {
            title: sag_module.tt('cc_user_22'),
            data: 'sag'
        }
        ],
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-user', data.username);
        },
        columnDefs: [{
            targets: [0, 1, 2],
            width: '25%',
            className: 'SAG'
        }, {
            targets: [3],
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).attr('data-sag', rowData.sag);
            },
            className: 'SAG'
        }, {
            targets: [4],
            visible: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass('hidden_sag_id');
            },
            className: 'SAG'
        }],
        dom: 'lftip',
        initComplete: function () {
            $('div.dataTables_filter input').addClass('form-control');
            setTimeout(() => {
                $(this).DataTable().columns.adjust().draw();
            }, 0);
            sag_module.handleSelects();
            sag_module.setUpOrSearch(this.api());
            console.log(performance.now());
            console.timeEnd('dt');
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, sag_module.tt('alerts_37')]
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_cc_users_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_cc_users_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_cc_users_empty_table'),
            info: sag_module.tt('dt_cc_users_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_cc_users_info_empty'),
            lengthMenu: sag_module.tt('dt_cc_users_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_cc_users_loading_records'),
            zeroRecords: sag_module.tt('dt_cc_users_zero_records'),
            select: {
                rows: {
                    _: sag_module.tt('dt_cc_users_select_rows_other'),
                    0: sag_module.tt('dt_cc_users_select_rows_zero'),
                    1: sag_module.tt('dt_cc_users_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_cc_users_paginate_first'),
                last: sag_module.tt('dt_cc_users_paginate_last'),
                next: sag_module.tt('dt_cc_users_paginate_next'),
                previous: sag_module.tt('dt_cc_users_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_cc_users_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_cc_users_aria_sort_descending')
            }
        }
    });

});