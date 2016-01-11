<?php

class tpuModule{
	function save(){
		$url = $_GET['referer'];
		es_session::set("gopreview",$url);
		if(!$GLOBALS['user_info']){
			app_redirect(url_wap("user#login"));
		}
		$info_data=array();
		$info_data['user_id'] = $GLOBALS['user_info']['id'];
		$info_data['name'] = $GLOBALS['user_info']['user_name'];
		$info_data['mobile']= $GLOBALS['user_info']['mobile'];
		$info_data['ip'] = get_client_ip();
		$info_data['type'] = $_GET['f'];
		$info_data['creat_time']= time();
		$tmp_zhuanti_user_info = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."zhuanti_user where (mobile='".$info_data['mobile']."' or user_id=".$info_data['user_id'].") and type=2");
		if($tmp_zhuanti_user_info){
			app_redirect("http://www.51zhishang.com/course/54.html");
		}
		$GLOBALS['db']->autoExecute(DB_PREFIX."zhuanti_user",$info_data,"INSERT");
		$msgInfo = array();
		$msgInfo['title'] = '专题报名';
		$msgInfo['content'] = '专题报名';
		send_zhuanti_sms($info_data['mobile'],$msgInfo,3812);
		$data['status'] = 1;
		$data['info'] = "报名成功！";
		app_redirect("http://www.51zhishang.com/course/54.html");
	}
}
?>