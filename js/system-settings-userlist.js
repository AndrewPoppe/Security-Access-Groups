const module = __MODULE__;
console.log(performance.now());
console.time('dt');

module.sags = JSON.parse('{{SAGS_JSON}}');

module.formatNow = function () {
    const d = new Date();
    return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) + '-' + (d.getDate()).toString()
        .padStart(2, 0)
}

module.toggleEditMode = function (event) {
    const button = $('button.editUsersButton');
    const editing = !$(button).data('editing');
    $('.sagSelect').attr('disabled', !editing);
    $(button).data('editing', editing);
    let style = 'none';
    if (editing) {
        $(button).find('span').text('Stop Editing');
        $(button).addClass('btn-outline-danger');
        $(button).removeClass('btn-danger');
    } else {
        $(button).find('span').text('Edit Users');
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

module.handleCsvExport = function () {
    if (module.dt.search() != '') {
        Swal.fire({
            title: 'Export Filtered Data?',
            text: 'You have a filter applied to the table. Do you want to export the filtered data or all data?',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Export Filtered Data',
            denyButtonText: 'Export All Data'
        }).then((result) => {
            if (result.isConfirmed) {
                module.exportCsv(true);
            } else if (result.isDenied) {
                module.exportCsv();
            }
        });
    } else {
        module.exportCsv();
    }
}

module.join = function (a, separator, boundary, escapeChar, reBoundary) {
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

module.exportCsv = function (useFilter = false) {
    const newLine = /Windows/.exec(navigator.userAgent) ? '\r\n' : '\n';
    const escapeChar = '"';
    const boundary = '"';
    const separator = ',';
    const extension = '.csv';
    const reBoundary = new RegExp(boundary, 'g');
    const filename = 'SecurityAccessGroups_Users_' + (useFilter ? 'FILTERED_' : '') + module.formatNow() + extension;
    let charset = document.characterSet;
    if (charset) {
        charset = ';charset=' + charset;
    }

    const useSearch = useFilter ? 'applied' : 'none';
    const allData = module.dt.rows({
        search: useSearch,
        page: 'all'
    }).data();
    const data = module.dt.buttons.exportData({
        format: {
            header: function (html, col, node) {
                return $(node).data('id');
            },
            body: function (html, row, col, node) {
                if (col === 0) {
                    return allData[row]["username"];
                } else if (col === 1) {
                    return allData[row]["user_firstname"] + " " + allData[row][
                        "user_lastname"
                    ];
                } else if (col === 2) {
                    return allData[row]["user_email"];
                } else if (col === 3) {
                    return module.sags[allData[row]["sag"]];
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

    const header = module.join(data.header, separator, boundary, escapeChar, reBoundary) + newLine;
    const footer = data.footer ? newLine + module.join(data.footer, separator, boundary, escapeChar, reBoundary) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(module.join(data.body[i], separator, boundary, escapeChar, reBoundary));
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

module.importCsv = function () {
    $('#importUsersFile').click();
}

module.handleImportError = function (errorData) {
    let body = errorData.error.join('<br>') + "<div class='container'>";
    if (errorData.users.length) {
        body +=
            "<div class='row justify-content-center m-2'>" +
            "<table><thead><tr><th>Username</th></tr></thead><tbody>";
        errorData.users.forEach((user) => {
            body += `<tr><td>${user}</td></tr>`;
        });
        body += "</tbody></table></div>";
    }
    if (errorData.sags.length) {
        body +=
            "<div class='row justify-content-center m-2'>" +
            "<table><thead><tr><th>SAG ID</th></tr></thead><tbody>";
        errorData.sags.forEach((sag) => {
            body += `<tr><td>${sag}</td></tr>`;
        });
        body += "</tbody></table></div>";
    }
    body += "</div>";
    Swal.fire({
        title: 'Error',
        html: body,
        icon: 'error'
    });
}

module.handleFiles = function () {
    if (this.files.length !== 1) {
        return;
    }
    const file = this.files[0];
    this.value = null;

    if (file.type !== "text/csv" && file.name.toLowerCase().indexOf('.csv') === -1) {
        return;
    }

    Swal.fire('Loading...');
    Swal.showLoading();

    const reader = new FileReader();
    reader.onload = (e) => {
        module.csv_file_contents = e.target.result;
        module.ajax('importCsvUsers', { data: module.csv_file_contents })
            .then((response) => {
                Swal.close();
                const result = JSON.parse(response);
                if (result.status != 'error') {
                    $(result.data).modal('show');
                } else {
                    module.handleImportError(result.data);
                }
            })
            .catch((error) => {
                Swal.close();
                console.error(error);
            });
    };
    reader.readAsText(file);
}

module.confirmImport = function () {
    $('.modal').modal('hide');
    if (!module.csv_file_contents || module.csv_file_contents === "") {
        return;
    }
    module.ajax('importCsvUsers', { data: module.csv_file_contents, confirm: true })
        .then((response) => {
            const result = JSON.parse(response);
            if (result.status != 'error') {
                module.dt.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    html: "Successfully imported assignments.",
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    buttonsStyling: false
                });
            } else {
                Toast.fire({
                    icon: 'error',
                    html: "Error importing CSV"
                });
            }
        })
        .catch((error) => {
            Toast.fire({
                icon: 'error',
                html: "Error importing CSV"
            });
            console.error(error);
        });
}

module.downloadTemplate = function () {
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
    const header = module.join(data.header, separator, boundary, escapeChar, reBoundary) + newLine;
    const footer = data.footer ? newLine + module.join(data.footer, separator, boundary, escapeChar, reBoundary) : '';
    const body = [];
    for (let i = 0, ien = data.body.length; i < ien; i++) {
        body.push(module.join(data.body[i], separator, boundary, escapeChar, reBoundary));
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

module.saveSag = function (selectNode) {
    const select = $(selectNode);
    const tr = $(selectNode).closest('tr');
    const user = tr.data('user');
    const newSag = select.val();

    let color = "#66ff99";
    module.ajax('assignSag', { username: user, sag: newSag })
        .then((response) => {
            const result = JSON.parse(response);
            if (result.status != 'error') {
                select.closest('td').data('sag', newSag);
                select.closest('td').attr('data-sag', newSag);
                const rowIndex = module.dt.row(select.closest('tr')).index();
                module.dt.cell(rowIndex, 4).data(newSag);
            } else {
                color = "#ff3300";
                Toast.fire({
                    icon: 'error',
                    title: 'Error assigning SAG'
                });
                module.dt.ajax.reload();
            }
            $(tr).find('td').effect('highlight', {
                color: color
            }, 2000);
        })
        .catch((error) => {
            console.error(error);
        });
}

module.handleSelects = function () {
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
    importFileElement.addEventListener("change", module.handleFiles, false);
    module.dt = $('#SUR-System-Table').DataTable({
        ajax: function (data, callback, settings) {
            module.ajax('getUsers')
                .then((response) => {
                    callback(JSON.parse(response));
                })
                .catch((error) => {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        drawCallback: function (settings) {
            module.handleSelects();
        },
        deferRender: true,
        paging: true,
        pageLength: 10,
        info: true,
        columns: [{
            title: 'Username',
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
            title: 'Name',
            data: function (row, type, set, meta) {
                return row.user_firstname + ' ' + row.user_lastname;
            }
        }, {
            title: 'Email',
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return `<a href="mailto:${row.user_email}">${row.user_email}</a>`;
                } else {
                    return row.user_email;
                }
            }
        }, {
            title: 'Security Access Group',
            data: function (row, type, set, meta) {
                if (row.sag === null) {
                    row.sag = '{{DEFAULT_SAG_ID}}';
                }
                if (type === 'filter') {
                    return row.sag + ' ' + module.sags[row.sag];
                } else if (type === 'sort') {
                    return module.sags[row.sag];
                } else {
                    let result =
                        `<select class="sagSelect" disabled="true" onchange="module.saveSag(this)">`;
                    for (let sag_id in module.sags) {
                        const sag_label = module.sags[sag_id];
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
            title: 'Hidden SAG',
            data: 'sag'
        }
        ],
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-user', data.username);
        },
        columnDefs: [{
            targets: [0, 1, 2],
            width: '25%'
        }, {
            targets: [3],
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).attr('data-sag', rowData.sag);
            }
        }, {
            targets: [4],
            visible: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass('hidden_sag_id');
            }
        }],
        dom: 'lftip',
        initComplete: function () {
            $('div.dataTables_filter input').addClass('form-control');
            setTimeout(() => {
                $(this).DataTable().columns.adjust().draw();
            }, 0);
            module.handleSelects();
            console.log(performance.now());
            console.timeEnd('dt');
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
            zeroRecords: "No matching users found"
        }
    });

});