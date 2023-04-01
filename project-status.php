<?php

namespace YaleREDCap\SystemUserRights;

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/015226af80.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />

<div class="projhdr">
    <i class='fa-solid fa-user-secret'></i>&nbsp;<span>System User Rights</span>
</div>

<?php
$project_id = $module->getProjectId();
$discrepantRights = $module->getUsersWithBadRights($project_id);
if (empty($discrepantRights)) {
    exit();
}
?>

<table class="table">
    <thead class="thead-dark">
        <tr>
            <th><input type="checkbox" onchange="$('.user-selector input').prop('checked', $(this).prop('checked'));"></input></th>
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
            $hasDiscrepancy = !empty($thisUsersRights["bad"]); ?>
            <tr class="<?= $hasDiscrepancy ? "table-danger" : "text-secondary" ?>">
                <td class="align-middle user-selector"><?= $hasDiscrepancy ? '<input type="checkbox"></input>' : '' ?></td>
                <td class="align-middle"><strong><?= $user ?></strong></td>
                <td class="align-middle">Name</td>
                <td class="align-middle">Email</td>
                <td class="align-middle">Expiration</td>
                <td class="align-middle">System Role</td>
                <td class="align-middle">
                    <?php
                    if ($hasDiscrepancy) { ?>
                        <a class="text-primary" style="text-decoration: underline; cursor: pointer;" data-toggle="modal" data-target="#modal-<?= $user ?>"><?= sizeof($thisUsersRights["bad"]) ?> Rights</a>
                        <div class="modal fade" id="modal-<?= $user ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <table class="table table-hover table-borderless">
                                            <tbody>
                                                <?php foreach ($thisUsersRights["bad"] as $right) {
                                                    echo "<tr><td>$right</td></tr>";
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } else {
                        echo "none";
                    }
                    ?>
                </td>
                <td class="align-middle">Project Role</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<script>

</script>