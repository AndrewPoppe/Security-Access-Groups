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
                            <div class="row mb-2">
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
                                                <input id="emailSubject" name="emailSubject" type="text" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col">
                                                <label for="emailBody" class="col-form-label col-form-label-sm">Email Body:</label>
                                                <textarea id="emailBody" name="emailBody" type="text" class="form-control form-control-sm richtext"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row" style="font-size: small;">
                                            <div class="col ml-4">
                                                <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                <table>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[user]</code></td>
                                                        <td>The user's username</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[user-fullname]</code></td>
                                                        <td>The user's name</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[user-email]</code></td>
                                                        <td>The user's email address</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[rights]</code></td>
                                                        <td>A formatted list of the rights that do not conform with the user's security access group.</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
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
                                            <div class="form-group row">
                                                <div class="col">
                                                    <label for="emailBody" class="col-form-label col-form-label-sm">Reminder Body:</label>
                                                    <textarea id="reminderBody" name="reminderBody" type="text" class="form-control form-control-sm richtext"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row" style="font-size: small;">
                                                <div class="col ml-4">
                                                    <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                    <table>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[user]</code></td>
                                                            <td>The user's username</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[user-fullname]</code></td>
                                                            <td>The user's name</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[user-email]</code></td>
                                                            <td>The user's email address</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[rights]</code></td>
                                                            <td>A formatted list of the rights that do not conform with the user's security access group.</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class=" modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary">Send Alerts</button>
                    </div>
                </div>
            </div>
        </div>
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
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-body">
                        <h5 class="modal-title" id="emailUserRightsHoldersTitle">Alert Project User Rights Holders</h5>
                        <button type="button" class="btn-close btn-warning align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="emailUserRightsHoldersForm">
                            <div class="row mb-2">
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
                                        <div class="form-group row">
                                            <div class="col">
                                                <label for="emailBody-UserRightsHolders" class="col-form-label col-form-label-sm">Email Body:</label>
                                                <textarea id="emailBody-UserRightsHolders" name="emailBody" type="text" class="form-control form-control-sm richtext"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row" style="font-size: small;">
                                            <div class="col ml-4">
                                                <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                <table>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[users]</code></td>
                                                        <td>A formatted list of usernames</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[user-fullnames]</code></td>
                                                        <td>A formatted list of users' full names</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[user-emails]</code></td>
                                                        <td>A formatted list of user emails</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="dataPlaceholder">[users-rights]</code></td>
                                                        <td>A formatted list of the rights that do not conform with the user's security access group.</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
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
                                            <div class="form-group row">
                                                <div class="col">
                                                    <label for="reminderBody-UserRightsHolders" class="col-form-label col-form-label-sm">Reminder Body:</label>
                                                    <textarea id="reminderBody-UserRightsHolders" name="reminderBody" type="text" class="form-control form-control-sm richtext"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row" style="font-size: small;">
                                                <div class="col ml-4">
                                                    <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                    <table>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[users]</code></td>
                                                            <td>A formatted list of usernames</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[user-fullnames]</code></td>
                                                            <td>A formatted list of users' full names</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[user-emails]</code></td>
                                                            <td>A formatted list of user emails</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code class="dataPlaceholder">[users-rights]</code></td>
                                                            <td>A formatted list of the rights that do not conform with the user's security access group.</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
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
