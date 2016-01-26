<?php
class userModule{
	 
	public function login()
	{
 		if($GLOBALS['user_info']){
			app_redirect(url_wap("settings#index"));
		}
		$gopreview = es_session::get("gopreview");
		if(!$gopreview){
			$url = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:url("index");
			es_session::set("gopreview",$url);
		}
//		$GLOBALS['tmpl']->caching = true;
//		$cache_id  = md5(MODULE_NAME.ACTION_NAME);
//		if (!$GLOBALS['tmpl']->is_cached('user_login.html', $cache_id))
//		{
		$GLOBALS['tmpl']->assign("page_title","会员登录");
        $GLOBALS['tmpl']->display("user_welcome.html");
//		}

//		$GLOBALS['tmpl']->display("user_login.html",$cache_id);
        $a = Array(
            'openid' => 'o02xeszVAvZG9UkJ9HmrNCt-l8JA',
            'nickname' => '贝xMan',
            'sex' => 2,
            'language' => 'zh_CN',
            'city' => '通州',
            'province' => '北京',
            'country' => '中国',
            'headimgurl' => "http://wx.qlogo.cn/mmopen/ajNVdqHZLLCicmCoWterhicQ2ia2B9ia6GMOiarGKia8y6diag9gEiaCljsiaAXmVa1sJUwJTQcLeibic5fTxK9EzetxqEwGw/0",
            'privilege' => Array(),
            'unionid' => "oCj9huKCm-JDZMIdiliXPyJgXsnU");
        es_session::set("wx_login_user_info",$a);
	}
    public function start_login()
    {
        if($GLOBALS['user_info']){
            app_redirect(url_wap("settings#index"));
        }
        $GLOBALS['tmpl']->assign("page_title","会员登录");
        $GLOBALS['tmpl']->display("user_login.html");
    }

	public function do_login()
	{
		if(!$_POST)
		{
 			app_redirect(APP_ROOT."/");
		}
		foreach($_POST as $k=>$v)
		{
			$_POST[$k] = strim($v);
		}
		$ajax = intval($_REQUEST['ajax']);

		require_once APP_ROOT_PATH."system/libs/user.php";
		if(check_ipop_limit(get_client_ip(),"user_dologin",5))
		$result = do_login_user($_POST['mobile'],$_POST['user_pwd']);
		else
		showErr("提交太快",$ajax,url_wap("user#login"));
		if($result['status'])
		{
 			$s_user_info = es_session::get("user_info");
			if(intval($_POST['auto_login'])==1)
			{
				//自动登录，保存cookie
				$user_data = $s_user_info;
				es_cookie::set("mobile",$user_data['mobile'],3600*24*30);
				es_cookie::set("user_pwd",md5($user_data['user_pwd']."_EASE_COOKIE"),3600*24*30);

			}
			if($ajax==0&&trim(app_conf("INTEGRATE_CODE"))=='')
			{
				$redirect = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:url_wap("index");
				app_redirect($redirect);
			}
			else
			{
				$jump_url = get_gopreview();
				if($ajax==1)
				{
					$return['status'] = 1;
					$return['info'] = "登录成功";
					$return['data'] = $result['msg'];
					$return['jump'] = $jump_url;
					ajax_return($return);
				}
				else
				{
					$GLOBALS['tmpl']->assign('integrate_result',$result['msg']);
					showSuccess("登录成功",$ajax,$jump_url);
				}
			}
		}
		else
		{
 			if($result['data'] == ACCOUNT_NO_EXIST_ERROR)
			{
				$err = "会员不存在";
			}
			if($result['data'] == ACCOUNT_PASSWORD_ERROR)
			{
				$err = "密码错误";
			}
                        if($result['data'] == ACCOUNT_NO_VERIFY_ERROR)
			{
				$err = "用户未通过验证";
				if(app_conf("MAIL_ON")==1&&$ajax==0)
				{
					$GLOBALS['tmpl']->assign("page_title",$err);
					$GLOBALS['tmpl']->assign("user_info",$result['user']);
					$GLOBALS['tmpl']->display("verify_user.html");
					exit;
				}

			}
			showErr($err,$ajax);
		}
	}
	public function register()
	{
                 //links
        if($GLOBALS['user_info']){
			app_redirect(url_wap("settings#index"));
		}
		$referer=$_SERVER['HTTP_REFERER'];
 		/*$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.ACTION_NAME);
		if (!$GLOBALS['tmpl']->is_cached('user_register.html', $cache_id))
		{
			$GLOBALS['tmpl']->assign("page_title","会员注册");
		}*/
        $GLOBALS['tmpl']->assign("page_title","会员注册");
		$GLOBALS['tmpl']->assign("referer",$referer);
		$GLOBALS['tmpl']->display("user_register.html");
	}
	public function do_register()
		{
            $mobile=strim($_POST['mobile']);
            $username =$_REQUEST['user_name'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $mobile);
            $verify_coder=strim($_POST['verify_coder']);
            require_once APP_ROOT_PATH."system/libs/user.php";
            $user_name_result = check_user("user_name",$username);
            if($user_name_result['data']['error']==EXIST_ERROR) {
                $username =  $_REQUEST['user_name'] = $username.rand(10000,99999);
            }
            $return = $this->register_check_all();
            if($return['status']==0){ajax_return($return);}
            $has_code=$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile='".$mobile."' and verify_code='".strim($verify_coder)."' ");
            if(!$has_code){
                showErr("验证码错误",1,"");
            }

            $user_data = $_POST;
            foreach($_POST as $k=>$v){
                $user_data[$k] = strim($v);
            }
            $user_data['user_name']=$username;
            $user_data['is_effect']=1;
//            print_r($user_data);
        $res = save_user($user_data);
        statistics('register');
        if($res['status'] == 1) {
            if(!check_ipop_limit(get_client_ip(),"user_do_register",5))
                showErr("提交太快",1);

            $user_id = intval($res['data']);
            $userinfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
//                print_r($userinfo);exit;
            if($userinfo['is_effect']==1){
                //在此自动登录
                $result = do_login_user($user_data['user_name'],$user_data['user_pwd']);
                ajax_return(array("status"=>1,"data"=>$userinfo['user_name'],"jump"=>get_gopreview()));
            }else{
                if(app_conf("USER_VERIFY")==1){
                    ajax_return(array("status"=>1,"jump"=>url("user#mail_check",array('uid'=>$user_id))));
                }else if(app_conf("USER_VERIFY")==3){
                    ajax_return(array("status"=>0,"info"=>"请等待管理员审核"));
                }

            }
        }else{
            $error = $res['data'];
            switch($error['field_name']){
                case 'mobile':
                    $field_name = "手机号码";
                    break;
                case 'verify_code':
                    $field_name = "验证码";
                    break;
                case 'user_name':
                    $field_name = "帐号";
                    break;
            }

            switch ($error['error']){
                case EMPTY_ERROR:
                    $error_info = "不能为空";
                    break;
                case FORMAT_ERROR:
                    $error_info = "错误";
                    break;
                case EXIST_ERROR:
                    $error_info = "已存在";
                    break;
            }
            ajax_return(array("status"=>0,"data"=>$field_name.$error_info));

        }

	}

	public function loginout(){
		$ajax = intval($_REQUEST['ajax']);
		require_once APP_ROOT_PATH."system/libs/user.php";
		$result = loginout_user();
		if($result['status'])
		{
			es_cookie::delete("email");
			es_cookie::delete("user_pwd");
			es_cookie::delete("hide_user_notify");
			if($ajax==1)
			{
				$return['status'] = 1;
				$return['info'] = "登出成功";
				$return['data'] = $result['msg'];
				$return['jump'] = get_gopreview_wap();
				ajax_return($return);
			}
			else
			{
				$GLOBALS['tmpl']->assign('integrate_result',$result['msg']);
				if(trim(app_conf("INTEGRATE_CODE"))=='')
				{
					app_redirect(get_gopreview_wap());
				}
				else
				showSuccess("登出成功",0,get_gopreview_wap());
			}
		}
		else
		{
			if($ajax==1)
			{
				$return['status'] = 1;
				$return['info'] = "登出成功";
				$return['jump'] = get_gopreview_wap();
				ajax_return($return);
			}
			else
			app_redirect(get_gopreview_wap());
		}
	}




	private function register_check_all()
	{

		if(app_conf("USER_VERIFY")!=2){
 			$user_name = strim($_REQUEST['user_name']);
			$email = strim($_REQUEST['email']);

			//	$mobile = strim($_REQUEST['mobile']);
			$user_pwd = strim($_REQUEST['user_pwd']);
			$confirm_user_pwd = strim($_REQUEST['confirm_user_pwd']);
			$data = array();
			require_once APP_ROOT_PATH."system/libs/user.php";

			$user_name_result = check_user("user_name",$user_name);
			$return = array('status'=>1,"info"=>"");
			if($user_name_result['status']==0)
			{
				if($user_name_result['data']['error']==EMPTY_ERROR)
				{
					$error = "不能为空";
					$type = "form_tip";
				}
				if($user_name_result['data']['error']==FORMAT_ERROR)
				{
					$error = "格式有误";
					$type="form_error";
				}
				if($user_name_result['data']['error']==EXIST_ERROR)
				{
					$error = "已存在";
					$type="form_error";
				}
				$return = array('status'=>0,"info"=>"会员帐号".$error);
				return $return;
 				//$data[] = array("type"=>$type,"field"=>"user_name","info"=>"会员帐号".$error);
			}

			/*
			$email_result = check_user("email",$email);
			if($email_result['status']==0)
			{
				if($email_result['data']['error']==EMPTY_ERROR)
				{
					$error = "不能为空";
					$type = "form_tip";
				}
				if($email_result['data']['error']==FORMAT_ERROR)
				{
					$error = "格式有误";
					$type="form_error";
				}
				if($email_result['data']['error']==EXIST_ERROR)
				{
					$error = "已存在";
					$type="form_error";
				}
				$return = array('status'=>0,"info"=>"电子邮箱".$error);
				return $return;
 				//$data[] = array("type"=>$type,"field"=>"email","info"=>"电子邮箱".$error);
			}
			*/

			if($user_pwd=="")
			{
				$user_pwd_result['status'] = 0;
 				$return = array('status'=>0,"info"=>"请输入会员密码");
 				return $return;
 			}
			elseif(strlen($user_pwd)<4)
			{
				$user_pwd_result['status'] = 0;
 				$return = array('status'=>0,"info"=>"密码不得小于四位");
 				return $return;

			}


			if($user_pwd!=$confirm_user_pwd)
			{
				$return = array('status'=>0,"info"=>"确认密码失败");
				return $return;
 			}


			return $return;
		}
		if(app_conf("USER_VERIFY")==2){
 			$user_name = strim($_REQUEST['user_name']);
			//$email = strim($_REQUEST['email']);

			$mobile = strim($_REQUEST['mobile']);
			//$verify_coder=strim($_REQUEST['verify_coder']);

			$user_pwd = strim($_REQUEST['user_pwd']);
			$confirm_user_pwd = strim($_REQUEST['confirm_user_pwd']);
			$data = array();
			require_once APP_ROOT_PATH."system/libs/user.php";

			$return = array('status'=>1,"info"=>"");
			$user_name_result = check_user("user_name",$user_name);

 			if($user_name_result['status']==0)
			{
				if($user_name_result['data']['error']==EMPTY_ERROR)
				{
					$error = "不能为空";
					$type = "form_tip";
				}
				if($user_name_result['data']['error']==FORMAT_ERROR)
				{
					$error = "格式有误";
					$type="form_error";
				}
				if($user_name_result['data']['error']==EXIST_ERROR)
				{
					$error = "已存在";
					$type="form_error";
				}
 				$return = array('status'=>0,"info"=>"会员帐号".$error);
				return $return;
			}



			$mobile_result = check_user("mobile",$mobile);

			if($mobile_result['status']==0)
			{
				if($mobile_result['data']['error']==EMPTY_ERROR)
				{
					$error = "不能为空";
					$type = "form_tip";
				}
				if($mobile_result['data']['error']==FORMAT_ERROR)
				{
					$error = "格式有误";
					$type="form_error";
				}
				if($mobile_result['data']['error']==EXIST_ERROR)
				{
					$error = "已存在";
					$type="form_error";
				}
 				$return = array('status'=>0,"info"=>"手机号码".$error);
				return $return;
			}
			 /*
			//=================================================这里面的要验证改y
			$verify_coder_result = check_user("verify_coder",$verify_coder);
			if($verify_coder_result['status']==0)
			{
				if($verify_coder_result['data']['error']==EMPTY_ERROR)
				{
					$error = "不能为空";
					$type = "form_tip";
				}
				if($verify_coder_result['data']['error']==FORMAT_ERROR)
				{
					$error = "格式错误";
					$type = "form_tip";
				}
				if($verify_coder_result['data']['error']==EXIST_ERROR)
				{
					$error = "错误";
					$type="form_error";
				}
 				$return = array('status'=>0,"info"=>"验证码".$error);
				return $return;
			}
			*/

			if($user_pwd=="")
			{
 				$return = array('status'=>0,"info"=>"请输入会员密码");
				return $return;
			}
			elseif(strlen($user_pwd)<4)
			{
 				$return = array('status'=>0,"info"=>"密码不得小于四位");
				return $return;
			}

 			return $return;
		}
	}
	
	//检查验证码是否正确
	function check_verify_code()
	{
 		$settings_mobile_code=strim($_REQUEST['code']);
  		$mobile=strim($_REQUEST['mobile']);
		//判断验证码是否正确=============================
 		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$mobile." AND verify_code='".$settings_mobile_code."'")==0){
			$data['status'] = 0;
			$data['info'] = "手机验证码出错";
			ajax_return($data);
		}else{
			$data['status'] = 1;
			$data['info'] = "验证码正确";
			ajax_return($data);
		}
	}
	
	public function getpassword()
	{
//		$GLOBALS['tmpl']->caching = true;
//		$cache_id  = md5(MODULE_NAME.ACTION_NAME);
		if (!$GLOBALS['tmpl']->is_cached('user_getpassword.html'))
		{
			$GLOBALS['tmpl']->assign("page_title","邮件取回密码");
		}
		$GLOBALS['tmpl']->display("user_getpassword.html");
	}
	
	public function wx_register(){
		if($GLOBALS['user_info']){
			app_redirect(url_wap("index#index"));
		}
		$referer=$_SERVER['HTTP_REFERER'];
		$GLOBALS['tmpl']->assign('wx_info',$GLOBALS['wx_info']);
		$GLOBALS['tmpl']->assign('referer',$referer);
		$GLOBALS['tmpl']->display("user_wx_register.html");
	}
	
	public function wx_do_register()
	{
		$user_info=array();
		$referer=$_REQUEST['referer'];
		$user_info['mobile'] = strim($_REQUEST['mobile']);
 		//$user_info['verify_coder']=strim($_REQUEST['code']);
		$user_info['wx_openid']=strim($_REQUEST['wx_openid']);
		$user_info['user_name']=strim($_REQUEST['user_name']);
		$user_info['province']=strim($_REQUEST['province']);
		$user_info['email']=strim($_REQUEST['email']);
		$user_info['city']=strim($_REQUEST['city']);
		$user_info['sex']=strim($_REQUEST['sex']);
		$user_pwd=$_REQUEST['password'];
		$user_new_pwd=$_REQUEST['new_password'];
		if(!$user_info['mobile'])
		{
			$data['status'] = 0;
			$data['info'] = "手机号码为空";
			ajax_return($data);
		}
		
		/*
		if($user_info['verify_coder']==""){
			$data['status'] = 0;
			$data['info'] = "手机验证码为空";
			ajax_return($data);
		}
		*/
		if($user_pwd==""){
			$data['status'] = 0;
			$data['info'] = "密码为空";
			ajax_return($data);
		}
		if(strlen($user_pwd)<4 || strlen($user_pwd)>12){
			$data['status'] = 0;
			$data['info'] = "密码请输入4~12个字符";
			ajax_return($data);
		}
		if($user_pwd!=$user_new_pwd){
			$data['status'] = 0;
			$data['info'] = "确认密码与密码不符";
			ajax_return($data);
		}
		/*
		//判断验证码是否正确=============================
		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$user_info['mobile']." AND verify_code='".$user_info['verify_coder']."'")==0){
 			$data['status'] = 0;
			$data['info'] = "手机验证码错误";
			ajax_return($data);
		}
		*/
		$user=get_user_has('mobile',$user_info['mobile']);
		require_once APP_ROOT_PATH."system/libs/user.php";
		if($user){
			$GLOBALS['db']->query("update ".DB_PREFIX."user set wx_openid='".$user_info['wx_openid']."' where id=".$user['id']);
 			$user_id = $user['id'];	
 		}else{
 			/*
 			if(!$user_info['email'])
			{
				$data['status'] = 0;
				$data['info'] = "邮箱为空";
				ajax_return($data);
			}
			if(!check_email($user_info['email'])){
				$data['status'] = 0;
				$data['info'] = "邮箱格式错误";
				ajax_return($data);
			}
			
			$has_email=get_user_has('email',$user_info['email']);
			if($has_email){
				$data['status'] = 0;
				$data['info'] = "邮箱已存在，请重新填写";
				ajax_return($data);
			}
			*/
			$has_user_name=get_user_has('user_name',$user_info['user_name']);
			if($has_user_name){
				$user_info['user_name']=$user_info['user_name'].rand(10000,99999);
			}
			
 			
 			if($user_info['sex']==0){
 				$user_info['sex']=-1;
 			}elseif($user_info['sex']==1){
 				$user_info['sex']=1;
 			}else{
 				$user_info['sex']=0;
 			}
 			//开启邮箱验证
            if(app_conf("USER_VERIFY")==0||app_conf("USER_VERIFY")==2){
                 $user_info['is_effect'] = 1;
            }else{
            	$user_info['is_effect'] = 0;
            }
 			
 			$user_info['create_time'] = get_gmtime();
			$user_info['update_time'] = get_gmtime();
			//新建用户 使用验证码作为密码
			$user_info['user_pwd']=$user_pwd;
			//$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user_info,"INSERT");
 			$res = save_user($user_info);
 			statistics('register');
			$user_id = intval($res['data']);	
 		}
 		 
  			$user_info_new = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
 			if($user_info_new['is_effect']==1)
			{
 				$result = do_login_user($user_info_new['mobile'],$user_info_new['user_pwd']);
  				ajax_return(array("status"=>1,"info"=>$result['msg'],"jump"=>$referer));
			}
			else
			{
                if(app_conf("USER_VERIFY")==1){
                    ajax_return(array("status"=>1,"jump"=>url_wap("user#mail_check",array('uid'=>$user_id))));
                }else if(app_conf("USER_VERIFY")==3){
                	ajax_return(array("status"=>0,"info"=>"请等待管理员审核"));
                }
					
			}                     
 	}
	
	//手机验证修改密码=====================================================================================
	public function phone_update_password()
	{
		$mobile = strim($_REQUEST['mobile']);
		$user_pwd = strim($_REQUEST['user_pwd']);
		$confirm_user_pwd=strim($_POST['confirm_user_pwd']);
		$settings_mobile_code1=strim($_POST['verify_coder']);
	
		if(!$mobile)
		{
			$data['status'] = 0;
			$data['info'] = "手机号码为空";
			ajax_return($data);
		}

		if($settings_mobile_code1==""){
			$data['status'] = 0;
			$data['info'] = "手机验证码为空";
			ajax_return($data);
		}

		if($user_pwd==""){
			$data['status'] = 0;
			$data['info'] = "密码为空";
			ajax_return($data);
		}
	
		if($user_pwd!==$confirm_user_pwd){
			$data['status'] = 0;
			$data['info'] = "两次密码不一致";
			ajax_return($data);
		}
	

		//判断验证码是否正确=============================
		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$mobile." AND verify_code='".$settings_mobile_code1."'")==0){
	
			$data['status'] = 0;
			$data['info'] = "手机验证码错误";
			ajax_return($data);
		}

	
	
		if($user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where mobile =".$mobile))
		{
				
			$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user SET user_pwd='".md5($user_pwd.$user_info['code'])."' where mobile=".$mobile);
			$result = 1;  //初始为1
			$data['status'] = 1;
			$data['info'] = "密码修改成功";
			ajax_return($data);//密码修改成功
		}
		else{
			$data['status'] = 0;
			$data['info'] = "没有该手机账户";
			ajax_return($data);//密码修改成功
		}
	}
	
	//判断邮箱类型及跳转到user_register_email.html界面
	function mail_check()
	{
	
		$GLOBALS['tmpl']->assign("g_links",$g_links);
		if(app_conf("MAIL_ON")==1)
		{
			$user_id = (int)$_REQUEST['uid'];
			//发邮件
			send_wap_user_verify_mail($user_id);
			$user_email = $GLOBALS['db']->getOne("select email from ".DB_PREFIX."user where id =".$user_id);
			//开始关于跳转地址的解析
			$domain = explode("@",$user_email);
			$domain = $domain[1];
			$gocheck_url = '';
			switch($domain)
			{
				case '163.com':
					$gocheck_url = 'http://mail.163.com';
					break;
				case '126.com':
					$gocheck_url = 'http://www.126.com';
					break;
				case 'sina.com':
					$gocheck_url = 'http://mail.sina.com';
					break;
				case 'sina.com.cn':
					$gocheck_url = 'http://mail.sina.com.cn';
					break;
				case 'sina.cn':
					$gocheck_url = 'http://mail.sina.cn';
					break;
				case 'qq.com':
					$gocheck_url = 'http://mail.qq.com';
					break;
				case 'foxmail.com':
					$gocheck_url = 'http://mail.foxmail.com';
					break;
				case 'gmail.com':
					$gocheck_url = 'http://www.gmail.com';
					break;
				case 'yahoo.com':
					$gocheck_url = 'http://mail.yahoo.com';
					break;
				case 'yahoo.com.cn':
					$gocheck_url = 'http://mail.cn.yahoo.com';
					break;
				case 'hotmail.com':
					$gocheck_url = 'http://www.hotmail.com';
					break;
				default:
					$gocheck_url = "";
					break;
			}
	
			$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['REGISTER_MAIL_SEND_SUCCESS']);
	
			$GLOBALS['tmpl']->assign("user_email",$user_email);
	
			$GLOBALS['tmpl']->assign("gocheck_url",$gocheck_url);
			//end
			$GLOBALS['tmpl']->display("user_register_email.html");
		}
	
	}
	
	public function verify()
	{
		$id = intval($_REQUEST['id']);
		$user_info  = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
		if(!$user_info)
		{
			showErr("没有该会员");
		}
		$verify = addslashes(trim($_REQUEST['code']));
		if($user_info['verify']!=''&&$user_info['verify'] == $verify)
		{
			//成功
//			send_register_success(0,$user_info);
			es_session::set("user_info",$user_info);
			$GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",verify = '',is_effect = 1 where id =".$user_info['id']);
			$GLOBALS['db']->query("update ".DB_PREFIX."mail_list set is_effect = 1 where mail_address ='".$user_info['email']."'");
			showSuccess("验证成功",0,get_gopreview_wap());
		}
	
		elseif($user_info['verify']=='')
		{
			showErr("已验证过",0,get_gopreview_wap());
	
		}
		else
		{
			showErr("验证失败",0,get_gopreview_wap());
		}
	}
	
	public function investor_result($from='wap'){
		if(!$GLOBALS['user_info']){
			if($from=='web'){
				app_redirect(url("user#login"));
			}elseif ($from=='wap'){
				app_redirect(url_wap("user#login"));
			}
		}
		if($GLOBALS['user_info']['investor_status']==1){
			$GLOBALS['tmpl']->assign("investor_status",$GLOBALS['user_info']['investor_status']);
			$GLOBALS['tmpl']->assign("is_investor",$GLOBALS['user_info']['is_investor']);
		}
		$GLOBALS['tmpl']->display("investor_success.html");
	}

	//投资认证申请信息入库(个人)
	public function investor_save_data($from='wap'){
		if(!$GLOBALS['user_info']){
			if($from=='web'){
				app_redirect(url("user#login"));
			}elseif($from=='wap'){
				app_redirect(url_wap("user#login"));
			}
		}
		if(!check_ipop_limit(get_client_ip(),"user_investor_result",5))
			showErr("提交太快",1);
		$id=intval($_REQUEST ['id']);
		$ajax = intval($_POST['ajax']);
		$identify_name=strim($_POST['identify_name']);
		$identify_number=strim($_POST['identify_number']);
		$image1['url']=replace_public(strim($_POST['idcard_zheng_u']));
		$image2['url']=replace_public(strim($_POST['idcard_fang_u']));
		$data=investor_save($id,$ajax='',$identify_name,$identify_number,$image1['url'],$image2['url']);
		ajax_return($data);
		return false;
	}
	
	//投资认证申请信息入库(机构)
	public function investor_agency_save_data($from='wap'){
		if(!$GLOBALS['user_info']){
			if($from=='web'){
				app_redirect(url("user#login"));
			}elseif ($from=='wap'){
				app_redirect(url_wap("user#login"));
			}
		}
		if(!check_ipop_limit(get_client_ip(),"user_investor_result",5))
			showErr("提交太快",1);
		$id=intval($_REQUEST ['id']);
		$ajax = intval($_POST['ajax']);
		$identify_business_name=strim($_POST['identify_business_name']);
		$identify_business_licence=es_session::get("identify_business_licence");
		$identify_business_code=es_session::get("identify_business_code");
		$identify_business_tax=es_session::get("identify_business_tax");
		$image1['url']=replace_public(strim($_POST['identify_business_licence_u']));
		$image2['url']=replace_public(strim($_POST['identify_business_code_u']));
		$image3['url']=replace_public(strim($_POST['identify_business_tax_u']));
		$data=investor_agency_save($id,$ajax='',$identify_business_name,$identify_business_licence,$identify_business_code,$identify_business_tax,$image1['url'],$image2['url'],$image3['url']);
		ajax_return($data);
		return false;
	}
	
	//(投资者认证)更新用户手机号码
	public function investor_save_mobile(){
		$id=$GLOBALS['user_info']['id'];
		$mobile=strim($_POST['mobile']);
		if((es_cookie::get(md5("mobile_is_bind".$id)))!=1)
		{
			$verify_coder=strim($_POST['verify_coder']);
			if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$mobile." AND verify_code='".$verify_coder."'")==0){
				$data['status'] = 0;
				$data['info'] = "手机验证码出错!";
				ajax_return($data);
				return false;
			}
		}
		$is_investor=strim($_POST['is_investor']);
		if($mobile==null){
			$data['status'] = 0;
			$data['info'] = "手机号码不能为空！";
			ajax_return($data);
			return false;
		}
		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user WHERE id!=".$id." AND mobile=".$mobile)>0){
			$data['status'] = 0;
			$data['info'] = "手机号码已经被使用！";
			ajax_return($data);
			return false;
		}
		if($GLOBALS['db']->query("UPDATE ".DB_PREFIX."user SET mobile=".$mobile." WHERE id = ".$id)&&$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user SET is_investor=".$is_investor." WHERE id = ".$id)){
			//绑定过回退不用再次发送短信
			es_cookie::set(md5("mobile_is_bind".$id),1);
			$data['status'] = 1;
			ajax_return($data);
		}
	
		return false;
	}
//wechat 绑定手机号码
    public function user_bind_mobile(){
        $userinfo = $GLOBALS['user_info'];
        $wx_Login_info = es_session::get('wx_login_user_info');
        $wx_info = $wx_Login_info?$wx_Login_info:es_session::get('wx_user_info');
        $wx_type = $wx_Login_info?'login':'auth';
        $pre = get_gopreview_wap();
        if(!$wx_info['unionid']){
            $GLOBALS['tmpl']->assign("error",'无法获取您的微信用户信息');
        }else{
            if($userinfo){
                //用户信息存在，检查用户绑定情况：
                //  没有绑定信息：使用手机密码登录，并绑定微信；
                //  有绑定信息：进入绑定页面进行异常提示；
                $select_openid_sql = "select userid from ".DB_PREFIX."user_idx where wechat_unionid = '".$wx_info['unionid']."'";
                $unionid_idx = $GLOBALS['db']->getOne($select_openid_sql);

                if($unionid_idx&&$unionid_idx!==$userinfo['id']){
                    showErr('此微信已与其它手机号绑定，请登录后至个人中心解绑，再绑定此微信号');
                    exit;
                }
                $userid = $userinfo['id'];
                //查询该用户是否有绑定信息
                $select_sql = "select wechat_unionid from ".DB_PREFIX."user_idx where userid = ".$userid;
                $user_unionid = $GLOBALS['db']->getOne($select_sql);

                if($user_unionid){
                    //有绑定信息：判断是否为该unionid
                    if($user_unionid == $wx_info['unionid']){
//                        echo $user_unionid;exit;
                        if($userinfo['is_effect']==1){
                            //在此自动登录
                            require_once APP_ROOT_PATH."system/libs/user.php";
                            do_login_user($userinfo['user_name'],$userinfo['user_pwd']);
                        }
                    }else{
                        showErr('此账号已与其它微信绑定，请更换手机号，或用手机号登录');
                        exit;
                    }
                }else{
                    $sql_user_idx_insert = "INSERT INTO `xlc_user_idx` SET userid=".$userinfo['id'].",mobile='".$userinfo['mobile']."',nickname='".$wx_info['nickname']."',wechat_".$wx_type."_openid='".$wx_info['openid']."',wechat_unionid='".$wx_info['unionid']."'";
                    $GLOBALS['db']->query($sql_user_idx_insert);
                }

                //是否替换用户昵称等信息
                if($userinfo['has_set_name']==0){
                    $sql_user_name_info ="select * from xlc_user where user_name='".$wx_info['nickname']."' limit 1";
                    $tmp_user_name_info = $GLOBALS['db']->query($sql_user_name_info);
                    if($tmp_user_name_info){
                        //微信用户昵称存在
                        $wx_info['nickname'] .= rand(10000,99999);
                    }
                    //更新用户表
                    $sql_user_update = "UPDATE `xlc_user` SET user_name='".$wx_info['nickname']."',headimgurl='".$wx_info['headimgurl']."',sex=".$wx_info['sex'].",province='".$wx_info['province']."',city='".$wx_info['city']."' where id=".$userinfo['id'];
//                        print_r($sql_user_update);
                    $GLOBALS['db']->query($sql_user_update);
                }

                //完成绑定跳转回个人中心
                $url = $pre?$pre:url("settings#thirdparties");
                showErr('绑定成功！',"",$url,0,"成功");
            }else{
                $sql_user_info ="select a.*,b.userid from ".DB_PREFIX."user as a , ".DB_PREFIX."user_idx as b where a.id=b.userid and b.wechat_unionid='".$wx_info['unionid']."' limit 1";
                    $wx_user_info = $GLOBALS['db']->getRow($sql_user_info);
                    if($wx_user_info){
                        if($wx_user_info['mobile']){
                            require_once APP_ROOT_PATH . "system/libs/user.php";
                            //如果会员存在，直接登录
                            do_login_user($wx_user_info['mobile'], $wx_user_info['user_pwd']);
                            app_redirect(url_wap("settings#index"));
                            exit;
                        }
                    }
                }

            $page_title = '绑定手机';

        }

        $GLOBALS['tmpl']->assign("wx_info",$wx_info);
        $GLOBALS['tmpl']->assign("page_title",$page_title);
        $html = "user_bind_mobile.html";

        $GLOBALS['tmpl']->display($html);
    }

    //更新用户手机号码
    public function save_mobile(){
        $mobile=strim($_POST['mobile']);
        $cid=strim($_POST['cid']);
        $verify_coder=strim($_POST['verify_coder']);
        if($mobile==null){
            $data['status'] = 0;
            $data['info'] = "手机号码不能为空！";
            ajax_return($data);
            return false;
        }
        if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$mobile." AND verify_code='".$verify_coder."'")==0){
            $data['status'] = 0;
            $data['info'] = "手机验证码出错";
            ajax_return($data);
            return false;
        }
        $id=$GLOBALS['user_info']['id'];
        if($GLOBALS['db']->query("UPDATE ".DB_PREFIX."user SET mobile=".$mobile." WHERE id = ".$id)){
            //绑定过回退不用再次发送短信
            es_cookie::set(md5("mobile_is_bind".$GLOBALS['user_info']['id']),1);
            $data['status'] = 1;
            ajax_return($data);
        }
        return false;
    }
    public function do_bind_mobile(){
        $type = $_REQUEST['type'];
        $mobile=strim($_POST['mobile']);
        $username = strim($_POST['user_name']);
        $password=strim($_POST['user_pwd']);
        $verify_coder=strim($_POST['verify_coder']);
        $wx_login_info = es_session::get('wx_login_user_info');
        $wx_info = es_session::get('wx_user_info');
        $user_info = $wx_login_info?$wx_login_info:$wx_info;
        //没有微信帐号信息
        if(!$user_info){showErr("请使用微信扫描二维码后完成帐号绑定",1,"");}
        //判断微信号是否已存在
        $select_openid_sql = "select id from ".DB_PREFIX."user_idx where wechat_unionid = '".$user_info['unionid']."'";
        $unionid_idx = $GLOBALS['db']->getOne($select_openid_sql);
        if($unionid_idx){showErr("此微信已与其它手机号绑定，请登录后至个人中心解绑，再绑定此微信号",1,"");}
        $user_info['sex'] = $user_info['sex']==2?0:1;
        $wx_type = $wx_login_info?'login':'auth';
        if($type=='reg'){
            //绑定注册新帐号
            require_once APP_ROOT_PATH."system/libs/user.php";
            $user_name_result = check_user("user_name",$user_info['nickname']);
            if($user_name_result['data']['error']==EXIST_ERROR) {
                $username =  $_REQUEST['user_name'] = $user_info['nickname'].rand(10000,99999);
            }
            $return = $this->register_check_all();
            $has_code=$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile='".$mobile."' and verify_code='".strim($verify_coder)."' ");
            if(!$has_code){
                showErr("验证码错误",1,"");
            }
            if($return['status']==0){ajax_return($return);}

            $user_data = array_merge($user_info,array('mobile'=>$mobile,'user_pwd'=>$password,'user_name'=>$username,'is_effect'=>1));
            $res = save_user($user_data);
            statistics('register');

            if($res['status'] == 1) {
                if(!check_ipop_limit(get_client_ip(),"user_do_register",5))
                    showErr("提交太快",1);

                $user_id = intval($res['data']);
                $userinfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
                if($userinfo['is_effect']==1){
                    //在此自动登录
                    $result = do_login_user($user_data['user_name'],$user_data['user_pwd']);
//                    ajax_return(array("status"=>1,"info"=>$result['msg'],"jump"=>$referer));
                }
            }else{
                $error = $res['data'];
                switch($error['field_name']){
                    case 'mobile':
                        $field_name = "手机号码";
                        break;
                    case 'verify_code':
                        $field_name = "验证码";
                        break;
                    case 'user_name':
                        $field_name = "帐号";
                        break;
                    default:
                        $field_name = '输入';
                        break;
                }

                switch ($error['error']){
                    case EMPTY_ERROR:
                        $error_info = "不能为空";
                        break;
                    case FORMAT_ERROR:
                        $error_info = "错误";
                        break;
                    case EXIST_ERROR:
                        $error_info = "已存在";
                        break;
                    default:
                        $error_info = '有误';
                        break;
                }
                ajax_return(array("status"=>0,"data"=>$field_name.$error_info));

            }
        }else{
            //帐号已存在，绑定并登录
            require_once APP_ROOT_PATH."system/libs/user.php";
            if(check_ipop_limit(get_client_ip(),"user_dologin",5)){
                $result = do_login_user($mobile,$password);
            }else{
                showErr('提交太频繁',1);
            }
        }
        //插入新索引
        if($result['status']){
            //不替换用户昵称等信息
            if($user_info['unionid']){
                $sql_user_idx_insert = "INSERT INTO `xlc_user_idx` SET userid=".$result['user']['id'].",mobile='".$result['user']['mobile']."',nickname='".$user_info['nickname']."',wechat_".$wx_type."_openid='".$user_info['openid']."',wechat_unionid='".$user_info['unionid']."'";
                $GLOBALS['db']->query($sql_user_idx_insert);
                if($result['user']['has_set_name']==0){
                    $sql_user_name_info ="select * from xlc_user where user_name='".$user_info['nickname']."' limit 1";
                    $tmp_user_name_info = $GLOBALS['db']->query($sql_user_name_info);
                    if($tmp_user_name_info){
                        //微信用户昵称存在
                        $user_info['nickname'] .= rand(10000,99999);
                    }
                    //更新用户表
                    $sql_user_update = "UPDATE `xlc_user` SET user_name='".$user_info['nickname']."',headimgurl='".$user_info['headimgurl']."',sex=".$user_info['sex'].",province='".$user_info['province']."',city='".$user_info['city']."' where id=".$result['user']['id'];
                    $GLOBALS['db']->query($sql_user_update);
                }
            }else{
                $return['status'] = -1;
                $return['info'] = array('msg'=>'无法获取您的微信用户信息');
                ajax_return($return);
            }

            $return['status'] = 1;
            $return['info'] = "登录成功";
            $return['data'] = $result['msg'];
            $return['jump'] = get_gopreview_wap();//与pc端不同
            ajax_return($return);
        }else{
            switch($result['data']){
                case ACCOUNT_NO_EXIST_ERROR:
                    $err = "会员不存在";
                    break;
                case ACCOUNT_PASSWORD_ERROR:
                    $err = "密码错误";
                    break;
                case ACCOUNT_NO_VERIFY_ERROR:
                    $err = "用户未通过验证";
                    break;
                default:
                    $err = "绑定失败，请重试";
                    break;
            }
            showErr($err,1);
        };
    }
    /*增加微信扫描二维码的页面*/
    public function wx_login(){
        $userinfo = $GLOBALS['user_info'];
        if($userinfo){
            $idx = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_idx where userid = ".$userinfo['id']);
            if($idx)
            app_redirect(url_wap("settings#index"));
        }
        $GLOBALS['tmpl']->assign("page_title","扫码登录");
        $GLOBALS['tmpl']->display("user_wx_login.html");
    }
}
?>