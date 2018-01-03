<?php

namespace Gribaa\Users\Account;
use Gribaa\Users\User;

class Login extends Account {

    private $_by = ['email', 'username'];
    private $_provider = 'email';
    
    

    public function __construct($data = NULL) {
        parent::__construct($data);
    }
    public function provider($provider)
    {
        $this->_provider = $provider;
        return $this;
    }
    public function by($by) 
    {
        $this->_by = $by;
        return $this;
    }

    

    private function generateQuery() {
        $query = new \stdClass();
        
        $query->provider = $this->_data[$this->_provider];
        $query->by = $this->_by;
        $query->password = $this->_data[$this->_password];
        $query->crypt = $this->_cryptPassword;
        $query->interval = $this->_tryinterval;
        $query->max = $this->_maxTry;
        $query->select = $this->_select;
        $query->where = $this->_forUsers;
        return $query;
    }

    protected function execute() {
        $this->select(['password']);
        $db = User::getDatabase()->login($this->generateQuery());
        return $this->handle($db);
    }

    public function isLogged() {
        return !$this->_errors;
    }

}
