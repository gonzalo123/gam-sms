<?php

/**
 * GSM Modem AT Send/receive
 * Adapter is loader via dependency injection
 *
 * THIS PROGRAM COMES WITH ABSOLUTELY NO WARANTIES !
 * USE IT AT YOUR OWN RISKS !
 *
 * @author Gonzalo Ayuso <gonzalo123@gmail.com>
 * @copyright under GPL 2 licence
 */
class Sms
{
    private $_serial;
    private $debug;
    protected $_pinOK = false;

    const EXCEPTION_PIN_ERROR = 1;
    const EXCEPTION_NO_PIN = 2;
    const EXCEPTION_SERVICE_NOT_IMPLEMENTED = 3;
    /**
     * Factory. Creates new instance. Dependency injections with the type os Modem
     * valid serial resources:
     *   Sms_Serial: GSM modem conected via seria interface
     *   Sms_Http: GSM modem conected via seria/ethernet converter
     *   Sms_Dummy: Mock for testing
     * 
     * @param Sms_Interface $serial
     * @param Boolean $debug       
     * @return Sms
     */
    public static function factory($serial, $debug=false)
    {
        if (!($serial instanceof Sms_Serial ||
                $serial instanceof Sms_Http ||
                $serial instanceof Sms_Dummy
                )) {
            throw new Exception("NOT IMPLEMENTED", self::EXCEPTION_SERVICE_NOT_IMPLEMENTED);
        }

        $serial->setValidOutputs(array(
            'OK',
            'ERROR',
            '+CPIN: SIM PIN',
            '+CPIN: READY'
        ));

        return new self($serial, $debug);
    }

    protected function __construct($serial, $debug=false)
    {
        $this->_serial = $serial;
        $this->_debug  = $debug;
    }

    private function readPort($returnBufffer = false)
    {
        $out = null;
        list($last, $buffer) = $this->_serial->readPort();
        if ($returnBufffer) {
            $out = $buffer;
        } else {
            $out = strtoupper($last);
        }
        if ($this->_debug == true) {
            echo $out . "\n";
        }
        return $out;
    }

    private function sendMessage($msg)
    {
        $this->_serial->sendMessage($msg);
    }

    private function deviceOpen()
    {
        $this->_serial->deviceOpen();
    }

    private function deviceClose()
    {
        $this->_serial->deviceClose();
    }

    /**
     * Delete selected id from SMS SIM
     *
     * @param unknown_type $id
     * @return unknown
     */
    public function deleteSms($id)
    {
        $this->deviceOpen();
        $this->sendMessage("AT+CMGD={$id}\r");
        $out = $this->readPort();
        $this->deviceClose();
        if ($out == 'OK') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sends a SMS to a selected tlfn
     * @param Integer $tlfn
     * @param String  $text
     * @return Boolean
     */
    public function sendSMS($tlfn, $text)
    {
        if ($this->_pinOK) {
            $text = substr($text, 0, 160);
            $this->deviceOpen();
            
            $this->sendMessage("AT+CMGS={$tlfn}");
            $this->sendMessage("\r");
            $this->sendMessage($text);
            $this->sendMessage(chr(26));
            
            $out = $this->readPort();

            $this->deviceClose();
            if ($out == 'OK') {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception("Please insert the PIN", self::EXCEPTION_NO_PIN);
        }
    }

    public function isPinOk()
    {
        return $this->_pinOK;
    }

    /**
     * Inserts the pin number.
     * first checks if PIN is set. If it's set nothing happens
     * @param Integer $pin
     * @return Sms
     */
    public function insertPin($pin)
    {
        $this->deviceOpen();

        $this->sendMessage("AT+CPIN?\r");
        $out = $this->readPort();
        $this->deviceClose();

        if ($out == "+CPIN: SIM PIN") {
            $this->deviceOpen();
            if (is_null($pin) || $pin == '') {
                throw new Exception("PIN ERROR", self::EXCEPTION_PIN_ERROR);
            }
            $this->sendMessage("AT+CPIN={$pin}\r");
            $out = $this->readPort();
            $this->deviceClose();
            // I don't know why but I need to wait a few seconds until
            // start sending SMS. Only after the first PIN
            sleep(20);
        }

        switch ($out) {
            case "+CPIN: READY":
            case "OK":
                $this->_pinOK = true;
                break;
        }

        if ($this->_pinOK === true) {
            return $this;
        } else {
            throw new Exception("PIN ERROR ({$out})", self::EXCEPTION_PIN_ERROR);
        }
    }

    const ALL = "ALL";
    const UNREAD = "REC UNREAD";
    /**
     * Read Inbox
     *
     * @param String $mode ALL | UNREAD
     * @return Array
     */
    public function readInbox($mode=self::ALL)
    {
        $inbox = $return = array();
        if ($this->_pinOK) {
            $this->deviceOpen();
            $this->sendMessage("AT+CMGF=1\r");
            $out = $this->readPort();
            if ($out == 'OK') {
                $this->sendMessage("AT+CMGL=\"{$mode}\"\r");
                $inbox = $this->readPort(true);
            }
            $this->deviceClose();
            if (count($inbox) > 2) {
                array_pop($inbox);
                array_pop($inbox);
                $arr = explode("+CMGL:", implode("\n", $inbox));
                
                for ($i = 1; $i < count($arr); $i++) {
                    $arrItem = explode("\n", $arr[$i], 2);

                    // Header
                    $headArr = explode(",", $arrItem[0]);

                    $fromTlfn = str_replace('"', null, $headArr[2]);
                    $id = $headArr[0];
                    $date = $headArr[4];
                    $hour = $headArr[5];

                    // txt
                    $txt = $arrItem[1];

                    $return[] = array('id' => $id, 'tlfn' => $fromTlfn, 'msg' => $txt, 'date' => $date, 'hour' => $hour);
                }
            }
            return $return;
        } else {
            throw new Exception("Please insert the PIN", self::EXCEPTION_NO_PIN);
        }
    }

}
