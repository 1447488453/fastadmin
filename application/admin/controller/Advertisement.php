<?php
namespace app\admin\controller;
use app\common\controller\Backend;
use think\Db;
use think\Cache\driver\Redis;
class Advertisement extends Backend{

	public function _initialize(){
		parent::_initialize();
	    $this->model = model('app\admin\model\Advertisement');
	}
   	/**
     *广告列表Advertisement
     */
	public function index(){
		$params = $this->request->param();
		$where = "a.enabled =1";
		if(!empty($params['pid'])){
			$where.=" AND pid = $params[pid] ";
		}
		if(!empty($params['keywords'])){
			$where.= " AND ad_name like '%$params[keywords]%'";
		}
		$res = DB::name('ad')->alias('a')->join('ad_position ap','a.pid=ap.position_id','LEFT')->field('a.ad_id,a.pid,a.ad_name,a.start_time,a.end_time,a.ad_link,a.enabled,a.orderby,ap.position_name')->where($where)->paginate(10)->toarray();
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch();
	}
   	/**
     * 编辑广告页
     */
	public function edit_ad(){
		$ad_id = $this->request->param('ad_id');
		if(!$ad_id){
			return json(['error'=>403,'msg'=>'非法操作']);
		}
		$res['data'] = Db::name('ad')->field('ad_id,pid,ad_name,start_time,end_time,ad_link,enabled,orderby')->where('ad_id='.$ad_id)->find();
		$res['position'] = Db::name('ad_position')->field('position_id,position_name')->where('is_open=1')->select();
		echo "<pre>";
		print_r($res);exit;
		return $this->view->fetch();
	}
   	/**
     * 添加/编辑 广告
     */
	public function save_ads(){
		$redis = new Redis();
		$params = $this->request->param();
		$data['ad_name'] 	= !empty($params['ad_name']) 	? trim($params['ad_name']):'';
		$data['media_type'] = !empty($params['media_type']) ? intval($params['media_type']):0;
		$data['ad_link'] 	= !empty($params['ad_link']) 	? trim($params['ad_link']):'';
		$data['pid'] 		= !empty($params['parent_id']) 	? intval($params['parent_id']):0;
		$data['start_time'] = !empty($params['start_time']) ? intval($params['start_time']):0;
		$data['end_time'] 	= !empty($params['end_time']) 	? intval($params['end_time']):0;
		$data['ad_code'] 	= !empty($params['ad_code']) 	? trim($params['ad_code']):'';	
		$data['orderby'] 	= !empty($params['orderby']) 	? intval($params['orderby']):0;
		if(!empty($params['ad_id'])){
			$res = Db::name('ad')->update($data);
		}else{
			$res = Db::name('ad')->insert($data);
		}
		if($res){
			if($data['pid']==1){
				$redis->HDEL('hash','banner_1');
			}
			return json(['error'=>0,'msg'=>'操作成功']);
		}else{
			return json(['error'=>402,'msg'=>'操作失败']);
		}	
	}
	/**
     * 删除广告
     */
	public function del_ads(){
		$ids = $this->request->param('ad_id');
		if(!$ids){
			return json(['error'=>403,'msg'=>'非法操作']);
		}
		$res = Db::name('ad')->whereIn('ad_id',$ids)->delete();
		if($res){
			return json(['error'=>0,'msg'=>'删除成功']);
		}else{
			return json(['error'=>405,'msg'=>'删除失败']);
		}
	}
	/**
     * 广告位置列表
     */
	public function ad_position(){
		$res = Db::name('ad_position')->field('position_id,position_name,ad_width,ad_height,is_open,position_desc')->paginate(10)->toarray();
		return $this->view->fetch();
	}
	/**
     * 广告位置编辑页
     */
	public function edit_position(){
		$position_id = $this->request->param('position_id');
		if(!$position_id){
			return json(['error'=>403,'msg'=>'非法操作']);
		}
		$res = Db::name('ad_position')->field('position_id,position_name,ad_width,ad_height,is_open,position_desc')->where('position_id='.$position_id)->find();
		return $this->view->fetch();
	}

	/**
     * 增加/编辑 广告位置
     */
	public function save_position(){
		$params = $this->request->param();
		$data['position_name'] 	= !empty($params['position_name'])	? 	trim($params['position_name']):'';
		$data['ad_width'] 		= !empty($params['ad_width'])  		? 	intval($params['ad_width']):0;
		$data['ad_height'] 		= !empty($params['ad_height']) 		?	intval($params['ad_height']):0;
		$data['position_desc'] 	= !empty($params['position_desc'])	?	trim($params['position_desc']):'';
		$data['is_open'] 		= !empty($params['is_open']) 		? 	intval($params['is_open']):0;
		if(!empty($params['position_id'])){
			$res = Db::name('ad_position')->where('position_id='.$params['position_id'])->update($data);
		}else{
			$res = Db::name('ad_position')->where('position_id='.$params['position_id'])->insert($data);
		}
		if($res){
			return json(['error'=>0,'msg'=>'操作成功']);
		}else{
			return json(['error'=>402,'msg'=>'操作失败']);
		}
	}

	/**
     * 删除广告位置
     */
	public function del_position(){
		$ids = $this->request->param('position_id');
		if(!$ids){
			return json(['error'=>403,'msg'=>'非法操作']);
		}
		if(Db::name('ad')->where('pid',$ids)->count()>0){
			return json(['error'=>406,'msg'=>'此广告位下还有广告，请先清除']);
		}else{
			$res = Db::name('ad_position')->whereIn('position_id',$ids)->delete();
		}

		if($res){
			return json(['error'=>0,'msg'=>'删除成功']);
		}else{
			return json(['error'=>405,'msg'=>'删除失败']);
		}
	}



}


?>