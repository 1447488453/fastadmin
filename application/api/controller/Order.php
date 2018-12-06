<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Session;
use think\Db;
use \think\Config;
use app\api\controller\Common;
class Order extends Api{
	// 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
     // 无需验签的接口
    protected $noNeedSign  = [];
    /**
     * 订单列表
     */
    public function order_list(){
        $params = $this->request->param();
        // if(empty($params['user_id'])){
        //     return json(['error'=>-1,'msg'=>'传参错误']);
        // }
        $token      = $params('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $where = "deleted=0 and user_id = $user_id ";
        
        if(!empty($params['pay_status'])){
            $where.=" and pay_status = $params[pay_status]";
        }
        if(!empty($params['order_status'])){
            $where.=" and order_status = $params[order_status]";
        }
        if(!empty($params['shipping_status'])){
            $where.=" and shipping_status = $params[shipping_status]";
        }
        if(!empty($params['order_sn'])){
            $where.=" and order_sn like '%$params[order_sn]%'";
        }

        $res = Db::name('order')->field('order_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,goods_price,total_amount,add_time,pay_time')->where($where)->paginate(10)->toarray();
        if($res){
            foreach ($res['data'] as $key => $value){
                $res['data'][$key]['goods_list']        = $this->get_order_goods($value['order_sn']);
                $res['data'][$key]['pay_status']        = Config::get("pay_status.$value[pay_status]");
                $res['data'][$key]['shipping_status']   = Config::get("shipping_status.$value[shipping_status]");
                $res['data'][$key]['order_status']      = Config::get("order_status.$value[order_status]");
            }
        }
       return json(['error'=>0,'msg'=>'success','order_list'=>$res]);
    }
    /**
     * 根据订单号获取订单商品
     */
    public function get_order_goods($order_sn){
        if(!$order_sn){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $order_goods = Db::name('order_goods')->field('goods_id,goods_name,goods_sn,goods_num,goods_price,spec_key_name')->where('order_sn='.$order_sn)->select();
        return $order_goods;
    }
    /**
    * 订单详情
    */
    public function order_detail(){
        $order_sn = $this->request->param('order_sn');
        if(empty($order_sn)){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $data['order_detail'] = Db::name('order')->field('order_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,mobile,pay_name,goods_price,
            order_amount,add_time,pay_time,province,city,district,address,zipcode,user_note,prom_type,pay_code')->where("order_sn=$order_sn")->find();
        $data['order_goods'] = Db::name('order_goods')->field('goods_sn,goods_name,goods_num,final_price,goods_price,spec_key,spec_key_name')->where("order_sn=$order_sn")->select();
        return json(['error'=>0,'msg'=>'获取详情成功','data'=>$data]);
    }
    /**
     * 用户删除订单
     */
    public function del_order(){
        $order_sn = $this->request->param('order_sn');
        if(!$order_sn){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $res = Db::name('order')->where('order_sn='.$order_sn)->setField('deleted',1);
        if($res!==false){
            return json(['error'=>0,'msg'=>'删除成功']);
        }else{
            return json(['error'=>-1,'msg'=>'删除失败']);
        }
    }

    /**
     * 确认订单页
     */
    public function order_confirm(){
        $params = $this->request->param();
        // $user_id    = !empty($params['user_id'])?intval($params['user_id']):'';
        // if(!$user_id){
        //     return json(['error'=>-1,'msg'=>'非法操作']);
        // }
        $token      = $params('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $goods_list = Db::name('cart')->where("user_id=$user_id and selected =1")->select();
        if(!$goods_list){
            return json(['error'=>-1,'msg'=>'购物车没有选中商品']);
        }
        $user_address = Db::name('user_address')->field('address_id,consignee,province,city,district,address,mobile,is_default')->where('user_id='.$user_id)->select();
        $shipping   = Db::name('shipping')->field('shipping_id,shipping_name,shipping_code')->where('is_open=1')->select();
        $user_info  = Db::name('user')->field('level,user_money,bher')->where('id='.$user_id)->find();
        $data['goods_list']         = $goods_list;
        $data['user_address']       = $user_address;
        $data['shipping']           = $shipping;
        $data['user_info']          = $user_info;
        return json(['error'=>0,'msg'=>'success','data'=>$data]);
    }
    /**
     * 提交订单
     */
    public function order_done(){
        $params = $this->request->param();
        if(!empty($params['mobile']) && !preg_match("/^1[34578]\d{9}$/", $params['mobile'])){
            return json(['error'=>-1,'msg'=>'手机号码格式不正确']);  
        }
        #order表数据
        $data['order']['order_sn']      = !empty($params['order_sn'])    ? $params['order_sn']  : $this->get_order_sn();
        // $data['order']['user_id']       = !empty($params['user_id'])     ? intval($params['user_id'])   : '';
        $token      = $params('token');
        $Common     = new Common();
        $data['order']['user_id']    = $Common->getUserId($token);//获取登入用户的user_id

        $data['order']['consignee']     = !empty($params['consignee'])   ? trim($params['consignee']):'';
        $data['order']['province']      = !empty($params['province'])    ? intval($params['province']):0;
        $data['order']['city']          = !empty($params['city'])        ? intval($params['city']):0;
        $data['order']['district']      = !empty($params['district'])    ? intval($params['district']):0;
        $data['order']['mobile']        = !empty($params['mobile'])      ? trim($params['mobile']):'';
        $data['order']['shipping_code'] = !empty($params['shipping_code'])    ? trim($params['shipping_code']):'';
        $data['order']['shipping_name'] = !empty($params['shipping_name'])    ? trim($params['shipping_name']):'';
        $data['order']['pay_name']      = !empty($params['pay_name'])    ? trim($params['pay_name']):'';
        $data['order']['pay_code']      = !empty($params['pay_code'])    ? trim($params['pay_code']):'';
        $data['order']['add_time']      = time();
       
        $order_goods = $params['order_goods'];
        try {
            foreach ($order_goods as $key => $value){
               if(!empty($value['item_id'])){
                    $res_goods = $this->get_goods_info($value['goods_id'],$value['item_id']);
                    $data['goods'][$key]['goods_num']       = $value['goods_num'];
                    $data['goods'][$key]['goods_name']      = $res_goods['goods_name'];
                    $data['goods'][$key]['price']           = $res_goods['price'];
                    $data['goods'][$key]['cost_price']      = $res_goods['cost_price'];
                    $data['goods'][$key]['spec_key']        = $res_goods['key'];
                    $data['goods'][$key]['spec_key_name']   = $res_goods['key_name'];
                    $data['goods'][$key]['order_sn']        = $data['order']['order_sn'];
                    $data['goods'][$key]['item_id']         = $value['item_id'];
                    // $res = $this->save_order_goods($data['goods']);
                }elseif(!empty($value['goods_id'])){
                    $res_goods = $this->get_goods_info($value['goods_id']);
                    $data['goods'][$key]['goods_num']       = $value['goods_num'];
                    $data['goods'][$key]['goods_name']      = $res_goods['goods_name'];
                    $data['goods'][$key]['goods_sn']        = $res_goods['goods_sn'];
                    $data['goods'][$key]['price']           = $res_goods['price'];
                    $data['goods'][$key]['cost_price']      = $res_goods['cost_price'];
                    $data['goods'][$key]['order_sn']        = $data['order']['order_sn'];
                    // $res = $this->save_order_goods($data['goods']);
                }else{
                     return json(['error'=>-1,'msg'=>'提交订单失败']);
                }
            }
        } catch (Exception $e){
                $error= $e->getMessage();
               return json($error);
        }
        $res= $this->save_order_goods($data['order'],$data['goods']);
        if($res){
            return json(['error'=>0,'msg'=>'提交订单成功']);
        }else{
            return json(['error'=>-1,'msg'=>'提交订单失败']);
        }
    }
    /**
     * 保存订单商品
     */
    public function save_order_goods($order,$goods){
          // 启动事务
        Db::startTrans();
        try{
            foreach ($goods as $key => $value) {
                Db::name('order_goods')->insert($goods[$k]);
            }
            $res = Db::name('order')->insert($data['order']);
            // 提交事务
            Db::commit();
           
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return $res;  
    }

    /**
     * 获取商品信息
     */
    public function get_goods_info($goods_id='',$item_id=''){
        if($item_id){
            $spec_goods = Db::name('spec_goods_price')->field('goods_name,price,cost_price,key,key_name')->where('item_id='.$item_id)->select();
            return $spec_goods;
        }else{
            $goods = Db::name('goods')->field('goods_name,goods_sn,price,cost_price')->where('goods_id='.$goods_id)->select();
            return $goods;
        }
    }

    /**
     * 得到新订单号(判断重复)
     * @return  string
     */
    public function get_order_sn(){
        mt_srand((double) microtime() * 1000000);
        $order_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $oder_sn_exist = Db::name('order')->where("order_sn='$order_sn'")->count();
        if($oder_sn_exist){
            return $this->get_order_sn();
        }else{
            return $order_sn;
        }
    }





}