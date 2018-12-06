<?php

namespace app\admin\controller;
use app\common\controller\Backend;
use think\Db;
use think\Session;
class Article extends Backend{
	public function _initialize(){
		parent::_initialize();
	    $this->model = model('app\admin\model\Article');
	}
	/**
	* 文章列表
	*/
	public function index(){
		$params = $this->request->param();
		$where = "1=1";
		if(!empty($params['keyword'])){
			$where.=" and title like '%$params[keyword]%' ";
		}
		$res = Db::name('article')->field('article_id,title,author,click,add_time')->where($where)->paginate(10)->toarray();
		if($res){
			foreach ($res['data'] as $key => $value) {
				$res['data'][$key]['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
			}
		}
		echo "<pre>";
		print_r($res);exit;
		$result = array("total" => $res['total'], "rows" => $res);
		return json($result);
		return $this->view->fetch();
	}
	
	public function edit_article(){
		$article_id = $this->request->param('article_id');
		if(!$article_id){
			return json(['status' => -1,'msg' =>"非法操作！"]);
		}
		$data['article_info'] 	= Db::name('article')->field('article_id,title,author,click,cat_id')->where('article_id='.$article_id)->select();
		$data['article_cat'] 	= Db::name('article_cat')->field('cat_id,cat_name,parent_id')->select();
		$data['article_cat'] 	= getTree($data['article_cat']);
		echo "<pre>";
		print_r($data);exit;
		return $this->view->fetch('edit_article');
	}
	/**
	* 添加/编辑文章
	*/
	public function save_article(){
		// $params = $this->request->param();
		$params = array(
			'article_id'=>35,
			'cat_id'=>1,
			'title'=>'66dddddddd',
			'content'=>'dasdasdsadasdsadasd',
			'author'=>'zz',
			'keywords'=>'wqewq',
			'is_open'=>1,
			'click'=>100,
			'description'=>'1kkkkkkkkkkkk',
		);
		$data['cat_id'] 	= !empty($params['cat_id']) ? intval($params['cat_id']):0 ;
		$data['title'] 		= !empty($params['title']) ? trim($params['title']):'';
		$data['content'] 	= !empty($params['content']) ? $params['content']:'';
		$data['author'] 	= !empty($params['author']) ?  trim($params['author']):'';
		$data['keywords'] 	= !empty($params['keywords']) ?  trim($params['keywords']):'';
		$data['description']= !empty($params['description']) ?  $params['description']:'';
		$data['is_open'] 	= !empty($params['is_open']) ?  trim($params['is_open']):0;
		$data['add_time'] 	= time();
		$data['click'] 		= !empty($params['click']) ?  intval($params['click']):100;
		$data['thumb'] 		= !empty($params['thumb']) ?  intval($params['thumb']):'';
		if(!empty($params['article_id'])){
			$res = Db::name('article')->where('article_id='.$params['article_id'])->update($data);
		}else{
			$res = Db::name('article')->insert($data); 
		}
		if($res!==false){
			$result['error'] = 0;
			$result['msg'] = '添加成功';
		}else{
			$result['error'] = -1;
			$result['msg'] = '添加失败';
		}
		return json($result);
	}

	/**
	* 删除文章
	*/
	public function del_article(){
		$ids = $this->request->param('article_id');
		if(!$ids){
			return json(['status' => -1,'msg' =>"非法操作！"]);
		}
		// 删除文章
        $res = DB::name('article')->where('article_id',$ids)->delete();
        if($res){
        	return json(['error' => 0,'msg' =>'操作成功','url'=>'Admin/Article/article/index']);
        }else{
        	return json(['error' =>-1,'msg' =>'操作失败','url'=>'Admin/Article/article/index']);
        }
        

	}
	/**
	* 文章分类列表
	*/
	public function article_type(){
		$res = Db::name('article_cat')->field('cat_id,cat_name,parent_id')->select();
		$res = getTree($res);
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch();
	}

	public function edit_article_type(){
		$cat_id = $this->request->param('cat_id');
		if(!$cat_id){
			return json(['status' => -1,'msg' =>"非法操作！"]);
		}
		$data['article_cat_info'] = Db::name('article_cat')->field('cat_id,cat_name,parent_id,sort_order')->where('cat_id='.$cat_id)->select();
		$data['article_cat'] = Db::name('article_cat')->field('cat_id,cat_name,parent_id,sort_order')->select();
		$data['article_cat'] = getTree($data['article_cat']);
		echo "<pre>";
		print_r($data);exit;
		return $this->view->fetch('edit_article');
	}

	/**
	* 添加/编辑文章分类
	*/
	public function save_article_type(){
		//$params = $this->request->param();
		$params = array(
			'cat_id' => 15,
			'cat_name'=>'新手上路',
			'parent_id'=>0,
			'shou_in_nav'=>0,
			'sort_order'=>'zz',
			'cat_desc'=>'wqewq',
			'is_open'=>0,
			'keywords'=>'dasdasdasd',
			
		);
		$data['cat_name']     	= !empty($params['cat_name']) ? trim($params['cat_name']):'';
		$data['parent_id']    	= !empty($params['parent_id']) ? intval($params['parent_id']):0;
		$data['show_in_nav']  	= !empty($params['show_in_nav']) ? intval($params['show_in_nav']):0;
		$data['sort_order']  	= !empty($params['sort_order']) ? intval($params['sort_order']):0;
		$data['cat_desc']  		= !empty($params['cat_desc']) ? trim($params['cat_desc']):'';
		$data['keywords']  		= !empty($params['keywords']) ? trim($params['keywords']):'';
		if(!empty($params['cat_id'])){
			$res = Db::name('article_cat')->where('cat_id='.$params['cat_id'])->update($data);
		}else{
			$res = Db::name('article_cat')->insert($data);
		}
		if($res!==false){
			$result['error'] = 0;
			$result['msg'] = '添加成功';
		}else{
			$result['error'] = -1;
			$result['msg'] = '添加失败';
		}
		return json($result);
	}
	/**
	* 删除文章分类
	*/
    public function del_article_type(){
        $ids = $this->request->param('ids');
         if(empty($ids)){
        	return json(['status' => -1,'msg' =>"非法操作！"]);
        } 
        // 判断子分类
        $count = Db::name("article_cat")->where("parent_id = {$ids}")->count("id");
        if($count > 0){
        	return json(['status' => -1,'msg' =>'该分类下还有分类不得删除!']);
        } 
        // 判断是否存在文章
        $article_count = Db::name('article')->where("cat_id = {$ids}")->count('1');
        if($goods_count > 0){
        	return json(['status' => -1,'msg' =>'该分类下有文章不得删除!']);
        } 
        // 删除分类
        DB::name('article_cat')->where('id',$ids)->delete();
        return json(['status' => 1,'msg' =>'操作成功','url'=>'Admin/Article/article_type']);
    }









}


?>