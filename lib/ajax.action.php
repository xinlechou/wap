<?php
class ajaxModule
{
	public function index()
	{
		$page_size = DEAL_PAGE_SIZE;
		$step_size = DEAL_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$deal_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal where is_effect = 1  and is_recommend = 1 and is_delete = 0 ");

		$GLOBALS['tmpl']->assign("deal_list",$deal_list);
		$data['html'] = $GLOBALS['tmpl']->fetch("inc/deal_list.html");

		if($step*$step_size<$page_size)
		{
			if($deal_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{
				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}

	}
/*保存新增地址*/
    public function save_consignee()
    {
        $ajax = 1;

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
        if(isset($_REQUEST['isdefault'])){
            $data['is_default']= $_REQUEST['isdefault']?1:0;
            //新地址设为默认地址时将其它地址更新为非默认地址
            $GLOBALS['db']->query("UPDATE ".DB_PREFIX."user_consignee SET is_default = 0 where (user_id = ".intval($GLOBALS['user_info']['id'])." and is_default = 1)");
        }
        if(!check_ipop_limit(get_client_ip(),"setting_save_consignee",5)){
            showErr("提交太频繁",$ajax,"");exit;
        }
        //id 是用来更新地址的
        if($id>0)
            $GLOBALS['db']->autoExecute(DB_PREFIX."user_consignee",$data,"UPDATE","id=".$id);
        else
            $GLOBALS['db']->autoExecute(DB_PREFIX."user_consignee",$data);

        showSuccess("保存成功",$ajax,get_gopreview_wap());
        //$res = save_user($user_data);
    }
/*获取支持项信息&支付方式&用户信息*/
    public function deal_items(){
        $id = intval($_REQUEST['id']);$result = array();
        $type = $_REQUEST['isap']=='1'?"ap":"wap";
        //status：0   未登录
        if(!$GLOBALS['user_info']){
            $result['status'] = '0';
            $result['error'] = '未登录';
        }else{
            $deal_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_item where id = ".$id);
            $payment_list = get_payment_list_wap($type);
            if($deal_item){
                /*status：1    订单正常*/
                $result['status'] = '1';
                $deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where is_delete = 0 and is_effect = 1 and id = ".$deal_item['deal_id']);
                if($deal_item['is_delivery']) {
                    $consignee_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_consignee where user_id = ".intval($GLOBALS['user_info']['id']));
//                    $consignee_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_consignee where user_id = 18");
                    $result['is_delivery'] = '1';
                    $result['consignee_list'] = $consignee_list;
                }
                $result['deal_item'] = $deal_item;
                $result['deal_info'] = $deal_info;
            }else{
                /*status：2    没有订单信息*/
                $result['status'] = '2';
                $result['error']  = '没有订单信息';
            }
            $result['payment_list'] = $payment_list;
        }
//        print_r($result);
        ajax_return($result);
    }
    /*爱前进用户注册&登录*/
    public function aqj_do_register(){
        //查询用户是否存在
        $aqj_id = trim($_POST['reg_aqjid']);
        $aqj_mobile = trim($_POST['reg_m']);
        $result = array();
        $xlc_user=$GLOBALS['db']->getRow("select id,mobile,user_pwd from ".DB_PREFIX."user where mobile='".$aqj_mobile."'");
//        print_r($xlc_user);
        if($xlc_user){
            //存在新乐筹用户
            $aqj_user=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_partner_user where user_id=".$xlc_user['id']);
            if(!$aqj_user){
                //未绑定：与爱钱进帐号绑定
                $user_ap_partner = array();
                $user_ap_partner['user_id'] = $xlc_user['id'];
                $user_ap_partner['partner_id'] = 2;
                $user_ap_partner['partner_user_id'] = $aqj_id;
                $user_ap_partner['create_time'] = time();
                $GLOBALS['db']->autoExecute(DB_PREFIX."ap_partner_user",$user_ap_partner);
                require_once APP_ROOT_PATH."system/libs/user.php";
                $result['status'] = 1;
                $result['data'] = "授权成功，正在为您登录...";

            }else{
                //存在爱前进用户,更新绑定
                $user_ap_partner = array();
                $user_ap_partner['partner_user_id'] = $aqj_id;
                $user_ap_partner['edit_time'] = time();
                $GLOBALS['db']->autoExecute(DB_PREFIX."ap_partner_user",$user_ap_partner,"UPDATE","id=".intval($aqj_user['id']));
                $result['status'] = 2;
                $result['data'] = "已授权，正在为您登录...";
            }
            $result['jump'] = url_wap("deals",array('aqjid'=>$aqj_id,'m'=>$aqj_mobile,'isap'=>1));
            ajax_return($result);
        }else{
            //不存在新乐筹用户，自动注册帐号
            require_once APP_ROOT_PATH."system/libs/user.php";
            $user_data = array();
            $user_data['user_name'] = $aqj_mobile;
            $user_data['mobile'] = $aqj_mobile;
            $user_data['user_pwd'] = rand(100000,999999);//自动生成六位密码

            if(app_conf("USER_VERIFY")==0||app_conf("USER_VERIFY")==2){
                $user_data['is_effect'] = 1;
            }else{
                $user_data['is_effect'] = 0;
            }
            $res = save_user($user_data);
            statistics('register');
            $user_ap_partner = array();
            $user_ap_partner['user_id'] = $res['data'];
            $user_ap_partner['partner_id'] = 2;
            $user_ap_partner['partner_user_id'] = $aqj_id;
            $user_ap_partner['create_time'] = time();
            $GLOBALS['db']->autoExecute(DB_PREFIX."ap_partner_user",$user_ap_partner);
            $result = do_login_user($user_data['mobile'],$user_data['user_pwd']);
            $result['data'] = "授权成功，正在为您登录...";
            $result['jump'] = url_wap("deals",array('aqjid'=>$aqj_id,'m'=>$aqj_mobile,'isap'=>1));
            send_auto_register_pwd($aqj_mobile,$user_data['user_pwd']);//send pwd message
            ajax_return($result);
        }
    }

    public function aqj_do_login(){
        $aqj_id = trim($_POST['log_aqjid']);
        $user_mobile = trim($_POST['log_mobile']);
        $user_pwd = trim($_POST['log_pwd']);


        require_once APP_ROOT_PATH."system/libs/user.php";
        $result = do_login_user($user_mobile,$user_pwd);

        if($result['status']=="1"){
            //登录成功，跳转到积分商城，
            $xlc_user=$GLOBALS['db']->getRow("select id,mobile,user_pwd from ".DB_PREFIX."user where mobile='".$user_mobile."'");

            $aqj_user=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_partner_user where user_id=".$xlc_user['id']);
            if($aqj_user){
                //存在新乐筹用户,更新绑定
                $user_ap_partner = array();
                $user_ap_partner['partner_user_id'] = $aqj_id;
                $user_ap_partner['edit_time'] = time();
                $GLOBALS['db']->autoExecute(DB_PREFIX."ap_partner_user",$user_ap_partner,"UPDATE","id=".intval($aqj_user['id']));
            }else{
                //插入新用户
                $user_ap_partner = array();
                $user_ap_partner['user_id'] = $result['user']['id'];
                $user_ap_partner['partner_id'] = 2;
                $user_ap_partner['partner_user_id'] = $aqj_id;
                $user_ap_partner['create_time'] = time();
                $GLOBALS['db']->autoExecute(DB_PREFIX."ap_partner_user",$user_ap_partner);
            }
            $result['msg'] = "登录中...";
            $result['jump'] = url_wap("deals",array('aqjid'=>$aqj_id,'m'=>$user_mobile,'isap'=>1));
        }else{
            //提示登录失败
            switch($result['data']){
                case "1":
                    $result['msg'] = '对不起，帐户不存在。';
                    break;
                case "2":
                    $result['msg'] = '对不起，帐户密码错误。';
                    break;
                case "3":
                    $result['msg'] = '对不起，帐户未激活。';
                    break;
            }
        }
        ajax_return($result);
    }
	//这里会导致多出最后一个的bug=================================
	public function deals()
	{

		$r = strim($_REQUEST['r']);   //推荐类型
		$id = intval($_REQUEST['id']);  //分类id
		$loc = strim($_REQUEST['loc']);  //地区
		$tag = strim($_REQUEST['tag']);  //标签
		$kw = strim($_REQUEST['k']);    //关键词
		$state = intval($_REQUEST['state']);  //状态
		$step = intval($_REQUEST['step']);


		$page_size = DEAL_PAGE_SIZE;
		$step_size = DEAL_STEP_SIZE;

		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$condition = " is_delete = 0 and is_effect = 1 ";
		if($r!="")
		{
			if($r=="new")
			{
				$condition.=" and ".NOW_TIME." - begin_time < ".(24*3600)." and ".NOW_TIME." - begin_time > 0 ";  //上线不超过一天
			}
			if($r=="rec")
			{
				$condition.=" and ".NOW_TIME." <= end_time AND ".NOW_TIME." >= begin_time and is_recommend = 1 ";
			}
            if($r=="yure")
			{
				$condition.=" and ".NOW_TIME." - begin_time < ".(24*3600)." and ".NOW_TIME." - begin_time <  0 ";  //上线不超过一天
			}
			if($r=="nend")
			{
				$condition.=" and end_time - ".NOW_TIME." < ".(24*3600)." and end_time - ".NOW_TIME." > 0 ";  //当天就要结束
			}
			if($r=="classic")
			{
				$condition.=" and is_classic = 1 ";
			}
			if($r=="limit_price")
			{
				$condition.=" and max(limit_price) ";
			}
		}

		switch($state)
		{
			//筹资成功
			case 1 :
				$condition.=" and end_time < ".NOW_TIME."  and support_amount >= limit_price";
				$GLOBALS['tmpl']->assign("page_title","筹资成功");
				break;
				//筹资失败
			case 2 :
				$condition.=" and end_time < ".NOW_TIME."  and  support_amount < limit_price ";
				$GLOBALS['tmpl']->assign("page_title","筹资失败");
				break;
				//筹资中
			case 3 :
				$condition.=" and end_time > ".NOW_TIME."  and begin_time < ".NOW_TIME." ";
				$GLOBALS['tmpl']->assign("page_title","筹资中");
				break;
		}

		$cate_list = load_dynamic_cache("INDEX_CATE_LIST");

		if(!$cate_list)
		{
			$cate_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_cate order by sort asc");
			set_dynamic_cache("INDEX_CATE_LIST",$cate_list);
		}
		$cate_result = array();
		$kk = 0 ;
		foreach($cate_list as $k=>$v){
			if($v['pid'] == 0){
				$temp_param = $param;
				$cate_result[$k+1]['id'] = $v['id'];
				$cate_result[$k+1]['name'] = $v['name'];
				$temp_param['id'] = $v['id'];
				$cate_result[$k+1]['url'] = url("deals",$temp_param);
				$kk ++;
			}
		}

		$GLOBALS['tmpl']->assign("cate_list",$cate_result);

		$pid = 0;
		//获取父类id
		if($cate_list){
			foreach($cate_list as $k=>$v)
			{
				if($v['id'] ==  $id){
					if($v['pid'] > 0){
						$pid = $v['pid'];
					}
					else{
						$pid = $id;
					}
				}
			}
		}

		/*子分类 start*/
		$cate_ids = array();
		$is_has_child = false;
		$temp_cate_ids = array();
		if($cate_list){
			$child_cate_result= array();
			foreach($cate_list as $k=>$v)
			{
				if($v['pid'] == $pid){
					if($v['pid'] > 0){
						$temp_param = $param;
						$child_cate_result[$v['id']]['id'] = $v['id'];
						$child_cate_result[$v['id']]['name'] = $v['name'];
						$temp_param['id'] = $v['id'];
						$child_cate_result[$v['id']]['url'] = url("deals",$temp_param);

						if($v['id'] == $id){
							$is_has_child = true;
						}
					}
				}
				if($v['pid'] == $pid || $pid==0){
					$temp_cate_ids[] = $v['id'];
				}
			}
		}

		//假如选择了子类 那么使用子类ID  否则使用 父类和其子类
		if($is_has_child){
			$cate_ids[] = $id;
		}
		else{
			$cate_ids[] = $pid;
			$cate_ids = array_merge($cate_ids,$temp_cate_ids);
		}

		if(count($cate_ids)>0)
		{
			$condition.= " and cate_id in (".implode(",",$cate_ids).")";

		}

		if($loc!="")
        {
            $condition.=" and (province = '".$loc."' or city = '".$loc."') ";
		}
		if($tag!="")
		{
			$unicode_tag = str_to_unicode_string($tag);
			$condition.=" and match(tags_match) against('".$unicode_tag."'  IN BOOLEAN MODE) ";
		}

		if($kw!="")
		{
			$kws_div = div_str($kw);
			foreach($kws_div as $k=>$item)
			{

				$kws[$k] = str_to_unicode_string($item);
			}
			$ukeyword = implode(" ",$kws);
			$condition.=" and (match(name_match) against('".$ukeyword."'  IN BOOLEAN MODE) or match(tags_match) against('".$ukeyword."'  IN BOOLEAN MODE)  or name like '%".$kw."%') ";

		}


		$result = get_deal_list($limit,$condition);
		$GLOBALS['tmpl']->assign("deal_list",$result['list']);
		$data['html'] = $GLOBALS['tmpl']->fetch("inc/deal_list.html");

		if($step*$step_size<$page_size)
		{
			if($result['rs_count']<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{

				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}


	}

	public function dealupdate()
	{

		$id = intval($_REQUEST['id']);
		$deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$id." and is_delete = 0 and (is_effect = 1 or (is_effect = 0 and user_id = ".intval($GLOBALS['user_info']['id'])."))");
		if(!$deal_info)
		{
			ajax_return(array("step"=>0));
		}
		else
		{
			$GLOBALS['tmpl']->assign("deal_info",$deal_info);
		}

		$page_size = DEALUPDATE_PAGE_SIZE;
		$step_size = DEALUPDATE_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$log_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_log where deal_id = ".$deal_info['id']." order by create_time desc limit ".$limit);
		$log_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal_log where deal_id = ".$deal_info['id']);

		if(!$log_list)
		{
			ajax_return(array("step"=>0));
		}
		if((($page-1)*$page_size+($step-1)*$step_size)+count($log_list)>=$log_count)
		{
			//最后一页
			$log_list[] = array("deal_id"=>$deal_info['id'],
								"create_time"=>$deal_info['begin_time']+1,
								"id"=>0);
		}

		$last_time_key = "";
		foreach($log_list as $k=>$v)
		{
			$log_list[$k]['pass_time'] = pass_date($v['create_time']);
			$online_time = online_date($v['create_time'],$deal_info['begin_time']);
			$log_list[$k]['online_time'] = $online_time['info'];
			if($online_time['key']!=$last_time_key)
			{
				$last_time_key = $log_list[$k]['online_time_key'] = $online_time['key'];
			}
			$log_list[$k] = cache_log_comment($log_list[$k]);
		}


		$GLOBALS['tmpl']->assign("log_list",$log_list);
		$data['html'] = $GLOBALS['tmpl']->fetch("inc/time_line_item.html");
		//$data['html'] = "select * from ".DB_PREFIX."deal_log where deal_id = ".$deal_info['id']." order by create_time desc limit ".$limit;

		if($step*$step_size<$page_size)
		{
			if($log_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{

				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}
	}

	public function login()
	{
		$GLOBALS['tmpl']->display("inc/user_login_box.html");
	}


	public function homeindex()
	{
		$id = intval($_REQUEST['id']);
		$page_size = DEAL_PAGE_SIZE;
		$step_size = DEAL_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$condition = " is_delete = 0 and is_effect = 1 and user_id = ".$id." ";

		$deal_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal where ".$condition." order by sort asc limit ".$limit);

		$deal_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal where ".$condition);
		foreach($deal_list as $k=>$v)
		{
			$deal_list[$k]['remain_days'] = floor(($v['end_time'] - NOW_TIME)/(24*3600));
			$deal_list[$k]['percent'] = round($v['support_amount']/$v['limit_price']*100);
		}
		$GLOBALS['tmpl']->assign("deal_list",$deal_list);
		$data['html'] = $GLOBALS['tmpl']->fetch("inc/deal_list.html");

		if($step*$step_size<$page_size)
		{
			if($deal_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{
				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}
	}

	public function homesupport()
	{
		$id = intval($_REQUEST['id']);
		$page_size = DEAL_PAGE_SIZE;
		$step_size = DEAL_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$sql = "select distinct(d.id) as id,d.* from ".DB_PREFIX."deal as d left join ".DB_PREFIX."deal_support_log as dsl on d.id = dsl.deal_id ".
			   " where dsl.user_id = ".$id." order by d.sort asc limit ".$limit;

		$sql_count = "select count(distinct(d.id)) from ".DB_PREFIX."deal as d left join ".DB_PREFIX."deal_support_log as dsl on d.id = dsl.deal_id ".
			   " where dsl.user_id = ".$id;

		$deal_list = $GLOBALS['db']->getAll($sql);
		$deal_count = $GLOBALS['db']->getOne($sql_count);

		foreach($deal_list as $k=>$v)
		{
			$deal_list[$k]['remain_days'] = floor(($v['end_time'] - NOW_TIME)/(24*3600));
			$deal_list[$k]['percent'] = round($v['support_amount']/$v['limit_price']*100);
		}
		$GLOBALS['tmpl']->assign("deal_list",$deal_list);
		$data['html'] = $GLOBALS['tmpl']->fetch("inc/deal_list.html");

		if($step*$step_size<$page_size)
		{
			if($deal_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{
				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}
	}

	public function news()
	{

		$page_size = DEALUPDATE_PAGE_SIZE;
		$step_size = DEALUPDATE_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$log_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_log order by create_time desc limit ".$limit);
		$log_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal_log");

		foreach($log_list as $k=>$v)
		{
			$log_list[$k]['pass_time'] = pass_date($v['create_time']);
			$log_list[$k] = cache_log_comment($log_list[$k]);
			$log_list[$k] = cache_log_deal($log_list[$k]);
		}
		$GLOBALS['tmpl']->assign('log_list',$log_list);

		$data['html'] = $GLOBALS['tmpl']->fetch("inc/news_item.html");

		if($step*$step_size<$page_size)
		{
			if($log_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{
				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}
	}

	public function newsfav()
	{

		$page_size = DEALUPDATE_PAGE_SIZE;
		$step_size = DEALUPDATE_STEP_SIZE;

		$step = intval($_REQUEST['step']);
		if($step==0)$step = 1;
		$page = intval($_REQUEST['p']);
		if($page==0)$page = 1;
		$limit = (($page-1)*$page_size+($step-1)*$step_size).",".$step_size	;

		$sql = "select dl.* from ".DB_PREFIX."deal_log as dl left join ".DB_PREFIX."deal_focus_log as dfl on dl.deal_id = dfl.deal_id where dfl.user_id = ".intval($GLOBALS['user_info']['id'])." order by dl.create_time desc limit ".$limit;
		$sql_count = "select count(*) from ".DB_PREFIX."deal_log as dl left join ".DB_PREFIX."deal_focus_log as dfl on dl.deal_id = dfl.deal_id where dfl.user_id = ".intval($GLOBALS['user_info']['id']);

		$log_list = $GLOBALS['db']->getAll($sql);
		$log_count = $GLOBALS['db']->getOne($sql_count);

		foreach($log_list as $k=>$v)
		{
			$log_list[$k]['pass_time'] = pass_date($v['create_time']);
			$log_list[$k] = cache_log_comment($log_list[$k]);
			$log_list[$k] = cache_log_deal($log_list[$k]);
		}
		$GLOBALS['tmpl']->assign('log_list',$log_list);

		$data['html'] = $GLOBALS['tmpl']->fetch("inc/news_item.html");

		if($step*$step_size<$page_size)
		{
			if($log_count<=(($page-1)*$page_size+($step-1)*$step_size)+$step_size)
			{
				$data['step'] = 0;
				ajax_return($data);
			}
			else
			{
				$data['step'] = $step+1;
				ajax_return($data);
			}
		}
		else
		{
			$data['step'] = 0;
			ajax_return($data);
		}
	}


	public function randdeal()
	{
		$rand_deals = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal where is_delete = 0 and is_effect = 1 and begin_time < ".NOW_TIME." and (end_time >".NOW_TIME." or end_time = 0) order by rand() limit 3");
		$GLOBALS['tmpl']->assign("rand_deals",$rand_deals);
		$GLOBALS['tmpl']->display("inc/rand_deals.html");
	}

	public function usermessage()
	{
		if(!$GLOBALS['user_info'])
		{
			$data['status'] = 2;
			ajax_return($data);
		}
		$id = intval($_REQUEST['id']);
		if($id==$GLOBALS['user_info']['id'])
		{
			$data['status'] = 0;
			$data['info'] = "不能给自己发私信";
			ajax_return($data);
		}
		$send_user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id." and is_effect = 1");
		if(!$send_user_info)
		{
			$data['status'] = 0;
			$data['info'] = "收信人不存在";
			ajax_return($data);
		}
		else
		{
			$GLOBALS['tmpl']->assign("send_user_info",$send_user_info);
			$data['status'] = 1;
			$data['html'] = $GLOBALS['tmpl']->fetch("inc/usermessage.html");
			ajax_return($data);
		}

	}

	public function close_notify()
	{
		es_cookie::set("hide_user_notify",1);
	}

	public function add_deal_faq()
	{
		$GLOBALS['tmpl']->display("inc/deal_faq_item.html");
	}
	public function check_field()
	{
		$field_name = addslashes(trim($_REQUEST['field_name']));
		$field_data = addslashes(trim($_REQUEST['field_data']));
		require_once APP_ROOT_PATH."system/libs/user.php";
		$res = check_user($field_name,$field_data);
		$result = array("status"=>1,"info"=>'');
		if($res['status'])
		{
			ajax_return($result);
		}
		else
		{
			$error = $res['data'];
			if(!$error['field_show_name'])
			{
					$error['field_show_name'] = $GLOBALS['lang']['USER_TITLE_'.strtoupper($error['field_name'])];
			}
			if($error['error']==EMPTY_ERROR)
			{
				$error_msg = sprintf("不能为空",$error['field_show_name']);
			}
			if($error['error']==FORMAT_ERROR)
			{
				$error_msg = sprintf("格式错误，请重新输入",$error['field_show_name']);
			}
			if($error['error']==EXIST_ERROR)
			{
				$error_msg = sprintf("已存在，请重新输入",$error['field_show_name']);
			}
			$result['status'] = 0;
			$result['info'] = $error_msg;
			ajax_return($result);
		}
	}

public function send_mobile_verify_code()
	{
		if(app_conf("SMS_ON")==0)
		{
			$data['status'] = 0;
			$data['info'] = "短信未开启";
			ajax_return($data);
		}
		$mobile = addslashes(htmlspecialchars(trim($_REQUEST['mobile'])));
		//is_only 为1的话，表示不允许手机号重复
		$is_only=intval($_REQUEST['is_only']);
		if($mobile == '')
		{
			$data['status'] = 0;
			$data['info'] = "请输入你的手机号";
			ajax_return($data);
		}

		if(!check_mobile($mobile))
		{
			$data['status'] = 0;
			$data['info'] = "请填写正确的手机号码";
			ajax_return($data);
		}

		if($is_only==1){
			$condition_1=" and mobile='".$mobile."' ";
			if($GLOBALS['user_info']['id']){
				$condition_1.=" and id!=".$GLOBALS['user_info']['id'];
			}
			if($GLOBALS['db']->getOne("select count(*) from  ".DB_PREFIX."user where 1=1 $condition_1 ")>0){
				$data['status'] = 0;
				$data['info'] = "该手机号已经存在";
				ajax_return($data);
			}
		}

 		$field_name = addslashes(trim($_REQUEST['mobile']));
		$field_data = $mobile;
		require_once APP_ROOT_PATH."system/libs/user.php";
		$res = check_user($field_name,$field_data);

		$result = array("status"=>1,"info"=>'');
		if(!$res['status'])
		{
			$error = $res['data'];
			if(!$error['field_show_name'])
			{
				$error['field_show_name'] = "手机号码";
			}
			if($error['error']==EMPTY_ERROR)
			{
				$error_msg = sprintf("手机号码不能为空",$error['field_show_name']);
			}
			if($error['error']==FORMAT_ERROR)
			{
				$error_msg = sprintf("格式错误，请重新输入",$error['field_show_name']);
			}
			if($error['error']==EXIST_ERROR)
			{
				$error_msg = sprintf("已存在，请重新输入",$error['field_show_name']);
			}
			$result['status'] = 0;
			$result['info'] = $error_msg;
			ajax_return($result);
		}


		if(!check_ipop_limit(get_client_ip(),"mobile_verify",60,0))
		{
			$data['status'] = 0;
			$data['info'] = "发送速度太快了";
			ajax_return($data);
		}
		$n_time=get_gmtime()-900;
		//删除超过5分钟的验证码
		$GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."mobile_verify_code WHERE create_time <=".$n_time);
		$codeInfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."mobile_verify_code where mobile = '".$mobile."' and client_ip='".get_client_ip()."' and create_time>=".$n_time." ORDER BY id DESC limit 1");
		if($codeInfo)
		{
			send_verify_sms($mobile,$codeInfo['verify_code']);
			$data['status'] = 1;
			$data['info'] = "验证码发送成功，10分钟内有效。";
			ajax_return($data);
		}else{
				//开始生成手机验证
				$code = rand(100000,999999);
				$GLOBALS['db']->autoExecute(DB_PREFIX."mobile_verify_code",array("verify_code"=>$code,"mobile"=>$mobile,"create_time"=>get_gmtime(),"client_ip"=>get_client_ip()),"INSERT");
		
				send_verify_sms($mobile,$code);
				$data['status'] = 1;
				$data['info'] = "验证码发送成功，10分钟内有效。";
				ajax_return($data);
		}
	}
	public function send_mobie_pwd_sncode_new(){
		if(app_conf("SMS_ON")==0)
		{
			$data['status'] = 0;
			$data['info'] = $GLOBALS['lang']['SMS_OFF'];
			ajax_return($data);
		}
		$mobile = addslashes(htmlspecialchars(trim($_REQUEST['mobile'])));

		if($mobile == '')
		{
			$data['status'] = 0;
			$data['info'] = "请输入你的手机号";
			ajax_return($data);
		}

		if(!check_mobile($mobile))
		{
			$data['status'] = 0;
			$data['info'] = "请填写正确的手机号码";
			ajax_return($data);
		}

		$field_name = addslashes(trim($_REQUEST['mobile']));
		$field_data = $mobile;
		$user_id=$GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where mobile='".$field_data."' ");

		if($user_id){
			if(!check_ipop_limit(get_client_ip(),"mobile_verify",60,0))
			{
				$data['status'] = 0;
				$data['info'] = "发送速度太快了";
				ajax_return($data);
			}
			
			$n_time=get_gmtime()-900;
			//删除超过5分钟的验证码
			$GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."mobile_verify_code WHERE create_time <=".$n_time);
			$codeInfo = $GLOBALS['db']->getRow("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile = '".$mobile."' and client_ip='".get_client_ip()."' and create_time>=".$n_time." ORDER BY id DESC limit 1");
			if($codeInfo)
			{
				send_verify_sms($mobile,$codeInfo['verify_code']);
				$data['status'] = 1;
				$data['info'] = "验证码发送成功";
				ajax_return($data);
			}else{
					//开始生成手机验证
					$verify_code = rand(100000,999999);
                print_r("verify_code".$verify_code);
					$GLOBALS['db']->autoExecute(DB_PREFIX."mobile_verify_code",array("verify_code"=>$verify_code,"mobile"=>$mobile,"create_time"=>get_gmtime(),"client_ip"=>get_client_ip()),"INSERT");
					//使用立即发送方式
					send_verify_sms($mobile,$verify_code);
					$data['status'] = 1;
					$data['info'] = "验证码发送成功";
					ajax_return($data);
			}
		}else{
			$result['status'] = 0;
			$result['info'] = "该手机不存在，请重新输入";
			ajax_return($result);
		}
	}
	public function set_repay_make(){
		$id = intval($_REQUEST['id']);
		$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$id." and repay_time>0 and user_id = ".intval($GLOBALS['user_info']['id']));
		if(!$order_info)
		{
			showErr("无效的项目支持",1);
		}else{
			if($order_info['repay_make_time']==0){
				$GLOBALS['db']->query("update ".DB_PREFIX."deal_order set repay_make_time =  ".get_gmtime()." where id = ".$order_info['id']." and user_id = ".intval($GLOBALS['user_info']['id']));
				showSuccess("设置成功",1);
			}
		}
	}
	//投资人详细信息
	function investor_detailed_information(){
		$id=intval($_REQUEST['id']);
		$result=array('status'=>'','info'=>'','url'=>'','html'=>'');
		if($id>0){
			//投资人信息
			$investor_info=$GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."user where id =$id");
			foreach($investor_info as $k=>$v){
				$investor_info['image']=get_user_avatar($v['id'], "middle");
			}
			$GLOBALS['tmpl']->assign("investor_info",$investor_info);
 			$result['html']= $GLOBALS['tmpl']->fetch("inc/investor_detailed_info.html");
			$result['status']=1;
			$result['user_name']=$investor_info['user_name'];
			ajax_return($result);
		}else{
			$result['status']=2;
			$result['info']="系统繁忙，请您稍后重试！";
			ajax_return($result);
		}
		return false;
	}
	/*获取会员所有项目列表*/
	public function ajax_get_recommend_project(){
		$result=array('status'=>'','info'=>'','url'=>'','html'=>'');
		if(!$GLOBALS['user_info'])
		{
			$result['status']=0;
			$result['info']="未登入";
			ajax_return($result);
			return false;
		}
		//推荐人id
		$id=intval($GLOBALS['user_info']['id']);
		//被推荐人id
		$user_id=intval($_REQUEST['user_id']);
		$effective_deal_info=get_effective_deal_info($id);
 		if(!$effective_deal_info){
			$result['status']=1;
			$result['info']="请您先创建项目！";
			ajax_return($result);
			return false;
		}else{
			$result['status']=2;
			$result['info']="项目列表！";
			$GLOBALS['tmpl']->assign("recommend_user_id",$id);
			$GLOBALS['tmpl']->assign("user_id",$user_id);
			$GLOBALS['tmpl']->assign("effective_deal_info",$effective_deal_info);
			$result['html'] = $GLOBALS['tmpl']->fetch("inc/ajax_get_recommend_project.html");
			ajax_return($result);
			return false;
		}
 	}
 	/*保存推荐内容,数据库是fanwe_recommend*/
	public function ajax_recommend_save(){
		$result=array('status'=>'','info'=>'','url'=>'','html'=>'');
		$memo=strim($_POST['memo']);
		//推荐项目id
		$deal_id=intval($_POST['deal_id']);
		//推荐项目图片
		$deal_image=strim($_POST['deal_image'])!=""?replace_public($_POST['deal_image']):"";
		//推荐项目名字
		$deal_name=strim($_POST['deal_name']);
		//被推荐人id
		$user_id=intval($_POST['user_id']);
		//项目类型 0普通 1股权
		$deal_type=intval($_POST['deal_type']);
		//推荐人id
		$recommend_user_id=intval($_POST['recommend_user_id']);
		$create_time=NOW_TIME;
		if($deal_id==null){
			$result['status']=0;
			$result['info']="请选择推荐项目！";
			ajax_return($result);
			return false;
		}
		if($memo==null){
			$result['status']=0;
			$result['info']="推荐理由不能为空！";
			ajax_return($result);
			return false;
		}
		if($GLOBALS['db']->autoExecute(DB_PREFIX."recommend",array("memo"=>$memo,"deal_id"=>$deal_id,"user_id"=>$user_id,"recommend_user_id"=>$recommend_user_id,"create_time"=>$create_time,"deal_type"=>$deal_type,"deal_name"=>$deal_name,"deal_image"=>$deal_image),"INSERT")>0){
			$result['status']=1;
			$result['info']="项目推荐成功！";
			ajax_return($result);
			return false;
		}else{
			$result['status']=0;
			$result['info']="系统繁忙,请您稍后重试！";
			ajax_return($result);
			return false;
		}
	}
	/*删除推荐项目*/
	public function ajax_delete_recommend(){
		$result=array('status'=>'','info'=>'','url'=>'','html'=>'');
		$id=intval($_POST['id']);
		if($id>0){
			if($GLOBALS['db']->query("delete from ".DB_PREFIX."recommend where id = ".$id)>0){
				$result['status']=1;
				$result['info']="删除成功！";
				ajax_return($result);
				return false;
			}else{
				$result['status']=0;
				$result['info']="删除失败！";
				ajax_return($result);
				return false;
			}
		}else{
			$result['status']=0;
			$result['info']="系统繁忙,请您稍后重试！";
			ajax_return($result);
			return false;
		}
	}
	//领投人详细信息
	function leader_detailed_information(){
		$id=intval($_REQUEST['id']);

		$result=array('status'=>'','info'=>'','url'=>'','html'=>'');
		if($id>0){
			//领投信息

			$leader_info=$GLOBALS['db']->getRow("select inv.*,u.user_name,u.identify_name from  ".DB_PREFIX."investment_list as inv left join ".DB_PREFIX."user as u on u.id=inv.user_id where inv.deal_id=".$id." and type=1");
			//echo "select inv.*,u.user_name,u.identify_name from  ".DB_PREFIX."investment_list as inv left join ".DB_PREFIX."user as u on u.id=inv.user_id where inv.deal_id=".$id." and type=1";die();
			$GLOBALS['tmpl']->assign("leader_info",$leader_info);
			$result['html']= $GLOBALS['tmpl']->fetch("inc/leader_detailed_info.html");
			$result['status']=1;
			ajax_return($result);
		}else{
			$result['status']=2;
			$result['info']="系统繁忙，请您稍后重试！";
			ajax_return($result);
		}
		return false;
	}
	public function three_seconds_jump(){
		$id=intval($_REQUEST['id']);
		$GLOBALS['tmpl']->assign("id",$id);
		$GLOBALS['tmpl']->assign("page_title","用户手机绑定");
		$GLOBALS['tmpl']->display("inc/three_seconds_jump.html");
	}
	
	/*检测是否已经购买过爱钱进红包*/
	public function check_iqianjin(){
		$dealItemId = intval($_REQUEST['item_id']);
		$user_info = es_session::get('user_info');
		$data = array();
		$dealItemInfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."iqianjin_deal_item where deal_item_id=".$dealItemId);
		if($dealItemInfo){
			if($dealItemInfo['type']==1){
				$iqianjinWinArray = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."iqianjin_winner where user_id=".$user_info['id']);
				if($iqianjinWinArray){
					$data['status']=3;
					$data['message']='您已中奖！获得了爱钱进普通红包，请您到爱钱进领取。';
				}else{
					$iqianjinBuyList = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."iqianjin_buy where deal_item_id=".$dealItemId." and user_id=".$user_info['id']);
					if($iqianjinBuyList){
						$data['status']=2;
						$data['message']='您今天已经购买了本红包，请明天12:00再来支持。';
					}else{
						$data['status']=1;
					}
				}
			}else{
				$iqianjinBuyList = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."iqianjin_buy where user_id=".$user_info['id']);
				if($iqianjinBuyList){
					$data['status']=2;
					$data['message']='您已经购买了本红包，请您到爱钱进领取。';
				}else{
					$data['status']=1;
				}
			}
		}else{
			$data['status']=1;
		}
		ajax_return($data);
	}
    /*用于绑定解绑第三方帐号 20160120*/
    public function unbindWechat(){
        $userid = $_REQUEST['userid'];
        $del_sql = "delete from ".DB_PREFIX."user_idx where userid = ".$userid;
        $select_sql = "select id from ".DB_PREFIX."user_idx where userid = ".$userid;
        $id = $GLOBALS['db']->getOne($select_sql);
        if($id){
            if($GLOBALS['db']->query($del_sql)>0){
                $result['status']=1;
                $result['info']="删除成功！";
                ajax_return($result);
                return false;
            }else{
                $result['status']=0;
                $result['info']="删除失败！";
                ajax_return($result);
                return false;
            }
        }else{
            $result['status']=0;
            $result['info']="删除失败：没有绑定信息！";
            ajax_return($result);
            return false;
        }
    }
}
?>