<?php
class oldsettingsModule{
	public function index()
	{	
		$GLOBALS['tmpl']->assign("page_title","个人中心");
		if(!$GLOBALS['user_info'])
		app_redirect(url_wap("olduser#login"));
		$level_name="";
		 if($GLOBALS['user_info']['user_level']){
		 	$level_name=$GLOBALS['db']->getOne("select name from ".DB_PREFIX."user_level where id=".$GLOBALS['user_info']['user_level']);
		 }
        /*是否为爱钱进用户*/
        $aqj_user=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_partner_user where user_id=".$GLOBALS['user_info']['id']);
        if($aqj_user){
            $GLOBALS['tmpl']->assign("is_ap",1);
            require_once APP_ROOT_PATH."system/payment/Appay_payment.php";
						$o = new Appay_payment();
						//获取用户积分
						$user_ap = $o->get_user_ap($GLOBALS['user_info']['id']);
            $GLOBALS['tmpl']->assign("ap",$user_ap);
        }
//        print_r($GLOBALS['user_info']);
		$GLOBALS['tmpl']->assign("userinfo",$GLOBALS['user_info']);
		$GLOBALS['tmpl']->assign("level_name",$level_name);

		$GLOBALS['tmpl']->display("old/oldsettings_index.html");
	}
	//修改资料（展示）
	public function modify()
	{	
		$GLOBALS['tmpl']->assign("page_title","最新动态");
		if(!$GLOBALS['user_info'])
		app_redirect(url_wap("olduser#login"));
		$region_pid = 0;
		$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
		foreach($region_lv2 as $k=>$v)
		{
			if($v['name'] == $GLOBALS['user_info']['province'])
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
				if($v['name'] == $GLOBALS['user_info']['city'])
				{
					$region_lv3[$k]['selected'] = 1;
					break;
				}
			}
			$GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
		}
		$weibo_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_weibo where user_id = ".intval($GLOBALS['user_info']['id']));
		//var_dump($weibo_list);
		$GLOBALS['tmpl']->assign("weibo_list",$weibo_list);
		
		$GLOBALS['tmpl']->display("old/oldsettings_modify.html");
	}
	//修改资料（保存）
	public function save_modify()
	{
		//$ajax = intval($_REQUEST['ajax']);
		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("olduser#login"));
		}
		$user_data = array();
		if(!check_ipop_limit(get_client_ip(),"setting_save_index",5)){
			$user_data['info']="提交太频繁";
			ajax_return($user_data);
			return false;
		}
		
		require_once APP_ROOT_PATH."system/libs/user.php";

		$user_data['province'] = strim($_REQUEST['province']);
		$user_data['city'] = strim($_REQUEST['city']);
		$user_data['sex'] = intval($_REQUEST['sex']);
		$user_data['intro'] = strim($_REQUEST['intro']);
        $user_data['email'] = strim($_REQUEST['email']);
        //增加昵称修改 20160121

        if(!empty($_REQUEST['user_name'])){
            $user_data['user_name'] = strim($_REQUEST['user_name']);
            $user_data['has_set_name'] = 1;
            $num=$GLOBALS['db']->getOne("select id from  ".DB_PREFIX."user where user_name='".$user_data['user_name']."'");
            if($num>0&&($GLOBALS['user_info']['id']!==$num)){
                showErr("昵称已存在！",1,"");
            }
        }
		
		if(strim($_REQUEST['mobile'])){
			$user_data['mobile'] = strim($_REQUEST['mobile']);
			$num=$GLOBALS['db']->getOne("select count(*) from  ".DB_PREFIX."user where mobile='".$user_data['mobile']."' and id!=".$GLOBALS['user_info']['id']);
			if($num>0){
				//showErr("手机已经绑定其他账号,请输入新的手机号",$ajax,"");
				$user_data['info']="手机已经绑定其他账号,请输入新的手机号";
				ajax_return($user_data);
				return false;
			}
            /*不校验验证码*/
			/*$has_code=$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile='".$user_data['mobile']."' and verify_code='".strim($_REQUEST['verify_coder'])."' ");
			if(!$has_code){
				//showErr("验证码错误",$ajax,"");
				$user_data['info']="验证码错误";
				ajax_return($user_data);
				return false;
			}*/
		}
		
		$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user_data,"UPDATE","id=".intval($GLOBALS['user_info']['id']));
		
		$GLOBALS['db']->query("delete from ".DB_PREFIX."user_weibo where user_id = ".intval($GLOBALS['user_info']['id']));
	
		$weibo_data = array();
		$weibo_data['user_id'] = intval($GLOBALS['user_info']['id']);
		$weibo_data['weibo_url'] = strim($_REQUEST['weibo_url']);
		$GLOBALS['db']->autoExecute(DB_PREFIX."user_weibo",$weibo_data);
		$user_data['info']="资料保存成功";
		ajax_return($user_data);
		//showSuccess("资料保存成功",$ajax,url('settings#index'));
		//$res = save_user($user_data);
	}
	
	public function add_consignee()
	{

		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("olduser#login"));
		}
		else
		{
			$id = intval($_REQUEST['id']);
 			if($id>0){
				$consignee_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_consignee where id = ".$id." and user_id = ".intval($GLOBALS['user_info']['id']));
				if(!$consignee_info){
					app_redirect(url_wap("old#consignee"));
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
							
				$GLOBALS['tmpl']->assign("consignee_info",$consignee_info);
			}else{
				$GLOBALS['tmpl']->caching = true;
				$cache_id  = md5(MODULE_NAME.ACTION_NAME);		
				if (!$GLOBALS['tmpl']->is_cached('inc/add_consignee.html', $cache_id))
				{		
					$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
					$GLOBALS['tmpl']->assign("region_lv2",$region_lv2);
				}
			}
						
			$GLOBALS['tmpl']->display("add_consignee.html",$cache_id);			
			//$data['status'] = 1;
		}
		//ajax_return($data);
	}
	
	public function save_consignee()
	{		
		$ajax = intval($_REQUEST['ajax']);
 		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("olduser#login"));
		}
		
		if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user_consignee where user_id = ".intval($GLOBALS['user_info']['id']))>10)
		{
			showErr("每个会员只能预设10个配送地址",$ajax,"");
		}
		
		$id = intval($_REQUEST['id']);
		$consignee = strim($_REQUEST['consignee']);
		$province = strim($_REQUEST['province']);
		$city = strim($_REQUEST['city']);
		$address = strim($_REQUEST['address']);
		$zip = strim($_REQUEST['zip']);
		$mobile = strim($_REQUEST['mobile']);
		if($consignee=="")
		{
			showErr("请填写收货人姓名",$ajax,"");	
		}
		if($province=="")
		{
			showErr("请选择省份",$ajax,"");	
		}
		if($city=="")
		{
			showErr("请选择城市",$ajax,"");	
		}
		if($address=="")
		{
			showErr("请填写详细地址",$ajax,"");	
		}
		if(!check_postcode($zip))
		{
			showErr("请填写正确的邮编",$ajax,"");	
		}
		if($mobile=="")
		{
			showErr("请填写收货人手机号码",$ajax,"");	
		}
		if(!check_mobile($mobile))
		{
			showErr("请填写正确的手机号码",$ajax,"");	
		}
		
		$data = array();
		$data['consignee'] = $consignee;
		$data['province'] = $province;
		$data['city'] = $city;
		$data['address'] = $address;
		$data['zip'] = $zip;
		$data['mobile'] = $mobile;
		$data['user_id'] = intval($GLOBALS['user_info']['id']);
		
		
		
		if(!check_ipop_limit(get_client_ip(),"setting_save_consignee",5)){
			showErr("提交太频繁",$ajax,"");exit;
		}
		
		
		if($id>0)
		$GLOBALS['db']->autoExecute(DB_PREFIX."user_consignee",$data,"UPDATE","id=".$id);
		else
		$GLOBALS['db']->autoExecute(DB_PREFIX."user_consignee",$data);
		
		showSuccess("保存成功",$ajax,get_gopreview_wap());
		//$res = save_user($user_data);
	}
	public function password()
	{
		if(intval($_REQUEST['code'])!=0)
		{
			$uid = intval($_REQUEST['id']);
			$code = intval($_REQUEST['code']); 
			$GLOBALS['user_info'] = $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$uid." and password_verify = '".$code."' and is_effect = 1");
			if($user_info) {
				es_session::set("user_info",$user_info);
				$GLOBALS['tmpl']->assign("user_info",$user_info);
			} else
			{
				app_redirect(url_wap("old"));
			}
		} else if(!$GLOBALS['user_info'])
		app_redirect(url_wap("olduser#login"));
		if(app_conf("USER_VERIFY")==2){//问问看是否要做2个
            $GLOBALS['tmpl']->assign("page_title",'修改密码');

            $GLOBALS['tmpl']->display("old/oldsettings_mobile_password.html");
		}else{
			$GLOBALS['tmpl']->display("old/settings_password.html");
		}
		
	}

    /*增加地址管理*/
    public function consignee()
	{
 		$GLOBALS['tmpl']->assign("page_title","最新动态");
		if(!$GLOBALS['user_info'])
		app_redirect(url_wap("user#login"));

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
		$GLOBALS['tmpl']->display("settings_consignee.html");
	}
	public function edit_consignee()
	{

		if(!$GLOBALS['user_info']) {app_redirect(url_wap("user#login"));} else {
			$id = intval($_REQUEST['id']);
            $title = empty($id)?"新建地址":"编辑收货地址";
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
        $GLOBALS['tmpl']->assign("title",$title);
        $GLOBALS['tmpl']->display("settings_edit_consignee.html");
	}
	public function del_consignee()
	{
		if(!$GLOBALS['user_info'])
		{
			$data['html'] = $GLOBALS['tmpl']->display("inc/user_login_box.html","",true);			
			$data['status'] = 1;
			ajax_return($data);
		}
		else
		{
			$id = intval($_REQUEST['id']);
			$data=array('status'=>1,'info'=>'删除成功','jump'=>url_wap("settings#consignee"));
			$re=$GLOBALS['db']->query("delete from ".DB_PREFIX."user_consignee where id = ".$id." and user_id = ".intval($GLOBALS['user_info']['id']));
			if(!$re){
				$data['status']=0;
				$data['info']="删除失败";
				$data['jump']="";
			}
			ajax_return($data);
		}
	}
    //设为默认地址
    public function set_default_consignee()
    {
        /*$id = intval($_REQUEST['id']);
        //新地址设为默认地址时将其它地址更新为非默认地址
        $GLOBALS['db']->query("UPDATE ".DB_PREFIX."user_consignee SET is_default = 0 where (user_id = ".intval($GLOBALS['user_info']['id'])." and is_default = 1)");
        $data['is_default'] = 1;
        $GLOBALS['db']->autoExecute(DB_PREFIX."user_consignee",$data,"UPDATE","id=".$id);
        ajax_return(array('status'=>1));*/
        $id = intval($_REQUEST['id']);
        if($GLOBALS['db']->getOne("select count(*) from  ".DB_PREFIX."user_consignee where id=$id")>0) {
            //新地址设为默认地址时将其它地址更新为非默认地址
            $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "user_consignee SET is_default = 0 where (user_id = " . intval($GLOBALS['user_info']['id']) . " and is_default = 1)");
            $consignee['is_default'] = 1;
            $GLOBALS['db']->autoExecute(DB_PREFIX . "user_consignee", $consignee, "UPDATE", "id=" . $id);
            if($GLOBALS['db']->affected_rows()){
                $data=array('status'=>1,'info'=>'设置成功');
            }else{
                $data['status']=2;//表示更新数据失败，让用户重新提交
                $data['info']="设置失败,请重新设置";
            }

        }else{
            $data=array('status'=>0,'info'=>'没有该地址');
        }
        ajax_return($data);
    }
	
	public function bank()
	{
		if(!$GLOBALS['user_info'])
		app_redirect(url_wap("user#login"));
		$GLOBALS['tmpl']->assign("save_bank_url",url_wap("settings#save_bank"));
		$GLOBALS['tmpl']->display("settings_bank.html");
	}
	
	public function save_bank()
	{
		$ajax = intval($_REQUEST['ajax']);
	
		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("user#login"));
		}
	
		$ex_real_name = strim($_REQUEST['ex_real_name']);
		$ex_account_info = strim($_REQUEST['ex_account_info']);
		$ex_account_bank = strim($_REQUEST['ex_account_bank']);
		$ex_contact = strim($_REQUEST['ex_contact']);
		$ex_qq = strim($_REQUEST['ex_qq']);
		
		$data =array();
		if($ex_real_name=="")
		{
		    $data['info']="请填写姓名";
		    ajax_return($data);
		    return false;
		}
		if($ex_account_bank=="")
		{
		    $data['info']="请填写开户银行";
		    ajax_return($data);
		    return false;
		}
		if($ex_account_info=="")
		{
		    $data['info']="请填写银行帐号";
		    ajax_return($data);
		    return false;
		}
		if($ex_contact=="")
		{
		    $data['info']="请填写联系电话";
		    ajax_return($data);
		    return false;
		}
		
		if($ex_qq=="")
		{
		    $data['info']="请填写联系qq";
		    ajax_return($data);
		    return false;
		}
		
		if($GLOBALS['db']->query("update ".DB_PREFIX."user set ex_qq = '".$ex_qq."',ex_account_bank = '".$ex_account_bank."',ex_real_name = '".$ex_real_name."',ex_account_info = '".$ex_account_info."',ex_contact = '".$ex_contact."',is_bank = '".'1'."' where id = ".intval($GLOBALS['user_info']['id'])))
		{
			$data['status'] =1;
		}else{
			$data['status'] =0;
		}
		ajax_return($data);
	}
	
	public function save_password()
	{
		$ajax = intval($_REQUEST['ajax']);
		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("user#login"));
		}
	
		if(!check_ipop_limit(get_client_ip(),"setting_save_password",5))
			showErr("提交太频繁",$ajax,"");
		$user_old_pwd=strim($_REQUEST['user_old_pwd']);
		$user_pwd = strim($_REQUEST['user_pwd']);
		$confirm_user_pwd = strim($_REQUEST['confirm_user_pwd']);
		$user_info=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
		$data=array();		
		if(strlen($user_pwd)<0){
			$data['info']="请输入旧密码";
			ajax_return($data);
			return false;
		}
		if( md5($user_old_pwd.$user_info['code'])!= $user_info['user_pwd']){
			$data['info']="旧密码输入错误";
			ajax_return($data);
			return false;
		}
		if(strlen($user_pwd)<4)
		{
			$data['info']="密码不能低于四位";
			ajax_return($data);
			return false;
		}
		if($user_pwd!=$confirm_user_pwd)
		{
			$data['info']="密码确认失败";
			ajax_return($data);
			return false;
		}
	
		require_once APP_ROOT_PATH."system/libs/user.php";
		$user_info['user_pwd'] = $user_pwd;
		save_user($user_info,"UPDATE");
		if(	$GLOBALS['db']->query("update ".DB_PREFIX."user set password_verify = '' where id = ".intval($GLOBALS['user_info']['id']))){
			$data['status']=1;
		}else{
			$data['status']=0;
		}
		ajax_return($data);
	}
	
	public function save_mobile_password()
	{
//		$ajax = intval($_REQUEST['ajax']);
        $ajax =1;
		if(!$GLOBALS['user_info'])
		{
			app_redirect(url_wap("user#login"));
		}
		$data=array();
		if(!check_ipop_limit(get_client_ip(),"setting_save_mobile_password",5)){
            showErr("提交太频繁",$ajax,"");
		}

		$user_pwd = strim($_REQUEST['user_pwd']);
		$confirm_user_pwd = strim($_REQUEST['confirm_user_pwd']);
		$user_info=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
		$mobile=strim($user_info['mobile']);
		$user_info['verify_coder']=strim($_REQUEST['verify_coder']);
		if($mobile){
				
			$has_code=$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile='".$mobile."' and verify_code='".strim($_REQUEST['verify_coder'])."' ");
			if(!$has_code){
				showErr("验证码错误",$ajax,"");
			}
		}else{
			showErr("请绑定手机号",$ajax,"");
		}
	
		if(strlen($user_pwd)<4)
		{
			showErr("密码不能低于四位",$ajax,"");
		}
		if($user_pwd!=$confirm_user_pwd)
		{
			showErr("密码确认失败",$ajax,"");
		}
	
		require_once APP_ROOT_PATH."system/libs/user.php";
		$user_info['user_pwd'] = $user_pwd;
		
		save_user($user_info,"UPDATE");
		
		if(	$GLOBALS['db']->query("update ".DB_PREFIX."user set password_verify = '' where id = ".intval($GLOBALS['user_info']['id']))){
			$data['status']=1;
		}else{
			$data['status']=0;
		}
//		ajax_return($data);
		showSuccess("保存成功",$ajax,url_wap("settings#index"));
	}
	public function invest_info()
	{	
		
		 settings_invest_info('wap',$GLOBALS['user_info']);
 	}
    /*用于绑定解绑第三方帐号 20160120*/
    public function thirdParties(){
//        print_r($GLOBALS['user_info']);
        if(!$GLOBALS['user_info']) {
            app_redirect(url_wap("olduser#login"));
        }else{
            $userid = $GLOBALS['user_info']['id'];
//            echo $userid;
            $select_sql = "select * from ".DB_PREFIX."user_idx where userid = ".$userid;
            $idx = $GLOBALS['db']->getRow($select_sql);
            if($idx){
                $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
            }
            $GLOBALS['tmpl']->assign("idx",$idx);
            $GLOBALS['tmpl']->assign("page_title",'绑定微信');
            $GLOBALS['tmpl']->display("old/oldsettings_third_parties.html");
        }

    }
}
?>