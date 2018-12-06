<?php

namespace addons\epay\controller;

use addons\epay\library\Service;
use fast\Random;
use think\addons\Controller;
use think\Db;
class Wxpay extends Controller{
	protected $layout = 'default';

    protected $config = [];
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        return $this->view->fetch();
    }

    public function wxpay(){
    	$amount = $this->request->param('amount');
        $type = $this->request->param('type');
        $method = $this->request->param('method');
    	//创建支付对象
		$pay = Service::createPay($type, $config);
		//这里配置两个回调地址,一个回调URL，一个支付完成返回URL，如果不配置则以payment.php中的为准
        $notifyurl = $this->request->root(true) . '/addons/epay/Wxpay/nofityit/type/' . $type;
        $returnurl = $this->request->root(true) . '/addons/epay/Wxpay/returnit/type/' . $type;
        $config = [
            'notify_url' => $notifyurl,
            'return_url' => $returnurl,
        ];
        switch ($method) {
            case 'mp':
                //公众号支付
                //公众号支付必须有openid
                $order['openid'] = 'onkVf1FjWS5SBxxxxxxxx';
                return $pay->mp($order);
            case 'wap':
                //手机网页支付,跳转
                return $pay->wap($order)->send();
            case 'app':
                //APP支付,直接返回字符串
                return $pay->app($order)->send();
            case 'scan':
                //扫码支付,直接返回字符串
                return $pay->scan($order);
            case 'pos':
                //刷卡支付,直接返回字符串
                //刷卡支付必须要有auth_code
                $order['auth_code'] = '289756915257123456';
                return $pay->pos($order);
            case 'miniapp':
                //小程序支付,直接返回字符串
                //小程序支付必须要有openid
                $order['openid'] = 'onkVf1FjWS5SBxxxxxxxx';
                return $pay->miniapp($order);
            default:
                //其它支付类型请参考：https://yansongda.gitbooks.io/pay/docs/wechat/pay.html
            }
    }

    /**
     * 通知回调(仅供开发测试体验)
     */
    public function notifyit(){
        $type = $this->request->param('type');
        $pay = Service::checkNotify($type);
        if (!$pay) {
            echo '签名错误';
            return;
        }
        //微信可以获取到out_trade_no,total_fee等信息
        $data = $pay->verify();
        /**--------------------
			这里处理业务逻辑----

 		*/
        //1：更新订单支付状态
        $res = Db::name('order')->where('order_sn='.$data['order_sn'])->setField('pay_status',1);
        //2:更新个人bher
        $user_id =$data['user_id'];
        $user = Db::name('user')->field('bher,parent_id')->where('id='.$user_id)->find();   
        $new_bher = $user['bher']+$total_fee;
        Db::name('user')->where('id='.$user_id)->setField('bher',$new_bher);
        //更新bher_log记录
        $bher_log['user_id'] = $user_id;
        $bher_log['num'] = $total_fee;
        $bher_log['bher_before'] = $user['bher'];
        $bher_log['bher_balance'] = $new_bher;
        $bher_log['add_time'] = time();
        $bher_log['add_reason'] = '消费';
        Db::name('bher_log')->insert($bher_log);
        //-----推荐奖开始-------------
        //3:更新上级bher
        if($user['parent_id']!==0){ 
            $res = Db::name('user')->field('bher,parent_id')->where("id=$user[parent_id]")->find();
            if($res['bher']>$total_fee){
                $p1_bher = $res['bher']+$total_fee;  
            }else{
                $p1_bher = $res['bher']+ $res['bher'];
            }
            Db::name('user')->where("id=$user[parent_id]")->setField('bher',$p1_bher);
            //p1_bher更新bher_log记录
            $p1_bher_log['user_id'] = $user['parent_id'];
            $p1_bher_log['num'] = $p1_bher-$res['bher'];
            $p1_bher_log['bher_before'] = $res['bher'];
            $p1_bher_log['bher_balance'] = $p1_bher;
            $p1_bher_log['add_time'] = time();
            $p1_bher_log['add_reason'] = '推荐奖';
            Db::name('bher_log')->insert($p1_bher_log);     
            //4:更新上上级bher
            if($res['parent_id']!==0){
                $p2_res = Db::name('user')->field('bher,parent_id')->where("id=$res[parent_id]")->find();
                if($p2_res['bher']>$total_fee){
                    $p2_bher = $p2_res['bher']+$total_fee*0.4;    
                }else{
                    $p2_bher = $p2_res['bher']+$p2_res['bher']*0.4;
                }
                Db::name('user')->where("id=$res[parent_id]")->setField('bher',$p2_bher);
                //pw_bher更新bher_log记录
                $p2_bher_log['user_id'] = $res['parent_id'];
                $p2_bher_log['num'] =  $p2_bher-$p2_res['bher'];
                $p2_bher_log['bher_before'] = $p2_res['bher'];
                $p2_bher_log['bher_balance'] = $p2_bher;
                $p2_bher_log['add_time'] = time();
                $p2_bher_log['add_reason'] = '推荐奖';
                Db::name('bher_log')->insert($p2_bher_log);     
                //5:更新上上上级bher
                if($p2_res['parent_id']!==0){
                    $p3_res = Db::name('user')->field('bher,parent_id')->where("id=$p2_res[parent_id]")->find();
                    if($p3_res['bher']>$total_fee){
                        $p3_bher = $p3_res['bher']+$total_fee*0.1;    
                    }else{
                        $p3_bher = $p3_res['bher']+$p3_res['bher']*0.1; 
                    }
                Db::name('user')->where("id=$p2_res[parent_id]")->setField('bher',$p3_bher);
                    //pw_bher更新bher_log记录
                    $p3_bher_log['user_id'] = $p2_res['parent_id'];
                    $p3_bher_log['num'] =   $p3_bher-$p3_res['bher'];
                    $p3_bher_log['bher_before'] = $p3_res['bher'];
                    $p3_bher_log['bher_balance'] = $p3_bher;
                    $p3_bher_log['add_time'] = time();
                    $p3_bher_log['add_reason'] = '推荐奖';
                    Db::name('bher_log')->insert($p2_bher_log); 
                }   
            }
        }
        //-----推荐奖结束-------------

        //-----奖开始-------------
                
        //-----奖结束-------------

        //下面这句必须要执行,且在此之前不能有任何输出
        echo $pay->success();

        return;
    }

    /**
     * 支付成功的返回(仅供开发测试体验)
     */
    public function returnit()
    {
        $type = $this->request->param('type');
        $pay = Service::checkReturn($type);
        if (!$pay) {
            $this->error('签名错误');
        }
        //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
        $this->success("恭喜你！支付成功!", addon_url("epay/index/index"));

        return;
    }


}

?>