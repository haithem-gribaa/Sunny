<?php
use app\Parameter as Config;
use Gribaa\Container;
use Gribaa\NotFoundException;
define('PROJECT_DIR', __DIR__);
define('DS', DIRECTORY_SEPARATOR);
define('STOP_EXECUTION', 11);
define('CONTINUE_EXECUTION', 12);

function recursive_array_set($key,$value,&$array)
    {
        $k = explode('.', $key);
        $target = &$array;
        foreach ($k as $v)
        {
            if(!isset($target[$v]))
            {
                $target[$v] = [];
            }
            $target = &$target[$v];
        }
        $target = $value;
    }

function recursive_array_get($key,&$array)
    {
        $k = explode('.', $key);
        $target = &$array;
        foreach ($k as $v)
        {
            if(!isset($target[$v]))
            {
                return FALSE;
            }
            $target = &$target[$v];
        }
        return $target;
}

function recursive_array_has($key,&$array)
    {
        $k = explode('.', $key);
        $target = &$array;
        foreach ($k as $v)
        {
            if(!isset($target[$v])||  is_null($target[$v]))
            {
                return FALSE;
            }
            $target = &$target[$v];
        }
        return TRUE;
    }
//coder functions
function coder_updateConst($const, $value, $class) {
    $file = (new \ReflectionClass($class))->getFileName();
    $pattern = "#const " . $const . " =([^;]*);#s";
    $replacement = "const " . $const . " = " . var_export($value, TRUE) . " ;";
    return file_put_contents($file, preg_replace($pattern, $replacement, file_get_contents($file)));
}

function coder_updateConsts(array $consts, $class) {
    $file = (new \ReflectionClass($class))->getFileName();
    $content = file_get_contents($file);
    foreach ($consts as $k => $v) {

        $pattern = "#const " . $k . " =([^;]*);#s";
        $replacement = "const " . $k . " = " . var_export($v, TRUE) . " ;";
        $content = preg_replace($pattern, $replacement, $content);
    }
    return file_put_contents($file, $content);
}
//http functions
function http_method($method = NULL)
{
    $meth = $_SERVER['REQUEST_METHOD'];
    $m = $meth?strtolower($meth):'any';
    unset($meth);
    if($method)
    {
        return strtolower($m) === strtolower($method);
    }
    return strtolower($m);
}

function http_get($key = NULL)
{
    $g = filter_input_array(INPUT_GET);
    if($key)
    {
        return recursive_array_get($key,$g );
    }
    return filter_input_array(INPUT_GET);
}

function http_post($key = NULL)
{
    $p = filter_input_array(INPUT_POST);
    if($key)
    {
        return recursive_array_get($key, $p);
    }
    return (array)$p;
}



function path_info()
{
    return isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'/';
}


function rewrite_response($url,$code = 302)
{
    http_response_code($code);
    header('location:'.$url);
    die;
}
function current_url()
{
    return sprintf("%s://%s%s",isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',$_SERVER['SERVER_NAME'],$_SERVER['REQUEST_URI']);
}
function json_reponse($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    die;
}

function user_from_session($index = NULL)
{
    return Gribaa\Users\User::fromSession($index);
}

function user_account($index = NULL)
{
    return Gribaa\Users\User::getAccount($index);
}
function user_role()
{
    return \Gribaa\Users\Acl::getInstance()->getRole();
}

function user_is_role($role)
{
    return \Gribaa\Users\Acl::getInstance()->isRole($role);
}

function user_is_root()
{
    return \Gribaa\Users\Acl::getInstance()->isRoot();
}

function user_is_guest()
{
    return \Gribaa\Users\Acl::getInstance()->isGuest();
}
function user_can($required)
{
    return \Gribaa\Users\Acl::getInstance()->userCan($required);
}
function get_roles()
{
    return array_keys(Container::get('acl'));
}
function role_exists($role)
{
    $roles = get_roles();
    return in_array($role,$roles);
}
/**
 * 
 * @return \Gribaa\Users\Acl
 */
function get_acl($new = FALSE)
{
    if($new)
    {
        return new \Gribaa\Users\Acl();
    }
    return \Gribaa\Users\Acl::getInstance();
}
//end users function

function url_args($index = NULL)
{   
    if($index)
    {
        return Container::$_matched['args'][$index];
    }
    return Container::$_matched['args'];
}
function route_name()
{
    return Container::$_matched['name'];
}



function current_route($index = NULL)
{
    if(!$index)
    {
        return Container::$_matched;
    }
    return Container::$_matched[$index];
    
}
function get_route($name,$index = NULL)
{
    $r = Container::getRoutes();
    if(!$index)
    {
        return $r[$name][$index];
    }
    return $r[$name][$index];
}

function route_url($name,$args = [])
{
    $route = Container::$_routes[$name];
    if(!isset($route['args']))
    {
        return Config::BASE_DIR.$route['base'];
    }
    $args = array_merge($route['args'],$args);
    $x =  preg_replace_callback('/:(\w+)/', function($mt) use($args){
    return $args[$mt[1]];
    }, $route['base']);
    return Config::BASE_DIR.$x;
}
function route_rewrite($name,$args = [],$code = 302)
{
    rewrite_response(route_url($name,$args), $code);
}
//session functions
function start_session()
{
        if(isset($_COOKIE[session_name()])&&!$_COOKIE[session_name()])
        {
            setcookie(session_name(), NULL,-1);
            return;
        }
        if(session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }
}

function session_get($key)
{
    start_session();
    return recursive_array_get($key, $_SESSION);
}
function session_set($key,$value)
{
    start_session();
    recursive_array_set($key, $value, $_SESSION);
}


function session_delete($key)
{
    start_session();
    recursive_array_set($key, NULL, $_SESSION);
}

function session_has($key)
{
    start_session();
    return recursive_array_has($key, $_SESSION);
}
//string functions
function string_startWith($string, $with) {
    return $with === "" || strrpos($string, $with, -strlen($string)) !== false;
}

function string_endWith($string, $with)
{
        return $with === "" || (($temp = strlen($string) - strlen($with)) >= 0 && strpos($string, $with, $temp) !== false);
}

function view_load($view,$data = [])
{
    $view = explode('.', $view);
    $global_path = PROJECT_DIR.DS.'res'.DS.'views'.DS.  implode(DS, $view).'.php';
    extract($data);
    if(file_exists($global_path))
    {
        ob_start();
        require_once $global_path;
        echo ob_get_clean();
        die;
    }
    $module = $view[0];
    unset($view[0]);
    ob_start();
    require_once  PROJECT_DIR.DS.'app'.DS.  ucfirst($module).DS.'views'.DS.  implode(DS, $view).'.php';
    echo ob_get_clean();
    die;
}

function view_part($view,$data = [])
{
    $view = explode('.', $view);
    $global_path = PROJECT_DIR.DS.'res'.DS.'views'.DS.  implode(DS, $view).'.php';
    extract($data);
    if(file_exists($global_path))
    {
        require_once $global_path;
        return;
    }
    $module = $view[0];
    unset($view[0]);
    require_once  PROJECT_DIR.DS.'app'.DS.  ucfirst($module).DS.'views'.DS.  implode(DS, $view).'.php';
}

function view_content($view,$data = [])
{
    $view = explode('.', $view);
    extract($data);
    $module = $view[0];
    unset($view[0]);
    ob_start();
    require  PROJECT_DIR.DS.'app'.DS.  ucfirst($module).DS.'views'.DS.  implode(DS, $view).'.php';
    return ob_get_clean();
}





/**
 * @return \Gribaa\Sql 
 */
function get_sql()
{
    return \Gribaa\Container::getSql();
}


function run_framework() {
    
    $routes = Container::getRoutes();
    foreach ($routes as $k => $index) {
        $p = path_info();
        if ($index['count'] != count(explode('/', $p))) {
            continue;
        }
        if (!in_array('any', $index['methods']) && !in_array(http_method(), $index['methods'])) {
            continue;
        }
        if (preg_match('#' . $index['regex'] . '#', $p, $args)) {
            array_shift($args);
            $args = $args?array_combine(array_keys($index['args']), $args):[];
            Container::$_matched = ['name' => $k, 'controller' => $index['controller'], 'method' => $index['method'], 'args' => $args];
            break;
        }
    }
    $matched = Container::$_matched;
    if (!$matched) {
        throw new NotFoundException;
    }
    Config::first();
    call_user_func_array([Container::object($matched['controller']), $matched['method']], $matched['args']);die;
}

spl_autoload_register(function($class){
    $path = implode(DS, explode('\\',$class));
    $p1 = PROJECT_DIR.DS.'vendor'.DS.$path.'.php';
    if(file_exists($p1))
    {
        require_once $p1;
        return;
    }
    $p2 = PROJECT_DIR.DS.DS.$path.'.php';
    if(file_exists($p2))
    {
        require_once $p2;
    }
});

//start set routes
Container::setRoutes(array (
));
//end set routes


try {
    run_framework();    
} catch (Exception $ex) {
    Config::error($ex);
}


