<?php

class drawModule{
	function save(){
		$url = 'http://www.xinlechou.com/index.php?ctl=draw&act=save';
		es_session::set("gopreview",$url);
		if(!$GLOBALS['user_info']){
			app_redirect(url_wap("user#login"));
		}
		if($_POST){
			$info_data=array();
			$info_data['user_id'] = $GLOBALS['user_info']['id'];
			$info_data['name'] = $GLOBALS['user_info']['user_name'];
			$info_data['mobile']= $GLOBALS['user_info']['mobile'];
			$info_data['hope'] = $_POST['hope'];
			$info_data['create_time']= time();
			$tmp_hopes_user_info = $GLOBALS['db']->getRow("select * from user_hopes where (mobile='".$info_data['mobile']."' or user_id=".$info_data['user_id'].") limit 0,1");
			if($tmp_hopes_user_info){
				app_redirect(url_wap("draw#get",array("id"=>$tmp_hopes_user_info['id'])));
			}else{
				$GLOBALS['db']->autoExecute("user_hopes",$info_data,"INSERT");
				$hopes_id = $GLOBALS['db']->insert_id();
				app_redirect(url_wap("draw#get", array("id"=>$hopes_id)));
			}

		}else{
      $GLOBALS['tmpl']->display("draw/html/user_hopes.html");
		}
	}
	
	
	function get(){
		$hopes_id = $_GET['id'];
		$share = $_GET['share'];
		$tmp_send_hopes_info = $GLOBALS['db']->getRow("select * from user_hopes where id=".$hopes_id);
		if($tmp_send_hopes_info){
			$GLOBALS['tmpl']->assign("hope_id",$hopes_id);
			if(($tmp_send_hopes_info['is_send']==1&&$tmp_send_hopes_info['is_share']==1)||($tmp_send_hopes_info['is_send']==1&&empty($share))){
				$GLOBALS['tmpl']->assign("is_send",1);
				$GLOBALS['tmpl']->assign("is_share",1);
			}else{
				$GLOBALS['db']->query("UPDATE user_hopes SET `is_send` = 1 where id = ".intval($hopes_id)."");
				if(!empty($share)){
					$GLOBALS['db']->query("UPDATE user_hopes SET `is_share` = 1 where id = ".intval($hopes_id)."");
				}
				$region_pid = 0;
				$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
				foreach($region_lv2 as $k=>$v)
				{
					if($v['name'] == $consignee_info['province'])
					{
						$region_lv2[$k]['selected'] = 1;
						$region_pid = $region_lv2[$k]['id'];
						break;
					}
				}
				$GLOBALS['tmpl']->assign("region_lv2",$region_lv2);
				
				
				if($region_pid>0)
				{
					$region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
					foreach($region_lv3 as $k=>$v)
					{
						if($v['name'] == $consignee_info['city'])
						{
							$region_lv3[$k]['selected'] = 1;
							break;
						}
					}
					$GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
				}
				$dram_data = $this->get_dram_data();
				if($dram_data['partners']['type']==3||$dram_data['partners']['type']==5){				
					$info_data=array();
					$info_data['user_id'] = $GLOBALS['user_info']['id'];
					$info_data['hope_id'] = $tmp_send_hopes_info['hope_id'];
					$info_data['prize_id'] = $dram_data['partners']['id'];
					$info_data['consignee'] = '';
					$info_data['province'] = '';
					$info_data['city'] = '';
					$info_data['address'] = '';
					$info_data['mobile'] = '';
					$info_data['zip'] = '';
					$info_data['email'] = '';
					$info_data['wechat'] = '';
					$info_data['create_time']= time();
					$GLOBALS['db']->autoExecute("winning",$info_data,"INSERT");
					$winning_id = $GLOBALS['db']->insert_id();
				}
				$GLOBALS['tmpl']->assign("dram_data",$dram_data);
				$GLOBALS['tmpl']->assign("is_send",$tmp_send_hopes_info['is_send']);
				$GLOBALS['tmpl']->assign("is_share",$tmp_send_hopes_info['is_share']);
			}
			$winningList = $GLOBALS['db']->getAll("select a.hope_id,a.prize_id,b.name,c.prize from winning as a,user_hopes as b, partners as c where a.hope_id=b.id and a.prize_id=c.id");
			$GLOBALS['tmpl']->assign("winningList",$winningList);
			require_once APP_ROOT_PATH."system/utils/jssdk.php";
			$jssdk = new JSSDK('wxd0efb676fbe5a8ed','cbe473ad7b19f99432a4a162ab772b5a');
			$signPackage = $jssdk->GetSignPackage();
			$GLOBALS['tmpl']->assign("signPackage",$signPackage);
			$GLOBALS['tmpl']->display("draw/html/user_hopes_draw.html");
		}else{
			app_redirect(url_wap("index"));
		}
	}
	
	
	function winning(){
		if(!$GLOBALS['user_info']){
			app_redirect(url_wap("user#login"));
		}
		if($_POST){
			$prize_type = $_POST['prize_type'];
			if($prize_type!=4){
				$GLOBALS['db']->query("UPDATE partners SET `remainder` = `remainder`-1 where id = ".intval($_POST['prize_id'])."");
			}
			$info_data=array();
			$info_data['user_id'] = $GLOBALS['user_info']['id'];
			$info_data['hope_id'] = $_POST['hope_id'];
			$info_data['prize_id'] = $_POST['prize_id'];
			$info_data['consignee'] = $_POST['consignee'];
			$info_data['province'] = $_POST['province'];
			$info_data['city'] = $_POST['city'];
			$info_data['address'] = $_POST['address'];
			$info_data['mobile'] = $_POST['mobile'];
			$info_data['zip'] = $_POST['zip'];
			$info_data['email'] = $_POST['email'];
			$info_data['wechat'] = $_POST['wechat'];
			$info_data['create_time']= time();
			$GLOBALS['db']->autoExecute("winning",$info_data,"INSERT");
			$winning_id = $GLOBALS['db']->insert_id();
			app_redirect(url_wap("draw#get", array("id"=>$info_data['hope_id'])));
		}else{
      app_redirect(url_wap("index"));
		}
	}
	
	
	
	
	function get_dram_data(){
		$tmp_num = rand(1,1000);
		if($tmp_num < 10){
			//二等奖中奖几率0.3%
			$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=2");
			if($allNum > 0){
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=2 limit ".$draw_num.",1");
				$angle = 157;
			}else{
				$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=5");
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=5 limit ".$draw_num.",1");
				$angle = 267;
			}
		}elseif($tmp_num < 100 && $tmp_num >= 10){
			//三等奖中奖几率1%
			$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=3");
			if($allNum > 0){
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=3 limit ".$draw_num.",1");
				$angle = 103;
			}else{
				$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=5");
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=5 limit ".$draw_num.",1");
				$angle = 267;
			}
		}elseif($tmp_num < 300 && $tmp_num >= 100){
			//四等奖中奖几率16%
			$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=4");
			if($allNum > 0){
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=4 limit ".$draw_num.",1");
				$angle = 22;
			}else{
				$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=5");
				$draw_num = rand(0,$allNum-1);
				$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=5 limit ".$draw_num.",1");
				$angle = 267;
			}
		}else{
			//幸运奖中奖几率82.7%
			$allNum = $GLOBALS['db']->getOne("select count(*) from partners where remainder>0 and lvl=5");
			$draw_num = rand(0,$allNum-1);
			$partners = $GLOBALS['db']->getRow("select * from partners where remainder>0 and lvl=5 limit ".$draw_num.",1");
			$angle = 267;
		}
		if($partners['type']==3||$partners['type']==4||$partners['type']==5){
			$GLOBALS['db']->query("UPDATE partners SET `remainder` = `remainder`-1 where id = ".intval($partners['id'])."");
		}
		if($partners['id']==3){
			$codeInfo = $GLOBALS['db']->getRow("select * from partners_code where is_used=0 limit 0,1");
			$GLOBALS['db']->query("UPDATE partners_code SET `is_used` = 1 where id = ".intval($codeInfo['id'])."");
			$tmp_info = str_replace("{@code}", $codeInfo['code'], $partners['info']);
			$partners['info'] = $tmp_info;
		}
		$dataarray = array();
		$dataarray['angle'] = $angle;
		$dataarray['partners'] = $partners;
		return $dataarray;
	}
	
	
}
?>