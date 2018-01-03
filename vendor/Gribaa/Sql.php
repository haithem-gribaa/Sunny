<?php
namespace Gribaa;
use app\Parameter;
class Sql extends \PDO
{

    protected $table;
    private $query;
    private $paging;
    private $fetched;
    private $where = false;

    public function __construct() {
            parent::__construct(Parameter::DB_DRIVER.':dbname='.Parameter::DB_NAME.';host='.Parameter::DB_HOST, Parameter::DB_USER, Parameter::DB_PASS, Parameter::$DB_CONNECTION_OPTIONS);
    }
    
    public function reset()
    {
        $this->table = NULL;
        $this->query = NULL;
        $this->paging = NULL;
        $this->fetched = NULL;
        $this->where = NULL;
        return $this;
    }

    public function forTable($t)
    {
        $this->table = $t;
        return $this;
    }
    
    public function tableExsists($t)
    {
        try {
            $this->query('select 1 from '.$t);
            return TRUE;
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function insert(array $args)
    {
        $fields = implode(',', array_keys($args));
        $values = implode(',', array_map(array($this, 'quote'), array_values($args)));
        $query = 'INSERT INTO ' . $this->table . ' (' . $fields . ') ' . ' VALUES (' . $values . ')';
        $this->exec($query);
        return $this->lastInsertId();
    }
    
    public function insertGroupeIgnore(array $data)
    {
        $query = "INSERT IGNORE INTO " . $this->table;
        $i = 0;
        foreach ($data as $v) {
            $values = implode(',', array_map(array($this, 'quote'), array_values((array)$v)));
            if ($i == 0) {
                $fields = implode(',', array_keys((array)$v));
                $values = implode(',', array_map(array($this, 'quote'), array_values((array)$v)));
                $query .= ' (' . $fields . ') ' . ' VALUES (' . $values . ')';
            } else {
                $query.=' , (' . $values . ')';
            }
            $i++;
        }
        
        return $this->exec($query);
    }

        public function append($sql)
    {
        $this->query.=$sql;
        return $this;
    }

    public function itemExists(array $data, $option = FALSE)
    {
        $prepare = $this->select()->where($data);
        if ($option) {
            if ($prepare->getCount()) {
                return array("exists" => TRUE, "data" => $prepare->find());
            } else {
                return array("exists" => FALSE);
            }
        } else {
            if ($prepare->getCount()) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function insertGroupe(array $data)
    {
        $query = "INSERT INTO " . $this->table;
        $i = 0;

        foreach ($data as $v) {
            $values = implode(',', array_map(array($this, 'quote'), array_values($v)));
            if ($i == 0) {
                $fields = implode(',', array_keys($v));
                $values = implode(',', array_map(array($this, 'quote'), array_values($v)));
                $query .= ' (' . $fields . ') ' . ' VALUES (' . $values . ')';
            } else {
                $query.=' , (' . $values . ')';
            }
            $i++;
        }
        return $this->exec($query);
    }

    public function itemCount(array $data, $option = "basic")
    {
        if ($option == "basic") {
            return $this->select()->where($data)->getCount();
        } elseif ($option == "like") {
            return $this->select()->whereLike($data)->getCount();
        }
    }

    public function select(array $data = array())
    {
        if ($data) {
            $values = implode(',', array_values($data));
            $this->query = "SELECT " . $values . " FROM " . $this->table;
        } else {
            $this->query = "SELECT * FROM " . $this->table;
        }
        return $this;
    }

    public function delete()
    {
        $this->query = "DELETE FROM " . $this->table;
        return $this;
    }

    public function update(array $data)
    {
        $add = "UPDATE  " . $this->table . " SET ";
        $i = 0;
        foreach ($data as $k => $v) {
            if ($i == 0) {
                $add .= $k . " = " . $this->quote($v);
            } else {
                $add .= " , " . $k . " = " . $this->quote($v);
            }

            $i ++;
        }

        $this->query = $add;
        return $this;
    }

    public function orderBy(array $data)
    {
        $add = " ORDER BY ";
        $i = 0;
        foreach ($data as $k => $v) {
            if ($i == 0) {
                $add .= $k . "  " . $v;
            } else {
                $add .= " , " . $k . "  " . $v;
            }

            $i ++;
        }
        $this->query.=$add;
        return $this;
    }

    public function limit($start, $end)
    {
        $this->query.= " limit " . $start . "," . $end;
        return $this;
    }
    /*
     * first where
     */
    public function where(array $data, $operator = 'AND')
    {
        $add = " WHERE ( ";
        $i = 0;
        foreach ($data as $k => $v) {
            if ($i == 0) {
                $add .= $k . " = " . $this->quote($v);
            } else {
                $add .= " " . $operator . " " . $k . " = " . $this->quote($v);
            }

            $i ++;
        }
        $this->query.=$add.' )';
        return $this;
    }
    public function whereLike(array $data, $operator = 'AND ')
    {
        $add = " WHERE ";
        $i = 0;
        foreach ($data as $k => $v) {
            if ($i == 0) {
                $add .= $k . " like " . $this->quote("%" . $v . "%");
            } else {
                $add .= ' ' . $operator . ' ' . $k . " like " . $this->quote("%" . $v . "%");
            }

            $i ++;
        }
        $this->query.=$add;
        return $this;
    }
    public function whereIn($key,array $in)
    {
        $this->query.=' WHERE '.$key.' IN  ( '.implode(',', array_map([$this,'quote'], $in)).' )';
        return $this;
    }
    /*
     * after where
     */
    public function andWhere($key,$pattern, $operator = '=')
    {
        $this->query.=' AND '.$key.' '.$operator.' '.$this->quote($pattern);
        return $this;
    }
    public function orWhere($key,$pattern,$operator = '=')
    {
        $this->query.=' OR '.$key.' '.$operator.' '.$this->quote($pattern);
        return $this;
    }
    
    
    
    public function in($key,array $in,$operator = 'AND')
    {
        $this->query.=' '.$operator.' '.$key.' IN  ( '.implode(',', array_map([$this,'quote'], $in)).' )';
        return $this;
    }
    
    public function like($key,$pattern,$operator = 'AND')
    {
        
        $this->query.=' '.$operator.' '.$key.' like '.$this->quote('%' . $pattern . '%');
        return $this;
    }
    
    

    public function paginate($offset, $actual)
    {
        $count_items = $this->getCount();
        $totalPage = ceil($count_items / $offset);
        if ($actual > $totalPage) {
            $actual = $totalPage;
        }
        if ($actual == 0) {
            $actual = 1;
        }
        $limitStart = ($actual - 1) * $offset;
        $this->limit($limitStart, $offset);
        $this->paging = array("total" => $totalPage,'total_count'=>$count_items, "actual" => $actual);
        return $this;
    }

    public function getCount()
    {
         return $this->query($this->query)->rowCount();
    }

    public function sql($query)
    {
        $this->query = $query;
        return $this;
    }

    public function find()
    {
        $this->fetched = $this->query($this->query)->fetch(self::FETCH_OBJ);
        return $this->fetched;
    }

    public function findMany()
    {
            $result = $this->query($this->query)->fetchAll(self::FETCH_OBJ);
            $this->fetched = $result;
            if (isset($this->paging)) {
                return ['data' => $result, 'paging' => $this->paging];
            }
            return $result;
    }

    public function run()
    {
            return $this->exec($this->query);
    }
    
    public function fromFetched($keys = [])
    {
        $ret = [];
        if($keys)
        {
            foreach ($keys as $key)
            {
                foreach ($this->fetched as $fetched)
                {
                    $ret[$key][] = $fetched[$key];
                }
            }
            return $ret;
        }
        return  $this->fetched;
    }
    
    
    public function getQuery()
    {
        return $this->query;
    }
    
    

    

}
