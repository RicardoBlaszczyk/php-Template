<?php


/**
 * Class DatabaseException
 */
class DatabaseException extends Exception
{
    /**
     * @var array
     */
    protected $databaseError = [];

    /**
     * @var string
     */
    protected $executedSql = '';
    /**
     * @var array
     */
    protected $params = [];

    /**
     * DatabaseException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     * @param array          $databaseError
     * @param string         $executedSql
     * @param array          $params
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, $databaseError = [], $executedSql = '', $params = [])
    {
        parent::__construct($message, $code, $previous);
        $this->databaseError = $databaseError;
        $this->executedSql = $executedSql;
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getDatabaseError()
    {
        return $this->databaseError;
    }

    /**
     * @return string
     */
    public function getExecutedSql()
    {
        return $this->executedSql;
    }

    public function getParams()
    {
        return $this->params;
    }
}