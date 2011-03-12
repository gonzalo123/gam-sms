<?php
interface Sms_Interface
{
    public function deviceOpen();

    public function deviceClose();

    public function sendMessage($msg);

    public function readPort();

    public function setValidOutputs($validOutputs);
}