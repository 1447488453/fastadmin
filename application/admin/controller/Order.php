<?php

namespace app\admin\controller;
use app\common\controller\Backend;
use think\Db;
use think\Session;
class Order extends Backend{
	public function _initialize(){
		parent::_initialize();
	    $this->model = model('app\admin\model\Order');
	}

	/**
	* 订单列表
	*/
	public function order_list(){
		$params = $this->request->param();
		$where="deleted=0";
		//还有一系列筛选条件
        //订单状态搜索
        if(!empty($params['order_status'])){
            $where.=" and order_status='$params[order_status]'";
        }
        //发货状态搜索
        if(!empty($params['shipping_status'])){
            $where.=" and shipping_status='$params[shipping_status]'";
        }
        //支付状态搜索
        if(!empty($params['pay_status'])){
            $where.=" and pay_status='$params[pay_status]'";
        }
        //关键词搜索
        if(!empty($params['keyword'])){
            $where.=" and consignee like '%$params[keyword]%'  or order_sn like '%$params[keyword]%' or mobile like '%$params[keyword]%'";
        }
		$order_info = Db::name('order')->field('order_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,mobile,pay_name,goods_price,
			order_amount,total_amount,add_time,pay_time,prom_type')->where($where)->paginate(10)->toarray();
		$this->assign('order_info',$order_info);
		echo "<pre>";
		print_r($order_info);exit;
		return $this->view->fetch('order_list');
	}
	/**
	* 订单详情
	*/
	public function order_detail(){
		$order_id = $this->request->param('order_sn');
		if(empty($order_id)){
            return json(['error'=>-1,'msg'=>'非法操作']);
		}
		$data['order_detail'] = Db::name('order')->field('order_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,mobile,pay_name,goods_price,
			order_amount,add_time,pay_time,province,city,district,address,zipcode,user_note,prom_type,pay_code')->where('order_sn='.$order_sn)->find();
		$data['order_goods'] = Db::name('order_goods')->field('goods_sn,goods_name,goods_num,final_price,goods_price,spec_key,spec_key_name')->where('order_sn='.$order_sn)->select();
		echo "<pre>";
		$data['adminOrderButton'] = $this->model->getOrderButton($data['order_detail']);
		print_r($data);exit;
		$this->assign('order_detail',$data);
		return $this->view->fetch('order_list');
	}

    /**
    * 删除订单
    */
    public function del_order(){
        $order_sn = $this->request->param('order_sn');
        if(!$order_sn){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        $res = Db::name('order')->where("order_sn ='$order_sn'")->delete();//删除订单
        $res_goods = Db::name('order_goods')->where("order_sn ='$order_sn'")->delete();//删除订单商品
        if($res&&$res_goods){
            return json(['error'=>1,'msg'=>'删除成功']);
        }else{
            return json(['error'=>-1,'msg'=>'删除失败']);
        }

    }

	/**
     * 订单操作
     * @param $order_id
     */
    public function order_action(){ 
        $action   = $this->request->param('type');
        $order_id = $this->request->param('order_id');
        $note     = $this->request->param('note');

        if($action && $order_id){
            if($action !=='pay'){
                $convert_action= config('$action');
                $res = $this->model->orderActionLog($order_id,$convert_action,$note);
            }
        	 $a = $this->model->orderProcessHandle($order_id,$action,array('note'=>$note,'admin_id'=>$_SESSION['think']['admin']['id']));
        	 if($res !== false && $a !== false){
                 if ($action == 'remove') {
                     return json(['status' => 1, 'msg' => '操作成功', 'url' => 'Order/order_list']);
                 }
                 return json(['status' => 1,'msg' => '操作成功','url' => "Order/order_detail/order_id/$order_id"]);
        	 }else{
                 if ($action == 'remove') {
                     return json(['status' => 0, 'msg' => '操作失败', 'url' => 'Order/order_list']);
                 }
        	 	return json(['status' => 0,'msg' => '操作失败','url' => 'Order/order_list']);
        	 }
        }else{
        	return json(['status' => 0,'msg' => '参数错误','url' => 'Order/order_list']);
        }
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
		$data = $this->request->param();
		$res = $this->model->deliveryHandle($data);
		if($res['status'] == 0){
			$this->success('操作成功',"Admin/Order/delivery_info/order_id/$data[order_id]");
		}else{
			$this->error($res['msg'],"Admin/Order/delivery_info/order_id/$data[order_id]");
		}
    }
    /**
     * 发货单列表
     */
    public function delivery_list(){
    	$res = Db::name('order')->field('order_sn,add_time,shipping_price,consignee,email,address,mobile,user_note')
    	->where('order_status=1 and pay_status=1')->paginate(10)->toarray();
    	echo "<pre>";
    	print_r($res);exit;
    	return $this->view->fetch('delivery_list');
    }
    /**
     * 发货单详情
     */
    public function delivery_info($order_id=''){
    	$order_id = $this->request->param('order_id');
    	if(!$order_id){
    		return json(['error' =>-1, 'msg' => '非法操作']);
    	}
    	$order_info  = Db::name('order')->field('order_sn,add_time,shipping_price,consignee,email,address,mobile,user_note')->where('order_id='.$order_id)->select();
    	$order_goods = Db::name('order_goods')->field('shipping_name,shipping_code,spec_key_name,goods_num,goods_price')->where('order_id='.$order_id)->select();
        $this->assign('order',$order_info);
        $this->assign('orderGoods',$order_goods);
        $shipping_list = Db::name('shipping')->field('shipping_name,shipping_code')->select();
        $this->assign('shipping_list',$shipping_list);
        return $this->view->fetch();    
        
    }
	
}


?>