<?php
namespace Gribaa\Users;

class User {
    
    const TABLE_NAME = 'users';
    const TRY_TABLE = 'users_try';
    const FORGET_TABLE = 'users_forget';
    const SESSION_NAME = 'users';
    private static $_database;

    public static $SELECT = ['id','username','role','activation_key','email','password','nom','prenom'];

    private static $_account;
    
    public static function setAccount(&$account)
    {
        self::$_account = (array)$account;
    }
    
    /**
     * @return Account\Database\DatabaseInterface
     */
    public static function getDatabase()
    {
        if(!self::$_database)
        {
            self::$_database = new Account\Database\Mysql();
        }
        return self::$_database;
    }

    public static function getAccount($index = NULL)
    {
        $acc =  self::$_account;
        if($index)
        {
            return recursive_array_get($index, $acc);
        }
        return $acc;
    } 
    
    public static function cryptPassword($password,$user = NULL)
    {
        return md5($password);
    }
    
    public static function valideUser($user,$eliminate = [])
    {
        $user = array_keys((array)$user);
        $u = array_diff(self::$SELECT,$eliminate);
        $i = array_intersect($user,$u);
        return count($i) == count($u);
    }
    
    public static function fromSession($field = NULL)
    {
        if($field)
        {
            return session_get(self::SESSION_NAME.'.'.$field);
        }
        return session_get(self::SESSION_NAME);
    }
}
