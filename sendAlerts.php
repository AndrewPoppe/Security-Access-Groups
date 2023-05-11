<?php

namespace YaleREDCap\SystemUserRights;

require_once 'TextReplacer.php';
require_once 'Alert.php';

use YaleREDCap\SystemUserRights\Alert;

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(400);
    exit;
}

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$data = filter_input_array(INPUT_POST, [
    'alertType'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'displayFromName'                                   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'displayFromName-userExpiration-UserRightsHolders'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'fromEmail'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'fromEmail-userExpiration-UserRightsHolders'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailSubject'                                      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailSubject-userExpiration-UserRightsHolders'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailBody'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'usersEmailBody'                                    => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'userRightsHoldersEmailBody'                        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'sendReminder'                                      => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_VALIDATE_BOOL
    ),
    'sendUserNotification'                              => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_VALIDATE_BOOL
    ),
    'sendNotification-userExpiration-UserRightsHolders' => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_VALIDATE_BOOL
    ),
    'delayDays'                                         => array(
        'filter'  => FILTER_VALIDATE_INT,
        'options' => array( 'min_range' => 1 )
    ),
    'delayDays-UserRightsHolders'                       => array(
        'filter'  => FILTER_VALIDATE_INT,
        'options' => array( 'min_range' => 1 )
    ),
    'delayDays-expiration'                              => array(
        'filter'  => FILTER_VALIDATE_INT,
        'options' => array( 'min_range' => 0 )
    ),
    'recipients'                                        => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_REQUIRE_ARRAY
    ),
    'reminderSubject'                                   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'reminderBody'                                      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'users'                                             => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_REQUIRE_ARRAY
    )
]);

if ( $data['alertType'] === 'expiration' ) {
    $data['sag_expiration_date'] = date('Y-m-d', strtotime("+" . $data['delayDays-expiration'] . " days"));
}

$Alert = new Alert($module, $data);
$Alert->sendAlerts();
echo json_encode($data);
exit();