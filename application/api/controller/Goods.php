<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Session;
use think\Db;
class Goods extends Api{

	// 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];
     // 无需验签的接口
    protected $noNeedSign  = [];

   	/**
     * 获取商品列表
     */
    public function ajax_goods_list(){
    	$params = $this->request->param();
        $where = "is_on_sale=1 and is_delete =0";
        $common =  new Common();
        if(!empty($params['cat_id'])){
            $str_cat_id = !empty($params['cat_id']) ? $params['cat_id'].$common->get_all_catid($params['cat_id']) : '';
            $where .=  " AND cat_id IN ($str_cat_id) ";
        }
        $res = Db::name('Goods')->field('goods_id,goods_sn,goods_name,goods_number,market_price,shop_price,goods_img,bhe,bher,is_free_shipping')->where($where)->paginate(10)->toarray();
        $this->success('获取成功', $res, 0);
    }

    /**
     * 获取商品详情
     */
    public function ajax_goods_detial(){
        $goods_id   = $this->request->param('goods_id');
        if(!$goods_id){
            $this->error('非法操作', null, 401);
        }
        $data['goods_info'] = Db::name('Goods')->field('goods_id,goods_sn,goods_name,goods_number,market_price,shop_price,goods_img,bhe,bher,is_free_shipping')->where('goods_id= '.$goods_id)->find();//商品基本信息
        $data['goods_images'] =  Db::name('goods_gallery')->where('goods_id= '.$goods_id)->select();//商品相册
        $data['goods_attr'] = Db::name('goods_attr')->where('goods_id='.$goods_id)->select();//商品公共属性
        $common =  new Common();
        $data['goods_spec'] = $common->get_spec($goods_id);//商品规格
        $data['goods_spec_price'] = Db::name('spec_goods_price')->field('item_id,key,key_name,price,market_price')->where('goods_id='.$goods_id)->select();
        //商品规格价格
        return json($data);
    }

    /**
     * 商品搜索列表页
     */
    public function search(){
        $params         = $this->request->param();
        $filter_param   = array(); // 筛选数组
        $cat_id         = !empty($params['cat_id']) ? intval($params['cat_id']):''; // 当前分类id
        $sort           = !empty($params['sort'])   ?trim($params['sort']):'sort'; // 排序
        $price          = !empty($params['price'])  ?trim($params['price']):0; // 价钱
        $start_price    = !empty($params['start_price']) ?trim($params['start_price']):0; // 输入框价钱
        $end_price      = !empty($params['end_price'])  ?trim($params['end_price']):0; // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱       
        $filter_param['cat_id'] = $cat_id; //加入筛选条件中        
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
        $q = !empty($params['q'])?urldecode(trim($params['q'])):''; // 关键字搜索
        $q  && ($filter_param['q'] = $q); //加入筛选条件中
        $where  = array('is_on_sale' => 1);
        if($q) $where['goods_name'] = array('like','%'.$q.'%');
        $filter_goods_id = Db::name('goods')->where($where)->column('goods_id');
        // 过滤筛选的结果集里面找商品
        $goods_list = Db::name('goods')->field('goods_id,goods_sn,goods_name,goods_number,market_price,shop_price,goods_img,bhe,bher,is_free_shipping')->whereIn("goods_id",$filter_goods_id)->paginate(10)->toarray();
        // echo "<pre>";
        // print_r($goods_list);exit;
        $result['goods_list'] = $goods_list;
        $result['filter_param'] = $filter_param;
      
        return json($result);
    }
}