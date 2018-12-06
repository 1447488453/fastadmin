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
class Audit extends Backend
{

    protected $relationSearch = true;
    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = model('AuditName');
    }
    /**
     * 实名审核列表
     */
    public function index(){
        if($this->request->isAjax()){
            $params = $this->request->param();

            //获取搜索参数-----------
            $this->request->filter(['strip_tags']);
            $filter = $this->request->get("filter", '');
            $filter = (array)json_decode($filter, TRUE);
            $filter = $filter ? $filter : [];
            $where ="status!=4";
            if(isset($filter['real_name'])){//按照用户名搜索
                $where.=" AND real_name like'%$filter[real_name]%'";
            }
            if(isset($filter['id_card'])){//按照昵称搜索
                $where.=" AND id_card like'%$filter[id_card]%'";
            }
            if(isset($filter['status'])){//按照状态搜索
                $where.=" AND status like'%$filter[status]%'";
            }
            if(!empty($params['search'])){
                $where.=" AND real_name like'%$params[search]%' or id_card like '%$params[search]%' ";
            }
            $res = Db::name('name_audit')->field('id,real_name,id_card,sfz_front_img,sfz_back_img,add_time,examine_time,status,remark')->where($where)->paginate(10)->toarray();
          
            $result = array("total" => $res['total'], "rows" => $res['data']);
            return json($result);
        }
        return  $this->view->fetch();
    }
    /**
     * 实名审核详情信息
     */
    public function detail_audit(){
        $id = $this->request->param('ids');
        if(empty($id)){ 
            return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        $res = Db::name('name_audit')->field('id,mobile,real_name,id_card,sfz_front_img,sfz_back_img,add_time,examine_time,status,remark')->where('id='.$id)->find();
        // echo "<pre>";
        // print_r($res);
        $this->assign('row',$res);
        return $this->view->fetch('detail_audit');
    }
    /**
     * 实名审核状态
     */
    public function audit_status(){
        $ids = $this->request->param('ids');
        $remark = $this->request->param('remark');
        if(empty($ids)){ 
            return json(['error' => -1,'msg' =>"非法操作！",'data'  =>'']);
        }
        // $data['status'] = $this->request->param('status');
        $data['remark'] = $remark;
        // echo "<pre>";
        // print_r( $this->request->param());exit;
        $res = Db::name('name_audit')->whereIn('id',$ids)->update($data);
        if($res!==false){
            // $this->success();
            return json(['error' =>0,'msg' =>"操作成功！"]);
        }else{
            return json(['error' =>-1,'msg' =>"操作失败！"]);
        }

    }

    /**
     * 实名审核删除
     */
    public function del_audit(){
        $ids = $this->request->param('ids');
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



}
