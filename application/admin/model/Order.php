<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use think\Db;
class Order extends Model{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /*
     * 订单操作记录
     */
    public function orderActionLog($order_id,$action,$note=''){
        $order = Db::name('order')->where(array('order_id'=>$order_id))->find();
        $data['order_id'] = $order_id;
        $data['action_user'] = $_SESSION['think']['admin']['id'];
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
        return Db::name('order_action')->insert($data);//订单操作记录
    }
    /*
     * 订单操作
     */
    public function orderProcessHandle($order_id,$act,$ext=array()){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
               	$order_sn = Db::name('order')->where("order_id = $order_id")->value("order_sn");
                $updata = array('pay_status'=>1,'pay_time'=>time());
                Db::name('order')->where("order_sn", $order_sn)->update($updata);
    			return true;    			
    		case 'pay_cancel': //取消付款
    			$updata['pay_status'] = 0;
                Db::name('order')->where("order_id", $order_id)->update($updata);
    			return true;
    		case 'confirm': //确认订单
    			$updata['order_status'] = 1;
    			break;
    		case 'cancel': //取消确认
    			$updata['order_status'] = 0;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_status'] = 5;
    			break;
    		case 'remove': //移除订单
    			$this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			$this->confirm_order($order_id); // 调用确认收货按钮
    			return true;
    		default:
    			return true;
    	}
    	return Db::name('order')->where("order_id=$order_id")->update($updata);//改变订单状态
    }

    /**
     * 管理员订单确认收货
     * @param $id 订单id
     * @param int $user_id
     * @return array
     */
    public function confirm_order($order_id){
    $where['order_id'] = $order_id;
    if($user_id){
        $where['user_id'] = $user_id;
    }
    $order = Dn::name('order')->where($where)->find();
    if($order['order_status'] != 1)
        return json(['status'=>-1,'msg'=>'该订单不能收货确认']);
    if(empty($order['pay_time']) || $order['pay_status'] != 1){
        return json(['status'=>-1,'msg'=>'商家未确定付款，该订单暂不能确定收货']);
    }
    $data['order_status'] = 2; // 已收货
    $data['pay_status'] = 1; // 已付款
    $data['confirm_time'] = time(); // 收货确认时间
    if($order['pay_code'] == 'cod'){
        $data['pay_time'] = time();
    }
    $row = Db::name('order')->where(array('order_id'=>$order_id))->save($data);
    if(!$row){
        return json(['status'=>-3,'msg'=>'操作失败']);
    }

    return json(['status'=>1,'msg'=>'操作成功','url'=>'Order/order_detail/order_id/$order_id']);
}
    /**
     * 管理员删除订单
     */
    public function delOrder($order_id){
        $order = Db::name('order')->where(array('order_id'=>$order_id))->find();
        if(empty($order)){
            return ['status'=>-1,'msg'=>'订单不存在'];
        };
        $del_order =Db::name('order')->where(array('order_id'=>$order_id))->delete();
        $del_order_goods = Db::name('order_goods')->where(array('order_id'=>$order_id))->delete();
        if(empty($del_order) && empty($del_order_goods)){
            return json(['status'=>-1,'msg'=>'订单删除失败']);
        };
        return json(['status'=>1,'msg'=>'删除成功']);
    }


    //管理员取消付款
    public function order_pay_cancel($order_id){
        //如果这笔订单已经取消付款过了
        $count = Db::name('order')->where("order_id = $order_id and pay_status = 1")->count();   // 看看有没已经处理过这笔订单
        if($count == 0) return false;
        // 找出对应的订单
        $order = Db::name('order')->where("order_id = $order_id")->find();
        // 增加对应商品的库存
        $orderGoodsArr = Db::name('OrderGoods')->where("order_id = $order_id")->select();
        foreach($orderGoodsArr as $key => $val)
        {
            if(!empty($val['spec_key']))// 有选择规格的商品
            {   // 先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
                $SpecGoodsPrice = new \app\common\model\SpecGoodsPrice();
                $specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
                $specGoodsPrice->where(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']])->setDec('store_count', $val['goods_num']);
                refresh_stock($val['goods_id']);
            }else{
                $specGoodsPrice = null;
                Db::name('Goods')->where("goods_id = {$val['goods_id']}")->update('store_count',$val['goods_num']); // 增加商品总数量
            }
            DB::name('Goods')->where("goods_id = {$val['goods_id']}")->update('sales_sum',$val['goods_num']); // 减少商品销售量
        
        }
        // 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
        M('order')->where("order_id=$order_id")->save(array('pay_status'=>0));
        update_user_level($order['user_id']);
        // 记录订单操作日志
        logOrder($order['order_id'],'订单取消付款','付款取消',$order['user_id']);
        //分销设置
        M('rebate_log')->where("order_id = {$order['order_id']}")->save(array('status'=>0));
    }

    /*
     * 获取当前可操作的按钮
     */
    public function getOrderButton($order){
        /*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         * 
         */
    	$os = $order['order_status'];//订单状态
    	$ss = $order['shipping_status'];//发货状态
    	$ps = $order['pay_status'];//支付状态
		$pt = $order['prom_type'];//订单类型：0默认1抢购2团购3优惠4预售5虚拟6拼团
        $btn = array();
        if($order['pay_code'] == 'cod') {
        	if($os == 0 && $ss == 0){
				if($pt != 6){
					$btn['confirm'] = '确认';
				}
        	}elseif($os == 1 && ($ss == 0 || $ss == 2)){
        		$btn['delivery'] = '去发货';
				if($pt != 6){
					$btn['cancel'] = '取消确认';
				}
        	}elseif($ss == 1 && $os == 1 && $ps == 0){
        		$btn['pay'] = '付款';
        	}elseif($ps == 1 && $ss == 1 && $os == 1){
				if($pt != 6){
					$btn['pay_cancel'] = '设为未付款';
				}
        	}
        }else{
        	if($ps == 0 && $os == 0 || $ps == 2){
        		$btn['pay'] = '付款';
        	}elseif($os == 0 && $ps == 1){
				if($pt != 6){
					$btn['pay_cancel'] = '设为未付款';
					$btn['confirm'] = '确认';
				}
        	}elseif($os == 1 && $ps == 1 && ($ss == 0 || $ss == 2)){
				if($pt != 6){
					$btn['cancel'] = '取消确认';
				}
        		$btn['delivery'] = '去发货';
        	}
        } 
               
        if($ss == 1 && $os == 1 && $ps == 1){
        	$btn['refund'] = '申请退货';
        }elseif($os == 2 || $os == 4){
        	$btn['refund'] = '申请退货';
        }elseif($os == 3 || $os == 5){
        	$btn['remove'] = '移除';
        }
        if($os != 5){
        	$btn['invalid'] = '无效';
        }
        return $btn;
    }


        /**
     *  处理发货单
     * @param array $data  查询数量
     * @return array
     * @throws \think\Exception
     */
    public function deliveryHandle($data){
        $selectgoods = $data['goods'];
        $order = Db::name('order')->field('order_sn,zipcode,user_id,consignee,mobile,country,province,city,district,address,shipping_price')->where('order_id='.$data['order_id'])->select();
        if($data['shipping'] == 1){
            if (!$this->updateOrderShipping($data,$order)){
                return array('status'=>0,'msg'=>'操作失败！！');
            }
        }
        $data['order_sn'] = $order['order_sn'];
        $data['delivery_sn'] = $this->get_delivery_sn();
        $data['zipcode'] = $order['zipcode'];
        $data['user_id'] = $order['user_id'];
        $data['admin_id'] = $_SESSION['think']['admin']['id'];
        $data['consignee'] = $order['consignee'];
        $data['mobile'] = $order['mobile'];
        $data['country'] = $order['country'];
        $data['province'] = $order['province'];
        $data['city'] = $order['city'];
        $data['district'] = $order['district'];
        $data['address'] = $order['address'];
        $data['shipping_price'] = $order['shipping_price'];
        $data['create_time'] = time();
        
        if($data['send_type'] == 0 || $data['send_type'] == 3){
            $did = Db::name('delivery_doc')->insert($data);
        }else{
            return json(['status'=>-1,'msg'=>'发货失败']);
        }
        $is_delivery = 0;
        foreach ($orderGoods as $k=>$v){
            if($v['is_send'] >= 1){
                $is_delivery++;
            }           
            if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
                $res['is_send'] = 1;
                $res['delivery_id'] = $did;
                $r = Db::name('order_goods')->where("rec_id=".$v['rec_id'])->update($res);//改变订单商品发货状态
                $is_delivery++;
            }
        }
        $update['shipping_time'] = time();
        $update['shipping_code'] = $data['shipping_code'];
        $update['shipping_name'] = $data['shipping_name'];
        if($is_delivery == count($orderGoods)){
            $update['shipping_status'] = 1;
        }else{
            $update['shipping_status'] = 2;
        }
        Db::name('order')->where("order_id=".$data['order_id'])->update($update);//改变订单状态
        $s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志
        
        if($s && $r){
            return array('status'=>0,'发货成功');
        }else{
            return array('status'=>1,'msg'=>'发货失败');
        }
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
      /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    /**
     * 修改订单发货信息
     * @param array $data
     * @param array $order
     * @return bool|mixed
     */
    public function updateOrderShipping($data=[],$order=[]){
        $updata['shipping_code'] = $data['shipping_code'];
        $updata['shipping_name'] = $data['shipping_name'];
        Db::name('order')->where(['order_id'=>$data['order_id']])->update($updata); //改变物流信息
        $updata['invoice_no'] = $data['invoice_no'];
        $delivery_res = Db::name('delivery_doc')->where(['order_id'=>$data['order_id']])->update($updata);  //改变售后的信息
        if ($delivery_res){
            return $this->orderActionLog($order['order_id'],'订单修改发货信息',$data['note']);//操作日志
        }else{
            return false;
        }

    }

    
}
