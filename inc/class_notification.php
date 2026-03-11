<?php


class Notification
{
    public static function info($message)
    {
        $_SESSION['messages'][] = $message;
    }

    public static function error($message)
    {
        $_SESSION['errors'][] = $message;
    }

    public static function getErrors($clear = true)
    {
        $result = $_SESSION['errors'] ?? [];
        if ($clear) {
            unset($_SESSION['errors']);
        }
        return $result;
    }

    public static function getMessages($clear = true)
    {
        $result = $_SESSION['messages'] ?? [];
        if ($clear) {
            unset($_SESSION['messages']);
        }
        return $result;
    }
}