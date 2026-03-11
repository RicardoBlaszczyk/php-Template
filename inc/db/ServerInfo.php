<?php


class ServerInfo
{
    public $host;
    public $type;
    public $serverVersion;
    public $database;

    /**
     * ServerInfo constructor.
     *
     * @param $host
     * @param $type
     * @param $serverVersion
     * @param $database
     */
    public function __construct($host, $type, $serverVersion, $database)
    {
        $this->host = $host;
        $this->type = $type;
        $this->serverVersion = $serverVersion;
        $this->database = $database;
    }
}