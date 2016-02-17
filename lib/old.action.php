<?php

class oldModule{
	function index(){
        $p = $_REQUEST['p']?$_REQUEST['p']:"1";
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_list&p='.$p;
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
                        }else{
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }
                    }
                }else{
                    $goods['data'][$k]['head_image'] = 'http://localhost:8888/htdocs/wap/tpl/default/old/images/list2.png';//test
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
//        print_r($goods['data']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods['data']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'Ta旧');
        $GLOBALS['tmpl']->display("old/old_index.html");
	}
    function home(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        print_r($GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'管理');
        $GLOBALS['tmpl']->display("old/home.html");
    }
    function home_goods(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        print_r($GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'我的物品');
        $GLOBALS['tmpl']->display("old/home_goods.html");
    }
    function home_exchange(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        print_r($GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'交换申请');
        $GLOBALS['tmpl']->display("old/home_exchange.html");
    }
    function release(){
        //发布物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        print_r($GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'发布物品');
        $GLOBALS['tmpl']->display("old/release.html");
    }
    function bind(){
        //实名认证
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        $GLOBALS['tmpl']->assign("page_title","实名认证");

        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->display("old/verified.html");
    }
    function detail(){
        //物品详情
        $id=$_REQUEST['id'];
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_info&id='.$id;
        $details = json_decode($this->getCurl($url),true);
        if(strval($details['error'])!=='0') {
            $GLOBALS['tmpl']->assign("error", $details['msg']);
        }else{
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
//            print_r($detail);
        $GLOBALS['tmpl']->assign("detail",$detail);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'物品详情');
        $GLOBALS['tmpl']->display("old/detail.html");
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
    function getUrl(){
        return get_domain().APP_ROOT."/olddata.php?";
    }
    function is_focus($user_id,$good_id){
        $tmp = $GLOBALS['db']->getOne("select `user_id` from old_goods_focus_log where goods_id=".$good_id);
        return $tmp==$user_id;
    }
}
?>