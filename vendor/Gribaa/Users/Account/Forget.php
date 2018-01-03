<?php
namespace Gribaa\Users\Account;

use Gribaa\Users\User;

class Forget extends Account {
    
    
    private $_provider = 'user';
    private $_type = 'email';
    private $_by = ['email','username'];
    private $_code;

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
    
    
    
    
    private function generateQuery()
    {
        $q = new \stdClass();
        $q->type = $this->_type;
        $q->code = $this->_code;
        $q->provider = $this->_data[$this->_provider];
        $q->by = $this->_by;
        $q->where = $this->_forUsers;
        $q->select = $this->_select;
        return $q;
    }
    
    public function execute()
    {
        $db = User::getDatabase()->forget($this->generateQuery());
        return $this->handle($db);
    }
}
