<?php
/**
 * @author Gonzalo Ayuso <gonzalo123@gmail.com>
 * GSM Modem AT Dummy interface
 *
 * THIS PROGRAM COMES WITH ABSOLUTELY NO WARANTIES !
 * USE IT AT YOUR OWN RISKS !
 *
 * @author Gonzalo Ayuso <gonzalo123@gmail.com>
 * @copyright under GPL 2 licence
 */
class Sms_Dummy implements Sms_Interface
{
    public function deviceOpen()
    {
    }

    public function deviceClose()
    {
    }

    public function sendMessage($msg)
    {
    }

    public function readPort()
    {
        return array("OK", array());
    }

    private $_validOutputs = array();

    public function setValidOutputs($validOutputs)
    {
        $this->_validOutputs = $validOutputs;
    }
}