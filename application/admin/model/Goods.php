<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use think\Db;

class Goods extends Model{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

	/**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id,$params){
        $item_img = !empty($params['item'])?$params['item']:'';
        // 商品图片相册  图册
        $goods_images = !empty($params['goods_images'])?$params['goods_images']:'';
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = Db::name('goods_gallery')->field('img_id,img_url')->where("goods_id = $goods_id")->select(); // 查出所有已经存在的图片
            // 删除图片
            if($goodsImagesArr){
                foreach($goodsImagesArr as $key => $val)
                {      
                    if(in_array($val['img_url'], $goods_images))  Db::name('goods_gallery')->where("img_id = $val[img_id]")->delete();
                }
            }
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array('goods_id' => $goods_id,'img_url' => $val);
                    Db::name('goods_gallery')->insert($data); // 实例化User对象
                }
            }
        }
        //商品规格处理
        $goods_item = !empty($params['item'])?$params['item']:'';
        $eidt_goods_id = $goods_id;
        if ($goods_item) {
            $keyArr = '';//规格key数组
            foreach ($goods_item as $k => $v) {
                $keyArr .= $k.',';
                // 批量添加数据
                $v['price'] = trim($v['price']);
                $data = [
                    'goods_id' => $goods_id,
                    'key' => $k,
                    'key_name' => $v['key_name'],
                    'price' => $v['price'],
                    'market_price'=>$v['market_price'],
                    'cost_price'=>$v['cost_price'],
                    'commission'=>$v['commission'],
                ];
                $specGoodsPrice = Db::name('spec_goods_price')->where(['goods_id' => $data['goods_id'], 'key' => $data['key']])->find();
                if ($item_img) {
                    $spec_key_arr = explode('_', $k);
                    foreach ($item_img as $key => $val) {
                        if (in_array($key, $spec_key_arr)) {
                            $data['spec_img'] = $val;
                            break;
                        }
                    }
                }

                if($specGoodsPrice){
                    Db::name('spec_goods_price')->where(['goods_id' => $goods_id, 'key' => $k])->update($data);
                }else{
                    Db::name('spec_goods_price')->insert($data);
                    }
                // 修改商品后购物车的商品价格也修改一下
                // Db::name('cart')->where("goods_id = $goods_id and spec_key = '$k'")->update(array(
                //     'market_price' => $v['market_price'], //市场价
                //     'goods_price' => $v['price'], // 本店价
                // ));
            }
            if($keyArr){
                Db::name('spec_goods_price')->where('goods_id',$goods_id)->whereNotIn('key',$keyArr)->delete();
            }
        }else{
            Db::name('spec_goods_price')->where(['goods_id' => $goods_id])->delete();
        }

        // 商品规格图片处理
        if(!empty($params['item_img']))
        {
            Db::name('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($params['item_img'] as $key => $val)
            {
                Db::name('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val));
            }
        }
        $this->refresh_stock($goods_id); // 刷新商品库存
    }


    /**
    * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
    * @param type $goods_id  商品id
    */
   public function refresh_stock($goods_id){
        $count = Db::name("SpecGoodsPrice")->where("goods_id", $goods_id)->count();
        if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

        $store_count = Db::name("SpecGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
        Db::name("Goods")->where("goods_id", $goods_id)->update(array('goods_number'=>$store_count)); // 更新商品的总库存
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type,$params){  
        $GoodsAttr = Db::name('GoodsAttr');   
         // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除        
        if($goods_type == 0)  
        {
            $GoodsAttr->where('goods_id = '.$goods_id)->delete(); 
            return;
        }
            $GoodsAttrList = $GoodsAttr->where('goods_id = '.$goods_id)->select();
            
            $old_goods_attr = array(); // 数据库中的的属性  以 attr_id _ 和值的 组合为键名
            foreach($GoodsAttrList as $k => $v)
            {                
                $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
            }            
                              
            // post 提交的属性  以 attr_id _ 和值的 组合为键名    
            $post_goods_attr = array();
            $post =  $params;
            foreach($post as $k => $v){
                $attr_id = str_replace('attr_','',$k);
                if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                   continue;                                 
               foreach ($v as $k2 => $v2)
               {                      
                   $v2 = str_replace('_', '', $v2); // 替换特殊字符
                   $v2 = str_replace('@', '', $v2); // 替换特殊字符
                   $v2 = trim($v2);
                   
                   if(empty($v2))
                       continue;
                
                    $tmp_key = $attr_id."_".$v2;
                    $post_attr_value = $post["attr_{$attr_id}"];
                    $attr_value = $post_attr_value[$k2]; 
                    $attr_value = $attr_value ? $attr_value : 0;
                 if(array_key_exists($tmp_key , $old_goods_attr)){ // 如果这个属性 原来就存在                         
                    $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];                         
                    $GoodsAttr->where("goods_attr_id = $goods_attr_id")->update(array('attr_value'=>$attr_value));                       
                   }else{
                        $GoodsAttr->insert(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2));    
                    }                   
                    unset($old_goods_attr[$tmp_key]);
               }
            }     
            // 没有被 unset($old_goods_attr[$tmp_key]); 掉是 说明 数据库中存在 表单中没有提交过来则要删除操作
            foreach($old_goods_attr as $k => $v)
            {                
               $GoodsAttr->where('goods_attr_id = '.$v['goods_attr_id'])->delete(); // 
            }                       

    }


    
}
