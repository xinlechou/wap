<?php

class similarModule{	
	function index(){
		require_once APP_ROOT_PATH.'system/utils/weixin.php';
		$is_weixin=isWeixin();
		if(!$is_weixin){
			echo '必须使用微信打开此页！';exit;
		}
		if($_REQUEST['code']){
			$weixin=new weixin('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a',"http://www.xinlechou.com/wap/index.php?ctl=similar&act=index");
			$wx_info=$weixin->scope_get_userinfo($_REQUEST['code']);
			es_session::set('wx_info',$wx_info);
		}else{
			$weixin_2=new weixin('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a',"http://www.xinlechou.com/wap/index.php?ctl=similar&act=index");
			$wx_url=$weixin_2->scope_get_code();
			app_redirect($wx_url);
		}
    $GLOBALS['tmpl']->display("similar/html/start.html");
	}
	
	
	function parents(){
		$id = $_POST['id'];
		$tmp_similar_info = $GLOBALS['db']->getRow("select * from h5_similar where id=".$id);
		if($tmp_similar_info){
			$GLOBALS['tmpl']->assign("similar_info",$tmp_similar_info);
		}else{
			app_redirect(url_wap("similar#index"));
		}
		$GLOBALS['tmpl']->assign("num",$num);
    $GLOBALS['tmpl']->display("similar/html/parent.html");
	}
	
	
	function uploadimg()
	{
		//上传处理
		//创建avatar临时目录
		if (!is_dir(APP_ROOT_PATH."uploads/h5image")) { 
         @mkdir(APP_ROOT_PATH."uploads/h5image");
         @chmod(APP_ROOT_PATH."uploads/h5image", 0777);
    }
		if (!is_dir(APP_ROOT_PATH."uploads/h5image/temp")) { 
         @mkdir(APP_ROOT_PATH."uploads/h5image/temp");
         @chmod(APP_ROOT_PATH."uploads/h5image/temp", 0777);
    }
    $fileName = time();
		$target_path  = APP_ROOT_PATH."uploads/h5image/temp/";//接收文件目录
		$target_path = $target_path . $fileName.'.jpg';
		$file_url = "uploads/h5image/temp/".$fileName.'.jpg';
		$img_result = save_image_upload($_FILES,"h5img_file","h5image/temp",$whs=array());
		@file_put_contents($target_path, file_get_contents($img_result['h5img_file']['path']));
		@unlink($img_result['h5img_file']['path']);
		$tmp_thumb_pic_1 = '/'.get_spec_image($file_url,200,200,1);
		$id = $_GET['id'];
		if(!empty($id)){
			$info_data=array();
			$info_data['parent_image'] = $tmp_thumb_pic_1;
			$GLOBALS['db']->autoExecute("h5_similar",$info_data,"UPDATE","id=".$id);
			$similar_id = $id;
		}else{
			$weixin_info = es_session::get('wx_info');
			$info_data=array();
			$info_data['name'] = $weixin_info['nickname'];
			$info_data['image'] = $tmp_thumb_pic_1;
			$info_data['like_num'] = rand(70,100);
			$info_data['create_time']= time();
			$GLOBALS['db']->autoExecute("h5_similar",$info_data,"INSERT");
			$similar_id = $GLOBALS['db']->insert_id();
		}
		
		$data['status'] = 1;
		$data['similar_id'] = $similar_id;
		$data['img_url'] = $tmp_thumb_pic_1;
		ajax_return($data);
	}
	
	function share(){
		$id = $_POST['id'];
		$tmp_similar_info = $GLOBALS['db']->getRow("select * from h5_similar where id=".$id);
		if($tmp_similar_info){
			$GLOBALS['tmpl']->assign("similar_info",$tmp_similar_info);
		}else{
			app_redirect(url_wap("similar#index"));
		}
		require_once APP_ROOT_PATH."system/utils/jssdk.php";
		$jssdk = new JSSDK('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a');
		$signPackage = $jssdk->GetSignPackage();
		$GLOBALS['tmpl']->assign("signPackage",$signPackage);
    $GLOBALS['tmpl']->display("similar/html/share.html");
	}
	
	
	function result(){
		if($_POST){
			$id = $_POST['id'];
			$info_data=array();
			$info_data['info'] = $_POST['info'];
			$GLOBALS['db']->autoExecute("h5_similar",$info_data,"UPDATE","id=".$id);
		}else{
			$id = $_GET['id'];
		}
		$tmp_similar_info = $GLOBALS['db']->getRow("select * from h5_similar where id=".$id);
		if($tmp_similar_info){
			$GLOBALS['tmpl']->assign("similar_info",$tmp_similar_info);
		}else{
			app_redirect(url_wap("similar#index"));
		}
    $GLOBALS['tmpl']->display("similar/html/result.html");
	}
	
}
?>