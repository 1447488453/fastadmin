<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Session;
use think\Db;
use \think\Config;
use app\api\controller\Common;
class Cart extends Api{

	// 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
     // 无需验签的接口
    protected $noNeedSign  = [];
    /**
     * 购物车列表
     */
    public function cart_list(){
        // $user_id = $this->request->param('user_id');
        // if(!$user_id){
        //     return json(['error'=>-1,'msg'=>'非法操作']);
        // }
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $res = Db::name('cart')->field('user_id,goods_id,goods_sn,goods_name,market_price,goods_price,goods_num,item_id,spec_key,spec_key_name')->where('user_id='.$user_id)->select();
        $total_num = Db::name('cart')->where('user_id='.$user_id)->sum('goods_num');
        return json(['error'=>0,'msg'=>'success','data'=>$res,'total_num'=>$total_num]);
    }
    /**
     * 加入购物车
     */
    public function add_to_cart(){
        $params = $this->request->param();
        // $params = array(
        //     'goods_id'=>'2516',
        //     'item_id' =>'440',
        //     'goods_num'=>'',
        //     'user_id' =>'3',
        // );
        $goods_id   = !empty($params['goods_id'])   ?intval($params['goods_id']):'';
        $item_id    = !empty($params['item_id'])    ?intval($params['item_id']):'';
        $goods_num  = !empty($params['goods_num'])  ?intval($params['goods_num']):1;
        // $user_id    = !empty($params['user_id'])    ?intval($params['user_id']):'';
        $token      = $params('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        if(!$user_id || !$goods_id){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $goods = Db::name('goods')->field('goods_name,goods_sn,shop_price,market_price,goods_number')->where('goods_id='.$goods_id)->find();
        if(!$goods){
            return json(['error'=>-1,'msg'=>'该商品不存在']);
        }
        $goods_price    = $goods['shop_price'];
        $market_prcie   = $goods['market_price'];

        if($item_id){
            $spec_goods = Db::name('spec_goods_price')->field('item_id,goods_id,key,key_name,price,market_price,store_count,commission')->where('item_id='.$item_id)->find();
            if($goods_num>$spec_goods['store_count']){
                return json(['error'=>-1,'msg'=>'库存不足']);
            }
            $goods_price            = $spec_goods['price'];
            $market_price           = $spec_goods['market_price'];
            $data['spec_key']       = $spec_goods['key']; 
            $data['spec_key_name']  = $spec_goods['key_name'];
            $data['item_id']        = $spec_goods['item_id']; 
        }
        if($goods_num>$goods['goods_number']){
            return json(['error'=>-1,'msg'=>'库存不足']);
        }
        $data['goods_num']      = $goods_num;
        $data['goods_id']       = $goods_id;
        $data['user_id']        = $user_id;
        $data['goods_sn']       = $goods['goods_sn'];
        $data['goods_name']     = $goods['goods_name'];
        $data['market_price']   = $market_price;
        $data['goods_price']    = $goods_price;
        $data['add_time']       = time();
        if(!$item_id){
            $num = Db::name('cart')->where("goods_id= $goods_id and user_id= $user_id")->value('goods_num');
            if($num){
                $total_num = $goods_num+$num;
                if($total_num>$goods['goods_number']){
                    return json(['error'=>-1,'msg'=>'库存不足']);
                }
                $res = Db::name('cart')->where('goods_id=$goods_id and user_id = $user_id')->setField('goods_num',$total_num);
                if($res){
                    return json(['error'=>0,'msg'=>'添加成功']);
                }else{
                    return json(['error'=>-1,'msg'=>'添加失败']);
                }
            }
        }else{
             $num = Db::name('cart')->where("item_id = $item_id and user_id = $user_id")->value('goods_num');
             if($num){
                $total_num = $goods_num+$num;
                if($total_num>$goods['goods_number']){
                    return json(['error'=>-1,'msg'=>'库存不足']);
                }
                $res = Db::name('cart')->where("item_id= $item_id and user_id = $user_id")->setField('goods_num',$total_num);
                if($res){
                    return json(['error'=>0,'msg'=>'添加成功']);
                }else{
                    return json(['error'=>-1,'msg'=>'添加失败']);
                }
            }
        }
        $cart_num =Db::name('cart')->where('user_id='.$user_id)->count('id');
        if($cart_num>=20){
            return json(['error'=>-1,'msg'=>'购物车商品数量不能超过20']);
        }
        $res = Db::name('cart')->insert($data);
        if($res){
            return json(['error'=>0,'msg'=>'添加成功']);
        }else{
            return json(['error'=>-1,'msg'=>'添加失败']);
        }

    }

    /**
     * 更新购物车商品数量
     */
    public function update_goods_num(){
        $cart_id        = $this->request->param('id');
        $cart_goods_num = $this->request->param('goods_num');
        $item_id        = $this->request->param('item_id');
        $goods_id       = $this->request->param('goods_id');

        if(empty($item_id)&&empty($goods_id)){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        if(empty($cart_id)||empty($cart_goods_num)||$cart_goods_num<1){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        if(!empty($item_id)){
            $limit_num = Db::name('spec_goods_price')->where('item_id='.$item_id)->value('store_count');
        }else{
            $limit_num = Db::name('goods')->where('goods_id='.$goods_id)->value('goods_number');
        }
        if($cart_goods_num>$limit_num){
            return json(['error'=>-1,'msg'=>"商品数量不能大于库存".$limit_num]);
        }
        $res = Db::name('cart')->where('id='.$cart_id)->setField('goods_num',$cart_goods_num);
        if($res!==false){
            return json(['error'=>0,'msg'=>'修改商品数量成功']);
        }else{
            return json(['error'=>-1,'msg'=>'修改商品数量失败']);
        }
    }
    /**
     * 删除购物车商品
     */
    public function del_cart_goods(){
        $cart_ids = $this->request->param('id');
        $res = Db::name('cart')->whereIn('id',$cart_ids)->delete();
        if($res){
            return json(['error'=>0,'msg'=>'删除成功']);
        }else{
            return json(['error'=>-1,'msg'=>'删除失败']);
        } 
    }

    /**
     * 更新购物车，并返回计算结果
     */
    public function UpdateCart(){
       // $user_id = $this->request->param('user_id');
       // if(!$user_id){
       //  return json(['error'=>-1,'msg'=>'传参错误']);
       // }
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id
       $total_goods_num = Db::name('cart')->where("user_id= $user_id and selected=1")->count('goods_num');
       $cart_goods = Db::name('cart')->field('goods_price,goods_num')->where("user_id= $user_id and selected=1")->select();
       if($cart_goods){
            $total_price = 0;
            foreach ($cart_goods as $key => $value){
                $cart_goods[$key]['goods_total_price'] =$value['goods_price']*$value['goods_num'];
                $total_price+= $value['goods_price']*$value['goods_num']; 
            }
       }
       return json(['error'=>0,'total_goods_num'=>$total_goods_num,'total_price'=>$total_price,'data'=>$cart_goods]);
    }

}