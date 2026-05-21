<?php
namespace App;

class Database
{
    private static ?\mysqli $conn = null;

    public static function connect(\mysqli $conn): void
    {
        self::$conn = $conn;
    }

    public static function get(): \mysqli
    {
        if (self::$conn === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }
        return self::$conn;
    }
}
