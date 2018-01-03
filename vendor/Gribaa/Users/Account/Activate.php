<?php
namespace Gribaa\Users\Account;
use Gribaa\Users\User;

class Activate extends Account {
    
    private $_provider;
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
    
    
    public function execute()
    {
        if(!$this->_provider)
        {
            throw new \InvalidArgumentException('You must set account provider');
        }
        $query = new \stdClass();
        $query->provider = $this->_provider;
        $query->code = $this->_code;
        $query->max = $this->_maxTry;
        $query->where = $this->_forUsers;
        $query->interval = $this->_tryinterval;
        $query->select = $this->_select;
        $db = User::getDatabase()->activate($query);
        return $this->handle($db);
        
    }
    
    

    
    
}
