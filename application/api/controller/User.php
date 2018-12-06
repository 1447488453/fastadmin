<?php
namespace app\api\controller;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Db;
use think\Validate;
use think\Request;
use app\api\controller\Common;
/**
 * 会员接口
 */
class User extends Api{
    protected $noNeedLogin = ['login', 'register', 'resetpwd','reset_trader_password'];
    protected $noNeedSign  = ['login', 'register', 'resetpwd','reset_trader_password','get_address'];

    public function _initialize(){
        parent::_initialize();   
    }
    /**
     * 会员中心
     */
    public function index(){
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     * 
     * @param string $username 账号
     * @param string $password 密码
     */
    public function login(){
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        if (!$username || !$password){
            $this->error(__('Invalid parameters'));
        }
        $res = Db::name('user')->field('id,salt,password')->where("username ='$username'")->find();
        if($res){
            $pw = md5(md5($password).$res['salt']);
            if($pw==$res['password']){
                $data['token'] = md5(uniqid()).md5(uniqid());
                $data['createtime'] = time();
                $data['expiretime'] = $data['createtime']+(30*24*60*60);
                $data['user_id']    = $res['id'];
                $res = Db::name('user_token')->insert($data);
                return json(['error'=>0,'msg'=>'登入成功','token'=>$data['token'],'username'=>$username,'user_id'=>$data['user_id'],'expiretime'=>$data['expiretime'],'url'=>'user/index']);
            }else{
                return json(['error'=>-1,'msg'=>'密码错误']);
            }
        }else{
            return json(['error'=>-1,'msg'=>'用户名不存在']);
        } 
    }

    /**
     * 注册会员
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机号
     */
    public function register(){
        $username           = input('username')       ?input('username'):'';
        $password           = input('password')       ?input('password'):'';
        $repassword         = input('repassword')     ?input('repassword'):'';
        $referee_name       = input('referee_name')   ?input('referee_name'):'';
        $mobile             = input('mobile')         ?input('mobile'):'';
        $trader_password    = input('trader_password')?input('trader_password'):'';
        $salt = Random::alnum();
        $Validate = new \app\api\controller\Validate;
        if (!$username || !$password){
            $this->error(__('用户名或密码不能为空'));
        }
        if($password!==$repassword){
            $this->error(__('两次输入的密码不一致'));
        }
        if(!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9a-zA-Z]{8}$/',$password)){
             $this->error('密码要是8位以数字+字母的组合'); 
        }
        if(!preg_match('/\d{6}/',$trader_password)){
            $this->error('交易密码要是6位数字'); 
        }
        if($referee_name){//检验推荐人是否存在,推荐人是已消费会员
            $parent_id = Db::name('user')->where('username',$referee_name)->where('level<> 0')->value('id');
            if(!$parent_id){
               $this->error('推荐人不存在'); 
            }else{
                $parent_id = $parent_id;
            }
        }
        if ($username){//检测用户名是否已存在
            $res = $Validate->check_username_available($username);
        }
       
        if ($mobile){//检测手机号是否已存在
            $res = $Validate->check_mobile_available($mobile);
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")){
            $this->error(__('Mobile is incorrect'));
        }
        $data = array(
            'username'          =>$username,
            'salt'              => $salt,
            'password'          =>md5(md5($password).$salt),
            'mobile'            =>$mobile,
            'joinip'            => request()->ip(),
            'jointime'          =>time(),
            'parent_id'         =>$parent_id,
            'trader_password'   =>md5(md5($trader_password).$salt),
        );
        $res =Db::name('user')->insert($data);
        if($res){
            return json(['error'=>0,'msg'=>'注册成功']);
        }else{
            return json(['error'=>-1,'msg'=>'注册失败']);
        }
    }

    /**
     * 注销登录
     */
    public function logout(){
        $token = $this->request->param('token');
        $res = Db::name('user_token')->where('token = $token')->delete();
        $this->success(__('退出成功'));
    }

    /**
     * 重置登入密码
     * 
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function resetpwd(){
        $mobile = $this->request->param("mobile");
        $newpassword = $this->request->param("newpassword");
        $renewpassword = $this->request->param("renewpassword");
        $captcha = $this->request->param("captcha");
        if (!$newpassword || !$captcha){
            $this->error(__('密码或验证码不能为空'));
        }
        if($newpassword!==$renewpassword){
            $this->error(__('两次输入的密码不一致'));
        }
        if(!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9a-zA-Z]{8}$/',$newpassword)){
             $this->error('密码要是8位以数字+字母的组合'); 
        }
        if (!Validate::regex($mobile, "^1\d{10}$")){
            $this->error(__('手机号格式不正确'));
        }
        $user = Db::name('user')->where('mobile',$mobile)->find();
        if (!$user){
            $this->error(__('用户不存在'));
        }
        $res = Sms::check($mobile, $captcha, 'resetpwd');
        if (!$res){
            $this->error(__('验证码不正确'));
        }
        Sms::flush($mobile, 'resetpwd');
        $salt = Random::alnum();
        $password = md5(md5($password).$salt);
        $res = Db::name('user')->where("mobile = $mobile")->setField('password',$password);
        if ($res){
            $this->success(__('重置登入密码成功'));
        }
        else
        {
            $this->error(__('重置登入密码失败'));
        }
    }

    /**
     * 重置支付密码
     * 
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function reset_trader_password(){
        $mobile = $this->request->param("mobile");
        $newpassword = $this->request->param("newpassword");
        $renewpassword = $this->request->param("renewpassword");
        $captcha = $this->request->param("captcha");
        if (!$newpassword || !$captcha){
            $this->error(__('密码或验证码不能为空'));
        }
        if($newpassword!==$renewpassword){
            $this->error(__('两次输入的密码不一致'));
        }
        if(!preg_match('/\d{6}/',$newpassword)){
            $this->error('交易密码要是6位数字'); 
        }
        if (!Validate::regex($mobile, "^1\d{10}$")){
            $this->error(__('手机号格式不正确'));
        }
        $user = Db::name('user')->where('mobile',$mobile)->find();
        if (!$user){
            $this->error(__('用户不存在'));
        }
        $res = Sms::check($mobile, $captcha, 'resetpwd');
        if (!$res){
            $this->error(__('验证码不正确'));
        }
        Sms::flush($mobile, 'resetpwd');
        $salt = Random::alnum();
        $password = md5(md5($password).$salt);
        $res = Db::name('user')->where("mobile = $mobile")->setField('trader_password',$password);
        if ($res){
            $this->success(__('重置交易密码成功'));
        }
        else{
            $this->error(__('重置交易密码失败'));
        }
    }

    /**
     * 编辑个人信息
     * 
     * @param int $user_id 用户id
     */
    public function edit_user_info(){
        $token = $this->request->param('token');
        $Common = new Common();
        $user_id = $Common->getUserId($token);//获取登入用户的user_id
        $params         = $this->request->param();
        $user_id        = !empty($params['user_id'])?intval($params['user_id']):0;
        $nickname       = !empty($params['nickname'])?trim($params['nickname']):'';
        $avatar         = !empty($params['avatar']) ? $params['avatar']:'';
        $user_data['nickname']      = $nickname;
        $user_data['avatar']        = $avatar;
        try{
            $res = Db::name('user')->where('id='.$user_id)->update($user_data);
            if($res!==false){
                return json(['error'=>0,'msg'=>'编辑成功']);
            }    
        } catch (\Exception $e) {
            return json(['error'=>-1,'msg'=>'编辑失败']);
        }
    }
        /**
     * 获取地址 省市区省信息
     */
    public function get_address(){
        $parent_id = $this->request->param('id')?$this->request->param('id'):0;
        $res = Db::name('region')->field('id,name,level,parent_id')->where('parent_id='.$parent_id)->select();
        if($res){
            return json(['error'=>0,'msg'=>"获取成功",'data'=>$res]);
        }else{
            return json(['error'=>-1,'msg'=>"获取失败",'data'=>'']);
        }

    }

    /**
     * 个人实名信息
     */
    public function audit_info(){
        $token = $this->request->param('token');
        $Common = new Common();
        $user_id = $Common->getUserId($token);//获取登入用户的user_id

        $res = Db::name('name_audit')->field('id,user_id,real_name,id_card,sfz_front_img,sfz_back_img,status,add_time,remark,examine_time')->where('user_id',$user_id)->select();
        return json(['error'=>0,'msg'=>'获取成功','data'=>$res]);
    }
    /**
     * 提交实名审核
     */
    public function real_name_audit(){
        $params         =   $this->request->param();
        $token      = $params('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $id             =   !empty($params['id'])      ?intval($params['id']):'';
        // $user_id        =   !empty($params['user_id'])      ?intval($params['user_id']):'';
        $real_name      =   !empty($params['real_name'])    ?trim($params['real_name']):'';
        $id_card        =   !empty($params['id_card'])      ?trim($params['id_card']):'';
        $sfz_front_img  =   !empty($params['sfz_front_img'])?trim($params['sfz_front_img']):'';
        $sfz_back_img   =   !empty($params['sfz_back_img']) ?trim($params['sfz_back_img']):'';
        $add_time       =   time();
        $status         =   0;
        $data = array(
        'user_id'       =>$user_id,
        'real_name'     =>$real_name,
        'id_card'       =>$id_card,
        'sfz_front_img' =>$sfz_front_img,
        'sfz_back_img'  =>$sfz_back_img,
        'add_time'      =>$add_time,
        'status'        =>$status
        );
        if($id){
            $res = Db::name('name_audit')->where('id',$id)->update($data);
        }else{
            $res = Db::name('name_audit')->insert($data);
        }
        if($res!==false){
            return json(['error'=>0,'msg'=>'提交成功,审核中']);
        }else{
            return json(['error'=>-1,'msg'=>'提交失败']);
        }

    }
    /**
     * 收货地址列表
     * 
     * @param int $user_id 用户id
     */
    public function address_list(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $res = Db::name()->where('user_id='.$user_id)->select();
        return json(['error'=>0,'msg'=>'success']);
    }    
    /**
     * 编辑收货地址
     * 
     * @param int $user_id 用户id
     */
    public function save_address(){
        $params = $this->request->param();
        $address_id = !empty($params['address_id'])?intval($params['address_id']):0;
        $data['consignee']  = !empty($params['consignee'])?trim($params['consignee']):'';   
        $data['province']   = !empty($params['province'])?intval($params['province']):'';   
        $data['city']       = !empty($params['city'])?intval($params['city']):'';   
        $data['district']   = !empty($params['district'])?intval($params['district']):'';   
        $data['address']    = !empty($params['address'])?trim($params['address']):'';
        $data['mobile']     = !empty($params['mobile'])?trim($params['mobile']):'';
        // $data['user_id']    = !empty($params['user_id'])?intval($params['user_id']):0;

        $token      = $params('token');
        $Common     = new Common();
        $data['user_id']    = $Common->getUserId($token);//获取登入用户的user_id

        $data['is_default'] = !empty($params['is_default'])?intval($params['is_default']):0;
        if(!$address_id){
            $num = Db::name('user_address')->where('user_id='.$data['user_id'])->count();
            if($num>10){
                return json(['error'=>-1,'msg'=>'最多添加10条收货地址']);
            }
            if($data['is_default']=1){
                $res = Db::name('user_address')->where("user_id = $data[user_id] and is_default=1")->setField('is_default',0);
            }
            $res = Db::name('user_address')->insert($data);
            return json(['error'=>0,'msg'=>'增加地址成功']);
        }else{
            if($data['is_default']=1){
                $res = Db::name('user_address')->where("user_id = $data[user_id] and is_default=1")->setField('is_default',0);
            }
            $res = Db::name('user_address')->where('address_id='.$address_id)->update($data);
            return json(['error'=>0,'msg'=>'编辑地址成功']);
        }
    }
    /**
     * 删除收货地址
     * @param int $address_id 用户收货地址id
     */
    public function del_address(){
        $address_id = $this->request->param('address_id');
        $res = Db::name('user_address')->where('address_id',$address_id)->delete();
        if($res){
            return json(['error'=>0,'msg'=>'删除地址成功']);
        }else{
            return json(['error'=>-1,'msg'=>'删除地址失败']);
        }
    }
    /**
     * 修改手机号
     */
    public function change_mobile(){
        $params = $this->request->param();
        $new_mobile = !empty($params['new_mobile']) ? $params['new_mobile']:'';
        $old_mobile = !empty($params['old_mobile']) ? $params['old_mobile']:'';
        $code = !empty($params['code']) ? $params['code']:'';
        if(!$new_mobile||!$old_mobile||!$code){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $res = Sms::check($old_mobile, $code, 'resetmobile');
        if(!$res){
            $this->error(__('验证码不正确'));
        }
        Sms::flush($old_mobile, 'resetmobile');
        $res = Db::name('user')->where('mobile',$old_mobile)->setField('mobile',$new_mobile);
        if($res){
            return json(['error'=>0,'msg'=>'修改成功']); 
        }else{
            return json(['error'=>-1,'msg'=>'修改失败']);
        }
    }
    /**
     * 用户确认收货
     */
    public function user_confirm(){
        $order_id = $this->request->param('order_id');
        if(!$order_id){
            return json(['error'=>-1,'msg'=>'非法操作']);
        }
        $res = Db::name('order')->where('order_id='.$order_id)->setField('oeder_status',2);
        if($res!==false){
            return json(['error'=>0,'msg'=>'确认收货成功']);
        }else{
            return json(['error'=>-1,'msg'=>'确认收货失败']);
        }
    }

    /**
     * 消息中心
     */
    public function user_message(){
     
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $res = Db::name('user_message')->alias('u')->join('message m','u.message_id=m.message_id','LEFT')->where('user_id='.$user_id)->paginate(10)->toarray();
        $num = Db::name('user_message')->field('rec_id')->where('status=0')->count();
        $is_read = $num>0?1:0;
        return json(['error'=>0,'msg'=>'success','data'=>$res,'is_read'=>$is_read]);
    }
    /**
     * 阅读消息
     */
    public function read_message(){
        $ids = $this->request->param('rec_id');
        if(!$ids){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        $res = Db::name('user_message')->whereIn('rec_id',$ids)->setField('status',1);
        if($res){
            return json(['error'=>0,'msg'=>'已读']);
        }else{
            return json(['error'=>-1,'msg'=>'未读']);
        }
    }
    /**
     * 我的团队
     */
    public function my_team(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $res =  Db::name('user')->field('username,level,total_use_money')->where('id='.$user_id)->find();
        return json(['error'=>0,'msg'=>'success','data'=>$res]);
    }
    /**
     * 获取下级会员
     */
    public function get_son_user(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $son_user = Db::name('user')->field('username,nickname,mobile,level,bher,bhe,user_money,frozen_money')->where('parent_id='.$user_id)->select();
        return json(['error'=>0,'msg'=>'success','data'=>$son_user]);
    }
    
    /**
     * 获取消费记录
     */
    public function get_user_log(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $user_log = Db::name('user_log')->field('id,order_sn,use_money,add_time')->where("user_id=$user_id")->paginate(10)->toarray();
        if($user_log){
            foreach ($user_log['data'] as $key => $value) {
                $user_log['data'][$key]['add_time'] = date('Y-m-d',$value['add_time']);
            }
        }
        return json(['error'=>0,'msg'=>'success','data'=>$user_log]);
    }

    /**
     * 代理分销
     */
    public function distribution(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $level = Db::name('user')->where('id='.$user_id)->value('level');
        if($level<3){
            return json(['error'=>-1,'msg'=>'会员等级要达到白金或黑金']);
        }
        $son_level = $level-1;
        //低于自己一个VIP级别的团队业绩
        $res = Db::name('user')->field('id,username,level,total_use_money')->where('level='.$son_level.' and parent_id ='.$user_id.'')->select();
        $sum = 0;
        foreach ($res as $key => $value) {
            $son_id = $value['id'].$this->get_all_sonid($value['id']);
            $res[$key]['son_total_num'] = Db::name('user')->whereIn('id',$son_id)->sum('total_use_money');
            $sum+=$res[$key]['son_total_num'];//累加
            //伞下会员数
            $res[$key]['son_num'] =Db::name('user')->whereIn('id',$son_id)->count('id')-1;
        }
       //自己的消费业绩
        $user_money = Db::name('user')->where('id='.$user_id)->value('total_use_money');
        //伞下所有总业绩
        $all_son_id = $user_id.$this->get_all_sonid($user_id);
        $res['total'] = Db::name('user')->whereIn('id',$all_son_id)->sum('total_use_money')-$user_money;
        //低于自己一个VIP级别的汇总团队数量
        $res['son_team_num'] = Db::name('user')->where('level='.$son_level.' and parent_id ='.$user_id.'')->count('id');
        //低于自己一个VIP级别的汇总团队业绩
        $res['son_team_money'] = $sum;
        return json(['error'=>0,'msg'=>'success','data'=>$res]);
       
    }
    /**
     * 获取所有伞下会员id,存入字符串
     */
    public function get_all_sonid($id){
        $child = Db::name("user")->field("id")->where("parent_id=".$id)->select();
        $son_id = '';
        if($child){
            foreach($child as $k=>$v){
                $son_id .= ','.$v['id'];
                $son_id .= $this->get_all_sonid($v['id']);
            }
        }
        return $son_id; 
    }
    /**
     * 个人资产bhe
     */
    public function user_asset_bhe(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $data['user_info'] = Db::name('user')->field('bher')->where('id='.$user_id)->find();
        $data['bhe_log']   = Db::name('bhe_log')->field('id,num,bhe_before,bhe_balance,add_reason,add_time')->where('user_id='.$user_id)->paginate(10)->toarray();
        return json(['error'=>0,'msg'=>'success','data'=>$data]);
    }
    public function bhe_detial(){
        $bhe_id  = $this->request->param('bhe_id');
        if(!$bhe_id){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        $res = Db::name('num,bhe_before,bhe_balance,add_reason,txid,add_time,rate_fee,receive_url,status')->where("id=$bhe_id")->find();
        return json(['error'=>0,'msg'=>'success','data'=>$res]);
    }
    /**
     * 个人资产bher
     */
    public function user_asset_bher(){
        $token      = $this->request->param('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id

        $data['user_info']  = Db::name('user')->field('bher')->where('id='.$user_id)->find();
        $data['bher_log']   = Db::name('bher_log')->field('id,num,bher_before,bher_balance,add_reason,add_time')->where('user_id='.$user_id)->paginate(10)->toarray();
        return json(['error'=>0,'msg'=>'success','data'=>$data]);
    }
    public function bher_detial(){
        $bher_id  = $this->request->param('bher_id');
        if(!$bher_id){
            return json(['error'=>-1,'msg'=>'传参错误']);
        }
        $res = Db::name('id,num,user_id,bher_before,bher_balance,add_reason,add_time')->where("id=$bher_id")->find();
        return json(['error'=>0,'msg'=>'success','data'=>$res]);
    }
    /**
     * 转账兑换
     */
    public function  exchange_bhe(){
        $params     = $this->request->param();
        // $user_id    = !empty($params['user_id'])?intval($params['user_id']):'';
        $token      = $params('token');
        $Common     = new Common();
        $user_id    = $Common->getUserId($token);//获取登入用户的user_id
        
        $num        = !empty($params['num'])?intval($params['num']):'';
        $bhe_before = !empty($params['bhe_before'])?intval($params['bhe_before']):'';
        $rate_fee   = !empty($params['rate_fee'])? $params['rate_fee']:'';
        $receive_url= !empty($params['receive_url'])? $params['receive_url']:'';
        $add_time   = time();
        $add_reason = !empty($params['add_reason'])? $params['add_reason']:'兑换';
        if($num>$bhe_before){
            return json(['error'=>-1,'msg'=>'兑换数量不能超过余额']); 
        }
        $res = Db::name('bhe_log')->insert($data);
        return json(['error'=>0,'msg'=>'兑换申请已提交']);
    }

    
   




}
