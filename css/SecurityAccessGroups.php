<?php header("Content-type: text/css; charset: UTF-8"); ?>
@font-face {
font-family: "Atkinson Hyperlegible";
src: url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF2/Atkinson-Hyperlegible-Regular-102a.woff2') ?>") format("woff2"),
url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF/Atkinson-Hyperlegible-Regular-102.woff') ?>") format("woff");
font-weight: normal;
font-style: normal;
}

@font-face {
font-family: "Atkinson Hyperlegible";
src: url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF2/Atkinson-Hyperlegible-Bold-102a.woff2') ?>") format("woff2"),
url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF/Atkinson-Hyperlegible-Bold-102.woff') ?>") format("woff");
font-weight: bold;
font-style: normal;
}

@font-face {
font-family: "Atkinson Hyperlegible";
src: url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF2/Atkinson-Hyperlegible-Italic-102a.woff2') ?>") format("woff2"),
url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF/Atkinson-Hyperlegible-Italic-102.woff') ?>") format("woff");
font-weight: normal;
font-style: italic;
}

@font-face {
font-family: "Atkinson Hyperlegible";
src: url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF2/Atkinson-Hyperlegible-BoldItalic-102a.woff2') ?>")
format("woff2"),
url("<?= $module->getUrl('fonts/Atkinson-Hyperlegible/WOFF/Atkinson-Hyperlegible-BoldItalic-102.woff') ?>") format("woff");
font-weight: bold;
font-style: italic;
}

div#control_center_window,
div#control_center_window p,
div#control_center_window div,
div#control_center_window a,
div#control_center_window table {
    font-family: "Atkinson Hyperlegible", sans-serif !important;
}

div.modal {
    font-family: "Atkinson Hyperlegible", sans-serif !important;
}

div.SAG-Container,
div.SAG-Container a,
div.SAG-Container a:hover {
    font-family: "Atkinson Hyperlegible", sans-serif !important;
}

table#SAG-System-Table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #aaa !important;
}

table#SAG-System-Table tbody td.SAG:first-child {
    border-left: 1px solid #aaa !important;
}

table#SAG-System-Table tbody td.SAG:last-child {
    border-right: 1px solid #aaa !important;
}

table#SAG-System-Table thead th:not(:first-child) {
    border-left: 1px solid #ccc !important;
}

table.sagTable tr.odd td.SAG,
table.sagTable tr.odd td.dataTables_empty {
    background-color: white !important;
}

table.sagTable tr.even td.SAG {
    background-color: #f5f5f5 !important;
}

div#SAG-System-Table_filter label {
    margin-bottom: 0;
}

table#SAG-System-Table thead {
    background-color: #ececec;
}

table.dataTable.cell-border tbody tr:first-child td.SAG {
    border-top: none !important;
}

table.dataTable.cell-border tbody tr td.SAG {
    border-top: 1px solid rgba(0, 0, 0, 0.15) !important;
}

table#SAG-System-Table tbody tr:hover td.SAG {
    background-color: #d9ebf5 !important;
}

table#SAG-System-Table.compact td.SAG,
table#SAG-System-Table.compact th {
    padding-left: 8px;
}

table#SAG-System-Table a.user-link {
    color: #A00000;
    text-decoration: underline;
}

table.sagTable tr.odd,
table.sagTable tr.odd>td.dtfc-fixed-left {
    background-color: white !important;
}

table.sagTable tr.even,
table.sagTable tr.even .dtfc-fixed-left {
    background-color: #efefef !important;
}

table.sagTable thead tr,
table.sagTable thead th.dtfc-fixed-left {
    background-color: #ececec;
}

table.sagTable tr.odd td.highlight,
table.sagTable tr.odd>td.dtfc-fixed-left.highlight,
table.sagTable tr.even td.highlight,
table.sagTable tr.even>td.dtfc-fixed-left.highlight {
    background-color: #d9ebf5 !important;
}

body:not(.dt-rowReorder-noOverflow) td.dt-rowReorder-grab {
    cursor: grab;
}

body:not(.dt-rowReorder-noOverflow) .dt-rowReorder-grab {
    cursor: grab;
}

div.dt-rowReorder-float-parent {
    cursor: grabbing !important;
}

body.dt-rowReorder-noOverflow {
    cursor: grabbing !important;
}

select.sagSelect {
    width: 100%;
}

form#SAG_Setting {
    font-family: "Atkinson Hyperlegible", sans-serif !important;
}

form#SAG_Setting div.SAG-form-row {
    margin: 5px -5px;
}

.form-check {
    margin: 0;
    min-height: 0;
}

.form-check-label {
    margin: 0;
    padding-top: 0.25em;
}

.form-check input {
    border-color: #999;
}

form#SAG_Setting hr {
    margin: 0.5rem 0;
    border-color: #00000060;
}

form#SAG_Setting .section-header {
    color: #A00000;
    font-size: 11px;
}

form#SAG_Setting .extra-text {
    margin-left: 1.5rem;
    font-size: 11px;
    color: #777;
}

a.SagLink:hover {
    cursor: pointer;
}

div.modal-xl {
    max-width: 1250px;
}

div#pagecontainer {
    max-width: 1600px;
}

div#sagTableWrapper {
    border-top: 1px solid rgba(0, 0, 0, 0.30);
    border-right: 1px solid rgba(0, 0, 0, 0.30);
    border-left: 1px solid rgba(0, 0, 0, 0.30);
}

table.sagTable.cell-border thead th:not(:last-child) {
    border-right: solid 1px rgba(0, 0, 0, 0.15);
}

table.sagTable.cell-border tbody td.dtfc-fixed-left {
    border-left: none !important;
}

table.sagTable.cell-border tbody td:last-child:not(.dtfc-fixed-left) {
    border-right: none;
}

.ui-tooltip {
    background: #333;
    color: white;
    border: none;
    padding: 0;
    opacity: 1;
    box-shadow: none;
}

.ui-tooltip-content {
    position: relative;
    padding: 0.5em;
}

.ui-tooltip-content::after {
    content: '';
    position: absolute;
    border-style: solid;
    display: block;
    width: 0;
}

.right .ui-tooltip-content::after {
    top: calc(50% - 10px);
    left: -10px;
    border-color: transparent #333;
    border-width: 10px 10px 10px 0;
}

.left .ui-tooltip-content::after {
    top: calc(50% - 10px);
    right: -10px;
    border-color: transparent #333;
    border-width: 10px 0 10px 10px;
}

.top .ui-tooltip-content::after {
    bottom: -10px;
    left: calc(50% - 10px);
    border-color: #333 transparent;
    border-width: 10px 10px 0;
}

.bottom .ui-tooltip-content::after {
    top: -10px;
    left: calc(50% - 10px);
    border-color: #333 transparent;
    border-width: 0 10px 10px;
}

div.dt-buttons {
    float: none;
}

.colored-toast.swal2-icon-success {
    background-color: #a5dc86 !important;
}

.colored-toast.swal2-icon-error {
    background-color: #f27474 !important;
}

.colored-toast.swal2-icon-warning {
    background-color: #f8bb86 !important;
}

.colored-toast.swal2-icon-info {
    background-color: #3fc3ee !important;
}

.colored-toast.swal2-icon-question {
    background-color: #87adbd !important;
}

.colored-toast .swal2-title {
    color: white;
}

.colored-toast .swal2-close {
    color: white;
}

.colored-toast .swal2-html-container {
    color: white;
}

a.dropdown-item {
    font-size: inherit !important;
    cursor: pointer !important;
}

.fa-sharp,
.fasl,
.fasr,
.fass {
    font-family: "Font Awesome 6 Sharp", sans-serif !important;
}

.fa-classic:not(.fa-sharp, .fasl, .fasr, .fass),
.fa-regular:not(.fa-sharp, .fasl, .fasr, .fass),
.fa-solid:not(.fa-sharp, .fasl, .fasr, .fass),
.far:not(.fa-sharp, .fasl, .fasr, .fass),
.fas:not(.fa-sharp, .fasl, .fasr, .fass),
.fa:not(.fa-sharp, .fasl, .fasr, .fass) {
    font-family: "Font Awesome 6 Pro", sans-serif !important;
}

.btn-close {
    box-sizing: content-box;
    width: 1em;
    height: 1em;
    padding: .25em .25em;
    color: #000;
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    border: 0;
    border-radius: .375rem;
    opacity: .5;
}

.dropdown-item {
    padding: .25rem 1.5rem .25rem 0.5rem;
}

td.sag-id-column {
    font-size: small;
    color: #222
}

.select2-container {
    width: 100% !important;
    padding-left: 0;
    padding-right: 0;
    font-family: "Atkinson Hyperlegible", sans-serif !important;
}

div.dataTables_filter input {
    font-family: "Atkinson Hyperlegible", sans-serif;
    margin-left: 0 !important;
}

div.dataTables_filter input::placeholder {
    color: #999 !important;
    opacity: 1 !important;
}

.select2-container--disabled .select2-selection--single {
    background-color: inherit !important;
    border: 0;
    color: black;
}

.select2-container--disabled .select2-selection__arrow {
    visibility: hidden;
}

button.btn-outline-danger.editUsersButton:hover,
button.btn-outline-danger.editUsersButton:active,
button.btn-outline-danger.editUsersButton:focus {
    color: #dc3545;
    background-color: white;
    border-color: #dc3545;
    outline: 1px solid #dc354566;
}

button.btn-danger.editUsersButton:hover,
button.btn-danger.editUsersButton:active,
button.btn-danger.editUsersButton:focus {
    color: white;
    background-color: #dc3545;
    border-color: #dc3545;
}

button.editUsersButton:focus {
    box-shadow: none;
}

button.editUsersButton:hover {
    outline: 1px solid #dc354566;
}

.dataPlaceholder {
    cursor: copy;
}

.bg-reminder {
    background-color: #ff000014 !important;
}

.bg-reminder.border {
    border: 1px solid #ff000020 !important;
}

.bg-users {
    background-color: #b8daff !important;
}

.bg-users.border {
    border: 1px solid #7abaff !important;
}

.bg-userRightsHolders {
    background-color: #ffeeba !important;
}

.bg-userRightsHolders.border {
    border: 1px solid #ffdf7e !important;
}

div.modal.userAlert label.col-form-label {
    font-weight: bold;
}

button.btn-warning:hover {
    outline: 1px solid #d39e00 !important;
}

button.btn-danger:hover {
    outline: 1px solid #bd2130 !important;
}

button.btn-secondary:hover {
    outline: 1px solid #545b62 !important;
}

.order-1 {
    order: 1;
}

.order-2 {
    order: 2;
}

.order-3 {
    order: 3;
}

.table.discrepancy-table thead th {
    background-color: #ececec !important;
}

.table.discrepancy-table {
    border-collapse: collapse;
}

div#discrepancy-table_wrapper div.dataTables_scroll {
    border: 1px solid rgba(0, 0, 0, 0.3) !important;
}

div#discrepancy-table_wrapper div.dataTables_scrollBody {
    border: none !important;
}

div#discrepancy-table_wrapper div.dataTables_scrollHead {
    background-color: #ececec
}

.table.discrepancy-table td.SAG {
    border-top: none !important;
}

tr:last-child td.SAG {
    border-bottom: none !important;
}

.table.discrepancy-table tr:not(:last-child) td.SAG {

    border-bottom: 1px solid #bbb !important;
}

table.discrepancy-table tr.table-danger-light,
table.discrepancy-table tr.table-danger-light>td.SAG {
    background-color: #f5c6cbaa !important;
}

table.discrepancy-table tr.table-success-light,
table.discrepancy-table tr.table-success-light>td.SAG {
    background-color: #c3e6cb7f !important;
}

table.discrepancy-table tr.table-expired,
table.discrepancy-table tr.table-expired>td.SAG {
    background-color: rgba(var(--bs-light-rgb), var(--bs-bg-opacity)) !important;
    background-color: #fdfdfe !important;
    color: rgb(var(--bs-secondary-rgb)) !important;
    color: var(--secondary) !important;
}

div.is-invalid div.tox.tox-tinymce {
    border: 1px solid var(--danger) !important;
}

table.table.is-invalid td.user-rights-holder-selector input {
    border: 1px solid var(--danger) !important;
    box-shadow: 0 0 0 0.1rem rgba(255, 0, 0, .25) !important;
}

table#alertLogTable {
    border-collapse: collapse;
}

table#alertLogTable tr.even {
    background-color: #f3f3f3 !important;
}

table#alertLogTable tr.odd {
    background-color: white !important;
}

table#alertLogTable tbody tr:hover {
    background-color: #d9ebf5 !important;
}

table#alertLogTable thead tr {
    background-color: #ececec !important;
}

table#alertLogTable.border {
    border: 1px solid rgba(0, 0, 0, 0.3) !important;
}

a.deleteAlertButton {
    opacity: 0.5;
}

a.deleteAlertButton:hover {
    opacity: 1;
}

input.timePicker {
    background-color: white !important;
}

table#alertLogTable ::placeholder,
table#SAG-System-Table ::placeholder {
    opacity: 1 !important;
}

table#alertLogTable .select2-container--default.select2-container--focus .select2-selection--multiple,
table#SAG-System-Table .select2-container--default.select2-container--focus .select2-selection--multiple {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

table#alertLogTable .select2-container--default .select2-selection--multiple,
table#SAG-System-Table .select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    font-size: 14px;
}

.default-cursor {
    cursor: default !important;
}

tr.selected>* {
    box-shadow: none !important;
    color: inherit !important;
}

.infoButton:hover {
    color: #138496 !important;
}

.tableWrapper .select2-selection--multiple {
    overflow: hidden !important;
    height: auto !important;
}