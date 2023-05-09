<?php
namespace YaleREDCap\SystemUserRights;

/** @var SystemUserRights $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$Alerts = new Alerts($module);
$alerts = $Alerts->getAlerts();
foreach ( $alerts as $alerts ) {
    //$Alerts->deleteAlert($alerts['id']);
}

var_dump($alerts);

?>
<link
    href="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.css"
    rel="stylesheet" />
<script
    src="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.js">
    </script>

<script defer src="<?= $module->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />


<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-user-secret'></i>&nbsp;<span>Security Access Groups</span>
    </div>
    <div class="clearfix">
        <div id="sub-nav" class="d-none d-sm-block mr-4 mb-0 ml-0">
            <ul>
                <li>
                    <a href="<?= $module->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li class="active">
                    <a href="<?= $module->getUrl('project-alert-log.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-envelopes-bulk"></i>
                        Alert Log
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="alertLogWrapper my-4 w-75">
        <table id="alertLogTable" class="border" style="">
            <thead>
                <tr>
                    <th>id</th>
                    <th>Send Time</th>
                    <th>Alert Type</th>
                    <th>User(s)</th>
                    <th>Recipient(s)</th>
                    <th>Status</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<script>
    $('#alertLogTable').DataTable({
        ajax: "<?= $module->getUrl('ajax/alerts.php') ?>",
        deferRender: true,
        columns: [{
            data: 'id',
        },
        {
            data: 'sendTime',
        },
        {
            data: 'alertType',
        },
        {
            data: 'users',
        },
        {
            data: 'recipients',
        },
        {
            data: 'status',
        }
        ],
        responsive: true
    });
</script>