<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++)
        {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
        $this->view->assign([
            'totaluser'        => 35200,
            'totalviews'       => 219390,
            'totalorder'       => 32143,
            'totalorderamount' => 174800,
            'todayuserlogin'   => 321,
            'todayusersignup'  => 430,
            'todayorder'       => 2324,
            'unsettleorder'    => 132,
            'sevendnu'         => '80%',
            'sevendau'         => '32%',
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode
        ]);

        return $this->view->fetch();
    }


    public function information(){
        //bher奖信息
        $params = $this->request->param();
        $where = "b.add_reason='$params[add_reason]'";
        if(!empty($params['keyword'])){
            $where.=" and u.username like '%$params[keyword]%' or u.mobile like '%$params[keyword]%'";
        }
        $res = Db::name('bher_log')->alias('b')->join('user u','b.user_id = u.id')->field('b.id,b.user_id,b.num,b.bher_before,b.bher_balance,b.add_reason')->group('b.user_id')->where($where)->paginate(10)->toarray();
        echo"<pre>";
        print_r($res);
    }
    public function jl_log(){
        $params = $this->request->param();
        if(empty($params['mobile'])||empty($params['add_reason'])){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        $where =" u.mobile='$params[mobile]'";
        $where.=" and b.add_reason='$params[add_reason]'";
        $res = Db::name('user')->alias('u')->join('bher_log b','u.id = b.user_id')->field('b.id,b.user_id,b.num,b.bher_before,b.bher_balance,b.add_reason,u.mobile,u.level')->where($where)->paginate(10)->toarray();
        echo"<pre>";
        print_r($res);

    }

}
