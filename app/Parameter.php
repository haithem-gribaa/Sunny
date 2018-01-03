<?php
namespace app;
class Parameter 
{
    const DB_DRIVER = '';
    const DB_NAME = '';
    const DB_HOST = '';
    const DB_USER = '';
    const DB_PASS = '';
    public static $DB_CONNECTION_OPTIONS = [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',\PDO::ATTR_PERSISTENT         => true];
    
    public static function first()
    {
        /*
         * before run
         */
    }

    public static function error(\Exception $e)
    {
        
        /*
         * handle exception
         */
    }
    const BASE_DIR = '';
    public static $acl = [
        
    ];
    
}
