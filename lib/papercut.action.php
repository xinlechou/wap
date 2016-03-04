<?php

class papercutModule{	
	function index(){
		$num = rand(1,4);
		$GLOBALS['tmpl']->assign("num",$num);
    $GLOBALS['tmpl']->display("papercut/html/start.html");
	}
	
	
	function go(){
		$p = $_GET['p'];
		if(empty($p)){
			$p = 1;
		}
		$num = $_GET['num'];
		$pageArray = array();
		if($p > 1){
			$pageArray['up'] = $p - 1;
		}else{
			$pageArray['up'] = '';
		}
		if($p < 4){
			$pageArray['down'] = $p + 1;
		}else{
			$pageArray['down'] = '';
		}
		$GLOBALS['tmpl']->assign("pageArray",$pageArray);
		$GLOBALS['tmpl']->assign("num",$num);
    $GLOBALS['tmpl']->display("papercut/html/go_".$p.".html");
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
		
		$info_data=array();
		$info_data['image'] = $tmp_thumb_pic_1;
		$info_data['mould'] = $_GET['m'];
		$info_data['like_num'] = rand(90,100);
		$info_data['create_time']= time();
		$GLOBALS['db']->autoExecute("h5_papercut",$info_data,"INSERT");
		$papercut_id = $GLOBALS['db']->insert_id();
		
		$data['status'] = 1;
		$data['papercut_id'] = $papercut_id;
		ajax_return($data);
	}
	
	function share(){
		$papercut_id = $_GET['id'];
		$tmp_papercut_info = $GLOBALS['db']->getRow("select * from h5_papercut where id=".$papercut_id);
		if($tmp_papercut_info){
			$like_str = '';
			if($tmp_papercut_info['like_num']>90 && $tmp_papercut_info['like_num']<=92){
				$like_str = '才华小露';
			}elseif($tmp_papercut_info['like_num']>92 && $tmp_papercut_info['like_num']<=94){
				$like_str = '您有一双巧手';
			}elseif($tmp_papercut_info['like_num']>94 && $tmp_papercut_info['like_num']<=96){
				$like_str = '才华横溢';	
			}elseif($tmp_papercut_info['like_num']>96 && $tmp_papercut_info['like_num']<=98){
				$like_str = '天赋异禀';	
			}elseif($tmp_papercut_info['like_num']>98 && $tmp_papercut_info['like_num']<=99){
				$like_str = '巧夺天工';	
			}elseif($tmp_papercut_info['like_num']==100){
				$like_str = '两个字“完美”~';	
			}
			$GLOBALS['tmpl']->assign("like_str",$like_str);
			$GLOBALS['tmpl']->assign("papercut_info",$tmp_papercut_info);
		}else{
			app_redirect(url_wap("papercut#index"));
		}
		require_once APP_ROOT_PATH."system/utils/jssdk.php";
		$jssdk = new JSSDK('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a');
		$signPackage = $jssdk->GetSignPackage();
		$GLOBALS['tmpl']->assign("signPackage",$signPackage);
    $GLOBALS['tmpl']->display("papercut/html/share.html");
	}
	
}
?>