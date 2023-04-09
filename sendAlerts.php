<?php

namespace YaleREDCap\SystemUserRights;

require_once 'TextReplacer.php';

use YaleREDCap\SystemUserRights\TextReplacer;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(400);
    exit;
}

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

$data = filter_input_array(INPUT_POST, [
    'alertType' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'displayFromName' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'fromEmail' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailSubject' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailBody' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'sendReminder' => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags' => FILTER_VALIDATE_BOOL
    ),
    'delayDays' =>  array(
        'filter'    => FILTER_VALIDATE_INT,
        'options'   => array('min_range' => 1)
    ),
    'reminderSubject' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'reminderBody' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'users' => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags' => FILTER_REQUIRE_ARRAY
    )
]);

$users = $data["users"];


// TODO: Add some (most?) of this logic to the Alerts class
foreach ($users as $index => $user) {
    $thisBodyReplacer = new TextReplacer($module, $data['emailBody'], $user);
    $thisSubjectReplacer = new TextReplacer($module, $data['emailSubject'], $user);

    $user['emailBody'] = $thisBodyReplacer->replaceText();
    $user['emailSubject'] = $thisSubjectReplacer->replaceText();

    $users[$index] = $user;

    // Send Immediate Alert
    $subject = htmlspecialchars_decode($user['emailSubject']);
    $body = htmlspecialchars_decode($user['emailBody']);
    $module->log('user alert email', ['user' => $user['sag_user'], 'type' => $data['alertType'], 'user_data' => json_encode($user), 'from' => $data['fromEmail']]);
    \REDCap::email($user['sag_user_email'], $data['fromEmail'], $subject, $body, null, null, $data['displayFromName']);

    // Schedule Reminder if Needed
    if ($data['sendReminder'] == true) {
        $thisReminderBodyReplacer = new TextReplacer($module, $data['reminderBody'], $user);
        $thisReminderSubjectReplacer = new TextReplacer($module, $data['reminderSubject'], $user);
        $user['reminderBody'] = $thisReminderBodyReplacer->replaceText();
        $user['reminderSubject'] = $thisReminderSubjectReplacer->replaceText();

        $user['reminderTime'] = strtotime("today +" . $data['delayDays'] . " days");

        // TODO: Alerts class should definitely handle this
        // schedule the reminder
        $module->log('user alert reminder', [
            'user' => $user['sag_user'],
            'type' => $data['alertType'],
            'user_data' => json_encode($user),
            'from' => $data['fromEmail'],
            'reminderTime' => $user['reminderTime']
        ]);
    }
}

$data["users"] = $users;

echo json_encode($data);
