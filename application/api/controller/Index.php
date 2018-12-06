<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\api\controller\Common;
use think\Db;
use think\Cache\driver\Redis;
/**
 * 首页接口
 */
class Index extends Api{

    protected $noNeedLogin = ['*'];
    // 无需验签的接口
    protected $noNeedSign  = ['*'];

    /**
     * 首页
     */
    public function index(){
        //首页banner图
        $redis = new Redis();
        //首页banner做Redis缓存
        $banner = $redis->hget('hash','banner_1');
        if($banner){
            $this->success('请求成功',json_decode($banner,true)); 
        }
        $res = Db::name('ad')->field('ad_id,ad_code,ad_link')->where('pid=1 and enabled = 1')->select();
        $redis->hset('hash','banner_1',json_encode($res));
        $this->success('请求成功',$res);
    }

}
