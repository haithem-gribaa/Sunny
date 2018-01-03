<?php
namespace Gribaa\Users;

use Gribaa\Users\User;

class Acl {
    
    private static $_instance;
    
    /**
     * 
     * @return Acl
     */
    public static function getInstance()
    {
        if(!self::$_instance)
        {
            self::$_instance = new Acl();
        }
        
        return self::$_instance;
    }
    


    private $_user = null;
    private $_role;
    private $_saveUser = true;
    private static $_permissions;
    private $_granted;
    private $_onAllow;
    private $_onDeny;

    
    //valid
    public function setUser($user)
    {
        $this->_user = $user;
        return $this;
    }
    
    public function dontSaveUser()
    {
        $this->_saveUser = FALSE;
        return $this;
    }

    //valid
    public function setRole($role)
    {        
        $this->_role = strtoupper($role);
        return $this;
    }
    //valid
    public function getRole()
    {
        if($this->_role)
        {
            return $this->_role;
        }
        if(!$this->_user)
        {
            $this->_user = User::fromSession('id');
        }
        $this->_role = strtoupper(User::getDatabase()->getRole($this->_user, $this->_saveUser));
        return strtoupper($this->_role);
    }
    //valid
    public function isRoot()
    {
        return $this->getRole() == 'ROOT';
    }
    ///valid
    public function isGuest()
    {
        return $this->getRole() == 'GUEST';
    }
    //valid
    public function isRole($role)
    {
        return $this->getRole() == strtoupper($role);
    }
    
    public function userCan($required)
    {
        $role = $this->getRole();
        if($role == 'ROOT')
        {
            return TRUE;
        }
        if($role == 'GUEST')
        {
            return FALSE;
        }
        self::$_permissions = \Gribaa\Container::get('acl');
        if(!isset(self::$_permissions[$role]))
        {
            return FALSE;
        }
        $required = explode(',', $required);
        $permissions = explode(',', self::$_permissions[$role]);
        if(count(array_intersect($required, $permissions)) != count($required))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    
    public function requiredPermissions($required)
    {
        $this->_granted = $this->userCan($required);
        return $this;
    }
    
    public function requiredRole($role)
    {
        $this->_granted = $this->getRole() == strtoupper($role);
        return $this;
    }
    
    public function onAllow($allow)
    {
        $this->_onAllow = $allow;
        return $this;
    }
    
    public function onDeny($deny)
    {
        $this->_onDeny = $deny;
        return $this;
    }
    
    public function run()
    {
        if($this->_granted)
        {
            return call_user_func($this->_onAllow);
        }
        else 
        {
            return call_user_func($this->_onDeny);
        }
    }
    
    
    public static function getRoles()
    {
        if(!self::$_permissions)
        {
            self::$_permissions = \Gribaa\Container::get('acl');
        }
        return array_keys(self::$_permissions);
    }
    
    
    
    
}
