<?php

class TCPArduino
{

    private $socket;
    private $host;
    private $port;
    private $timeout;

    public function __construct()
    {
        $this->isRunningCli();
        $this->isParametersValid();
        $this->setHost();
        $this->setPort();
        $this->setTimeout();
        $this->socketConnect();
        $this->socketListen();
    }

    public function __destruct()
    {
        socket_close($this->socket);
    }

    private function isRunningCli()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('This script is meant to be executed only command-line');
        }
    }

    private function isParametersValid()
    {
        if ($GLOBALS['argc'] < 3) {
            throw new Exception(sprintf('Script misformed: php %s host port [timeout]', str_replace(__DIR__ . '\\', '', __FILE__)));
        }
    }

    private function setHost()
    {
        $this->host = filter_var($GLOBALS['argv'][1], FILTER_VALIDATE_IP);
        if ($this->host === false) {
            throw new Exception('Invalid host IP');
        }
    }

    private function setPort()
    {
        $this->port = filter_var($GLOBALS['argv'][2], FILTER_VALIDATE_INT);
        if ($this->port === false) {
            throw new Exception('Invalid port number');
        }
    }

    private function setTimeout()
    {
        $this->timeout = array_key_exists(3, $GLOBALS['argv']) ? filter_var($GLOBALS['argv'][3], FILTER_VALIDATE_INT) : 10;
        if ($this->timeout === false) {
            throw new Exception('Invalid timeout');
        }
    }

    private function socketConnect()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new Exception(sprintf('socket_create() failed (%s)', socket_strerror(socket_last_error())));
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $this->timeout, 'usec' => 0));
        $result = socket_connect($this->socket, $this->host, $this->port);
        if ($result === false) {
            throw new Exception(sprintf('socket_connect() failed (%s)', socket_strerror(socket_last_error($this->socket))));
        }
    }

    private function socketListen()
    {
        while (true) {
            $message = socket_read($this->socket, 2048);
            $dt = new DateTime();
            if ($message !== false) {
                echo sprintf("[%s]: %s", $dt->format('d-m-Y H:i:s'), $message);
                $fp = fopen($this->host, 'w+');
                fwrite($fp, $message);
                fclose($fp);
            } else {
                echo sprintf("[%s]: %s", $dt->format('d-m-Y H:i:s'), socket_strerror(socket_last_error($this->socket)));
                $this->socketConnect();
            }
        }
    }

}

error_reporting(~E_ALL);
try {
    new TCPArduino();
} catch (Exception $e) {
    echo $e->getMessage();
}
