<?php
namespace Gribaa;
class Validator 
{
    private $_rules = array();
    private $_data = array();
    private $_error;
    private $_filters;

    public function setRules($rules) 
    {
        $this->_rules = $rules;
        return $this;
    }
    
    public function setFilters($filters)
    {
        $this->_filters = $filters;
        return $this;
    }
    
    public function addData($data)
    {
        $this->_data = $data;
        return $this;
    }

    private function formatInputName(&$k)
    {
            $e = explode('.', $k);
            if(count($e) === 2&&$e[0] == 'file')
            {
                $this->_data[$k] = isset($_FILES[$e[1]])?$_FILES[$e[1]]:NULL;
            }
            if(count($e) === 2&&$e[0] == 'get')
            {
                $this->_data[$k] = isset($_GET[$e[1]])?$_GET[$e[1]]:NULL;
            }
            if(count($e) === 2&&$e[0] == 'post')
            {
                $this->_data[$k] = isset($_POST[$e[1]])?$_POST[$e[1]]:NULL;
            }
    }

    public function validate($data = NULL) {
        if (!$data) {
            $data = filter_input_array(INPUT_POST);
        }
        $this->_data = array_merge((array)$data,  $this->_data);
        foreach ($this->_rules as $k => $v) {
            $this->formatInputName($k);
            $rules = explode('|', $v);
            if (in_array('required', $rules) && (!isset($this->_data[$k]) || empty($this->_data[$k]))) {
                $this->_error[$k] = 'required';
                return $this;
            }
            if (!in_array('required', $rules) && (!isset($this->_data[$k]) || empty($this->_data[$k]))) {
                continue;
            }
            if (($key = array_search('required', $rules)) !== false) {
                unset($rules[$key]);
            }
            foreach ($rules as $v1) {
                if (!$this->call($k, $v1)) {
                    return $this;
                }
            }
        }
        return $this;
    }

    private function call($input, $rule) {
        $not = FALSE;
        $e = explode(':', $rule);
        $method = $e[0];
        if (substr($method, 0, 4) == 'not_') {
            $not = 'not_';
            $method = substr($method, 4);
        }
        $params = [$this->_data[$input]];
        $args = isset($e[1]) ? explode(',', $e[1]) : [];
        unset($e);
        $filter = function($var) {
            return $var != '';
        };
        foreach ($args as $v) {
            $l = str_replace(array('[', ']'), '', $v);
            if ($l !== $v) {
                $params[] = \array_filter(\explode(' ', $l), $filter);
            } elseif (substr($v, 0, 1) == '.') {
                $params[] = $this->_data[substr($v, 1)];
            } else {
                $params[] = $v;
            }
        }
        unset($args);
        $res = call_user_func_array([$this, $method], $params);
        if ($not) {
            $res = !$res;
        }
        if (!$res) {
            $this->_error[$input] = $not . '' . $method;
            return FALSE;
        }return TRUE;
    }
    public function type_array($field)
    {
        return is_array($field);
    }

    public function email($email) 
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function alpha($str, array $mask = array()) {
        return ctype_alpha(str_replace($mask, '', $str));
    }

    public function alnum($str, array $mask = array()) {
        return ctype_alnum(str_replace($mask, '', $str));
    }

    public function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public function ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    public function ipv4($ip) {
        filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public function ipv6($ip) {
        filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
    
    public function mobile($str)
    {
        
    }

    public function maxlen($str, $max) {
        return mb_strlen($str) <= $max;
    }

    public function minlen($str, $min) {
        return mb_strlen($str) >= $min;
    }

    public function length($str, $len) {
        return mb_strlen($str) === (int)$len;
    }

    public function equal($str, $to) {
        return $str === $to;
    }

    public function startwith($str, array $with) {
        foreach ($with as $v) {
            if (strrpos($str, $v, -mb_strlen($str)) !== false) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function endwith($str, array $with) {
        foreach ($with as $v) {
            if ((($temp = mb_strlen($str) - mb_strlen($v)) >= 0 && strpos($str, $v, $temp) !== false)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function contains($str, array $contains) {
        foreach ($contains as $v) {
            if (!strstr($str, $v)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function in($str, array $in) {
        return in_array($str, $in);
    }

    public function json($json) {
        json_decode($json);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function int($int) {
        return (int) $int > 0 && filter_var($int, FILTER_VALIDATE_INT);
    }

    public function float($float) {
        return filter_var($float, FILTER_VALIDATE_FLOAT);
    }
    
    public function positif($number)
    {
        return $number>0;
    }


    public function isValid() {
        return !$this->_error;
    }

    public function getErrors() {
        return $this->_error;
    }

    public function extention($file,$extentions) {
        $f = isset($file['name'])?$file['name']:$file;
        $inf = pathinfo($f);
        return isset($inf['extension'])&&  in_array($inf['extension'], $extentions);
    }

    public function mime($file,$mime) {
        $f = isset($file['tmp_name'])?$file['tmp_name']:$file;
        return file_exists($f)&&  in_array(mime_content_type($f), $mime);
    }
    
    public function maxsize($file,$size)
    {
        $fsize = isset($file['size'])?$file['size']:filesize($file);
        return $fsize<$size*1048576;
    }
    
    public function minsize($file,$size)
    {
        $fsize = isset($file['size'])?$file['size']:filesize($file);
        return $fsize>$size*1048576;
    }
    
    public function size($file,$size)
    {
        $fsize = isset($file['size'])?$file['size']:filesize($file);
        return $fsize === $size*1048576;
    }
    
    public function gt($int,$value)
    {
        return $int>=$value;
    }
    public function lt($int)
    {
    return $int<=$int;
    }
}

