<?php

class oldModule{
	function index(){
        $p = $_REQUEST['p']?$_REQUEST['p']:"1";
        $is_ajax = $_REQUEST['ajax'];
        $pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'10';
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_list&p='.$p.'&pagesize='.$pagesize;
        $goods = json_decode($this->getCurl($url),true);

        if(strval($goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("error",$goods['msg']);
        }else{
            foreach($goods['data'] as $k=>$v){
                //封面图像
                if(!empty($v['images_data']['list'])){
                    foreach($v['images_data']['list'] as $list_k=>$list_v){
                        if($list_v['is_top'] == 1){
                            $goods['data'][$k]['head_image'] = $list_v['url'];
                        }
                        if(empty($goods['data'][$k]['head_image'])){
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }
                    }
                }
                //获取用户头像
                $user_id = $v['user_id'];
                $avatar = get_user_avatar($user_id,'middle');
                $goods['data'][$k]['avatar'] = $avatar;
                if($GLOBALS['user_info']){
                    $is_focus = $this->is_focus($GLOBALS['user_info']['id'],$v['id']);
                }else{
                    $is_focus = 0;
                }
                $goods['data'][$k]['is_focus'] = $is_focus;
            }
        }
        if($is_ajax==1){
            $data = array('goods'=>$goods,'now_page'=>$p);
            ajax_return($data);
            exit;
        }
//        print_r($goods['data']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods['data']);
        $GLOBALS['tmpl']->assign("page",$p);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'HUAN');
        $GLOBALS['tmpl']->display("old/old_index.html");
	}
    function home(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $id = $GLOBALS['user_info']['id'];
        $good_num = $GLOBALS['db']->getOne("select count(*) from old_goods where is_del=0 and user_id=".$id);
        $order_num = $GLOBALS['db']->getOne("select count(*) from old_exchange where from_user_id=".$id.' and exchange_state !=2');

//        print_r($order_num);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'管理');
        $GLOBALS['tmpl']->assign("good_num",$good_num);
        $GLOBALS['tmpl']->assign("order_num",$order_num);
        $GLOBALS['tmpl']->display("old/home.html");
    }
    function home_goods(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        $url = $this->getUrl().'act=get_user_goods_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
//        echo $url;
        $goods = json_decode($this->getCurl($url),true);
        if(strval($goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("error",$goods['data']['msg']);
        }else{
            foreach($goods['data'] as $k=>$v){
                //封面图像
                if(!empty($v['images_data']['list'])){
                    foreach($v['images_data']['list'] as $list_k=>$list_v){
                        if($list_v['is_top'] == 1){
                            $goods['data'][$k]['head_image'] = $list_v['url'];
                        }
                        if(empty($goods['data'][$k]['head_image'])){
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }
                    }

                }
                //获取用户头像
                $user_id = $v['user_id'];
                $avatar = get_user_avatar($user_id,'middle');
                $goods['data'][$k]['avatar'] = $avatar;
                if($GLOBALS['user_info']){
                    $is_focus = $this->is_focus($GLOBALS['user_info']['id'],$v['id']);
                }else{
                    $is_focus = 0;
                }
                $goods['data'][$k]['is_focus'] = $is_focus;
            }
        }
//        print_r($GLOBALS['user_info']);

        $card_state = $this->get_user_state($GLOBALS['user_info']['id']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods['data']);
        $GLOBALS['tmpl']->assign("card_state",$card_state);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_old());
        $GLOBALS['tmpl']->assign("page_title",'我的物品');
        $GLOBALS['tmpl']->display("old/home_goods.html");
    }
    function home_exchange(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
//        get_from_exchange_list
//        print_r($GLOBALS['user_info']);
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        //-----------别人向我申请的
        $from_url = $this->getUrl().'act=get_from_exchange_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $from_user_goods = json_decode($this->getCurl($from_url),true);
//        echo $from_url;
        if(strval($from_user_goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("from_error",$from_user_goods['data']['msg']);
        }else{
            foreach($from_user_goods['data']['list'] as $k=>$v){
                $from_user_goods['data']['list'][$k]['to_rest_time'] = $this->get_last_time($v['exchange_log']['to_last_set_time'],time());
                $from_user_goods['data']['list'][$k]['from_rest_time']= $this->get_last_time($v['exchange_log']['from_last_set_time'],time());
            }
            $GLOBALS['tmpl']->assign("from_user_goods",$from_user_goods['data']['list']);

        }
        //------------我申请交换的
        $to_url =  $this->getUrl().'act=get_to_exchange_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $to_user_goods = json_decode($this->getCurl($to_url),true);

        if(strval($to_user_goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("to_error",$to_user_goods['data']['msg']);
        }else{
            foreach($to_user_goods['data']['list'] as $k=>$v){
                $to_user_goods['data']['list'][$k]['to_rest_time'] = $this->get_last_time($v['exchange_log']['to_last_set_time'],time());
                $to_user_goods['data']['list'][$k]['from_rest_time']= $this->get_last_time($v['exchange_log']['from_last_set_time'],time());
            }
            $GLOBALS['tmpl']->assign("to_user_goods",$to_user_goods['data']['list']);
        }
        $user_consignee = $this->get_user_consignee($GLOBALS['user_info']['id']);
        //get_payment_list
        $payment_list_url = $this->getUrl().'act=get_payment_list';
        $payment_list = json_decode($this->getCurl($payment_list_url),true);
//echo $payment_list_url;
//        print_r($from_user_goods);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user_consignee",$user_consignee['data']);
        $GLOBALS['tmpl']->assign("payment_list",$payment_list);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_old());
        $GLOBALS['tmpl']->assign("page_title",'交换申请');
        $GLOBALS['tmpl']->display("old/home_exchange.html");
    }
    function release(){
        //发布物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        //判断实名认证状态
        $card_state = $this->get_user_state($GLOBALS['user_info']['id']);
//        $card_state = json_decode($card_state,true);
//print_r($card_state);
        if($card_state==2||$card_state==1){
            app_redirect(url_wap("old#failed",array('msg'=>urlencode('对不起，您还没有完成实名认证，不能发布新物品！'))));
        }
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("card_state",$card_state);
        $GLOBALS['tmpl']->assign("page_title",'发布物品');
        $GLOBALS['tmpl']->display("old/release.html");
    }
    function get_user_state($id){
        $id = $id?$id:$_REQUEST['id'];
        $old_user = $this->getUrl().'act=get_user_info&user_id='.$id;
        $good_num = $GLOBALS['db']->getOne("select count(*) from old_goods where user_id=".$id.' and is_del=0');
        $old_user_info = json_decode($this->getCurl($old_user),true);
        //实名认证：1:审核中 2:认证失败 0:未认证通过，有一次发布机会 4:发布过了，但在审核中
        $card_state = $old_user_info['data']['card_state'];
        if($card_state==0&&$good_num){
            $card_state = 4;
        }
        if(!isset($_REQUEST['id'])){
            return $card_state;
        }else{
            ajax_return(array('data'=>$card_state));
        };
    }
    function edit(){
        //发布物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $id = $_REQUEST['id'];
        $is_new = $_REQUEST['is_new'];//用来判断是编辑旧id的物品（edit_goods）还是生成新id的物品（edit_online_goods）
        $url = $this->getUrl().'act=get_goods_info&id='.$id;
        $goods_info = json_decode($this->getCurl($url),true);
        $goods_info = $this->get_cover($goods_info);
        foreach($goods_info['data']['images_data']['list'] as $k=>$v){
            $goods_info['data']['images_data']['list'][$k]['index'] = $k+1;
        }
        //get_goods_info
//        print_r($goods_info);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods_info['data']);
        $GLOBALS['tmpl']->assign("is_new",$is_new);
        $GLOBALS['tmpl']->assign("num",$goods_info['data']['images_data']['num']['num']);
        $GLOBALS['tmpl']->assign("page_title",'编辑物品');
        $GLOBALS['tmpl']->display("old/edit_release.html");
    }
    function notify(){
        $p = $_REQUEST['p'];
        $pagesize = $_REQUEST['pagesize'];
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $notifyUrl = $this->getUrl().'act=get_notify&user_id='.$GLOBALS['user_info']['id'].'&is_read=0&p='.$p.'&pagesize='.$pagesize;
        $notify_info = json_decode($this->getCurl($notifyUrl),true);
        foreach($notify_info['data']['list'] as $k => $v){
            $notify_info['data']['list'][$k]['log_time'] = date("Y-m-d ", $v['log_time']);
//            print_r($v);
        }
//        $messageUrl = $this->getUrl().'act=get_message&user_id='.$GLOBALS['user_info']['id'].'&is_read=1';
        $messageUrl = $this->getUrl().'act=get_message&user_id='.$GLOBALS['user_info']['id'];
        $message_info = json_decode($this->getCurl($messageUrl),true);
//        print_r($messageUrl);
//        print_r($notify_info);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'消息');
        $GLOBALS['tmpl']->assign("notify",$notify_info['data']['list']);
        $GLOBALS['tmpl']->assign("message",$message_info['data']['list']);
        $GLOBALS['tmpl']->display("old/notify.html");
    }
    function bind(){
        //实名认证
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $id = $GLOBALS['user_info']['id'];

        $goods_id= $_GET['goods_id'];
        $old_user = $this->getUrl().'act=get_user_info&user_id='.$id;
        $old_user_info = json_decode($this->getCurl($old_user),true);
        $GLOBALS['tmpl']->assign("page_title","实名认证");
//        print_r($old_user_info);
        $GLOBALS['tmpl']->assign("info",$old_user_info);
        $GLOBALS['tmpl']->assign("goods_id",$goods_id);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->display("old/verified.html");
    }
    function select(){
        //提出交换&选择用来交换的物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        //获取物品列表
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        $from_user_id = $_REQUEST['from_user_id'];
        $from_goods_id = $_REQUEST['from_goods_id'];
        $url = $this->getUrl().'act=get_user_goods_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $goods = json_decode($this->getCurl($url),true);
//        print_r($goods);
        $goods = $this->get_cover($goods['data']);
//        print_r($_SERVER);
        es_session::set("from_user_id",$from_user_id);
        es_session::set("from_goods_id",$from_goods_id);
        $GLOBALS['tmpl']->assign("page_title","提出交换");
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods);
        $GLOBALS['tmpl']->assign("from_user_id",$from_user_id);
        $GLOBALS['tmpl']->assign("from_goods_id",$from_goods_id);
        $GLOBALS['tmpl']->display("old/select.html");
    }
    function detail(){
        //物品详情
        $id=$_REQUEST['id'];
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_info&id='.$id;
        if($id){
            $share_info = $this->get_wx_share_info($id);
//            print_r($share_info);
            $GLOBALS['tmpl']->assign('share_info',$share_info);
        }
        $details = json_decode($this->getCurl($url),true);
        if(strval($details['error'])!=='0') {
            $GLOBALS['tmpl']->assign("error", $details['msg']);
        }else{
            $detail = $details['data'];
            //获取用户头像
            $user_id = $detail['user_id'];
            $owner = $this->getUrl().'act=get_user_info&user_id='.$user_id;
            $owner_info = json_decode($this->getCurl($owner),true);
            $avatar = get_user_avatar($user_id,'middle');
            $username = $GLOBALS['db']->getOne("select `user_name` from  ".DB_PREFIX."user where id=".$user_id);
            $detail['avatar'] = $avatar;
            $detail['username'] = $username;
            $detail['card_state'] = $owner_info['data']['card_state'];
            //是否赞
            $detail['is_focus'] = $this->is_focus($GLOBALS['user_info']['id'],$id);
        }
        $ex_list = $this->get_goods_exchange_list($id);
//        print_r($detail);
//            print_r($ex_list);
        if($ex_list['error']=='0'){
            //有交换记录
            $GLOBALS['tmpl']->assign("ex",$ex_list['data']);
        }else{
            //没有交换记录，取推荐位,还没接口，等一等
//            $GLOBALS['tmpl']->assign("ex_empty",$ex_list['data']);

        }
        $GLOBALS['tmpl']->assign("detail",$detail);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_old());
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'物品详情');
        $GLOBALS['tmpl']->display("old/detail.html");
    }
    function preview(){
        //物品详情
        $id=$_REQUEST['id'];
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_info&id='.$id;
        if($id){
            $share_info = $this->get_wx_share_info($id);
//            print_r($share_info);
            $GLOBALS['tmpl']->assign('share_info',$share_info);
        }
        $details = json_decode($this->getCurl($url),true);
        if(strval($details['error'])!=='0') {
            $GLOBALS['tmpl']->assign("error", $details['msg']);
        }else{
            $details = $this->get_cover($details);
            $detail = $details['data'];
            //获取用户头像
            $user_id = $detail['user_id'];
            $avatar = get_user_avatar($user_id,'middle');
            $username = $GLOBALS['db']->getOne("select `user_name` from  ".DB_PREFIX."user where id=".$user_id);
            $detail['avatar'] = $avatar;
            $detail['username'] = $username;
            //是否赞
            $detail['is_focus'] = $this->is_focus($GLOBALS['user_info']['id'],$id);
        }
//print_r($detail);
        $GLOBALS['tmpl']->assign("good_list",$detail);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'预览');
        $GLOBALS['tmpl']->display("old/preview.html");
    }
    function success(){
        $goods_id = $_REQUEST['goods_id'];
        $from = es_session::get('from_goods_id');

        if($goods_id){
            $share_info = $this->get_wx_share_info($goods_id);
//            print_r($share_info);
            $GLOBALS['tmpl']->assign('share_info',$share_info);
        }
        echo $from;
        if($from){
            $gopreview = url_wap('old#select',array('from_goods_id'=>$from,'from_user_id'=>es_session::get('from_user_id')));
            es_session::delete('from_goods_id');
        }else{
            /*if($goods_id){
                $gopreview = url_wap('old#detail',array('id'=>$goods_id));
            }else{*/
                $gopreview = url_wap('old');
//            }
        }
        $GLOBALS['tmpl']->assign("page_title",'发布成功');
        $GLOBALS['tmpl']->assign('pre',$gopreview);
        $GLOBALS['tmpl']->display("old/success.html");
    }
    function failed(){
        $msg = urldecode($_REQUEST['msg']);
        $GLOBALS['tmpl']->assign("page_title",'发布失败');
        $GLOBALS['tmpl']->assign("msg",$msg);
        $GLOBALS['tmpl']->display("old/failed.html");
    }
    /**
     * GET-CURL
     * @return array
     */
    function getCurl($url=''){
        try {
            // 1. 初始化
            $ch = curl_init();
            // 2. 设置选项，包括URL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // 3. 执行并获取HTML文档内容
            $output = curl_exec($ch);
            $response = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//            $curl_error_no = curl_errno($ch);

            // 4. 释放curl句柄
            curl_close($ch);

            if($output && !empty($output) && $response == '200'){
                return $output;
            }
            else{
                return false;
            }
        }
        catch(Exception $e){
            return false;
        }
    }

    /*私信*/
    function chat(){
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));
        $dest_user_id = $_REQUEST['id'];
        $url = $this->getUrl().'act=get_message&user_id='.$GLOBALS['user_info']['id'].'&dest_user_id='.$dest_user_id;
        $list = json_decode($this->getCurl($url),true);
        if($list['data']['list']){
            //每隔五条输出一次时间
            foreach($list['data']['list'] as $k=>$v){
                if($k%5==0){
                    $list['data']['list'][$k]['create_time']= date("Y-m-d h:m:s", $v['create_time']);
                }else{
                    $list['data']['list'][$k]['create_time']='';
                }
            }
        }
        $dest_name = $GLOBALS['db']->getOne("select user_name from ".DB_PREFIX."user where id = ".$dest_user_id);
//echo $url;
//        print_r($list);
        $GLOBALS['tmpl']->assign("msg",$list['data']['list']);
        $GLOBALS['tmpl']->assign("page_title",$dest_name);
        $GLOBALS['tmpl']->assign("dest_user_id",$dest_user_id);
        $GLOBALS['tmpl']->assign("dest_user_name",$dest_name);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->display("old/chat.html");
    }
    /*copy from settings.action.php*/
    /*增加地址管理*/
    function consignee(){
        $GLOBALS['tmpl']->assign("page_title","最新动态");
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("olduser#login"));

        $consignee_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_consignee where user_id = ".intval($GLOBALS['user_info']['id']));
        $region_pid = 0;
        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
        $GLOBALS['tmpl']->assign("region_lv2",$region_lv2);


        if($region_pid>0)
        {
            $region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
            $GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
        }
        $GLOBALS['tmpl']->assign("consignee_list",$consignee_list);
        $GLOBALS['tmpl']->display("old/old_consignee.html");
    }
    function edit_consignee(){

        if(!$GLOBALS['user_info']) {app_redirect(url_wap("olduser#login"));} else {
            $id = intval($_REQUEST['id']);
            $title = empty($id)?"新建地址":"编辑";
            $consignee_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_consignee where id = ".$id." and user_id = ".intval($GLOBALS['user_info']['id']));
            $region_pid = 0;
            $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
            foreach($region_lv2 as $k=>$v) {
                if($v['name'] == $consignee_info['province']) {
                    $region_lv2[$k]['selected'] = 1;
                    $region_pid = $region_lv2[$k]['id'];
                    break;
                }
            }
            $GLOBALS['tmpl']->assign("region_lv2",$region_lv2);

            if($region_pid>0) {
                $region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
                foreach($region_lv3 as $k=>$v) {
                    if($v['name'] == $consignee_info['city']) {
                        $region_lv3[$k]['selected'] = 1;
                        break;
                    }
                }
                $GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
            }
            $GLOBALS['tmpl']->assign("item",$consignee_info);
        }
        $GLOBALS['tmpl']->assign("page_title",$title);
        $GLOBALS['tmpl']->display("old/old_edit_consignee.html");
    }

    /*支付*/
    //获取微信支付的二维码
    function jump_wxzf(){
        $notice_id = intval($_REQUEST['id']);
        $notice_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id."   and user_id = ".intval($GLOBALS['user_info']['id']));
        if($notice_info['is_paid']==1)
        {
            $data['pay_status'] = 1;
            $data['pay_info'] = '已支付.';
            $data['show_pay_btn'] = 0;
            $GLOBALS['tmpl']->assign('data',$data);
            $GLOBALS['tmpl']->display('pay_order_index.html');
        }else{
            $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$notice_info['payment_id']);
            $class_name = $payment_info['class_name']."_payment";
            require_once APP_ROOT_PATH."system/payment/".$class_name.".php";
            $o = new $class_name();
            $picUrl = $o->get_payment_codepic($notice_id);
            $GLOBALS['tmpl']->assign('notice_id',$notice_id);
            $GLOBALS['tmpl']->assign('page_title','扫码支付');
            $GLOBALS['tmpl']->assign('picurl',urlencode($picUrl));
//            echo (urlencode($picUrl));
            $GLOBALS['tmpl']->display('old/oldpay_wx_qrpay.html');
        }
    }
    function wx_jspay(){
        $notice_id = intval($_REQUEST['id']);
        $notice_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id."   and user_id = ".intval($GLOBALS['user_info']['id']));
        if($notice_info['is_paid']==1){
            $data['pay_status'] = 1;
            $data['pay_info'] = '已支付';
            $data['deal_id'] = $notice_info['deal_id'];
            $GLOBALS['tmpl']->assign('data',$data);
        }else{
            $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$notice_info['payment_id']);
            $class_name = $payment_info['class_name']."_payment";

            require_once APP_ROOT_PATH."system/payment/".$class_name.".php";
            $o = new $class_name();
            $pay= $o->get_payment_code($notice_id);
            $GLOBALS['tmpl']->assign('jsApiParameters',$pay['parameters']);
            $notice_info['pay_status'] = 0;
            $notice_info['pay_info'] = '未支付';
            $notice_info['show_pay_btn'] = 1;
            $notice_info['deal_id'] = $notice_info['deal_id'];
            $GLOBALS['tmpl']->assign('data',$notice_info);
        }
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign('page_title','微信支付');
        $GLOBALS['tmpl']->display('old/oldpay_wx_jspay.html');

    }
    function getUrl(){
        return get_domain().APP_ROOT."/olddata.php?";
    }
    function is_focus($user_id,$good_id){
        $tmp = $GLOBALS['db']->getOne("select `user_id` from old_goods_focus_log where goods_id=".$good_id." and user_id=".$user_id);
        return $tmp;
    }
    function get_cover($arr){
        foreach($arr as $k=>$v){
            //封面图像
            if(!empty($v['images_data']['list'])){
                foreach($v['images_data']['list'] as $list_k=>$list_v){
                    if($list_v['is_top'] == 1){
                        $arr[$k]['head_image'] = $list_v['url'];
                        $arr[$k]['top_num'] = $list_k+1;
                    }
                }
                if(empty($arr[$k]['head_image'])){
                    $arr[$k]['head_image'] = $v['images_data']['list'][0]['url'];
                    $arr[$k]['top_num'] = 1;
                }
//                print_r($arr[$k]['head_image']);

            }
        }
        return $arr;
    }
    function get_goods_exchange_list($goods_id){
        $goods_id = $_GET['id'];
        $p = 1;
        $pagesize = 6;
        $url = $this->getUrl().'act=get_goods_exchange_list&id='.$goods_id.'&p='.$p.'&pagesize='.$pagesize;
//        echo $url;
        $goods = json_decode($this->getCurl($url),true);
        return $goods;
    }
    function get_last_time($time_s,$time_n){
        $strtime = '';
        $a = $time =$time_s+3600*24 -  $time_n;//先设置24小时过期
        if($time >= 86400){
            return $strtime = date('Y-m-d H:i:s',$time_s);
        }
        if($time >= 3600){
            $strtime .= intval($time/3600).'小时';
            $time = $time % 3600;
        }else{
            $strtime .= '';
        }
        if($time >= 60){
            $strtime .= intval($time/60).'分钟';
            $time = $time % 60;
        }else{
            $strtime .= '';
        }
        if($time > 0){
            $strtime .= intval($time).'秒';
        }else{
            $strtime = "时间错误";
        }
//        print_r(intval($a)/(36*24).'--------');
        return array('time'=>$strtime,'percent'=>100-intval(intval($a)/(36*24)));
    }
    function get_user_consignee($user_id){
        $url = $this->getUrl().'act=get_user_consignee&user_id='.$user_id;
        $list = json_decode($this->getCurl($url),true);
        return $list;
    }
    function get_wx_share_info($goods_id){
        $url = $this->getUrl().'act=get_goods_info&id='.$goods_id;
        $details = json_decode($this->getCurl($url),true);
        $details = $this->get_cover($details);
        $head_image = $details['data']['head_image'];
        $title = $details['data']['name'];
        $info = $details['data']['info'];
        $src = get_domain().url_wap('old#detail',array('id'=>$goods_id));
        $arr = array(
            'src'=>$src,
            'info'=>$info,
            'title'=>$title,
            'head_image'=>$head_image
        );
        return $arr;
    }
}
?>