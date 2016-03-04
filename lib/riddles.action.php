<?php

class riddlesModule{
	function index(){
		$id = $_GET['id'];
		$num = 1;
		require_once APP_ROOT_PATH.'system/utils/weixin.php';
		$is_weixin=isWeixin();
		if(!$is_weixin){
			echo '必须使用微信打开此页！';exit;
		}
		$weixin_info = es_session::get('wx_info');
		if(!$weixin_info){
			if($_REQUEST['code']){
				$weixin=new weixin('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a',"http://www.xinlechou.com/wap/index.php?ctl=riddles&act=index");
				$wx_info=$weixin->scope_get_userinfo($_REQUEST['code']);
				es_session::set('wx_info',$wx_info);
			}else{
				$weixin_2=new weixin('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a',"http://www.xinlechou.com/wap/index.php?ctl=riddles&act=index");
				$wx_url=$weixin_2->scope_get_code();
				app_redirect($wx_url);
			}
		}
		$riddleArray = array();
		for($i=1;$i<21;$i++){
			$riddleArray[$i] = $i;
		}
		if($id){
			$tmp_riddles_info = $GLOBALS['db']->getRow("select * from h5_riddles_data where id=".$id);
			if($tmp_riddles_info){
				$problemsArray = explode(",",$tmp_riddles_info['problems']);
				$num = count($problemsArray)+1;
				foreach($problemsArray as $pv){
					unset($riddleArray[$pv]);
				}
				$GLOBALS['tmpl']->assign("tmp_riddles_info",$tmp_riddles_info);
			}
		}
		$randNum = array_rand($riddleArray,1);
		$tmp_riddle = $GLOBALS['db']->getRow("select * from h5_riddles where id=".$randNum);
		$GLOBALS['tmpl']->assign("num",$num);
		$GLOBALS['tmpl']->assign("tmp_riddle",$tmp_riddle);
    $GLOBALS['tmpl']->display("riddles/html/content.html");
	}
	
	
	function go_next()
	{
		$id = $_GET['id'];
		$riddle = $_GET['riddle'];
		$answer = $_GET['answer'];
		$data = array();
		$tmp_riddle = $GLOBALS['db']->getRow("select * from h5_riddles where id=".$riddle);
		if($tmp_riddle['answer']==$answer){
			$data['status'] = 1;
		}else{
			$data['status'] = -1;
		}
		if($id!=''){
			$tmp_riddle_data = $GLOBALS['db']->getRow("select * from h5_riddles_data where id=".$id);
			$info_data=array();
			if($data['status']==1){
				$info_data['problems'] = $tmp_riddle_data['problems'].','.$riddle;
				$info_data['right_num'] = $tmp_riddle_data['right_num']+1;
				$GLOBALS['db']->autoExecute("h5_riddles_data",$info_data,"UPDATE","id=".$id);
				$data['num'] = $tmp_riddle_data['right_num']+1;
				$data['msg'] = '回答正确';
			}else{
				$data['msg'] = '回答错误';
			}
			$riddles_id = $id;
		}else{
			$weixin_info = es_session::get('wx_info');
			$info_data=array();
			$info_data['name'] = $weixin_info['nickname'];
			if($data['status']==1){
				$info_data['problems'] = $riddle;
				$info_data['right_num'] = 1;
				$data['num'] = 1;
			}else{
				$info_data['problems'] = "";
				$info_data['right_num'] = 0;
				$data['num'] = 0;
			}
			$info_data['create_time']= time();
			$GLOBALS['db']->autoExecute("h5_riddles_data",$info_data);
			$riddles_id = $GLOBALS['db']->insert_id();
			$data['msg'] = '回答错误';
		}
		$data['riddles_id'] = $riddles_id;
		ajax_return($data);
	}
	
	function result(){
		$id = $_GET['id'];
		$tmp_riddles_info = $GLOBALS['db']->getRow("select * from h5_riddles_data where id=".$id);
		if($tmp_riddles_info){
			$weixin_info = es_session::get('wx_info');
			if($weixin_info){
				if($weixin_info['nickname']==$tmp_riddles_info['name']){
					$is_new = 0;
				}else{
					$is_new = 1;
				}
			}else{
				$is_new = 1;
			}
			if($tmp_riddles_info['right_num']==0){
				$show = "人有失手，马有失蹄";
				$bg_img = "fail";
				$img_show = 0;
			}elseif($tmp_riddles_info['right_num'] > 0 && $tmp_riddles_info['right_num'] < 4){
				$show = "步入秀才行列";
				$bg_img = "xiucai";
				$img_show = 1;
			}elseif($tmp_riddles_info['right_num'] > 3 && $tmp_riddles_info['right_num'] < 7){
				$show = "被进士录取";
				$bg_img = "jinshi";
				$img_show = 1;
			}elseif($tmp_riddles_info['right_num'] > 6 && $tmp_riddles_info['right_num'] < 9){
				$show = "金榜题名，探花郎";
				$bg_img = "tanhua";
				$img_show = 1;
			}elseif($tmp_riddles_info['right_num'] == 9){
				$show = "距状元只有一关的距离";
				$bg_img = "steap";
				$img_show = 1;
			}elseif($tmp_riddles_info['right_num'] == 10){
				$show = "通关：灯谜争霸状元";
				$bg_img = "tongguan";
				$img_show = 0;
			}
			$GLOBALS['tmpl']->assign("is_new",$is_new);
			$GLOBALS['tmpl']->assign("img_show",$img_show);
			$GLOBALS['tmpl']->assign("show",$show);
			$GLOBALS['tmpl']->assign("bg_img",$bg_img);
			$GLOBALS['tmpl']->assign("riddles_info",$tmp_riddles_info);
		}else{
			app_redirect(url_wap("riddles#index"));
		}
		require_once APP_ROOT_PATH."system/utils/jssdk.php";
		$jssdk = new JSSDK('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a');
		$signPackage = $jssdk->GetSignPackage();
		$GLOBALS['tmpl']->assign("signPackage",$signPackage);
    $GLOBALS['tmpl']->display("riddles/html/result.html");
	}
	
	
}
?>