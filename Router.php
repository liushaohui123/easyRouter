<?php
/**
 * a simple router for php website
 *
 * PHP version 5
 *
 * @category PHP
 * @author liushaohui <liu.sh.hui@gmail.com>
 * @copyright 2016 router
 * @link http://github.com/liushaohui123/easyRouter
 */ 
class Router{
	/**
	 * store router structure, the core var
	 *
	 * @var Array
	 */ 
	private $tree;
	/**
	 * store the function of user that execute before match url
	 * 
	 * @var callback
	 */
	private $first;
	/**
	 * store the function of user that execute after match url
	 * 
	 * @var callback
	 */
	private $last;
	/**
	 * configured website config
	 *
	 * @var Array
	 */ 
	private $webConfig;
	/**
	 * configured router config
	 *
	 * @var Array
	 */
	private $config;
	/**
	 * initial router
	 */ 
	public function __construct(){
		//read route's config from router.conf,you can change filename 
		$this->config = json_decode(file_get_contents('router.conf'), true);
		$this->tree = array();
		$this->first = function(){};
		$this->last = function(){};
		$this->webConfig = array();
	}
	/**
	 * set website config
	 *
	 * @params Array $webConfig
	 */
	public function web_config($webConfig){
		$this->webConfig = $webConfig;	
		return $this;
	}
	public function clean_tree(){
		$this->tree = array();
		
	}
	/**
	 * store cache(can't work)
	 */  
	public function cache(){
		if(!self::is_cache($this->config['cache'])){
			file_put_contents($this->config['cache']['addr'], serialize($this));
		}
	}
	/**
	 * construct cache of router(can't work)
	 */ 
	public static function _CRouter(){
		$config = json_decode(file_get_contents("router.conf"), true);
		date_default_timezone_set($config['cache']['timezone']);
		if(self::is_cache($config['cache'])){
			return unserialize(file_get_contents($config['cache']['addr']));
		}else{
			return (new Router);
		}
	}
	/**
	 * check is caching(can't work)
	 */ 
	public static function is_cache($cache){
		if($cache['isCache']&&(time()-$cache['lastUpdate']<$cache['time'])&&file_exists($cache['addr'])){
			return true;
		}else return false;
	}
	/**
	 * add a url to match,we can set the method of request
	 *
	 * @var string $url
	 * @var callback $func
	 * @var string $method
	 */
	public function add_one_path($url, $func, $method='GET'){
		/*if(self::is_cache($this->config['cache'])){
			return $this;
	}*/
		//translate url from /a/b/c to array('a','b','c')
		$urlArray = explode('/', $url);
		array_shift($urlArray);
		$branch = &$this->tree;
		$count = count($urlArray);
		for($i=0; $i<$count; $i++){
			$newBranch = array_shift($urlArray);
			//check the style like /id:value  
			//,if it is,store it like id:
			$isMatch = preg_match('/([\w]+)([-:!])([\w.]*)/i', $newBranch, $matchs)&&($newBranch = $matchs[1].$matchs[2]);
			if($i!=$count-1){
				$branch = &$branch['branch'][$newBranch];
				//to the style like /id:value ,store callbackfunction and type of value
				$isMatch&&($type = array_key_exists($matchs[3], $this->config['type'])?$this->config['type'][$matchs[3]]:"string")&&($branch['type'] = $type);	
			}else{
				$branch['leaf'][$newBranch]['method'][$method] = $func;
				$isMatch&&($type = array_key_exists($matchs[3], $this->config['type'])?$this->config['type'][$matchs[3]]:"string")&&($branch['leaf'][$newBranch]['type'] = $type);	
			}
		}
		return $this;
	}
	/**
	 * match path by url and call the true function of user
	 *
	 * @var string $url
	 * @var Array $params
	 * @var string $method
	 */ 
	public function match_one_path($url, &$params, $method='GET'){
		$urlArray = explode('?',$url);
		//translate url from /a/b/c to array('a','b','c')
		$urlArray = explode('/', $urlArray[0]);
		array_shift($urlArray);
		//get callback function that user define from tree
		$branch = &$this->tree;
		$count = count($urlArray);
		for($i=0; $i<$count; $i++){
			$newBranch = array_shift($urlArray);
			//check the style like /id:value and check the type of 
			//value
			$isMatch = preg_match('/([\w]+)([-:!])([\w.]+)/i', $newBranch, $matchs)&&($params[$matchs[1]] = $matchs[3])&&($newBranch = $matchs[1].$matchs[2]);
			if($i!=$count-1){
				if(isset($branch['branch'][$newBranch])){
					$branch = &$branch['branch'][$newBranch]; 	
					$isMatch?settype($params[$matchs[1]], $branch['type']):"";
				}else{
					return false;
				}
			}else{
				//select true callback function by request method 
				if(isset($branch['leaf'][$newBranch])&&is_callable($branch['leaf'][$newBranch]['method'][$method])){
					$isMatch?settype($params[$matchs[1]], $branch['leaf'][$newBranch]['type']):"";
					return call_user_func_array($branch['leaf'][$newBranch]['method'][$method], array(&$params, $this->webConfig));
														}else{						                                        return false;								}

			}
		}
	}
	/**
	 * register callback function before match url
	 *
	 * @var callback $func
	 */ 
	public function register_first($func){
		/*if(self::is_cache($this->config['cache'])){
			return $this;
	}*/
		$this->first = $func;
		return $this;
	}
	/**
	 * register callback function after match url
	 *
	 * @var callback $func
	 */ 
	public function register_last($func){
		/*if(self::is_cache($this->config['cache'])){
			return $this;
	}*/
		$this->last = $func;
		return $this;
	}
	/**
	 * user execute router by use this function
	 */ 
	public function execute(){
		$method = $_SERVER['REQUEST_METHOD'];
		switch($method){
		case 'GET':$params = $_GET;break;
		case 'POST':$params = $_POST;break;
		default:parse_str(file_get_contents('php://input'), $params);
		}
		//when router call callback function that user define,pass $params by reference to the callback function 
		call_user_func_array($this->first, array(&$params, $this->webConfig));
		$result = $this->match_one_path($_SERVER['REQUEST_URI'], $params, $method);
		call_user_func_array($this->last, array(&$params, $result, $this->webConfig));
		//$this->cache();
	}
	/**
	 * execute magic function when user use function that isn't define
	 */ 
	public function __call($name, $arguments){
		switch($name){
		case 'get':
			return $this->add_one_path($arguments[0], $arguments[1], 'GET');break;
		case 'post':
			return $this->add_one_path($arguments[0], $arguments[1], 'POST');break;
		case 'put':
			return $this->add_one_path($arguments[0], $arguments[1], 'PUT');break;
		case 'delete':
			return $this->add_one_path($arguments[0], $arguments[1], 'DELETE');break;
		default:
			return $this;
		}
	}
}
