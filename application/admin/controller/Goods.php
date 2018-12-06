<?php
namespace app\admin\controller;
use app\common\controller\Backend;
use think\Db;
class Goods extends Backend{
	// protected $layout = '布局模板';
	public function _initialize(){
		parent::_initialize();
	    $this->model = model('app\admin\model\Goods');
	}

	/**
	* 商品列表
	*/
	public function goods_list(){
		$params 	= $this->request->param();
		$cat_id 	= isset($params['cat_id']) ? intval($params['cat_id']) :'';
		$keyword 	= isset($params['keyword']) ? trim($params['keyword']) :'';
		$where = 'is_delete = 0';
		//分类id
		if($cat_id){
			$where.= " and cat_id = $cat_id";
		}
		if($keyword){
			$where.=" and goods_name like '%$keyword%' or goods_sn like '%$keyword%'";
		}
		$res = Db::name('Goods')->field('goods_id,goods_name,shop_price')->where($where)->paginate(10)->toarray();
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch('goods_list');
	}
	
	public function edit_goods(){
		$goods_id = $this->request->param('goods_id');
		$goods_info['data'] = Db::name('goods')->field('goods_id,cat_id,goods_sn,goods_name,goods_number,goods_weight,market_price,shop_price,cost_price,goods_brief,goods_desc,
			goods_img,is_free_shipping,commission,goods_type,keywords')->where('goods_id='.$goods_id)->select();
		$goods_info['images'] = Db::name('goods_gallery')->field('img_id,goods_id,img_url,img_desc')->where('goods_id='.$goods_id)->select();
		$goods_info['goods_attr'] = Db::name('goods_attr')->field('goods_attr_id,goods_id,attr_id,attr_value')->where('goods_id='.$goods_id)->select();
		$goods_info['spec_goods_price'] = Db::name('spec_goods_price')->field('item_id,goods_id,key,key_name,price,market_price,cost_price,commission,store_count,spec_img')->where('goods_id='.$goods_id)->select();
		$this->assign('goods_info',$goods_info);
		return $this->view->fetch('goods_edit');
	}

	/**
	* 商品添加编辑
	*/
    public function save(){
        // $data = $this->request->param();
        $params = array('goods_id'=>'2516',
    			'goods_name'=>'测试啊1',
    			'goods_brief'=>'商品测试',
    			'goods_sn'=>'',
    			'cat_id'=>56,
    			'cat_id_2'=>570,
    			'cat_id_3'=>575,
    			'shop_price'=>111,
    			'market_price'=>222,
    			'cost_price'=>10,
    			'commission' => 10,
    			'original_img'=>'/public/upload/goods/2018/08-21/44bac872439ffd34cc8efe9c6b01cca3.png',
    			'is_free_shipping'=>1,
    			'goods_desc'=>'商品详情简介啊',	
    			'goods_images'=>array(
					'0' => '/public/upload/goods/2018/08-21/dfb8d808c676852dbedb3dff45073b06.png',
            		'1' => '/public/upload/goods/2018/08-21/b30ac2c90ada0b4bc8325f22f0ae81e8.jpg',
            		'2' => '/public/upload/goods/2018/08-21/3665d8a6ace9cf92b131f35dc0592b68.png',
            		'3' => '',
    			),
    			'goods_type'=>1,
    			'item_img'=>array(
					'1' => '',
            		'2' => '',
            		'3' => '',
            		'4' => '',
            		'5' => '',
            		'6' => '',
            		'7' => '',
            		'18' => '',
            		'19' => '',
            		'51' => '',
            		'52' => '',
            		'53' => '',
    			),
    			'item'=>array(
    				'1_6'=>array(
    					'price' => 123,
                    	'market_price' => 222,
                    	'cost_price' => 11,
                    	'commission' => 0,
                    	'store_count' => 1,
                    	'key_name' => '颜色:蓝色 尺码:M',
    				),
    				'2_6'=>array(
    					'price' => 123,
                    	'market_price' => 222,
                    	'cost_price' => 11,
                    	'commission' => 0,
                    	'store_count' => 1,
                    	'key_name' => '颜色:紫色 尺码:M',
    				),
    				'1_7'=>array(
    					'price' => 123,
                    	'market_price' => 222,
                    	'cost_price' => 11,
                    	'commission' => 0,
                    	'store_count' => 1,
                    	'key_name' => '颜色:蓝色 尺码:L',
    				),
    				'2_7'=>array(
    					'price' => 123,
                    	'market_price' => 222,
                    	'cost_price' => 11,
                    	'commission' => 0,
                    	'store_count' => 1,
                    	'key_name' => '颜色:紫色 尺码:L',
    				)
    			),
    			'attr_1'=>array(
    				'0' => '中国制造11',
    			),
    			'attr_2'=>array(
    				'0' => '一年12221',
    			),

				'attr_3'=>array(
    				'0' => 'sad',
    			),
    			'attr_4'=>array(
    				'0' => 'x',
    			),
    		);
        // echo $params['goods_name'];exit;
        $spec_item = !empty($params['item'])?$params['item']:'';
		//通用信息-----------------------------
		$data['goods_name'] 	= !empty($params['goods_name']) ? trim($params['goods_name']) :'';
		$data['goods_brief'] 	= !empty($params['goods_brief']) ? trim($params['goods_brief']) :'';
	    if(empty($params['goods_sn'])){
	        $max_id     = (Db::name('goods')->max('goods_id'))+1;
	        $data['goods_sn']   = 'gb' . str_repeat('0', 7 - strlen($max_id)) . $max_id;//为某商品生成唯一的货号
    	}else{
        	$data['goods_sn']   = $params['goods_sn'];
    	}
		$data['shop_price']  	= !empty($params['shop_price']) ? $params['shop_price']:0;
		$data['market_price'] 	= !empty($params['market_price']) ? $params['market_price']:0;
		$data['cost_price'] 	= !empty($params['cost_price'])  ? $params['cost_price']: 0;
		$data['commission'] 	= !empty($params['commission'])  ? $params['commission']: 0;
		// $data['goods_img'] 		= upload(request()->file('image'),'goods');
		$data['goods_weight'] 	= !empty($params['goods_weight'])  ? trim($params['goods_weight']): '';
		$data['goods_number'] 	= !empty($params['goods_number'])  ? intval($params['goods_number']): 0;
		$data['is_free_shipping'] 	= !empty($params['is_free_shipping'])  ? intval($params['is_free_shipping']): 1;
		$data['keywords'] 		= !empty($params['keywords'])  ? trim($params['keywords']): '';
		$data['goods_desc'] 	= !empty($params['goods_desc'])  ? $params['goods_desc']: '';
		if(!empty($params['cat_id_3'])){ 
			$data['cat_id'] = $params['cat_id_3'];
		}elseif(!empty($params['cat_id_2'])){
			$data['cat_id'] = $params['cat_id_2'];
		}else{
			$data['cat_id'] = !empty($params['cat_id']) ? intval($params['cat_id']):0;
		}
		$data['goods_type']  	= !empty($params['goods_type']) ? intval($params['goods_type']):0;
		if($params['goods_id']>0){
			$goods_id = $params['goods_id'];
			$r = Db::name('goods')->where("goods_name='$data[goods_name]' and goods_id !=$params[goods_id]")->count();
			if($r>0){
				return json(['error'=>-1,'msg'=>'商品名称重复']);
			}
			$result =Db::name('goods')->where('goods_id='.$params['goods_id'])->update($data);
		}else{
			$r = Db::name('goods')->where("goods_name='$data[goods_name]'")->count();
			if($r>0){
				return json(['error'=>-1,'msg'=>'商品名称重复']);
			}
			$res =Db::name('goods')->insert($data);
			$goods_id = Db::name('goods')->getLastInsID();
		}
      	$this->model = model('goods');
        $res = $this->model->afterSave($goods_id,$params);// 处理商品规格 图片
        $this->model->saveGoodsAttr($goods_id, $data['goods_type'],$params); // 处理商品 属性
        $return_arr = ['status' => 0, 'msg' => '操作成功'];
        return json($return_arr);
    }
    /**
     * 删除商品
     */
    public function delGoods(){
        $ids = $this->request->param('ids');
        if(empty($ids)){ 
         return json(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);
    	}
        $goods_ids = rtrim($ids,",");
        // 判断此商品是否有订单
        $ordergoods_count = Db::name('OrderGoods')->whereIn('goods_id',$goods_ids)->group('goods_id')->value('goods_id');
        if($ordergoods_count){
            $goods_count_ids = implode(',',$ordergoods_count);
            return json(['status' => -1,'msg' =>"ID为【{$goods_count_ids}】的商品有订单,不得删除!",'data'  =>'']);
        }     
        //删除用户收藏商品记录
        //Db::name('GoodsCollect')->whereIn('goods_id',$goods_ids)->delete();
        
        // 删除此商品        
        Db::name("Goods")->whereIn('goods_id',$goods_ids)->delete();  //商品表
        // Db::name("cart")->whereIn('goods_id',$goods_ids)->delete();  // 购物车
        Db::name("goods_gallery")->whereIn('goods_id',$goods_ids)->delete();  //商品相册
        Db::name("spec_goods_price")->whereIn('goods_id',$goods_ids)->delete();  //商品规格
        Db::name("spec_image")->whereIn('goods_id',$goods_ids)->delete();  //商品规格图片
        Db::name("goods_attr")->whereIn('goods_id',$goods_ids)->delete();  //商品属性
        // Db::name("goods_collect")->whereIn('goods_id',$goods_ids)->delete();  //商品收藏
        return json(['status' => 0,'msg' => '操作成功','url'=>$this->redirect("Admin/goods/goods_list")]);
    }

	/**
	* 商品模型列表
	*/
	public function goodsType(){
		$res = Db::name('goods_type')->field('cat_id,cat_name')->paginate(10)->toarray();
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch('goodsType');
	}
	//增加商品模型
	public function goodsType_add(){
		$params = $this->request->param();
		if(!empty($params['cat_id'])){
			$data['cat_name'] = $params['cat_name'];
			$res = Db::name('goods_type')->where('cat_id='.$params['cat_id'])->update($data);
			if($res){
			$result['error'] = 0;
			$result['msg']   = '编辑模型成功';
			}else{
				$result['error'] = 966;
				$result['msg']   = '编辑模型失败';
			}
			return json($result);
		}
		$data['cat_name'] = $params['cat_name'];
		$res = Db::name('goods_type')->insert($data);
		if($res){
			$result['error'] = 0;
			$result['msg']   = '添加模型成功';
		}else{
			$result['error'] = -1;
			$result['msg']   = '添加模型失败';
			
		}
		return json($result);
		
	}

	/**
	* 编辑商品模型列表
	*/
	public function edit_goodsType(){
		$cat_id = $this->request->param('cat_id');
		$goods_type_info = Db::name('goods_type')->field('cat_id,cat_name')->where('cat_id='.$cat_id)->select();
		$this->assign('goods_type_info',$goods_type_info);
		return $this->view->fetch('goodsType_edit');
	}

    /**
     * 删除商品模型 
     */
    public function delGoodsType(){
        // 判断 商品规格
        $cat_id = $this->request->param('cat_id');
        $count = Db::name("Spec")->where("cat_id =".$cat_id)->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!','Admin/Goods/goodsType');
        // 判断 商品属性        
        $count = Db::name("GoodsAttribute")->where("type_id =".$cat_id)->count("1");
        $count > 0 && $this->error('该类型下有商品属性不得删除!','Admin/Goods/goodsType');        
        // 删除分类
        Db::name('GoodsType')->where("cat_id =".$cat_id)->delete();
        $this->success("操作成功!!!",'Admin/Goods/goodsType');
    }  

	//商品属性列表
	public function goods_attribute(){
		$res = Db::name('goods_attribute')->field('attr_id,cat_id,attr_name,attr_values')->paginate(10)->toarray();
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch('goods_attribute');
	}

	/**
	* 编辑商品属性列表
	*/
	public function edit_goods_attribute(){
		$attr_id = $this->request->param('attr_id');
		$goods_attribute_info = Db::name('goods_attribute')->field('attr_id,attr_name,attr_input_type,attr_values')->where('attr_id='.$attr_id)->select();
		$this->assign('goods_attribute_info',$goods_attribute_info);
		return $this->view->fetch('goodsType_edit');
	}

	//增加编辑商品属性
	public function goods_attribute_add(){
		$params = $this->request->param();
		$data['attr_name'] 			= $params['attr_name'];
		$data['cat_id']    			= $params['cat_id'];
		$data['attr_input_type']    = $params['attr_input_type'];
		$data['attr_type']    		= $params['attr_type'] ? $params['attr_type']:0;
		$data['attr_index']    		= $params['attr_index'];
		$data['attr_values']    	= $params['attr_values'];
		$data['sort_order']    		= $params['sort_order'];
		if(!empty($params['attr_id'])){
			$res = Db::name('attribute')->where('attr_id='.$params['attr_id'])->update($data);
			if($res){
				$result['error'] = 0;
				$result['msg']   = '编辑属性成功';
			}else{
				$result['error'] = 966;
				$result['msg']   = '编辑属性失败';
			}
			return json($result);
		}
		$res = Db::name('attribute')->insert($data);
		if($res){
			$result['error'] = 0;
			$result['msg']   = '添加属性成功';
		}else{
			$result['error'] = 966;
			$result['msg']   = '添加属性失败';
		}
		return json($result);
	}
    /**
     * 删除商品属性
     */
    public function delGoodsAttribute(){
        $ids = $this->request->param('ids');
        if(empty($ids)){
        	return json(['status' => -1,'msg' =>"非法操作！"]);
        } 
        $attrBute_ids = rtrim($ids,",");
        // 判断 有无商品使用该属性
        $count_ids = Db::name("GoodsAttr")->field('attr_id')->whereIn('attr_id',$attrBute_ids)->group('attr_id')->select();
        if($count_ids){
            $count_ids = implode(',',$count_ids);
            return json(['status' => -1,'msg' => "ID为【{$count_ids}】的属性有商品正在使用,不得删除!"]);
        }
        // 删除 属性
        Db::name('GoodsAttribute')->whereIn('attr_id',$attrBute_ids)->delete();
        return json(['status' => 1,'msg' => "操作成功!",'url'=>'Admin/Goods/goods_attribute']);
    } 

	//商品分类列表
	public function goods_category(){
		$res = Db::name('goods_category')->field('cat_id,cat_name,cat_desc,parent_id')->select();
		$r   =  getTree($res);
		echo "<pre>";
		print_r($r);exit;
		// foreach($r as $value){
  //      		echo str_repeat('--', $value['level']), $value['name'].'<br />';
  //   	}
		return $this->view->fetch('goods_category');
	}

    //编辑商品分类
    public function edit_goods_category(){
    	$cat_id = $this->request->param('cat_id');
    	$goods_category_info = Db::name('goods_category')->field('cat_name,parent_id,cat_desc,image,cat_group,sort_order,commission_rate')->where('cat_id='.$cat_id)->select();
    	$this->assign('goods_category_info',$goods_category_info);
    	return $this->view->fetch('goods_category_edit');
    }
	//增加商品分类
	public function goods_category_add(){
		$params = $this->request->param();
		$data['cat_name'] 			= !empty($params['cat_name']) ? trim($params['cat_name']): '';
		$data['cat_id']    			= !empty($params['cat_id']) ? intval($params['cat_id'])  : 0;
		$data['parent_id']          = !empty($params['parent_id'])?intval($params['parent_id']): 0;
		$data['cat_desc']    		= !empty($params['cat_desc'])?$params['cat_desc']:'';
		$data['sort_order']    		= !empty($params['sort_order']) ? intval($params['sort_order'])  : 0;
		$data['image']    			= upload(request()->file('image'),'category');
		$data['is_hot']    			= !empty($params['is_hot'])?intval($params['is_hot']):0;
		$data['is_show']    		= !empty($params['is_show'])?$params['is_show']:0;
		$data['cat_group']    		= !empty($params['cat_group'])?$params['cat_group']:'';
		$data['commission_rate']    = !empty($params['commission_rate'])?$params['commission_rate']:'';
		if($params['cat_id']){
			$res = Db::name('goods_category')->where('cat_id='.$params['cat_id'])->update($data);
			if($res){
				$result['error'] = 0;
				$result['msg']   = '编辑分类成功';
			}else{
				$result['error'] = 966;
				$result['msg']   = '编辑分类失败';
			}
			return json($result);
		}

		$res = Db::name('goods_category')->insert($data);
		if($res){
			$result['error'] = 0;
			$result['msg']   = '添加分类成功';
		}else{
			$result['error'] = 966;
			$result['msg']   = '添加分类失败';
		}
		return json($result);
	}

    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        $ids = $this->request->param('ids');
         if(empty($ids)){
        	return json(['status' => -1,'msg' =>"非法操作！"]);
        } 
        // 判断子分类
        $count = Db::name("goods_category")->where("parent_id = {$ids}")->count("id");
        if($count > 0){
        	return json(['status' => -1,'msg' =>'该分类下还有分类不得删除!']);
        } 
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$ids}")->count('1');
        if($goods_count > 0){
        	return json(['status' => -1,'msg' =>'该分类下有商品不得删除!']);
        } 
        // 删除分类
        DB::name('goods_category')->where('id',$ids)->delete();
        return json(['status' => 1,'msg' =>'操作成功','url'=>'Admin/Goods/categoryList']);
    }
    

	//商品规格列表
	public function goods_spec(){
		$res = Db::name('spec')->field('id,cat_id,name')->paginate(10)->toarray();		
		foreach($res['data'] as $k => $v){
		    // 获取规格项     
        	$arr = Db::name('SpecItem')->where("spec_id =".$v['id'])->select(); 
        	$arr = get_id_val($arr, 'id','item');  
            $res['data'][$k]['spec_item'] = implode(',', $arr);
        }
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch('spec');
	}
    //编辑规格
    public function edit_goods_spec(){
    	$cat_id = $this->request->param('cat_id');
    	$goods_spec_info = Db::name('spec')->field('id,cat_id,name,order')->where('cat_id='.$cat_id)->select();
		foreach($goods_spec_info as $k => $v){
		    // 获取规格项     
        	$arr = Db::name('SpecItem')->where("spec_id =".$v['id'])->select(); 
        	$arr = get_id_val($arr, 'id','item');  
            $goods_spec_info[$k]['spec_item'] = implode(',', $arr);
        }
    	$this->assign('goods_spec_info',$goods_spec_info);
    	return $this->view->fetch('goods_spec_edit');
    }

	//添加编辑商品规格
	public function goods_spec_add(){
		$params = $this->request->param();
		$data['name'] 			= !empty($params['name']) ? trim($params['name']):'';
		$data['cat_id']    		= !empty($params['cat_id'])?intval($params['cat_id']):0;
		$items     				= !empty($params['items']) ?$params['items']:'';
		$data['order']     		= !empty($params['order']) ?intval($params['order']):0;
		if(!empty($params['id'])){
			$n = Db::name('spec')->where("id!=$params[id] and cat_id=$data[cat_id] and name='$data[name]'")->count('id');
			if($n>0){
				return json(['error'=>-1,'msg'=>'该规格已存在']);
			}
			$res = Db::name('spec')->where('id='.$params['id'])->update($data);
			if($res!==false){
				$this->spec_afterSave($params['id'],$items);
				$result['error'] = 0;
				$result['msg']   = '编辑规格成功';
			}else{
				$result['error'] = -1;
				$result['msg']   = '编辑规格失败';
			}
			return json($result);
		}
		$n = Db::name('spec')->where("cat_id=$data[cat_id] and name='$data[name]'")->count('id');
		if($n>0){
			return json(['error'=>-1,'msg'=>'该规格已存在']);
		}
		$res = Db::name('spec')->insert($data);
		if($res){
			$insert_id = Db::name('spec')->getLastInsID();
			$this->spec_afterSave($insert_id,$items);
			$result['error'] = 0;
			$result['msg']   = '添加规格成功';
		}else{
			$result['error'] = 966;
			$result['msg']   = '添加规格失败';
		}
		return json($result);
	}
	/**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $id 规格id
     */
    public function spec_afterSave($id,$items){
        $post_items = explode(PHP_EOL, $items);
        foreach ($post_items as $key => $val)  // 去除空格
        {
            $val = str_replace('_', '', $val); // 替换特殊字符
            $val = str_replace('@', '', $val); // 替换特殊字符
            
            $val = trim($val);
            if(empty($val)) 
                unset($post_items[$key]);
            else                     
                $post_items[$key] = $val;
        }
        $db_items = Db::name('spec_item')->where("spec_id = $id")->column('id,item');
        // 两边 比较两次
        /* 提交过来的 跟数据库中比较 不存在 插入*/
        $dataList = array();
        foreach($post_items as $key => $val){
            if(!in_array($val, $db_items))            
                $dataList[] = array('spec_id'=>$id,'item'=>$val);            
        }
        // 批量添加数据
        if($dataList){
        	Db::name('spec_item')->insertAll($dataList);
        } 
        /* 数据库中的 跟提交过来的比较 不存在删除*/
        foreach($db_items as $key => $val){
            if(!in_array($val, $post_items)){       
                Db::name("SpecGoodsPrice")->where("`key` REGEXP '^{$key}_' OR `key` REGEXP '_{$key}_' OR `key` REGEXP '_{$key}$' or `key` = '{$key}'")->delete(); // 删除规格项价格表
                Db::name("SpecItem")->where('id='.$key)->delete(); // 删除规格项
            }
        }        
    }    

    /**
     * 删除商品规格
     */
    public function delGoodsSpec(){
        $ids = $this->request->param('ids');
       	if(empty($ids)){
        	return json(['status' => -1,'msg' =>"非法操作！"]);
        } 
        $aspec_ids = rtrim($ids,",");
        // 判断 商品规格项
        $count_ids = Db::name("SpecItem")->field('spec_id')->whereIn('spec_id',$aspec_ids)->group('spec_id')->select();
        if($count_ids){
            $count_ids = implode(',',$count_ids);
            return json(['status' => -1,'msg' => "ID为【{$count_ids}】规格，清空规格项后才可以删除!"]);
        }
        // 删除分类
        Db::name('Spec')->whereIn('id',$aspec_ids)->delete();
        Db::name('SpecItem')->whereIn('spec_id',$aspec_ids)->delete();
        return json(['status' => 1,'msg' => "操作成功!!!",'url'=>'Admin/Goods/goods_spec']);
    } 

	//根据模型id获取商品属性和规格
	public function getGoodsAttr(){
		$params = $this->request->param();
		$cat_id =$params['cat_id'];
		$data = array();
		//获取属性
		$data['attr'] =  Db::name('goods_attribute')->field('attr_id,attr_name,attr_values,attr_type,attr_input_type')->where('cat_id='.$cat_id)->select();
		$data['spec'] =  Db::name('spec')->field('id,name')->where('cat_id='.$cat_id)->select();
		foreach($data['spec'] as $k => $v){
		    // 获取规格项     
        	$arr = Db::name('SpecItem')->where("spec_id =".$v['id'])->select(); 
        	$arr = get_id_val($arr, 'id','item');  
            $data['spec'][$k]['spec_item'] = $arr;
        }
        // echo "<pre>";
        // print_r($data);exit;
		return json($data);
	}
}

?>