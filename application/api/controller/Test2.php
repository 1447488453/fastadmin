<?php
namespace app\api\controller;
use app\common\controller\Api;
use think\Session;
use think\Db;
use think\Cache\driver\Redis;
class Test2 extends Api{
        
    public function test(){
    	$params = $this->request->param();
    	$user_id 	= $params['user_id'];
    	$money 		= $params['money'];
    	$res = Db::name('user')->where('id',$user_id)->setField('user_money',$money);
    	return $res;

    }  
}


?>