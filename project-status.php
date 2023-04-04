<?php

namespace YaleREDCap\SystemUserRights;

require_once "Alerts.php";

use YaleREDCap\SystemUserRights\Alerts;

$Alerts = new Alerts($module);

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/015226af80.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />

<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-user-secret'></i>&nbsp;<span>System User Rights</span>
    </div>

    <?php
    $project_id = $module->getProjectId();
    $adminUsername = $module->getUser()->getUsername();
    $discrepantRights = $module->getUsersWithBadRights($project_id);
    if (empty($discrepantRights)) {
        exit();
    }
    ?>
    <div>
        <p style="font-size:large;">Check User Rights</p>
        <p>Current users in the ...</p>
    </div>
    <div class="buttonContainer mb-2 pl-3">
        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#emailUsersModal" disabled><i class="fa-sharp fa-regular fa-envelope"></i> Email User(s)</button>
        <button type="button" class="btn btn-xs btn-info" disabled><i class="fa-kit fa-sharp-regular-envelope-circle-exclamation"></i> Email User Rights Holders</button>
        <button type="button" class="btn btn-xs btn-danger" disabled><i class="fa-solid fa-user-xmark"></i> Expire User(s)</button>
    </div>
    <div class="container ml-0">
        <table class="table table-sm table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th><input type="checkbox" onchange="$('.user-selector input').prop('checked', $(this).prop('checked')).trigger('change');"></input></th>
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
                    $badRights = $thisUsersRights["bad"];
                    $hasDiscrepancy = !empty($badRights);
                    $isExpired = $thisUsersRights["expiration"] !== "never" && strtotime($thisUsersRights["expiration"]) < strtotime("today");
                    $rowClass = $hasDiscrepancy ? "table-danger" : "table-success";
                    $rowClass = $isExpired ? "text-secondary" : $rowClass; ?>
                    <tr data-user="<?= $user ?>" class="<?= $rowClass ?>">
                        <td class="align-middle user-selector"><?= $hasDiscrepancy ? '<input type="checkbox"></input>' : '' ?></td>
                        <td class="align-middle"><strong><?= $user ?></strong></td>
                        <td class="align-middle"><?= $thisUsersRights["name"] ?></td>
                        <td class="align-middle"><?= $thisUsersRights["email"] ?></td>
                        <td class="align-middle <?= $isExpired ? 'text-danger' : '' ?>"><?= $thisUsersRights["expiration"] ?></td>
                        <td class="align-middle"><?= $thisUsersRights["system_role"] ?></td>
                        <td class="align-middle">
                            <?php
                            if ($hasDiscrepancy) { ?>
                                <a class="text-primary" style="text-decoration: underline; cursor: pointer;" data-toggle="modal" data-target="#modal-<?= $user ?>"><?= sizeof($badRights) . (sizeof($badRights) > 1 ? " Rights" : " Right") ?></a>
                                <div class="modal fade" id="modal-<?= $user ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5>Discrepant Rights for <?= $thisUsersRights["name"] . " (" . $user . ")" ?></h5>
                                            </div>
                                            <div class="modal-body">
                                                <div class="d-flex justify-content-center">
                                                    <table class="table table-sm table-hover table-borderless mb-0">
                                                        <tbody>
                                                            <?php foreach ($badRights as $right) {
                                                                echo "<tr style='cursor: default;'><td><span>$right</span></td></tr>";
                                                            } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
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
                        <td class="align-middle"><?= $thisUsersRights["project_role"] ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php $Alerts->getUserEmailModal($project_id, $adminUsername); ?>
    <script>
        $(document).ready(function() {
            $('.user-selector input').change(function(event) {
                if ($('.user-selector input').is(':checked')) {
                    $('.buttonContainer button').prop('disabled', false);
                } else {
                    $('.buttonContainer button').prop('disabled', true);
                }
            });
            $('#emailUsersModal').modal('show');
            tinymce.init({
                entity_encoding: "raw",
                default_link_target: '_blank',
                selector: ".richtext",
                height: 350,
                branding: false,
                statusbar: true,
                menubar: false,
                elementpath: false,
                plugins: ['paste autolink lists link searchreplace code fullscreen table directionality hr'],
                toolbar1: 'formatselect | hr | bold italic underline link | alignleft aligncenter alignright alignjustify | undo redo',
                toolbar2: 'bullist numlist | outdent indent | table tableprops tablecellprops | forecolor backcolor | searchreplace code removeformat | fullscreen',
                contextmenu: "copy paste | link image inserttable | cell row column deletetable",
                content_css: app_path_webroot + "Resources/webpack/css/fontawesome/css/all.min.css," + app_path_webroot + "Resources/css/style.css",
                relative_urls: false,
                convert_urls: false,
                convert_fonts_to_spans: true,
                extended_valid_elements: 'i[class]',
                paste_word_valid_elements: "b,strong,i,em,h1,h2,u,p,ol,ul,li,a[href],span,color,font-size,font-color,font-family,mark,table,tr,td",
                paste_retain_style_properties: "all",
                paste_postprocess: function(plugin, args) {
                    args.node.innerHTML = cleanHTML(args.node.innerHTML);
                },
                remove_linebreaks: true,
                // content_style: 'body { font-weight: bold; }',
                // formats: {
                //     bold: {
                //         inline: 'span',
                //         styles: {
                //             'font-weight': 'normal' // Make the 'bold' option function like an 'unbold' instead.
                //         }
                //     }
                // }
            });
        });
    </script>
</div>