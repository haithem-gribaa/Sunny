<?php
namespace Gribaa\Users\Account;

use Gribaa\Users\User;

class Account {
    
    protected $_onfail;
    protected $_onsuccess;
    protected $_view;
    protected $_data;
    protected $_onexecute;
    protected $_viewmethods = [];
    protected $_validation;
    protected $_password = 'password';
    protected $_viewdata = [];
    protected $_viewvarname = 'response';
    protected $_validationMethods = 'any';
    protected $_checksession = [];
    protected $_login = false;
    protected $_select;
    protected $_cryptPassword;
    protected $_maxTry;
    protected $_tryinterval;
    protected $_logUser = false;
    protected $_forUsers = [];

    public static function error($name,$options = [])
    {
        return (object)array_merge(['ok'=>FALSE,'error'=>$name],$options);
    }
    
    
    public static function ok($options = [])
    {
        return (object)array_merge(['ok'=>TRUE],$options);
    }
    
    
    public function logUser()
    {
        $this->_logUser = TRUE;
        return $this;
    }
    
    
    public function forUsers($where)
    {
        $this->_forUsers = $where;
        return $this;
    }

    
    public function maxTry($maxTry, $interval = NULL) {
        $this->_maxTry = $maxTry;
        $this->_tryinterval = $interval?$interval:3;
        return $this;
    }
    
    public function viewVarName($name)
    {
        $this->_viewvarname = $name;
        return $this;
    }

    public function select($select)
    {
        $this->_select = array_merge($this->_select,$select);
        return $this;
    }

    
    public function login()
    {
        $this->_login = TRUE;
        return $this;
    }

    

    public function checkSession($name,$value = NULL)
    {
        $this->_checksession = ['name'=>$name,'value'=>$value];
        return $this;
    }

    
    public function __construct($data = NULL) {
        $this->_select = \Gribaa\Users\User::$SELECT;
        $this->_cryptPassword = [new \Gribaa\Users\User,'cryptPassword'];
        if($data)
        {
            $this->_data = $data;
        }
    }
    
    public function cryptPassword($callable)
    {
        $this->_cryptPassword = $callable;
        return $this;
    }

    public function viewData($data)
    {
        $this->_viewdata = $data;
        return $this;
    }

    public function validation($validation,$methods = 'any')
    {
        $this->_validation = $validation;
        $this->_validationMethods = $methods;
        return $this;
    }

    

    public function onExecute($callback)
    {
        $this->_onexecute = $callback;
        return $this;
    }
    
    
    
    
    
    
    public function password($pass)
    {
        $this->_password = $pass;
        return $this;
    }

    
    
    
    
    public function setDtada($data)
    {
        $this->_data = $data;
        return $this;
    }
    
    
    public function onFail($callback)
    {
        $this->_onfail = $callback;
        return $this;
    }
    
    public function onSuccess($callback)
    {
        $this->_onsuccess = $callback;
        return $this;
    }
    
    public function view($view,$methods = [])
    {
        $this->_view = $view;
        $this->_viewmethods = $methods;
        return $this;
    }
    
    
    
    

    public function run() {
        if (($this->_checksession&&!session_get($this->_checksession['name']))||(isset($this->_checksession['value'])&&session_get($this->_checksession['name']) != $this->_checksession['value'])) {
            return $this->handle(self::error('session'));
        }
        if (in_array(http_method(), $this->_viewmethods) && $this->_view) {
            view_load($this->_view, $this->_viewdata);
        }
        if (!$this->_data) {
            $this->_data = http_post();
        }
        if ($this->_validation && ($this->_validationMethods == 'any' || in_array(http_method(), $this->_validationMethods))) {
            $v = new \Gribaa\Validator();
            $v->setRules($this->_validation)->validate($this->_data);
            if (!$v->isValid()) {
                $err= self::error('validation',['fields'=>$v->getErrors()]);
                return $this->handle($err);
            }
        }
        return $this->execute();
    }
    
    protected function handle(&$response) {
        if ($response->ok && $this->_logUser && isset($response->user)) {
            session_set(User::SESSION_NAME, array_merge((array) $response->user, ['login_token' => md5($response->user->id) . uniqid()]));
        }
        if (isset($response->user)) {
            User::setAccount($response->user);
        }
        if ($this->_onexecute) {
            $call = call_user_func_array($this->_onexecute, [$response]);
            if ($call !== CONTINUE_EXECUTION) {
                return $this;
            }
        }
        $call = [0 => '_onfail', 1 => '_onsuccess'];
        $method = $this->{$call[(int) $response->ok]};
        if ($method) {
            $call = call_user_func_array($method, [$response]);
            if ($call !== CONTINUE_EXECUTION) {
                return $this;
            }
        }
        if ($this->_view) {
            view_load($this->_view, array_merge([$this->_viewvarname => $response], $this->_viewdata));
            return $this;
        }
        return $this;
    }

    public function getUser()
    {
        return \Gribaa\Users\User::getAccount();
    }

}
