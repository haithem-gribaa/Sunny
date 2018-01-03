<?php
namespace Gribaa\Users\Account;

use Gribaa\Users\User;

class Logout extends Account {
    
    protected $_token;
    
    public function token($token)
    {
        $this->_token = $token;
        return $this;
    }
    
    public function execute()
    {
        $token = session_get(User::SESSION_NAME.'.login_token');
        if(!$token)
        {
            $res = self::error('not_logged');
            return $this->handle($res);
        }
        if($this->_token !==  $token)
        {
            $res = self::error('invalid_token');
            return $this->handle($res);
        }
        session_delete(User::SESSION_NAME);
        $res = self::ok();
        return $this->handle($res);
    }
    
    
}
