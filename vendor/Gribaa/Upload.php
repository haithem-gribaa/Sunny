<?php
namespace Gribaa;

class Upload 
{
    private  $_file;

    private $_validationErrors = [];


    public function __construct()
    {
            $this->_file = $_FILES;
    }
    
    
    public function all()
    {
        return $this->_file;
    }
    public function has($index)
    {
        return isset($this->_file[$index]);
    }
    
    public function get($index)
    {
        return isset($this->_file[$index])?$this->_file[$index]:NULL;
    }
    
    public function count()
    {
        return count($this->_file);
    } 
    
    public function content()
    {
        return file_get_contents($this->_file[self::$_name]['tmp_name']);
    }
    
    public function getSize($index)
    {
        isset($this->_file[$index]['size'])?$size = $this->_file[$index]['size']:$size = NULL;
        return $size;
    }
    
    public function getExtention($index)
    {
        isset($this->_file[$index]['name'])?$extention = pathinfo($this->_file[$index]['name'],PATHINFO_EXTENSION):$extention = NULL;
        return $extention;
    }

    
    
    public function getMimetype($index)
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info,$this->_file[$index]['tmp_name']);
        finfo_close($info);
        return $mime;
    }
    
    public function getEncoding($index)
    {
        $info = finfo_open(FILEINFO_MIME_ENCODING);
        $encoding = finfo_file($info,$this->_file[$index]['tmp_name']);
        finfo_close($info);
        return $encoding;
    }
    
    
    public function validate($index,$rules)
    {
        foreach ($rules as $k=>$v)
        {
            $method = 'get'.ucfirst($k);
            if($k == 'size')
            {
                $this->getSize($index)>$v?$this->_validationErrors[] = $k:NULL;
            }
            elseif(!in_array($this->{$method}($index),$v))
            {
                $this->_validationErrors[] = $k;
            }
        }
        $v = new \stdClass();
        if($this->_validationErrors)
        {
            $v->valid = FALSE;
            $v->errors = $this->_validationErrors;
        }
        else 
        {
            $v->valid = TRUE;
        }
        return $v;
    }
    
    public function upload($index,$p,$name = NULL,$rules = [])
    {
        //error_reporting(E_ERROR | E_PARSE);
        $u = new \stdClass();
        if(!is_dir($p))
        {
            $u->uploaded = FALSE;
            $u->errors = 'path_not_found';
            return $u;
        }
        if(!is_writable($p))
        {
            $u->uploaded = FALSE;
            $u->errors = 'permission_denied';
            return $u;
        }
        if($rules)
        {
            $v = $this->validate($index, $rules);
            if(!$v->valid)
            {
                $u->uploaded = FALSE;
                $u->errors = 'invalid';
                return $u;
            }
        }
        $name = $name?$name:uniqid();
        if(move_uploaded_file($this->_file[$index]['tmp_name'], $p.$name.'.'.$this->getExtention($index)))
        {
            $u->uploaded = TRUE;
            $u->name = $name.'.'.$this->getExtention($index);
            return $u;
        }
        $u->uploaded = FALSE;
        $u->error = 'unknown';
        return $u;
    }
}
