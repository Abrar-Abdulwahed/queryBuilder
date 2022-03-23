<?php

final class DBConnection
{
    private const HOST = "localhost";
    private const DB = "blog";
    private const USER = "root";
    private const PASSWORD = "";
    private static $PDO = null;
    private const DSN = "mysql:host=".self::HOST.";dbname=".self::DB.";";

    public static function connect()
    {
        try {
            if (!self::isConnected()) {
                self::$PDO = new PDO(self::DSN, self::USER, self::PASSWORD);
                return self::$PDO;
            }
        } catch (PDOException $error) {
            echo $error->getMessage();
        }
    }

    private static function isConnected()
    {
        return self::$PDO != null;
    }
}