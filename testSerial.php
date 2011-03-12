<?php
require_once('Sms.php');
require_once('Sms/Interface.php');
require_once('Sms/Serial.php');

$pin = 1234;

try {
    $serial = new Sms_Serial;
    $serial->deviceSet("/dev/ttyS0");
    $serial->confBaudRate(9600);
    $serial->confParity('none');
    $serial->confCharacterLength(8);
    
    $sms = Sms::factory($serial)->insertPin($pin);

    if ($sms->sendSMS(555987654, "test Hi")) {
        echo "SMS sent\n";
    } else {
        echo "Sent Error\n";
    }

    // Now read inbox
    foreach ($sms->readInbox() as $in) {
        echo"tlfn: {$in['tlfn']} date: {$in['date']} {$in['hour']}\n{$in['msg']}\n";

        // now delete sms
        if ($sms->deleteSms($in['id'])) {
            echo "SMS Deleted\n";
        }
    }
} catch (Exception $e) {
    switch ($e->getCode()) {
        case Sms::EXCEPTION_NO_PIN:
            echo "PIN Not set\n";
            break;
        case Sms::EXCEPTION_PIN_ERROR:
            echo "PIN Incorrect\n";
            break;
        case Sms::EXCEPTION_SERVICE_NOT_IMPLEMENTED:
            echo "Service Not implemented\n";
            break;
        default:
            echo $e->getMessage();
    }
}
