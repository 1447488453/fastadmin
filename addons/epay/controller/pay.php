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
   
        //下面这句必须要执行,且在此之前不能有任何输出
        echo $pay->success();

        return;
    }

    /**
     * 支付成功的返回(仅供开发测试体验)
     */
    public function returnit(){
        $type = $this->request->param('type');
        $pay = Service::checkReturn($type);
        if (!$pay) {
            $this->error('签名错误');
        }
        //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
        $this->success("恭喜你！支付成功!", addon_url("epay/index/index"));

        return;
    }
    //获取上一级会员信息
    public function p_level($p_id){
        $p_res =  Db::name('user')->field('id,bher,parent_id,level')->where("id=".$p_id)->find();
        return $p_res;
    }

    //获取所有父级id
    public function get_parent_id($id){
        $p_id = Db::name('user')->field('parent_id')->where('id='.$id)->select();
        $p_ids='';
        if($p_id){
            foreach ($p_id as $key => $value) {
                $p_ids.=$value['parent_id'].',';
                $p_ids.=$this->get_parent_id($value['parent_id']);
            }
           
        }
        return $p_ids;
    }

    //获取所有下级ID,存入字符串
    public function get_all_catid($user_id){
        $child = Db::name("user")->field("cat_id")->where("parent_id=".$user_id)->select();
        $sub_id = '';
        if($child){
            foreach($child as $k=>$v){
                $sub_id .= $v['id'].',';
                $sub_id .= $this->get_all_catid($v['id']);
            }
        }
        return $sub_id; 
    }
    //树级分类
    public function getTree($array, $parent_id =0, $l = 0){
        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['parent_id'] == $parent_id){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['l'] = $l;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getTree($array, $value['cat_id'], $l+1);
            }
        }
        return $list;
    }

 
}

?>