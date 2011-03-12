<?php
/**
 * GSM Modem AT Interface with a serial-ethernet adapter
 *
 * THIS PROGRAM COMES WITH ABSOLUTELY NO WARANTIES !
 * USE IT AT YOUR OWN RISKS !
 *
 * @author Gonzalo Ayuso <gonzalo123@gmail.com>
 * @copyright under GPL 2 licence
 */
class Sms_Http implements Sms_Interface
{
    private $_host;
    private $_port;

    function __construct($host=null, $port=null)
    {
        $this->_host = $host;
        $this->_port = $port;
    }

    public function confHost($host)
    {
        $this->_host = $host;
    }

    public function confPort($port)
    {
        $this->_port = $port;
    }

    private $_socket;

    public function deviceOpen()
    {
        $this->_socket = @fsockopen($this->_host, $this->_port, $errno, $errstr, 30);

        if (!$this->_socket) {
            throw new Exception("SOCKET ERROR");
        } else {
            socket_set_timeout ($this->_socket, 10);
        }
    }

    public function deviceClose()
    {
         fclose($this->_socket);
    }

    public function sendMessage($msg)
    {
        fwrite($this->_socket, $msg);
    }

    private $_validOutputs = array();

    public function setValidOutputs($validOutputs)
    {
        $this->_validOutputs = $validOutputs;
    }

    public function readPort()
    {
    	$last = null;
    	$buffer = array();
    	
        if ($this->_socket) {
            while (!in_array($last, $this->_validOutputs)) {
            	$_buffer = trim(fgets($this->_socket));
            	$last = strtoupper($_buffer);
            	$buffer[] = $_buffer;
            }
            return array($last, $buffer);
        }
    }
}