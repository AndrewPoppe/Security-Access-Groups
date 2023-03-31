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
var_dump($module->getUsersWithBadRights($project_id));
