<?php
namespace Gribaa;

class Container {
    public static $_routes;
    public static $_matched;
    public static $_objects;
    public static $_data;
    public static $_profile;
    public static $_sql;
    public static $_tabs;

    
    /**
     *
     * @var \app\Bot\Driver
     */
    public static $_driver;

    public static function setRoutes($routes)
    {
        self::$_routes = $routes;
    }
    
    public static function getRoutes()
    {
        return self::$_routes;
    }
    
    
    
   

    public static function object($namespace)
    {
        if(!isset(self::$_objects[$namespace]))
        {
            self::$_objects[$namespace] = new $namespace;
        }
        return self::$_objects[$namespace];
    }
    
    
    public static function get($key)
    {
        return recursive_array_get($key, self::$_data);
    }
    
    public static function set($key,$value)
    {
        recursive_array_set($key, $value, self::$_data);
    }
    
    public static function setDriver(&$driver)
    {
        self::$_driver = $driver;
    }
    /**
     * 
     * @return \app\Bot\Driver
     */
    public static function getDriver($discover = false)
    {
        if(!self::$_driver)
        {
            if($discover)
            {
                \app\Bot\Sessions\Profile::getDiscover();
            }
            self::$_driver = new \app\Bot\Sessions\Driver();
        }
        return self::$_driver;
    }
    
    public static function killDriver()
    {
        if(!self::$_driver)
        {
            return;
        }
        $hs = self::$_driver->getDriver()->getWindowHandles();
        foreach ($hs as $v)
        {
            self::$_driver->getDriver()->switchTo()->window($v)->close();
        }
        self::$_driver->close();
        self::$_driver = NULL;
    }
    
    public static function driverExistse()
    {
        return !is_null(self::$_driver);
    }

        public static function getProfile()
    {
        return self::$_profile;
    }
    
    public static function setProfile(&$profile)
    {
        if(is_array($profile))
        {
            foreach ($profile as $k=>$v)
            {
                self::$_profile->{$k} = $v;
            }
        }
        else
        {
            self::$_profile = $profile;
        }
    }
    /**
     * 
     * @return Sql
     */
    public static function getSql()
    {
        if(!self::$_sql)
        {
            self::$_sql = new Sql();
        }
        return self::$_sql;
    }
}
