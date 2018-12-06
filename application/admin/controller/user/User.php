<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
use think\Paginator;
/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL){
        $row = $this->model->get($ids);
        if (!$row)$this->error(__('No Results were found'));
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 会员列表
     */
    public function index(){
            if($this->request->isAjax()){
            $params = $this->request->param();
            //获取搜索参数-----------
            $this->request->filter(['strip_tags']);
            $filter = $this->request->get("filter", '');
            $filter = (array)json_decode($filter, TRUE);
            $filter = $filter ? $filter : [];
            $where = "status='normal'";
            $order = "$params[sort] $params[order]";
            if(isset($filter['username'])){//按照用户名搜索
                $where.=" AND username like'%$filter[username]%'";
            }
            if(isset($filter['nickname'])){//按照昵称搜索
                $where.=" AND nickname like'%$filter[nickname]%'";
            }
            if(isset($filter['mobile'])){//按手机号搜索
                $where.=" AND mobile like'%$filter[mobile]%'";
            }
            if(!empty($params['search'])){
                $where.=" AND username like'%$params[search]%' or mobile like '%$params[search]%'  or nickname like '%$params[search]%' ";
            }

            $res = Db::name('user')->field('id,username,nickname,mobile,email,jointime,parent_id,status,level')->where($where)->order($order)->paginate(10)->toarray();
            if($res){
                foreach ($res['data'] as $key => $value){
                    $res['data'][$key]['level'] = get_level($value['level']);
                    $res['data'][$key]['status'] = $value['status']?"正常":"关闭";
                }
            }
            $result = array("total" => $res['total'], "rows" => $res['data']);
            return json($result);
        }
        return $this->view->fetch();
    }    
    /**
     * 获取下级会员
     */
    public function get_son_user(){
        $parent_id = $this->request->param('user_id');
        if($parent_id){
            $res = Db::name('user')->field('id,username,nickname,mobile,email,jointime,parent_id,status,level')->where('parent_id='.$parent_id)->select();
            $data['error'] = 0;
            $data['msg']  = "获取成功";
            $data['data']  = $res; 
            return json($data);
        }else{
            return json(['error' =>-1,'msg' =>"获取数据失败！",'data'=>'']);
        }
    }
    /**
     * 会员详细信息
     */
    public function detial_user(){
        $user_id    = $this->request->param('user_id');
        $user_info  = Db::name('user')->field('id,username,nickname,mobile,email,jointime,parent_id,status,level,bhe,bher')->where('id='.$user_id)->find();
        $this->assign('user_info',$user_info);
        return $this->view->fetch('detial_user'); 
    }
    /**
     * 会员bhe变化明细
     */
    public function user_bhe_log(){
        $user_id = $this->request->param('user_id');
        if($user_id){
            return json(['error' =>-1,'msg' =>"非法操作"]);
        }
        $res = Db::name('bhe_log')->field('id,user_id,num,bhe_balance,txid,rate_fee,add_time,add_reason')->where('user_id='.$user_id)->paginate(10)->toarray();
        if($res){
            $result['error']    = 0;
            $result['msg']      = "获取明细成功";
            $result['data']     = $res;
        }else{
            $result['error']    = -1;
            $result['msg']      = "获取明细失败";
            $result['data']     = '';
        }
            return json($result);
    }

    /**
     * 会员bher变化明细
     */
    public function user_bher_log(){
        $user_id = $this->request->param('user_id');
        if($user_id){
            return json(['error' =>-1,'msg' =>"非法操作"]);
        }
        $res = Db::name('bher_log')->field('id,user_id,num,bher_balance,add_time,add_reason')->where('user_id='.$user_id)->paginate(10)->toarray();
        if($res){
            $result['error']    = 0;
            $result['msg']      = "获取明细成功";
            $result['data']     = $res;
        }else{
            $result['error']    = -1;
            $result['msg']      = "获取明细失败";
            $result['data']     = '';
        }
            return json($result);
    }
    /**
     * 实名审核列表
     */
    public function real_name_audit(){
        if($this->request->isAjax()){
            $params = $this->request->param();
            $where ="1=1";
            $res = Db::name('name_audit')->field('id,real_name,id_card,sfz_front_img,sfz_back_img,add_time,examine_time,status')->where($where)->paginate(10)->toarray();
            // echo "<pre>";
            // print_r($res);exit;
            $result = array("total" => $res['total'], "rows" => $res['data']);
            return json($result);

        }

        return  $this->view->fetch('real_name_audit');
    }
    /**
     * 实名审核详情信息
     */
    public function detail_audit(){
        $id = $this->request->param('id');
        if(empty($id)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $res = Db::name('name_audit')->field('id,mobile,real_name,id_card,sfz_front_img,sfz_back_img,add_time,examine_time,status')->where('id='.$id)->find;
        echo "<pre>";
        print_r($res);
        return $this->view->fetch('detail_audit');
    }
    /**
     * 实名审核状态
     */
    public function audit_status(){
        $ids = $this->request->param('id');
        $remark = $this->request->param('remark');
        if(empty($ids)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $data['status'] = $this->request->param('status');
        $data['remark'] = $remark;
        $res = Db::name('name_audit')->whereIn('id',$ids)->update($data);
        if($res!==false){
            return json(['error' =>0,'msg' =>"操作成功！"]);
        }else{
            return json(['error' =>2,'msg' =>"操作失败！"]);
        }

    }

    /**
     * 实名审核删除
     */
    public function del_audit(){
        $ids = $this->request->param('id');
        if(empty($ids)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $data['status'] = 4;
        $res = Db::name('name_audit')->whereIn('id',$ids)->update($data);
        if($res!==false){
            return json(['error' =>0,'msg' =>"删除成功！"]);
        }else{
            return json(['error' =>2,'msg' =>"删除失败！"]);
        }

    }

    /**
     * 兑换审核列表
     */
    public function exchange_list(){
        $params = $this->request->param();
        $where ="1=1";
        $res = Db::name('exchange')->field('id,mobile,user_name,add_time,num,examine_time,txid,status')->where($where)->paginate(10)->toarray();
        echo "<pre>";
        print_r($res);exit;
       return  $this->view->fetch('exchange_list');
    }
    /**
     * 兑换审核详情信息
     */
    public function detail_exchange(){
        $id = $this->request->param('id');
        if(empty($id)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $res = Db::name('exchange')->field('id,mobile,user_name,num,wallet_url,txid,add_time,examine_time,status')->where('id='.$id)->find;
        echo "<pre>";
        print_r($res);
        return $this->view->fetch('detail_exchange');
    }

    /**
     * 兑换审核状态
     */
    public function exchange_status(){
        $ids = $this->request->param('id');
        $remark = $this->request->param('remark');
        if(empty($ids)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $data['status'] = $this->request->param('status');
        $data['remark'] = $remark ? $remark:'';
        $res = Db::name('exchange')->whereIn('id',$ids)->update($data);
        if($res!==false){
            return json(['error' =>0,'msg' =>"操作成功！"]);
        }else{
            return json(['error' =>2,'msg' =>"操作失败！"]);
        }
    }

    /**
     * 实名审核兑换删除
     */
    public function del_exchange(){
        $ids = $this->request->param('id');
        if(empty($ids)){ 
         return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $data['status'] = 4;
        $res = Db::name('exchange')->whereIn('id',$ids)->update($data);
        if($res!==false){
            return json(['error' =>0,'msg' =>"删除成功！"]);
        }else{
            return json(['error' =>2,'msg' =>"删除失败！"]);
        }
    }


    /**
     * 获取地址信息 省市区
     */
    public function get_address(){
        $parent_id = $this->request->param('id')?$this->request->param('id'):0;
        $res = Db::name('region')->field('id,name,level,parent_id')->where('parent_id='.$parent_id)->select();
        echo "<pre>";
        print_r($res);exit;
        if($res){
            return json(['error'=>0,'msg'=>"获取成功",'data'=>$res]);
        }else{
            return json(['error'=>-1,'msg'=>"获取失败",'data'=>'']);
        }

    }




}
