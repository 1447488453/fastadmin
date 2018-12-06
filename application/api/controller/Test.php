<?php
namespace app\api\controller;
use app\common\controller\Api;
use think\Session;
use think\Db;
use think\Cache\driver\Redis;
use Vendor\Stomp\doc\Classes;
class Test extends Api{
    public function test(){
        $redis = new Redis();
        // echo 666;exit;
        $data = array(
            'user_id' => 6,
            'order_sn'=>333666,
        );
        $total_fee = 600;

        //1：更新订单支付状态
        Db::name('order')->where('order_sn='.$data['order_sn'])->setField('pay_status',1);
        //2:更新个人bher
        $user_id =$data['user_id'];
        $user = Db::name('user')->field('bher,parent_id,level,total_use_money')->where('id='.$user_id)->find();   
        $new_bher = $user['bher']+$total_fee;
        Db::name('user')->where('id='.$user_id)->setField('bher',$new_bher);
        if($user['level']==0){
            Db::name('user')->where('id='.$user_id)->setField('level',1);
        }
        //插入bher_log记录
        $bher_log['user_id']        = $user_id;
        $bher_log['num']            = $total_fee;
        $bher_log['bher_before']    = $user['bher'];
        $bher_log['bher_balance']   = $new_bher;
        $bher_log['add_time']       = time();
        $bher_log['add_reason']     = '消费';
        // Db::name('bher_log')->insert($bher_log);
        try{
            $redis->LPUSH('bher_log',json_encode($bher_log));
        }catch(Exception $e){
            echo $e->getMessage();
        } 
        //自己消费总金额
        $total_use_money = $user['total_use_money'] +$total_fee;
        Db::name('user')->where('id='.$user_id)->setField('total_use_money',$total_use_money);
        //直推会员数
        $user_num = Db::name('user')->where('parent_id='.$user_id)->count('id');
        //至少3个直推团队伞下无限极会员“现金”累计达到10W
        $son_ids = Db::name('user')->field('id,total_use_money')->where('parent_id='.$user_id)->select();
        $i=0;
        foreach ($son_ids as $key => $va) {
            $i_id = $this->get_all_catid($va['id']).$va['id'];
            $t = Db::name('user')->whereIn('id',$i_id)->sum('total_use_money')-$va['total_use_money'];
            if($t>=100000){
                $i++;
            }
        }
        $c=0;
        foreach ($son_ids as $key => $va) {//至少3个直推团队伞下无限极会员中各有一名白金会员
            $c_id = $this->get_all_catid($va['id']).$va['id'];
            $c1 = Db::name('user')->whereIn('id',$c_id)->where('level>=3')->count('id');
            if($c1>=1){
                $c++;
            }
        }
        $b=0;
        foreach ($son_ids as $key => $va) {//至少3个直推团队伞下无限极会员中各有一名黄金会员
            $b_id = $this->get_all_catid($va['id']).$va['id'];
            $b1 = Db::name('user')->whereIn('id',$b_id)->where('level>=2')->count('id');
            if($b1>=1){
                $b++;
            }
        }
         //伞下所有总业绩
        $all_son_id = $this->get_all_catid($user_id).$user_id;
        $total = Db::name('user')->whereIn('id',$all_son_id)->sum('total_use_money')-$total_use_money;
        $data['level_time'] = time();
        if($total_use_money>=5000&&$user_num>=3&&$i>=3&&$total>=500000){
            $data['level'] = 2;
            Db::name('user')->where('id='.$user_id)->update($data);
        }elseif($total_use_money>=10000&&$user_num>=3&&$b>=3&&$total>=2000000){
            $data['level'] = 3;
            Db::name('user')->where('id='.$user_id)->update($data);
        }elseif($total_use_money>=15000&&$user_num>=3&&$c>=3&&$total>=8000000){
            $data['level'] = 4;
            Db::name('user')->where('id='.$user_id)->update($data);
        }

        //-----bher奖开始-------------
        //更改上级bher
        if($user['level']<=1){//当前消费会员是普通会员或游客
            //获取该消费会员所有的上级id
            $p_ids = substr($this->get_parent_id($user_id),0,-1);
            $p_ids = explode(',',$p_ids);
            array_pop($p_ids); 
            $level =1;
            $yf_bher=0;//统计已发放了的bher 黄金白金
            $i=0;//用来判断当前循环的次数
            foreach ($p_ids as $key => $value) {
                $res = $this->p_level($value);//获取上一级会员信息
                $p1_bher_log['user_id']         = $res['id'];
                $p1_bher_log['bher_before']     = $res['bher'];
                $p1_bher_log['add_time']        = time();
                if($res['level']==1&&$level==1){//如果是普通会员 为推荐奖,推荐奖 只往上推3级
                $level =1;   
                $i++;
                if($i==1){
                    $rs = 1;
                }elseif($i==2){
                    $rs = 0.4;
                }elseif($i==3){
                    $rs = 0.1;
                }else{
                    continue;
                }
                if($res['bher']>$total_fee){//取得推荐奖奖励基数
                    $p1_bher = $res['bher']+$total_fee*$rs;  
                }else{
                    $p1_bher = $res['bher']+ $res['bher']*$rs;
                }
                $p1_bher_log['bher_balance']  = $p1_bher;
                Db::name('user')->where("id=$res[id]")->setField('bher',$p1_bher);//改变bher
                //p1_bher更新bher_log记录
                $p1_bher_log['num']             = $p1_bher-$res['bher'];
                $p1_bher_log['add_reason']      = '推荐奖';
                // Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                }elseif($res['level']==2){//如果是黄金会员 为辅导奖 增加的bher是 消费现金*10%
                    $level =2; //记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                    $p_her = $res['bher']+$total_fee*0.1;
                    Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                    //p1_bher更新bher_log记录
                    $p1_bher_log['num']             = $total_fee*0.1;
                    $p1_bher_log['add_reason']      = '辅导奖';
                    $p1_bher_log['bher_balance']    = $p_her;
                    // Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                    $yf_bher+=$p1_bher_log['num'];
                }elseif($res['level']==$level){//平级奖励  判断是否是同级且要是离自己最近的同级团队
                    //先取出下级所有的成员信息 然后再筛选出和自己等级相同的会员。然后再排出会员的层级取出最近的，最后再比较达成时间;得到最近的团队后判断当前消费会员是否在这个团队里。
                    //第一步取出下一所有成员的信息
                    $all_chird_id = substr($this->get_all_catid($res['id']),0,-1);
                    $user_info = Db::name('user')->field('id,level,level_time')->whereIn('id',$all_chird_id)->select();
                    $res = $this->getTree($user_info);
                    //取出层级最近的
                    foreach($res as $key=> $v){
                        if($v['level']==1){
                            $b[] = $v;
                        }
                    }
                    $son_info =$b;
                    $time = time();
                    foreach ($son_info as $key => $value){
                        if($value['level_time']<$time){
                            $time =   $value['level_time'];
                            $id         =   $value['id'];
                        }
                    }
                    $zj_id = $id;//最近团队的id
                    $all_son_id = substr($this->get_all_catid($zj_id),0,-1);
                    $a = strpos($all_son_id,$user_id);
                    if($a){
                        $level =$level;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                        $p_her = $res['bher']+$total_fee*0.02;
                        Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                        //p1_bher更新bher_log记录
                        $p1_bher_log['bher_balance']    = $p_her;
                        $p1_bher_log['num']             = $total_fee*0.02;
                        $p1_bher_log['add_reason']      = '平级奖';
                        // Db::name('bher_log')->insert($p1_bher_log);
                        try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                    }
                }elseif($res['level']<$level){//上级会员等级小于下级等级不获得奖励
                    continue;
                }elseif($res['level']==3){//如果是白金会员 为辅导奖 增加的bher是 消费现金*20%
                    $level =3;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                    $p_her = $res['bher']+$total_fee*0.2;
                    Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                    //p1_bher更新bher_log记录
                    $p1_bher_log['bher_balance']    = $p_her;
                    $p1_bher_log['num']             = $total_fee*0.2-$yf_bher;;
                    $p1_bher_log['add_reason']      = '辅导奖';
                    // Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                    $yf_bher+=$p1_bher_log['num'];
                }elseif($res['level']==4){//如果是黑金会员 为辅导奖 增加的bher是 消费现金*30%
                    $level =4;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                    $p_her = $res['bher']+$total_fee*0.3-$yf_bher;
                    Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                    //p1_bher更新bher_log记录
                    $p1_bher_log['bher_balance']    = $p_her;
                    $p1_bher_log['num']             = $total_fee*0.3-$yf_bher;
                    $p1_bher_log['add_reason']      = '辅导奖';
                    // Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
        }            
                }  
            }
        }else{
            $user_level = $user['level'];//当前消费会员等级level=2黄金level=4白金或者level=4黑金
            //获取该会员所有的上级id
            $p_ids = substr($this->get_parent_id($user_id),0,-1);
            $p_ids = explode(',',$p_ids);
            array_pop($p_ids);
            $yf_bher = 0; //统计已发放了的bher 黄金白金
            $level = $user_level;//当前消费会员的等级
            $i=0;//用来判断当前循环的次数
            foreach ($p_ids as $key => $value) {
                $level = $user_level;
                $res = $this->p_level($value);//获取上一级会员信息
                $p1_bher_log['user_id']         = $res['id'];
                $p1_bher_log['bher_before']     = $res['bher'];
                $p1_bher_log['add_time']        = time();
                $i++;//当前循环次数
               if($res['level']<$level&&$i==1){
                    if($res['level']==3){//如果是白金会员 为辅导奖 增加的bher是 消费现金*20%
                        $level =3;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                        $p_her = $res['bher']+$total_fee*0.2;
                        Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                        //p1_bher更新bher_log记录
                        $p1_bher_log['bher_balance']    = $p_her;
                        $p1_bher_log['num']             = $total_fee*0.2-$yf_bher;
                        $p1_bher_log['add_reason']      = '辅导奖';
                        // Db::name('bher_log')->insert($p1_bher_log);
                        try{
                            $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                        }catch(Exception $e){
                            echo $e->getMessage();
                        }
                        $yf_bher+=$p1_bher_log['num'];
                        continue;
                    }elseif($res['level']==4){
                        $level =4;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                        $p_her = $res['bher']+$total_fee*0.3;
                        Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                        //p1_bher更新bher_log记录
                        $p1_bher_log['bher_balance']    = $p_her;
                        $p1_bher_log['num']             = $total_fee*0.3-$yf_bher;
                        $p1_bher_log['add_reason']      = '辅导奖';
                        // Db::name('bher_log')->insert($p1_bher_log);
                        try{
                            $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                        }catch(Exception $e){
                            echo $e->getMessage();
                        }
                        continue;
                    }elseif($res['level']==2){//如果是黄金会员 为辅导奖 增加的bher是 消费现金*10%
                        $level =2; //记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                        $p_her = $res['bher']+$total_fee*0.1;
                        Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                        //p1_bher更新bher_log记录
                        $p1_bher_log['num']             = $total_fee*0.1-$yf_bher;
                        $p1_bher_log['add_reason']      = '辅导奖';
                        $p1_bher_log['bher_balance']    = $p_her;
                        // Db::name('bher_log')->insert($p1_bher_log);
                        try{
                            $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                        }catch(Exception $e){
                            echo $e->getMessage();
                        }
                        $yf_bher+=$p1_bher_log['num'];
                        continue;
                    }
                }elseif($res['level']==$level){//平级奖励 判断是否是同级且要是离自己最近的同级团队

                    //先取出下级所有的成员信息 然后再筛选出和自己等级相同的会员。然后再排出会员的层级取出最近的，最后再比较达成时间;得到最近的团队后判断当前消费会员是否在这个团队里。
                    //第一步取出下一所有成员的信息
                    $all_chird_id = substr($this->get_all_catid($res['id']),0,-1);
                    $user_info = Db::name('user')->field('id,level,level_time')->whereIn('id',$all_chird_id)->select();
                    $res = $this->getTree($user_info);
                    //取出层级最近的
                    foreach($res as $key=> $v){
                        if($v['l']==1 &&$v['level']==$level){
                            $b[] = $v;
                        }
                    }
                    $son_info =$b;
                    $time = time();
                    foreach ($son_info as $key => $value) {
                        if($value['level_time']<$time){
                            $time       =   $value['level_time'];
                            $id         =   $value['id'];
                        }
                    }
                    $zj_id = $id;//最近团队的id
                    $all_son_id = substr($this->get_all_catid($zj_id),0,-1);
                    $a = strpos($all_son_id,$user_id);
                    if($a){
                        $level =$level;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                        $p_her = $res['bher']+$total_fee*0.02;
                        Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                        //p1_bher更新bher_log记录
                        $p1_bher_log['bher_balance']    = $p_her;
                        $p1_bher_log['num']             = $total_fee*0.02;
                        $p1_bher_log['add_reason']      = '平级奖';
                        // Db::name('bher_log')->insert($p1_bher_log);
                        try{
                            $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                        }catch(Exception $e){
                            echo $e->getMessage();
                        }
                    }
                }elseif($res['level']<$level){//上级会员等级小于下级等级不获得奖励
                    continue;
                }elseif($res['level']==3){//如果是白金会员 为辅导奖 增加的bher是 消费现金*20%
                    $level =3;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                    $p_her = $res['bher']+$total_fee*0.2;
                    Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                    //p1_bher更新bher_log记录
                    $p1_bher_log['bher_balance']    = $p_her;
                    $p1_bher_log['num']             = $total_fee*0.2-$yf_bher;
                    $p1_bher_log['add_reason']      = '辅导奖';
                    // Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                    $yf_bher+=$p1_bher_log['num'];
                }elseif($res['level']==4){
                    $level =4;//记录当前会员的等级,以便于进入下次循环判断 是否低于这个等级
                    $p_her = $res['bher']+$total_fee*0.3;
                    Db::name('user')->where("id=$res[id]")->setField('bher',$p_her);//改变bher
                    //p1_bher更新bher_log记录
                    $p1_bher_log['bher_balance']    = $p_her;
                    $p1_bher_log['num']             = $total_fee*0.3-$yf_bher;
                    $p1_bher_log['add_reason']      = '辅导奖';
                    //Db::name('bher_log')->insert($p1_bher_log);
                    try{
                        $redis->LPUSH('bher_log',json_encode($p1_bher_log));
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                }
            } 
        }
        try{
            $count = $redis->LLEN('bher_log');
        while($count>0) {
            $value = $redis->LPOP('bher_log');
            $count = $redis->LLEN('bher_log');
            Db::name('bher_log')->insert(json_decode($value,true));
        }
        }catch(Exception $e){
            echo $e->getMessage();
        }
        //-----bher奖结束-------------
        return json(['error'=>0,'msg'=>'success']);
    }

    //获取上一级会员信息
    public function p_level($p_id){
        $p_res =  Db::name('user')->field('id,bher,parent_id,level')->where("id=".$p_id)->find();
        return $p_res;
    }
    //获取所有父级id
    public function get_parent_id($id){
        $p_id = Db::name('user')->field('parent_id')->where('id='.$id)->select();
        $p_ids='';
        if($p_id){
            foreach ($p_id as $key => $value) {
                $p_ids.=$value['parent_id'].',';
                $p_ids.=$this->get_parent_id($value['parent_id']);
            }
           
        }
        return $p_ids;
    }
    //获取所有下级ID,存入字符串
    public function get_all_catid($user_id){
        $child = Db::name("user")->field("id")->where("parent_id=".$user_id)->select();
        $sub_id = '';
        if($child){
            foreach($child as $k=>$v){
                $sub_id .= $v['id'].',';
                $sub_id .= $this->get_all_catid($v['id']);
            }
        }
        return $sub_id; 
    }
    //树级分类
    public function getTree($array, $parent_id =0, $l = 0){
        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['parent_id'] == $parent_id){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['l'] = $l;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getTree($array, $value['id'], $l+1);
            }
        }
        return $list;
    }


/* ----------------------------------------华丽的分割线-----------------------------------------------*/


    public function activemq(){
        $stomp = new \Stomp('tcp://localhost:61613');
        $obj = new \Stdclass();
        //下面这些数据，实际中是用户通过前端页面post来的，这里只做演示
        //发送一个注册消息到队列，我们这里模拟用户注册
         for($i=0; $i<3; $i++){
            $obj->username = 'test';
            $obj->password = '123456';
            $queneName   = "/queues/userReg";
            // 4.发送一个注册消息到队列
            $stomp->send($queneName, json_encode($obj));
          }
    }

    public function consumer(){
        $stomp = new \Stomp('tcp://localhost:61613');
        $stomp->subscribe('/queues/userReg');
        while(true) {
        //判断是否有读取的信息
        if($stomp->hasFrame()){
            $frame = $stomp->readFrame();
            $data = json_decode($frame->body, true);
           print_r($data);
 
        //我们通过获取的数据
        //处理相应的逻辑，比如存入数据库，发送验证码等一系列操作。
        //$db->query("insert into user values('{$username}','{$password}')");
        //sendVerify();
        //表示消息被处理掉了，ack()函数很重要
            $stomp->ack($frame);
        }
            sleep(1);
        }
    }

    public function pub(){//发布
        $stomp = new \Stomp('tcp://localhost:61613');
        $stomp->clientId = "testwwsd33";
        $obj = new \Stdclass();
        $obj->message = '123456';
        $queneName   = "/topic/tt1";
        $stomp->send($queneName, json_encode($obj),array('persistent'=>'true'));
        echo "Sent message success\n";
    }
    public function sub(){//订阅
        $stomp = new \Stomp('tcp://localhost:61613');
        $stomp->clientId = "testwwsd33";
        $stomp->subscribe('/topic/tt1');
        while(true) {
        //判断是否有读取的信息
        if($stomp->hasFrame()) {
            $frame = $stomp->readFrame();
            $data = json_decode($frame->body, true);
            print_r($data);
        //表示消息被处理掉了，ack()函数很重要
            $stomp->ack($frame);
        }
            sleep(1);
        }
    }

    public function bher(){
        $params = $this->request->param();
        $where = '1=1';
        if(!empty($params['username'])){
            $where.=" and u.username like '%$params[username]%'";
        }
        $res=Db::name('bher_log')->alias('b')->join('user u','b.user_id = u.id')->field('u.username,u.id')->where($where)->select();
        echo "<pre>";
        print_r($res);
    }
}



?>