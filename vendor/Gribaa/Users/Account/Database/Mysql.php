<?php

namespace Gribaa\Users\Account\Database;

use Gribaa\Users\User;
use Gribaa\Users\Account\Account;

class Mysql extends \Gribaa\Sql implements DatabaseInterface {
    
    

        public function login($query) {
        $q = 'SELECT ' . implode(',', $query->select);
        if ($query->max) {
            $q.=', (select count(id) from ' . User::TRY_TABLE . ' where user_id = ' . User::TABLE_NAME . '.id and try_type = "login" and date_try > "' . strtotime('-' . $query->interval . ' minutes', time()) . '") trys ';
        }
        $by = ' WHERE  (';
        $i = 0;
        foreach ($query->by as $v) {
            $by.=$v . ' = ' . $this->quote($query->provider);

            if ($i != count($query->by) - 1) {
                $by.=' OR ';
            }
            $i++;
        }
        $by.=' ) ';
        $by = $this->forUsers($by, $query->where);
        $q.=' from ' . User::TABLE_NAME . $by;
        $user = $this->query($q)->fetch(self::FETCH_OBJ);
        $x = $this->error($user);
        if ($x) {
            return $x;
        }
        $max = $this->maxTry($user, 'login', $query->max, $query->interval);
            if ($max) {
                return $max;
            }
        if ($user->password != call_user_func_array($query->crypt,[$query->password,$user])) {
            
            return Account::error('wrong_password', ['user' => $user]);
        }
        if ($query->max) {
            $this->exec('delete from ' . User::TRY_TABLE . ' where user_id = ' . $user->id . ' and try_type = "login" ');
        }
        return Account::ok(['user' => $user]);
    }

    public function register($data,$crypt) {
        $data['password'] = call_user_func_array($crypt, [$data['password'],(object)$data]);
        $i = $this->forTable(User::TABLE_NAME)->insert($data);
        if (!$i) {
            return Account::error('provider_exists');
        }
        $data['id'] = $i;
        return Account::ok(['user' => (object) $data]);
    }

    public function activate($query) 
    {
        $q = 'SELECT  '.  implode(',', $query->select);
        if ($query->max) {
            $q.=', (select count(id) from ' . User::TRY_TABLE . ' where user_id = ' . User::TABLE_NAME . '.id and try_type = "activate" and date_try > "' . strtotime('-' . $query->interval . ' minutes', time()) . '") trys ';
        }
        $q.=' FROM ' . User::TABLE_NAME.' WHERE  activation_key IS NOT NULL  ';
        if (filter_var($query->provider, FILTER_VALIDATE_INT)) {
            $w = ' AND id = ' . $query->provider . ' ';
        } else {
            $w = ' AND (email=' . $this->quote($query->provider) . ' or username=' . $this->quote($query->provider) . ') ';
        }
        $w = $this->forUsers($w, $query->where);
        $user = $this->query($q . $w)->fetch(self::FETCH_OBJ);
        $x = $this->error($user,[]);
        if ($x) {
            return $x;
        }
        if(!$query->code)
        {
            return Account::ok(['user'=>$user]);
        }
        if ($user->activation_key !== $query->code) 
        {
            $max = $this->maxTry($user, 'activate', $query->max, $query->interval);
            if ($max) {
                return $max;
            }
            return Account::error('wrong_code', ['user' => $user]);
        }
        $this->exec('delete from ' . User::TRY_TABLE . ' where user_id = ' . $user->id . ' and try_type = "activate" ');
        $this->exec('UPDATE ' . User::TABLE_NAME . ' SET activation_key = NULL WHERE id =  '.$user->id );
        return Account::ok(['user' => $user]);
    }

    public function forget($query) 
    {
        $q = $this->forUsers($this->sql('SELECT '.  implode(',', $query->select).' FROM '.User::TABLE_NAME)->where(array_fill_keys($query->by, $query->provider), ' OR ')->getQuery(), $query->where);
        $user = $this->sql($q)->find();
        $x = $this->error($user);
        if ($x) {
            return $x;
        }
        $i = $this->forTable(User::FORGET_TABLE)->insert(['user_id' => $user->id, 'forget_date' => time(), 'forget_type' => $query->type, 'forget_key' => $query->code]);
        if (!$i) {
            return Account::error('already_forget',['user'=>$user]);
        }
        return Account::ok(['user'=>$user]);
    }

    public function renew($query) 
    {
        $q = 'SELECT  '.  implode(',', $query->select);
        if ($query->max) {
            $q.=', (select count(id) from ' . User::TRY_TABLE . ' where user_id = ' . User::TABLE_NAME . '.id and try_type = "forget_' . $query->type . '" and date_try > "' . strtotime('-' . $query->interval . ' minutes', time()) . '") trys ';
        }
        $q.=', (SELECT concat(forget_date,",",forget_key) from ' . User::FORGET_TABLE . ' where user_id = ' . User::TABLE_NAME . '.id and forget_type = "' . $query->type . '") forget  ';
        $q.=' from ' . User::TABLE_NAME . ' WHERE (';
        $i = 0;
        foreach ($query->provider as $k => $v) {
            $q.=$i == 0 ? $k . ' = ' . $this->quote($v) : ' OR ' . $k . ' = ' . $this->quote($v);
            $i++;
        }
        $q.= ' )';
        $q = $this->forUsers($q, $query->where);
        $user = $this->query($q)->fetch(\PDO::FETCH_OBJ);
        if (!$user) {
            return Account::error('wrong_user');
        }
        
        if (!$user->forget) {
            return Account::error('wrong_user', ['user' => $user]);
        }
        
        if (!$query->code) {
            return Account::ok();
        }
        $forget = explode(',', $user->forget);
        unset($user->forget);
        $user->forget_date = $forget[0];
        $user->code = $forget[1];
        if ($query->expire && (time() > $user->forget_date + (60 * $query->expire))) {
            $this->exec('DELETE FROM ' . User::FORGET_TABLE . ' WHERE user_id = ' . $user->id . ' AND forget_type = ' . $this->quote($query->type));
            $this->exec('delete from ' . User::TRY_TABLE . ' where user_id = ' . $user->id . ' and try_type = "forget_' . $query->type . '" ');
            return Account::error('expired', ['user' => $user]);
        }
        if ($user->activation_key) {
            return Account::error('unactif', ['user' => $user]);
        }
        if ($user->code != $query->code) {
            $max = $this->maxTry($user, 'forget_' . $query->type, $query->max, $query->interval);
            if ($max) {
                return $max;
            }
            return Account::error('wrong_code', ['user' => $user]);
        }
        
        $this->exec('delete from ' . User::TRY_TABLE . ' where user_id = ' . $user->id . ' and try_type = "forget_' . $query->type . '" ');
        if ($query->new) {
            $query->new = ($query->type == 'password')?call_user_func_array($query->crypt, [$query->new,$user]):$query->new;
            $ok = $this->query(' UPDATE '.User::TABLE_NAME.' SET '.$query->type.' = '.$this->quote($query->new).' WHERE id = '.$user->id)->execute();
            if (!$ok) {
                return Account::error('unknown');
            }
            $this->exec('delete from ' . User::FORGET_TABLE . ' where user_id = ' . $user->id . ' AND forget_type = ' . $this->quote($query->type));
        }
        return Account::ok(['user' => $user]);
    }

    

    private function maxTry(&$user, $type, $max, $interval) {
        if (!$max) {
            return;
        }
        $this->exec('delete from ' . User::TRY_TABLE . ' where user_id = ' . $user->id . '  and try_type = "' . $type . '" and date_try < "' . strtotime('-' . $interval . ' minutes', time()) . '"');
        $user->remain = $max - $user->trys;
        if ($user->remain <= 0) {
            $last_try = $this->query('select date_try from ' . User::TRY_TABLE . ' where try_type = "' . $type . '" and user_id =  ' . $user->id . ' order by date_try desc')->fetch(self::FETCH_ASSOC);
            if (isset($last_try['date_try'])) {
                $user->wait = ($last_try['date_try'] + (60 * $interval)) - time();
            }
            return Account::error('max_try', ['user' => $user]);
        }
        $this->forTable(User::TRY_TABLE)->insert(['date_try' => time(), 'user_id' => $user->id, 'ip' => filter_input(INPUT_SERVER, 'REMOTE_ADDR'), 'try_type' => $type]);
    }

    private function error(&$user, $checks = ['unactif']) {
        if (!$user) {
            return Account::error('wrong_user');
        }
        if ($user->activation_key && in_array('unactif', $checks)) {
            return Account::error('unactif', ['user' => $user]);
        }
    }
    
    public function getRole($user_id,$save = NULL)
    {
        if($save)
        {
            $select = implode(',', User::$SELECT);
        }
        else
        {
            $select = ' activation_key,role ';
        }
        $this->sql('SELECT '.  $select.' from '.User::TABLE_NAME);
        if(is_array($user_id))
        {
            $this->where($user_id);
        }
        else 
        {
            $this->where(['id'=>$user_id]);
        }
        $user = $this->find();
        if(isset($user->activation_key)&&$user->activation_key)
        {
            return 'unactif';
        }
        if($save)
        {
            User::setAccount($user);
        }
        return ($user)?$user->role:'guest';
    }
    
    
    private function forUsers($q,$where)
    {
            if(!$where)
            {
                return $q;
            }
            
            if(is_array($where))
            {
                foreach ($where as $k=>$v)
                {
                    $q.=' AND '.$k.' = '.  $this->quote($v);
                }
            }
            else 
            {
                $q.=' AND '.$where;
            }
            return $q;
            
    }

}
