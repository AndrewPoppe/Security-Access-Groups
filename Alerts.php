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
        <div class="modal fade" id="emailUsersModal" aria-labelledby="emailUsersTitle" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="emailUsersTitle">Alert Project Users</h5>
                        <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="emailUsersForm">
                            <div class="row mb-2">
                                <div class="col">
                                    <div class="border bg-light p-4">
                                        <div class="form-group row">
                                            <label for="displayFromName" class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                            <div class="col-sm-4">
                                                <input id="displayFromName" type="text" class="form-control form-control-sm" placeholder="Display name (optional)">
                                            </div>
                                            <div class="col-sm-5 pl-0">
                                                <select id="fromEmail" class="form-control form-control-sm">
                                                    <?php foreach ($emailAddresses as $key => $emailAddress) { ?>
                                                        <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="emailSubject" class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                            <div class="col-sm-9">
                                                <input id="emailSubject" type="text" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col">
                                                <textarea id="emailBody" type="text" class="form-control form-control-sm richtext"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row" style="font-size: small;">
                                            <div class="col ml-4">
                                                <span><strong>You can use the following placeholders to insert information into your email subject and body:</strong></span>
                                                <table>
                                                    <tr>
                                                        <td><code class="user-select-all">[user]</code></td>
                                                        <td>The user's username</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="user-select-all">[user-fullname]</code></td>
                                                        <td>The user's name</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="user-select-all">[user-email]</code></td>
                                                        <td>The user's email address</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code class="user-select-all">[rights]</code></td>
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
                                    <div class="border bg-light p-4">
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label col-form-label-sm">Send Reminder?</label>
                                            <div class="col-sm-9">
                                                <div class="form-check">
                                                    <input id="sendReminder0" type="radio" name="sendReminder" class="form-check-input" value="0" checked>
                                                    <label class="form-check-label" for="sendReminder0">No</label>
                                                </div>
                                                <div class="form-check">
                                                    <input id="sendReminder1" type="radio" name="sendReminder" class="form-check-input" value="1">
                                                    <label class="form-check-label" for="sendReminder1">Yes</label>
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
