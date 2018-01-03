<?php
namespace Gribaa\Users\Account;

use Gribaa\Users\User;

class Register extends Account {
    
    private $_fields = [
        'email'=>'email',
        'password'=>'password',
        'username'=>'username',
        'role'=>'role'
    ];
    private $_activationcode = '';
    private $_activation;
    private $_added_data = [];
    private $_role;
    private $_eliminate = ['id'];

    public function __construct($data = NULL) {
        parent::__construct($data);
    }
    
    public function eliminate(array $eliminate)
    {
        $this->_eliminate = array_merge($eliminate, $this->_eliminate);
        return $this;
    }

    public function activation($callback)
    {
        $this->_activation = $callback;
        return $this;
    }

    public function addFields($fields)
    {
        $this->_fields = array_merge($this->_fields,$fields);
        return $this;
    }
    
    public function addData($data)
    {
        $this->_added_data = array_merge($this->_added_data, $data);
        return $this;
    }

    public function activationCode($code)
    {
        $this->_activationcode = $code;
        return $this;
    }

    private function formatData(){
        $data = [];
        foreach ($this->_fields as $k => $v) {
            if (isset($this->_data[$v])) {
                $data[$k] = $this->_data[$v];
            }
        }
        $data['activation_key'] = $this->_activationcode;
        if(!isset($data['role'])&&$this->_role)
        {
            $data['role'] = $this->_role;
        }
        $this->_data = array_merge($data,  $this->_added_data);
    }

    protected function execute() {
        $this->formatData();
        if(!\Gribaa\Users\User::valideUser($this->_data, $this->_eliminate))
        {
            throw new \Exception('User must be compatible with User::$SELECT');
        }
        $db = User::getDatabase()->register($this->_data,  $this->_cryptPassword);
        if($db->ok&&$this->_activation)
        {
            call_user_func_array($this->_activation, [$db->user]);
        }
        return $this->handle($db);
    }
    
    
    public function role($role)
    {
        $this->_role = $role;
        return $this;
    }

   
    
    
}
