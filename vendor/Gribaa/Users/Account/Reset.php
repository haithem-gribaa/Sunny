<?php
namespace Gribaa\Users\Account;
use Gribaa\Users\User;


class Reset extends Account {
    
    private $_provider;
    private $_code;
    private $_by = ['email','username'];
    private $_type = 'password';
    private $_expire;
    private $_session = true;
    private $_newvalue;
    
    
    
    public function newValue($new)
    {
        $this->_newvalue = $new;
        return $this;
    }

    
    public function provider($provider)
    {
        $this->_provider = $provider;
        return $this;
    }
    
    

    public function code($code)
    {
        $this->_code = $code;
        return $this;
    }
    
    public function by($by)
    {
        $this->_by = $by;
        return $this;
    }
    
    
    
    
    public function type($type)
    {
        $this->_type = $type;
        return $this;
    }
    
    public function expire($expire)
    {
        $this->_expire = $expire;
        return $this;
    }
    
    private function generateQuery()
    {
        $q = new \stdClass();
        $q->provider = array_fill_keys($this->_by, $this->_provider);
        $q->code = $this->_code;
        $q->max = $this->_maxTry;
        $q->interval = $this->_tryinterval;
        $q->where = $this->_forUsers;
        $q->type = $this->_type;
        $q->select = $this->_select;
        $q->expire = $this->_expire;
        $q->new = $this->_newvalue;
        $q->crypt = $this->_cryptPassword;
        return $q;
    }


    protected function execute()
    {
        $db = User::getDatabase()->renew($this->generateQuery());
        return $this->handle($db);
    }
}
