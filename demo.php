<?php
require('Router.php');
(new Router)
//->register_first(function(&$params){
	
	//})
->web_config(array('abc'=>123))
->get('/product:f/item', function(&$params, $webConfig){
//$params = array('456');
	print_r($webConfig);
	return "product";
})
->add_one_path('/product/listid:i', function($params){
	return array('123','456');
})
->register_last(function($params, $result){
	echo "<pre>";
	print_r($params);
	print_r($result);
	echo "</pre>";
})->execute();
