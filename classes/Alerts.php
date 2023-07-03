<?php

namespace YaleREDCap\SecurityAccessGroups;

class Alerts
{

    private SecurityAccessGroups $module;

    public function __construct(SecurityAccessGroups $module)
    {
        $this->module = $module;
    }

    /**
     * @param mixed $project_id 
     * @param string $adminUsername
     * 
     * @return void
     */
    public function getUserEmailModal($project_id, string $adminUsername) : void
    {
        $emailAddresses = $this->getEmailAddresses($adminUsername);

        ?>
<div class="modal fade userAlert" id="emailUsersModal" aria-labelledby="emailUsersTitle" data-backdrop="static"
    data-keyboard="false" aria-hidden="true">
    <div class="modal-lg modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-light">
                <h5 class="modal-title" id="emailUsersTitle">Alert Project Users</h5>
                <button type="button" class="btn-close btn-primary align-self-center" data-bs-dismiss="modal"
                    data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailUsersForm">
                    <div class="row mb-2 primaryEmail">
                        <div class="col">
                            <div class="border bg-light pt-4 px-4 pb-2">
                                <div class="form-group row">
                                    <label for="displayFromName"
                                        class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                    <div class="col-sm-4">
                                        <input id="displayFromName" name="displayFromName" type="text"
                                            class="form-control form-control-sm" placeholder="Display name (optional)">
                                    </div>
                                    <div class="col-sm-5 pl-0">
                                        <select id="fromEmail" name="fromEmail" class="form-control form-control-sm">
                                            <?php foreach ( $emailAddresses as $key => $emailAddress ) { ?>
                                            <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="emailSubject"
                                        class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                    <div class="col-sm-9">
                                        <input id="emailSubject" name="emailSubject" type="text"
                                            class="form-control form-control-sm" required aria-required="true">
                                        <div class="invalid-feedback">You must provide a subject for the email</div>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <div class="col">
                                        <label for="emailBody" class="col-form-label col-form-label-sm">Email
                                            Body:</label>
                                        <textarea id="emailBody" name="emailBody" type="text"
                                            class="form-control form-control-sm richtext emailBody"></textarea>
                                        <div class="invalid-feedback">You must provide a body for the email</div>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <div class="col text-right">
                                        <button class="btn btn-info btn-xs" type="button"
                                            onclick="previewEmail($('.primaryEmail'));"><i
                                                class="fa-eye fa-regular mr-1"></i>Preview</button>
                                    </div>
                                </div>
                                <div class="form-group row mb-0" style="font-size: small;">
                                    <div class="col ml-2">
                                        <table aria-label="placeholders">
                                            <thead>
                                                <tr>
                                                    <th colspan=2><span><strong>You can use the following placeholders
                                                                to insert information into
                                                                your email subject and body:</strong></span></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $this->getPlaceholdersUsers() as $placeholder => $description ) {
                                                            echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                        } ?>
                                            </tbody>
                                        </table>
                                        <p><span>You can also use <button
                                                    class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                    style="margin-left:3px;font-size:11px;padding:0 3px 1px;line-height:14px;"
                                                    onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                        class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart
                                                    Variables</button></span></p>
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
                                            <input id="sendReminder" name="sendReminder" type="checkbox"
                                                class="form-check-input" value="1" data-toggle="collapse"
                                                data-target="#reminderInfo" aria-expanded="false"
                                                aria-controls="reminderInfo">
                                            <label class="form-check-label" for="sendReminder">Yes, send a
                                                reminder</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse mt-2" id="reminderInfo">
                                    <div class="form-group row">
                                        <label for="delayDays" class="col-sm-3 col-form-label col-form-label-sm">How
                                            many days until the reminder is sent?</label>
                                        <div class="col-sm-9 mt-2">
                                            <input id="delayDays" name="delayDays" type="number" min="1" value="14"
                                                class="form-control form-control-sm" required aria-required="true">
                                            <div class="invalid-feedback">You must provide a number of days greater than
                                                1</div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group row">
                                        <label for="reminderSubject"
                                            class="col-sm-3 col-form-label col-form-label-sm">Reminder Subject:</label>
                                        <div class="col-sm-9">
                                            <input id="reminderSubject" name="reminderSubject" type="text"
                                                class="form-control form-control-sm" required aria-required="true">
                                            <div class="invalid-feedback">You must provide a subject for the reminder
                                                email</div>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col">
                                            <label for="reminderBody" class="col-form-label col-form-label-sm">Reminder
                                                Body:</label>
                                            <textarea id="reminderBody" name="reminderBody" type="text"
                                                class="form-control form-control-sm richtext emailBody"></textarea>
                                            <div class="invalid-feedback">You must provide a body for the reminder email
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col text-right">
                                            <button class="btn btn-info btn-xs" type="button"
                                                onclick="previewEmail($('.reminderEmail'));"><i
                                                    class="fa-eye fa-regular mr-1"></i>Preview</button>
                                        </div>
                                    </div>
                                    <div class="form-group row" style="font-size: small;">
                                        <div class="col ml-2">
                                            <table aria-label="placeholders">
                                                <thead>
                                                    <tr>
                                                        <th colspan=2><span><strong>You can use the following
                                                                    placeholders
                                                                    to insert information into
                                                                    your email subject and body:</strong></span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ( $this->getPlaceholdersUsers() as $placeholder => $description ) {
                                                                echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                            } ?>
                                                </tbody>
                                            </table>
                                            <p><span>You can also use <button
                                                        class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                        style="margin-left:3px;font-size:11px;padding:0 3px 1px;line-height:14px;"
                                                        onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                            class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>]
                                                        Smart Variables</button></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class=" modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendEmailAlerts();">Send Alerts</button>
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
     * @return void
     */
    public function getUserRightsHoldersEmailModal($project_id, string $adminUsername) : void
    {
        $emailAddresses = $this->getEmailAddresses($adminUsername);

        ?>
<div class="modal fade userAlert" id="emailUserRightsHoldersModal" aria-labelledby="emailUserRightsHoldersTitle"
    data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-xl modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-body">
                <h5 class="modal-title" id="emailUserRightsHoldersTitle">Alert Project User Rights Holders</h5>
                <button type="button" class="btn-close btn-warning align-self-center" data-bs-dismiss="modal"
                    data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailUserRightsHoldersForm">
                    <div class="row">
                        <div class="col-7">
                            <div class="row mb-2 primaryEmail-UserRightsHolders">
                                <div class="col">
                                    <div class="border bg-light p-4">
                                        <div class="form-group row">
                                            <label for="displayFromName-UserRightsHolders"
                                                class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                            <div class="col-sm-4">
                                                <input id="displayFromName-UserRightsHolders" name="displayFromName"
                                                    type="text" class="form-control form-control-sm"
                                                    placeholder="Display name (optional)">
                                            </div>
                                            <div class="col-sm-5 pl-0">
                                                <select id="fromEmail-UserRightsHolders" name="fromEmail"
                                                    class="form-control form-control-sm">
                                                    <?php foreach ( $emailAddresses as $key => $emailAddress ) { ?>
                                                    <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?>
                                                    </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="emailSubject-UserRightsHolders"
                                                class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                            <div class="col-sm-9">
                                                <input id="emailSubject-UserRightsHolders" name="emailSubject"
                                                    type="text" class="form-control form-control-sm">
                                                <div class="invalid-feedback">You must provide a subject for the email
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <div class="col">
                                                <label for="emailBody-UserRightsHolders"
                                                    class="col-form-label col-form-label-sm">Email Body:</label>
                                                <textarea id="emailBody-UserRightsHolders" name="emailBody" type="text"
                                                    class="form-control form-control-sm richtext emailBody"></textarea>
                                                <div class="invalid-feedback">You must provide a body for the email
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <div class="col text-right">
                                                <button class="btn btn-info btn-xs" type="button"
                                                    onclick="previewEmailUserRightsHolders($('.primaryEmail-UserRightsHolders'));"><i
                                                        class="fa-eye fa-regular mr-1"></i>Preview</button>
                                            </div>
                                        </div>
                                        <div class="form-group row" style="font-size: small;">
                                            <div class="col ml-2">
                                                <table aria-label="placeholders">
                                                    <thead>
                                                        <tr>
                                                            <th colspan=2><span><strong>You can use the following
                                                                        placeholders
                                                                        to insert information into
                                                                        your email subject and body:</strong></span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ( $this->getPlaceholdersUserRightsHolders() as $placeholder => $description ) {
                                                                    echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                                } ?>
                                                    </tbody>
                                                </table>
                                                <p><span>You can also use <button
                                                            class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                            style="margin-left:3px;font-size:11px;padding:0 3px 1px;line-height:14px;"
                                                            onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                                class="fa-solid fa-bolt fa-xs"
                                                                style="margin:0 1px;"></i>] Smart
                                                            Variables</button></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row reminderEmail-UserRightsHolders">
                                <div class="col">
                                    <div class="border bg-reminder p-4">
                                        <div class="form-group row mb-0">
                                            <label class="col-sm-3 col-form-label col-form-label-sm">Send
                                                Reminder?</label>
                                            <div class="col-sm-9">
                                                <div class="form-check">
                                                    <input id="sendReminder-UserRightsHolders" name="sendReminder"
                                                        type="checkbox" class="form-check-input" value="1"
                                                        data-toggle="collapse"
                                                        data-target="#reminderInfo-UserRightsHolders"
                                                        aria-expanded="false" aria-controls="reminderInfo">
                                                    <label class="form-check-label"
                                                        for="sendReminder-UserRightsHolders">Yes, send a
                                                        reminder</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="collapse mt-2" id="reminderInfo-UserRightsHolders">
                                            <div class="form-group row">
                                                <label for="delayDays-UserRightsHolders"
                                                    class="col-sm-3 col-form-label col-form-label-sm">How many days
                                                    until the reminder is sent?</label>
                                                <div class="col-sm-9 mt-2">
                                                    <input id="delayDays-UserRightsHolders"
                                                        name="delayDays-UserRightsHolders" type="number" min="1"
                                                        value="14" class="form-control form-control-sm">
                                                    <div class="invalid-feedback">You must provide a number of days
                                                        greater than 1</div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="form-group row">
                                                <label for="reminderSubject-UserRightsHolders"
                                                    class="col-sm-3 col-form-label col-form-label-sm">Reminder
                                                    Subject:</label>
                                                <div class="col-sm-9">
                                                    <input id="reminderSubject-UserRightsHolders" name="reminderSubject"
                                                        type="text" class="form-control form-control-sm">
                                                    <div class="invalid-feedback">You must provide a subject for the
                                                        reminder email</div>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-1">
                                                <div class="col">
                                                    <label for="reminderBody-UserRightsHolders"
                                                        class="col-form-label col-form-label-sm">Reminder Body:</label>
                                                    <div class="invalid-feedback">You must provide a body for the
                                                        reminder email</div>
                                                    <textarea id="reminderBody-UserRightsHolders" name="reminderBody"
                                                        type="text"
                                                        class="form-control form-control-sm richtext emailBody"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-1">
                                                <div class="col text-right">
                                                    <button class="btn btn-info btn-xs" type="button"
                                                        onclick="previewEmailUserRightsHolders($('.reminderEmail-UserRightsHolders'));"><i
                                                            class="fa-eye fa-regular mr-1"></i>Preview</button>
                                                </div>
                                            </div>
                                            <div class="form-group row" style="font-size: small;">
                                                <div class="col ml-2">
                                                    <table aria-label="placeholders">
                                                        <thead>
                                                            <tr>
                                                                <th colspan=2><span><strong>You can use the following
                                                                            placeholders
                                                                            to insert information into
                                                                            your email subject and body:</strong></span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ( $this->getPlaceholdersUserRightsHolders() as $placeholder => $description ) {
                                                                        echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                                    } ?>
                                                        </tbody>
                                                    </table>
                                                    <p><span>You can also use <button
                                                                class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                                style="margin-left:3px;font-size:11px;padding:0 3px 1px;line-height:14px;"
                                                                onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                                    class="fa-solid fa-bolt fa-xs"
                                                                    style="margin:0 1px;"></i>] Smart
                                                                Variables</button></span>
                                                    </p>
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
                                    <table aria-label="alert recipients" id="recipientTable_UserRightsHolders"
                                        class="table table-sm table-bordered" style="font-size: 12px;">
                                        <colgroup>
                                            <col class="col-md-1">
                                            <col class="col-md-2">
                                            <col class="col-md-3">
                                            <col class="col-md-4">
                                            <col class="col-md-2">
                                        </colgroup>
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col" style="color: #333 !important;"><input
                                                        style="display:block; margin: 0 auto;" type="checkbox"
                                                        class="selectAll" id="selectAllUserRightsHolders"
                                                        onchange="$('.user-rights-holder-selector input').prop('checked', $(this).prop('checked')).trigger('change');">
                                                </th>
                                                <th scope="col" style="color: #333 !important;">REDCap Username</th>
                                                <th scope="col" style="color: #333 !important;">Name</th>
                                                <th scope="col" style="color: #333 !important;">Email</th>
                                                <th scope="col" style="color: #333 !important;">Previously Notified?
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="word-wrap" style="word-wrap: anywhere;">
                                            <?php
                                                    $userRightsHolders = $this->module->getUserRightsHolders($this->module->getProjectId());
                                                    foreach ( $userRightsHolders as $userRightsHolder ) { ?>
                                            <tr data-user="<?= $userRightsHolder["username"] ?>">
                                                <td class="align-middle user-rights-holder-selector"
                                                    style="vertical-align: middle !important;"><input
                                                        style="display:block; margin: 0 auto;" type="checkbox"></td>
                                                <td>
                                                    <?= $userRightsHolder["username"] ?>
                                                </td>
                                                <td>
                                                    <?= $userRightsHolder["fullname"] ?>
                                                </td>
                                                <td>
                                                    <?= $userRightsHolder["email"] ?>
                                                </td>
                                                <td></td>
                                            </tr>
                                            <?php }
                                                    ?>
                                        </tbody>
                                    </table>
                                    <div class="invalid-feedback">You must select at least one recipient</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class=" modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="sendEmailAlerts_UserRightsHolders();">Send
                    Alerts</button>
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
    public function getUserExpirationModal($project_id, string $adminUsername)
    {
        $emailAddresses = $this->getEmailAddresses($adminUsername);
        ?>
<div class="modal fade userAlert" id="userExpirationModal" aria-labelledby="userExpirationTitle" data-backdrop="static"
    data-keyboard="false" aria-hidden="true">
    <div class="modal-lg modal-dialog modal-dialog-scrollable" id="userExpirationModalSecondary">
        <div class="modal-content">
            <div class="modal-header bg-danger text-light">
                <h5 class="modal-title" id="userExpirationTitle">Expire Project Users</h5>
                <button type="button" class="btn-close btn-danger align-self-center" data-bs-dismiss="modal"
                    data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userExpirationForm">
                    <!-- User Expiration Info -->
                    <div class="row mb-2 userExpirationListContainer">
                        <div class="col">
                            <div class="border bg-light">
                                <div class="row ml-4 mt-3 text-dangerrc">
                                    <h5><strong><i class="fa-solid fa-triangle-exclamation"></i> The following users
                                            will be expired in this project:</strong></h5>
                                </div>
                                <div class="form-group row ml-4 my-0">
                                    <table aria-label="users to be expired" id="userExpirationTable"
                                        class="table table-sm table-hover table-borderless w-50 ml-2">
                                        <thead>
                                            <tr style="border-bottom: 1px solid #bdbdbd;">
                                                <th>REDCap Username</th>
                                                <th>Name</th>
                                                <th>Email Address</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="form-group row mx-3">
                                    <div class="col-sm-7">
                                        <div class="row">
                                            <label class="col-form-label col-form-label-sm">Set the number of days until
                                                the users are expired</label>
                                        </div>
                                        <div class="row">
                                            <span class="text-dangerrc">Choose a value of "0" to expire the users
                                                immediately</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-5 mt-2">
                                        <input id="delayDays-expiration" name="delayDays-expiration" type="number"
                                            min="0" value="0" class="form-control form-control-sm">
                                        <div class="invalid-feedback">You must provide a number of days of at least 0
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- User Notification -->
                    <div class="row mb-2 userNotification">
                        <div class="col">
                            <div class="border bg-users p-4">
                                <div class="form-group row mb-0">
                                    <label class="col-sm col-form-label col-form-label-sm">Send Notification to
                                        User(s)?</label>
                                    <div class="col-sm">
                                        <div class="form-check">
                                            <input id="sendUserNotification" name="sendUserNotification" type="checkbox"
                                                class="form-check-input" value="1"
                                                onchange="$('#userNotificationInfo').collapse(this.checked ? 'show' : 'hide');">
                                            <label class="form-check-label" for="sendUserNotification">Yes, send a
                                                notification</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse mt-2" id="userNotificationInfo">
                                    <div class="form-group row">
                                        <label for="fromEmail"
                                            class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                        <div class="col-sm-4">
                                            <input id="displayFromName-userExpiration" name="displayFromName"
                                                type="text" class="form-control form-control-sm"
                                                placeholder="Display name (optional)">
                                        </div>
                                        <div class="col-sm-5 pl-0">
                                            <select id="fromEmail-userExpiration" name="fromEmail"
                                                class="form-control form-control-sm">
                                                <?php foreach ( $emailAddresses as $key => $emailAddress ) { ?>
                                                <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="emailSubject-userExpiration"
                                            class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                        <div class="col-sm-9">
                                            <input id="emailSubject-userExpiration" name="emailSubject" type="text"
                                                class="form-control form-control-sm" required aria-required="true">
                                            <div class="invalid-feedback">You must provide a subject for the email</div>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col">
                                            <label for="emailBody-userExpiration"
                                                class="col-form-label col-form-label-sm">Email Body:</label>
                                            <textarea id="emailBody-userExpiration" name="emailBody" type="text"
                                                class="form-control form-control-sm richtext emailBody"></textarea>
                                            <div class="invalid-feedback">You must provide a body for the email</div>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col text-right">
                                            <button class="btn btn-info btn-xs" type="button"
                                                onclick="previewEmail($('#userExpirationForm .userNotification'));"><i
                                                    class="fa-eye fa-regular mr-1"></i>Preview</button>
                                        </div>
                                    </div>
                                    <div class="form-group row mt-2" style="font-size: small;">
                                        <div class="col mx-3 p-2" style="background-color:#FFFFFF80;">
                                            <table aria-label="placeholders">
                                                <thead>
                                                    <tr>
                                                        <th colspan=2><span><strong>You can use the following
                                                                    placeholders
                                                                    to insert information into
                                                                    your email subject and body:</strong></span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ( $this->getPlaceholdersUsers(true) as $placeholder => $description ) {
                                                                echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                            } ?>
                                                </tbody>
                                            </table>
                                            <p><span>You can also use <button
                                                        class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                        style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;"
                                                        onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                            class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>]
                                                        Smart Variables</button></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- User Rights Holders Notification -->
                    <div class="row userRightsHoldersNotification">
                        <div class="col">
                            <div class="border bg-userRightsHolders p-4">
                                <div class="form-group row mb-0">
                                    <label class="col-sm col-form-label col-form-label-sm">Send Notification to User
                                        Rights Holders?</label>
                                    <div class="col-sm">
                                        <div class="form-check">
                                            <input id="sendNotification-userExpiration-UserRightsHolders"
                                                name="sendNotification-userExpiration-UserRightsHolders" type="checkbox"
                                                class="form-check-input" value="1"
                                                onchange="userExpirationUserRightsHoldersToggle(this.checked);">
                                            <label class="form-check-label"
                                                for="sendNotification-userExpiration-UserRightsHolders">Yes, send a
                                                notification</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse mt-2" id="notificationInfo-userExpiration-UserRightsHolders">
                                    <div class="form-group row">
                                        <label for="displayFromName-userExpiration-UserRightsHolders"
                                            class="col-sm-3 col-form-label col-form-label-sm">From:</label>
                                        <div class="col-sm-4">
                                            <input id="displayFromName-userExpiration-UserRightsHolders"
                                                name="displayFromName-userExpiration-UserRightsHolders" type="text"
                                                class="form-control form-control-sm"
                                                placeholder="Display name (optional)">
                                        </div>
                                        <div class="col-sm-5 pl-0">
                                            <select id="fromEmail-userExpiration-UserRightsHolders"
                                                name="fromEmail-userExpiration-UserRightsHolders"
                                                class="form-control form-control-sm">
                                                <?php foreach ( $emailAddresses as $key => $emailAddress ) { ?>
                                                <option <?= $key == 0 ? "selected" : "" ?>><?= $emailAddress ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="emailSubject-userExpiration-UserRightsHolders"
                                            class="col-sm-3 col-form-label col-form-label-sm">Subject:</label>
                                        <div class="col-sm-9">
                                            <input id="emailSubject-userExpiration-UserRightsHolders"
                                                name="emailSubject-userExpiration-UserRightsHolders" type="text"
                                                class="form-control form-control-sm">
                                            <div class="invalid-feedback">You must provide a subject for the email</div>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col">
                                            <label for="emailBody-userExpiration-UserRightsHolders"
                                                class="col-form-label col-form-label-sm">Email Body:</label>
                                            <div class="invalid-feedback">You must provide a body for the email</div>
                                            <textarea id="emailBody-userExpiration-UserRightsHolders" name="emailBody"
                                                type="text"
                                                class="form-control form-control-sm richtext emailBody"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <div class="col text-right">
                                            <button class="btn btn-info btn-xs" type="button"
                                                onclick="previewEmailUserRightsHolders($('#userExpirationForm .userRightsHoldersNotification'));"><i
                                                    class="fa-eye fa-regular mr-1"></i>Preview</button>
                                        </div>
                                    </div>
                                    <div class="form-group row mt-2" style="font-size: small;">
                                        <div class="col mx-3 p-2" style="background-color:#FFFFFFA0;">
                                            <table aria-label="placeholders">
                                                <thead>
                                                    <tr>
                                                        <th colspan=2><span><strong>You can use the following
                                                                    placeholders
                                                                    to insert information into
                                                                    your email subject and body:</strong></span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ( $this->getPlaceholdersUserRightsHolders(true) as $placeholder => $description ) {
                                                                echo "<tr><td><code class='dataPlaceholder'>[$placeholder]</code></td><td>$description</td></tr>";
                                                            } ?>
                                                </tbody>
                                            </table>
                                            <p><span>You can also use <button
                                                        class="btn btn-xs btn-rcgreen btn-rcgreen-light"
                                                        style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;"
                                                        onclick="smartVariableExplainPopup();setTimeout(function() {$('#smart_variable_explain_popup').parent().css('z-index', 1051);},300); return false;">[<i
                                                            class="fa-solid fa-bolt fa-xs" style="margin:0 1px;"></i>]
                                                        Smart Variables</button></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 pl-0" id="expireUsersUserRightsHolderSelection">

                            <div class="mb-1" style="font-size: 14px;">
                                <strong>Select the recipients:</strong>
                            </div>
                            <table aria-label="alert recipients - user rights holders"
                                id="recipientTable_userExpiration_UserRightsHolders"
                                class="table table-sm table-bordered" style="font-size: 12px;">
                                <colgroup>
                                    <col class="col-md-1">
                                    <col class="col-md-2">
                                    <col class="col-md-4">
                                    <col class="col-md-5">
                                </colgroup>
                                <thead class="table-warning">
                                    <tr>
                                        <th scope="col"><input style="display:block; margin: 0 auto;" type="checkbox"
                                                class="selectAll" id="selectAllUserRightsHolders"
                                                onchange="$('.user-rights-holder-selector input').prop('checked', $(this).prop('checked')).trigger('change');">
                                        </th>
                                        <th scope="col">REDCap Username</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                    </tr>
                                </thead>
                                <tbody class="word-wrap" style="word-wrap: anywhere;">
                                    <?php
                                            $userRightsHolders = $this->module->getUserRightsHolders($this->module->getProjectId());
                                            foreach ( $userRightsHolders as $userRightsHolder ) { ?>
                                    <tr data-user="<?= $userRightsHolder["username"] ?>">
                                        <td class="align-middle user-rights-holder-selector"
                                            style="vertical-align: middle !important;"><input
                                                style="display:block; margin: 0 auto;" type="checkbox"></td>
                                        <td>
                                            <?= $userRightsHolder["username"] ?>
                                        </td>
                                        <td>
                                            <?= $userRightsHolder["fullname"] ?>
                                        </td>
                                        <td>
                                            <?= $userRightsHolder["email"] ?>
                                        </td>
                                    </tr>
                                    <?php }
                                            ?>
                                </tbody>
                            </table>
                            <div class="invalid-feedback">You must select at least one recipient</div>

                        </div>
                    </div>
                </form>
            </div>
            <div class=" modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="expireUsersAndSendAlerts();">Expire Users</button>
            </div>
        </div>
    </div>
</div>
<script>
$('#notificationInfo-UserRightsHolders').on('shown.bs.collapse', function() {
    if (!$('#sendNotification-UserRightsHolders').is(':checked')) $(this).collapse('hide');
});
$('#notificationInfo-UserRightsHolders').on('hidden.bs.collapse', function() {
    if ($('#sendNotification-UserRightsHolders').is(':checked')) $(this).collapse('show');
});
$('#userNotificationInfo').on('shown.bs.collapse', function() {
    if (!$('#sendUserNotification').is(':checked')) $(this).collapse('hide');
});
$('#userNotificationInfo').on('hidden.bs.collapse', function() {
    if ($('#sendUserNotification').is(':checked')) $(this).collapse('show');
});
$('#expireUsersUserRightsHolderSelection').on('shown.bs.collapsed', function() {
    if (!$('#sendNotification-userExpiration-UserRightsHolders').is(':checked')) $(this).collapse('hide');
});
$('#expireUsersUserRightsHolderSelection').on('hidden.bs.collapse', function() {
    if ($('#sendNotification-userExpiration-UserRightsHolders').is(':checked')) $(this).collapse('show');
});

function userExpirationUserRightsHoldersToggle(checked) {
    if (checked) {
        $('#notificationInfo-userExpiration-UserRightsHolders').collapse("show");
        // $('#expireUsersUserRightsHolderSelection').collapse("show");
        $('#expireUsersUserRightsHolderSelection').show();
        $('#userExpirationModalSecondary').addClass("modal-xl").removeClass("modal-lg");
        $('div.row.userExpirationListContainer > div.col').addClass('col-8').removeClass('col');
        $('div.row.userNotification > div.col').addClass('col-8').removeClass('col');
    } else {
        $('div.row.userExpirationListContainer > div.col-8').addClass('col').removeClass('col-8');
        $('div.row.userNotification > div.col-8').addClass('col').removeClass('col-8');
        // $('#expireUsersUserRightsHolderSelection').collapse("hide");
        $('#expireUsersUserRightsHolderSelection').hide();
        $('#notificationInfo-userExpiration-UserRightsHolders').collapse("hide");
        $('#userExpirationModalSecondary').addClass("modal-lg").removeClass("modal-xl");
    }

}
</script>
<?php
    }



    public function getEmailPreviewModal()
    {
        ?>
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
<?php
    }

    public function getPlaceholdersUserRightsHolders($expiration = false) : array
    {
        $placeholders = [
            "sag-users"            => "A formatted list of usernames",
            "sag-user-fullnames"   => "A formatted list of users' full names",
            "sag-user-emails"      => "A formatted list of user emails",
            "sag-users-table"      => "A formatted table of usernames, full names, and email addresses",
            "sag-users-table-full" => "A formatted table of usernames, full names, email addresses, and non-compliant rights",
            "sag-project-title"    => "The title of the project",
        ];

        if ( $expiration ) {
            $placeholders["sag-expiration-date"] = "The date the users will be expired from the project";
        }

        return $placeholders;
    }

    public function getPlaceholdersUsers($expiration = false) : array
    {
        $placeholders = [
            "sag-user"          => "The user's username",
            "sag-user-fullname" => "The user's full name",
            "sag-user-email"    => "The user's email address",
            "sag-rights"        => "<span>A formatted list of the rights that do not</span><br><span>conform with the user's security access group.</span>",
            "sag-project-title" => "The title of the project",
        ];

        if ( $expiration ) {
            $placeholders["sag-expiration-date"] = "The date the user will be expired from the project";
        }

        return $placeholders;
    }
    private function getEmailAddresses(string $username) : array
    {
        $emails   = [];
        $sql      = "SELECT user_email, user_email2, user_email3 FROM redcap_user_information WHERE username = ?";
        $result   = $this->module->query($sql, [ $username ]);
        $emailRow = $result->fetch_assoc();
        foreach ( $emailRow as $email ) {
            if ( !empty($email) ) {
                $emails[] = $email;
            }
        }
        $universalEmail = $this->getUniversalEmailAddress();
        if ( !empty($universalEmail) ) {
            $emails[] = $universalEmail;
        }
        return $this->module->framework->escape($emails);
    }

    private function getUniversalEmailAddress()
    {
        $sql    = "SELECT value FROM redcap_config WHERE field_name = 'from_email'";
        $result = $this->module->query($sql, []);
        return $this->module->framework->escape($result->fetch_assoc()["value"]);
    }

    public function sendUserReminders($project_id)
    {
        $reminders = $this->getUserRemindersToSend($project_id);
        foreach ( $reminders as $reminder ) {
            // Send Alert
            $email_success = \REDCap::email($reminder['to'], $reminder['from'], $reminder["subject"], $reminder["body"], null, null, $reminder['displayFromName']);

            if ( !$email_success ) {
                $this->module->framework->log('Failure sending reminder', [ 'reminder_id' => $reminder['reminder_log_id'], 'to' => $reminder['to'], 'from' => $reminder['from'], 'project_id' => $project_id ]);
                $this->module->updateLog($reminder['reminder_log_id'], [ 'status' => 'error' ]);
            } else {
                $this->module->framework->log('Reminder sent', [ 'reminder_id' => $reminder['reminder_log_id'], 'to' => $reminder['to'], 'from' => $reminder['from'], 'project_id' => $project_id ]);
                $this->module->updateLog($reminder['reminder_log_id'], [ 'sentTimestamp' => time(), 'status' => 'sent' ]);
            }

        }
    }

    private function getUserRemindersToSend($project_id) : array
    {
        $sql               = "SELECT log_id, user, users,  alertType, reminderDate, fromEmail, displayFromName, emailBody, emailSubject, alert_log_id WHERE message = 'REMINDER' AND sentTimestamp < 0 AND status = 'scheduled' AND reminderDate < ? AND project_id = ?";
        $params            = [ time(), $project_id ];
        $result            = $this->module->queryLogs($sql, $params);
        $reminders_to_send = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( empty($row["users"]) ) {
                $users = [ json_decode($row["user"], true) ];
            } else {
                $users = json_decode($row["users"], true);
            }
            foreach ( $users as $user ) {
                $thisAlert                    = [];
                $thisAlert["to"]              = \REDCap::escapeHtml($user["sag_user_email"]);
                $thisAlert["from"]            = \REDCap::escapeHtml($row["fromEmail"]);
                $thisAlert["displayFromName"] = \REDCap::escapeHtml($row["displayFromName"]);
                $thisAlert["subject"]         = \REDCap::filterHtml($row["emailSubject"]);
                $thisAlert["body"]            = \REDCap::filterHtml($row["emailBody"]);
                $thisAlert["alert_log_id"]    = intval($row["alert_log_id"]);
                $thisAlert["reminder_log_id"] = intval($row["log_id"]);
                $reminders_to_send[]          = $thisAlert;
            }
        }
        return $reminders_to_send;
    }

    private function getRawAlerts($project_id)
    {
        $sql    = "SELECT log_id,
        timestamp,
        message 'Type',
        user,
        users,
        alertType 'Alert Type',
        recipient,
        recipientAddress,
        reminderDate,
        fromEmail,
        emailBody,
        emailSubject,
        sentTimestamp,
        status  WHERE message IN ('ALERT', 'REMINDER') AND project_id = ?";
        $params = [ $project_id ];
        $result = $this->module->framework->queryLogs($sql, $params);
        $alerts = [];
        while ( $row = $result->fetch_assoc() ) {
            $alerts[] = $row;
        }
        return $alerts;
    }

    /**
     * Grab array of all alerts and reminders in the project, sent and scheduled
     * 
     * @param mixed $project_id - if null, current project is used
     * 
     */
    public function getAlerts($project_id = null)
    {
        if ( empty($project_id) ) {
            $project_id = $this->module->framework->getProjectId();
        }
        $rawAlerts = $this->getRawAlerts($project_id);
        $alerts    = [];
        foreach ( $rawAlerts as $row ) {
            $thisAlert  = [];
            $users      = [];
            $usersArray = isset($row["users"]) ?
                json_decode($row["users"], true) :
                [ json_decode($row["user"], true) ];
            foreach ( $usersArray as $user ) {
                $users[] = \REDCap::escapeHtml($user["sag_user"]);
            }

            $recipients      = "";
            $recipientsArray = isset($row["recipients"]) ?
                json_decode($row["recipients"], true) :
                [ $row["recipient"] ];
            foreach ( $recipientsArray as $key => $recipient ) {
                $recipients .= $key = array_key_first($recipientsArray) ?
                    "" :
                    "<br>";
                $thisRecipient      = \REDCap::escapeHtml($recipient);
                $thisRecipientEmail = $this->module->framework->getUser($thisRecipient)->getEmail();
                $recipients .= "<strong>" . $thisRecipient . "</strong> (" . $thisRecipientEmail . ")";
            }

            $thisAlert["id"] = \REDCap::escapeHtml($row["log_id"]);
            if ( $row["Type"] === "ALERT" ) {
                $thisAlert["sendTime"] = $row["sentTimestamp"];
                $thisAlert["reminder"] = false;
            } else {
                $thisAlert["sendTime"] = $row["sentTimestamp"] > 0 ? $row["sentTimestamp"] : $row["reminderDate"];
                $thisAlert["reminder"] = true;
            }
            $thisAlert["sendTime"]   = \REDCap::escapeHtml($thisAlert["sendTime"]);
            $thisAlert["alertType"]  = \REDCap::escapeHtml($row["Alert Type"]);
            $thisAlert["users"]      = $users;
            $thisAlert["recipients"] = $recipients;
            $thisAlert["status"]     = \REDCap::escapeHtml($row["status"]) ?? "";
            $thisAlert["to"]         = $row["recipientAddress"];
            $thisAlert["from"]       = \REDCap::escapeHtml($row["fromEmail"]);
            $thisAlert["subject"]    = \REDCap::filterHtml($row["emailSubject"]);
            $thisAlert["body"]       = \REDCap::filterHtml($row["emailBody"]);

            $alerts[] = $thisAlert;
        }
        return $alerts;
    }

    public function getAlertById($alertId)
    {
        $project_id = $this->module->framework->getProjectId();
        $alerts     = $this->getAlerts($project_id);
        $alert      = null;
        foreach ( $alerts as $thisAlert ) {
            if ( $thisAlert['id'] == $alertId ) {
                $alert = $thisAlert;
            }
        }
        if ( $alert ) {
            $alert['table'] = $this->getAlertTable($alert);
        }
        return $alert;
    }

    private function getAlertTable(array $alert)
    {
        $table = "<table aria-label='alerts' class='table bg-white' style='border:1px solid #dee2e6'>";
        $table .= "<tr><th>From:</th><td>" . \REDCap::filterHtml($alert['from']) . "</td></tr>";
        $table .= "<tr><th>To:</th><td>" . \REDCap::filterHtml($alert['to']) . "</td></tr>";
        $table .= "<tr><th>Subject:</th><td>" . \REDCap::filterHtml($alert['subject']) . "</td></tr>";
        $table .= "<tr><th>Message:</th><td>" . \REDCap::filterHtml($alert['body']) . "</td></tr>";
        $table .= "</table>";
        return $table;
    }

    public function deleteAlert($alertId)
    {
        try {
            $sql    = "log_id = ?";
            $result = $this->module->framework->removeLogs($sql, [ $alertId ]);
            if ( !$result ) {
                throw new \Exception("Error deleting alert");
            }
            $this->module->framework->log("Alert deleted", [ "alertId" => $alertId ]);
            return $result;
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error deleting alert', [ 'error' => $e->getMessage(), 'alertId' => $alertId ]);
            return false;
        }
    }
}