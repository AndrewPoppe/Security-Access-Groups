<?php

namespace YaleREDCap\SystemUserRights;

use YaleREDCap\SystemUserRights\SystemUserRights;

class Alerts
{

    private $module;

    public function __construct(SystemUserRights $module)
    {
        $this->module = $module;
    }

    /**
     * @param mixed $project_id 
     * @param string $adminUsername
     * 
     * @return string
     */
    public function getUserEmailModal($project_id, string $adminUsername)
    {
        $emailAddresses = $this->getEmailAddresses($adminUsername);

?>
        <div class="modal fade userAlert" id="emailUsersModal" aria-labelledby="emailUsersTitle" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-light">
                        <h5 class="modal-title" id="emailUsersTitle">Alert Project Users</h5>
                        <button type="button" class="btn-close btn-primary align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="emailUsersForm">
                            <div class="row mb-2 primaryEmail">
                                <div class="col">
                                    <div class="border bg-light p-4">
                                        <div class="form-group row">
                                            <label for="fromEmail" class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                            <div class="col-sm-4">
                                                <input id="displayFromName" name="displayFromName" type="text" class="form-control form-control-sm" placeholder="Display name (optional)">
                                            </div>
                                            <div class="col-sm-5 pl-0">
                                                <select id="fromEmail" name="fromEmail" class="form-control form-control-sm">
                                                    <?php foreach ($emailAddresses as $key => $emailAddress) { ?>
                                                        <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="emailSubject" class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                            <div class="col-sm-9">
                                                <input id="emailSubject" name="emailSubject" type="text" class="form-control form-control-sm" required aria-required="true">
                                            </div>
                                            <div class="invalid-feedback">You must provide a subject for the email</div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <div class="col">
                                                <label for="emailBody" class="col-form-label col-form-label-sm">Email Body:</label>
                                                <textarea id="emailBody" name="emailBody" type="text" class="form-control form-control-sm richtext emailBody"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <div class="col text-right">
                                                <button class="btn btn-info btn-xs" type="button" onclick="previewEmail($('.primaryEmail'));"><i class="fa-eye fa-regular mr-1"></i>Preview</button>
                                            </div>
                                        </div>
                                        <div class="form-group row" style="font-size: small;">
                                            <div class="col ml-2">
                                                <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                <table>
                                                    <?php foreach ($this->getPlaceholdersUsers() as $placeholder => $description) {
                                                        echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                    } ?>
                                                </table>
                                                <p><span>You can also use <button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>, but few will be applicable.</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row reminderEmail">
                                <div class="col">
                                    <div class="border bg-reminder p-4">
                                        <div class="form-group row mb-0">
                                            <label class="col-sm-3 col-form-label col-form-label-sm">Send Reminder?</label>
                                            <div class="col-sm-9">
                                                <div class="form-check">
                                                    <input id="sendReminder" name="sendReminder" type="checkbox" name="sendReminder" class="form-check-input" value="1" data-toggle="collapse" data-target="#reminderInfo" aria-expanded="false" aria-controls="reminderInfo">
                                                    <label class="form-check-label" for="sendReminder">Yes, send a reminder</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="collapse mt-2" id="reminderInfo">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label col-form-label-sm">How many days until the reminder is sent?</label>
                                                <div class="col-sm-9 mt-2">
                                                    <input name="delayDays" type="number" min="1" value="14" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="form-group row">
                                                <label for="reminderSubject" class="col-sm-3 col-form-label col-form-label-sm">Reminder Subject:</label>
                                                <div class="col-sm-9">
                                                    <input id="reminderSubject" name="reminderSubject" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <div class="form-group row mb-1">
                                                <div class="col">
                                                    <label for="emailBody" class="col-form-label col-form-label-sm">Reminder Body:</label>
                                                    <textarea id="reminderBody" name="reminderBody" type="text" class="form-control form-control-sm richtext emailBody"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-1">
                                                <div class="col text-right">
                                                    <button class="btn btn-info btn-xs" type="button" onclick="previewEmail($('.reminderEmail'));"><i class="fa-eye fa-regular mr-1"></i>Preview</button>
                                                </div>
                                            </div>
                                            <div class="form-group row" style="font-size: small;">
                                                <div class="col ml-2">
                                                    <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                    <table>
                                                        <?php foreach ($this->getPlaceholdersUsers() as $placeholder => $description) {
                                                            echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                        } ?>
                                                    </table>
                                                    <p><span>You can also use <button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>, but few will be applicable.</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="submitButton" style="display:none;"></button>
                        </form>
                    </div>
                    <div class=" modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="$('#emailUsersForm button.submitButton').click();">Send Alerts</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="emailPreview" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            async function previewEmail($emailContainer) {
                const id = $emailContainer.find('textarea.emailBody').prop('id');
                const content = tinymce.get(id).getContent();
                const replacedContent = await replaceKeywordsPreview(content);
                $('#emailPreview div.modal-body').html(replacedContent);
                $('#emailUsersModal').css('z-index', 1039);
                $('#emailPreview').modal('show');
                $('#emailPreview').on('hidden.bs.modal', function(event) {
                    $('#emailUsersModal').css('z-index', 1050);
                });
            }

            async function replaceKeywordsPreview(text) {
                const data = {
                    'sag_user': 'robin123',
                    'sag_user_fullname': 'Robin Jones',
                    'sag_user_email': 'robin.jones@email.com',
                    'sag_rights': ['Project Design and Setup', 'User Rights', 'Create Records']
                };

                return $.post('<?= $this->module->getUrl('replaceSmartVariables.php') ?>', {
                    text: text,
                    data: data
                });
            }
        </script>
    <?php
    }

    /**
     * @param mixed $project_id 
     * @param string $adminUsername
     * 
     * @return string
     */
    public function getUserRightsHoldersEmailModal($project_id, string $adminUsername)
    {
        $emailAddresses = $this->getEmailAddresses($adminUsername);

    ?>
        <div class="modal fade userAlert" id="emailUserRightsHoldersModal" aria-labelledby="emailUserRightsHoldersTitle" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-xl modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-body">
                        <h5 class="modal-title" id="emailUserRightsHoldersTitle">Alert Project User Rights Holders</h5>
                        <button type="button" class="btn-close btn-warning align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="emailUserRightsHoldersForm">
                            <div class="row">
                                <div class="col-7">
                                    <div class="row mb-2 primaryEmail-UserRightsHolders">
                                        <div class="col">
                                            <div class="border bg-light p-4">
                                                <div class="form-group row">
                                                    <label for="fromEmail-UserRightsHolders" class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                                    <div class="col-sm-4">
                                                        <input id="displayFromName-UserRightsHolders" name="displayFromName" type="text" class="form-control form-control-sm" placeholder="Display name (optional)">
                                                    </div>
                                                    <div class="col-sm-5 pl-0">
                                                        <select id="fromEmail-UserRightsHolders" name="fromEmail" class="form-control form-control-sm">
                                                            <?php foreach ($emailAddresses as $key => $emailAddress) { ?>
                                                                <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="emailSubject-UserRightsHolders" class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                                    <div class="col-sm-9">
                                                        <input id="emailSubject-UserRightsHolders" name="emailSubject" type="text" class="form-control form-control-sm">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-1">
                                                    <div class="col">
                                                        <label for="emailBody-UserRightsHolders" class="col-form-label col-form-label-sm">Email Body:</label>
                                                        <textarea id="emailBody-UserRightsHolders" name="emailBody" type="text" class="form-control form-control-sm richtext emailBody"></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-1">
                                                    <div class="col text-right">
                                                        <button class="btn btn-info btn-xs" type="button" onclick="previewEmailUserRightsHolders($('.primaryEmail-UserRightsHolders'));"><i class="fa-eye fa-regular mr-1"></i>Preview</button>
                                                    </div>
                                                </div>
                                                <div class="form-group row" style="font-size: small;">
                                                    <div class="col ml-2">
                                                        <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                        <table>
                                                            <?php foreach ($this->getPlaceholdersUserRightsHolders() as $placeholder => $description) {
                                                                echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                            } ?>
                                                        </table>
                                                        <p><span>You can also use <button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>, but few will be applicable.</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row reminderEmail-UserRightsHolders">
                                        <div class="col">
                                            <div class="border bg-reminder p-4">
                                                <div class="form-group row mb-0">
                                                    <label class="col-sm-3 col-form-label col-form-label-sm">Send Reminder?</label>
                                                    <div class="col-sm-9">
                                                        <div class="form-check">
                                                            <input id="sendReminder-UserRightsHolders" name="sendReminder" type="checkbox" name="sendReminder" class="form-check-input" value="1" data-toggle="collapse" data-target="#reminderInfo-UserRightsHolders" aria-expanded="false" aria-controls="reminderInfo">
                                                            <label class="form-check-label" for="sendReminder-UserRightsHolders">Yes, send a reminder</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="collapse mt-2" id="reminderInfo-UserRightsHolders">
                                                    <div class="form-group row">
                                                        <label class="col-sm-3 col-form-label col-form-label-sm">How many days until the reminder is sent?</label>
                                                        <div class="col-sm-9 mt-2">
                                                            <input name="delayDays" type="number" min="1" value="14" class="form-control form-control-sm">
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="form-group row">
                                                        <label for="reminderSubject-UserRightsHolders" class="col-sm-3 col-form-label col-form-label-sm">Reminder Subject:</label>
                                                        <div class="col-sm-9">
                                                            <input id="reminderSubject-UserRightsHolders" name="reminderSubject" type="text" class="form-control form-control-sm">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-1">
                                                        <div class="col">
                                                            <label for="reminderBody-UserRightsHolders" class="col-form-label col-form-label-sm">Reminder Body:</label>
                                                            <textarea id="reminderBody-UserRightsHolders" name="reminderBody" type="text" class="form-control form-control-sm richtext emailBody"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-1">
                                                        <div class="col text-right">
                                                            <button class="btn btn-info btn-xs" type="button" onclick="previewEmailUserRightsHolders($('.reminderEmail-UserRightsHolders'));"><i class="fa-eye fa-regular mr-1"></i>Preview</button>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row" style="font-size: small;">
                                                        <div class="col ml-2">
                                                            <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                            <table>
                                                                <?php foreach ($this->getPlaceholdersUserRightsHolders() as $placeholder => $description) {
                                                                    echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                                } ?>
                                                            </table>
                                                            <p><span>You can also use <button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>, but few will be applicable.</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5 pl-1">
                                    <div class="row mb-2">
                                        <div class="col">
                                            <div class="mb-1" style="font-size: 14px;">
                                                <strong>Select the recipients:</strong>
                                            </div>
                                            <table class="table table-sm table-bordered" style="font-size: 12px;">
                                                <colgroup>
                                                    <col class="col-md-1">
                                                    <col class="col-md-2">
                                                    <col class="col-md-3">
                                                    <col class="col-md-4">
                                                    <col class="col-md-2">
                                                </colgroup>
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th scope="col" style="color: #333 !important;"><input style="display:block; margin: 0 auto;" type="checkbox" class="selectAll" id="selectAllUserRightsHolders" onchange="$('.user-rights-holder-selector input').prop('checked', $(this).prop('checked')).trigger('change');"></th>
                                                        <th scope="col" style="color: #333 !important;">REDCap Username</th>
                                                        <th scope="col" style="color: #333 !important;">Name</th>
                                                        <th scope="col" style="color: #333 !important;">Email</th>
                                                        <th scope="col" style="color: #333 !important;">Previously Notified?</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="word-wrap" style="word-wrap: anywhere;">
                                                    <?php
                                                    $userRightsHolders = $this->module->getUserRightsHolders($this->module->getProjectId());
                                                    foreach ($userRightsHolders as $userRightsHolder) { ?>
                                                        <tr data-user="<?= $userRightsHolder["username"] ?>">
                                                            <td class="align-middle user-rights-holder-selector" style="vertical-align: middle !important;"><input style="display:block; margin: 0 auto;" type="checkbox"></td>
                                                            <td><?= $userRightsHolder["username"] ?></td>
                                                            <td><?= $userRightsHolder["fullname"] ?></td>
                                                            <td><?= $userRightsHolder["email"] ?></td>
                                                            <td></td>
                                                        </tr>
                                                    <?php }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class=" modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning">Send Alerts</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="emailPreview-UserRightsHolders" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            async function previewEmailUserRightsHolders($emailContainer) {
                const id = $emailContainer.find('textarea.emailBody').prop('id');
                const content = tinymce.get(id).getContent();
                const replacedContent = await replaceKeywordsPreviewUserRightsHolders(content);
                $('#emailPreview-UserRightsHolders div.modal-body').html(replacedContent);
                $('#emailUserRightsHoldersModal').css('z-index', 1039);
                $('#emailPreview-UserRightsHolders').modal('show');
                $('#emailPreview-UserRightsHolders').on('hidden.bs.modal', function(event) {
                    $('#emailUserRightsHoldersModal').css('z-index', 1050);
                });
            }

            async function replaceKeywordsPreviewUserRightsHolders(text) {

                const data = {
                    "sag_users": [
                        'robin123',
                        'alex456',
                        'drew789'
                    ],
                    "sag_fullnames": [
                        'Robin Jones',
                        'Alex Thomas',
                        'Drew Jackson'
                    ],
                    "sag_emails": [
                        'robin.jones@email.com',
                        'alex.thomas@email.com',
                        'drew.jackson@email.com'
                    ],
                    "sag_rights": [
                        ['Project Design and Setup', 'User Rights', 'Create Records'],
                        ['Logging', 'Reports & Report Builder'],
                        ['Data Export - Full Data Set', 'Data Viewing - View & Edit', 'Data Access Groups', 'Stats & Charts', 'Survey Distribution Tools', 'File Repository']
                    ]
                };

                return $.post('<?= $this->module->getUrl('replaceSmartVariables.php') ?>', {
                    text: text,
                    data: data
                });
            }
        </script>
    <?php
    }

    /**
     * @param mixed $project_id 
     * 
     * @return string
     */
    public function getUserExpirationSchedulerModal($project_id)
    {
    ?>
        <div class="modal fade userAlert" id="userExpirationSchedulerModal" aria-labelledby="userExpirationSchedulerTitle" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-light">
                        <h5 class="modal-title" id="userExpirationSchedulerTitle">Schedule Expiration of User(s)</h5>
                        <button type="button" class="btn-close btn-danger align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userExpirationSchedulerForm">
                            <div class="row">
                                <div class="col">
                                    <div class="border p-4">
                                        <div class="form-group row">
                                            <label class="col-sm-5 col-form-label col-form-label-sm">How many days until the users are expired?</label>
                                            <div class="col-sm-7 mt-2">
                                                <input name="delayDays" type="number" min="1" value="14" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class=" modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">Schedule Expiration</button>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    public function getPlaceholdersUserRightsHolders(): array
    {
        return [
            "sag-users" => "A formatted list of usernames",
            "sag-user-fullnames" => "A formatted list of users' full names",
            "sag-user-emails" => "A formatted list of user emails",
            "sag-users-table" => "A formatted table of usernames, full names, and email addresses",
            "sag-users-table-full" => "A formatted table of usernames, full names, email addresses, and non-compliant rights",
            "sag-project-title" => "The title of the project",
        ];
    }

    public function getPlaceholdersUsers(): array
    {
        return [
            "sag-user" => "The user's username",
            "sag-user-fullname" => "The user's full name",
            "sag-user-email" => "The user's email address",
            "sag-rights" => "<span>A formatted list of the rights that do not</span><br><span>conform with the user's security access group.</span>",
            "sag-project-title" => "The title of the project",
        ];
    }

    private function getEmailAddresses(string $username): array
    {
        $emails = [];
        $sql = "SELECT user_email, user_email2, user_email3 FROM redcap_user_information WHERE username = ?";
        $result = $this->module->query($sql, [$username]);
        $emailRow = $result->fetch_assoc();
        foreach ($emailRow as $email) {
            if (!empty($email)) {
                $emails[] = $email;
            }
        }
        $universalEmail = $this->getUniversalEmailAddress();
        if (!empty($universalEmail)) {
            $emails[] = $universalEmail;
        }
        return $emails;
    }

    private function getUniversalEmailAddress()
    {
        $sql = "SELECT value FROM redcap_config WHERE field_name = 'from_email'";
        $result = $this->module->query($sql, []);
        return $result->fetch_assoc()["value"];
    }
}
