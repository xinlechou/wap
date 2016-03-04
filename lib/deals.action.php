<?php
class dealsModule{
	function __construct() {$GLOBALS['tmpl']->assign('now',NOW_TIME);}

	public function index()
	{
        $is_ap = $_REQUEST['isap'];//判断是否是爱前进积分商城
        //积分商城现判断用户合法性
        if($is_ap == 1){
        	if($_REQUEST['token']){
			    	$token=$_REQUEST['token'];
        		$token_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_token where token='".$token."'");
        		if(!$token_info){
        			app_redirect(url_wap("user#login"));
        		}
        		if($GLOBALS['user_info']){
			    		$partner_user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_partner_user where partner_user_id='".$token_info['iqj_user_id']."'");
			    		if($partner_user_info['user_id'] != $GLOBALS['user_info']['id']){
				    		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
								es_session::set("gopreview",$url);
				    		app_redirect(url_wap("deals#ap_login",array('token'=>$token)));
				    	}
			    	}else{
				    	$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
							es_session::set("gopreview",$url);
			    		app_redirect(url_wap("deals#ap_login",array('token'=>$token)));
				    }
		    	}else{
			    	if(!$GLOBALS['user_info']){
			    		app_redirect(url_wap("user#login"));
			    	}
			    }
        }
        $page_title = $is_ap==1?"积分商城":"推荐项目";
        //获取页码
        $p = $_REQUEST['p']?intval($_REQUEST['p']):1;
		$to_num = ($p-1) * 10;
		$condition = " d.is_delete = 0 and d.is_effect = 1 ";
        if($is_ap==1){$condition .= "and (d.is_ap = 1 or d.is_ap = 2) ";}else{$condition .= "and (d.is_ap = 0 or d.is_ap = 2) ";};//积分商城时需判断

        $limit = $to_num.' , 10';
        /*
         * 增加搜索条件
         * 1.cate 分类搜索
         * 2.status 状态搜索
         *      status=1  已成功
                status=2  失败
                status=3  筹资中
                status=4  长期项目
                status=0  未开始
                all 全部
            3.keyword 关键字搜索
         * */
        if($_REQUEST['keyword']){//keyword
            $condition .= "and d.name like N'%";
            $condition .= urldecode($_REQUEST['keyword'])."%' ";
            $page_title = "搜索结果";
        }

        if($_REQUEST['cate']){//cate
            $cates_condition = $_REQUEST['cate']!=="all"?explode(',',$_REQUEST['cate']):"";
            if($cates_condition){
                $condition .= 'and (';
                for($i=0;$i<count($cates_condition);$i++){
                    $condition .= ' d.cate_id = ';
                    $condition .= $cates_condition[$i];
                    if($i<count($cates_condition)-1)
                    {$condition .= ' or';}
                }
                $condition .= ")";
            }
            $page_title = "搜索结果";
        }
        if(isset($_REQUEST['status'])){//status
            $status = $_REQUEST['status']!=="all"?explode(',',$_REQUEST['status']):"";
            $status_condition = "";
            if(!empty($status[0])){
                foreach($status as $key => $v){
                    $status_condition .= $this->get_status_condition($v);//获取该状态sql语句
                    if($key != count($status)-1){$status_condition .= ' or ';}
                }
                $condition .= ' and ';
                $condition .= $status_condition;
                $page_title = "搜索结果";
            }
        }
        /*search condition END*/
//        echo $condition;
		$result = get_deal_list($limit,$condition);
		$index_list = $result['list'];

		$deal_list = array();
		$time = time();

        foreach($index_list as $k=>$v)
        {
            if ($v['image'] != ''){
                $v['image'] = get_abs_img_root_wap(get_spec_image($v['image'],560,220,1));
            }
            if($v['start_time']>$time){
                $tmp_start_day = ceil(($v['start_time'] - $time)/86400);
                $v['show'] = "还有".$tmp_start_day."天开始";
                $v['showtype'] = 1;
            }elseif($time>$v['end_time']){
                $v['show'] = "已过期";
                $v['showtype'] = 2;
            }else{
                $tmp_end_day = ceil(($v['end_time'] - $time)/86400);
                $v['show'] = $tmp_end_day;
                $v['showtype'] = 3;
            }
            $v['cate_name'] = get_cate($v['cate_id']);
            //如果为积分商城，则将总数＊ratio后显示
            if($is_ap == 1){
                $v['support_amount'] = (int)$v['support_amount'] * (int)$v['ap_ratio'];
                $v['limit_price'] =  (int)$v['limit_price'] * (int)$v['ap_ratio'];
            }
            $deal_list[] = $v;
        }
        //获取页面分页数据
       // $alldealnum = $GLOBALS['db']->getRow(" select count(id) as num from ".DB_PREFIX."deal as d where ".$condition);
        $allPNum = ceil($result['rs_count']/ 10);
        $page = get_pages($allPNum);
        //获取所有分类
        $cates = getCateList();
//        print_r($deal_list);
        $GLOBALS['tmpl']->assign('page',$page);
        //数量有待验证
        $GLOBALS['tmpl']->assign('result_count',ceil($result['rs_count']));
        $GLOBALS['tmpl']->assign('page_title',$page_title);
        $GLOBALS['tmpl']->assign('cates',$cates);
 		$GLOBALS['tmpl']->assign('deal_list',$deal_list);
        if($is_ap == 1){
            $GLOBALS['tmpl']->display("ap_deals_index.html");
            exit;
        }
		$GLOBALS['tmpl']->display("deals_index.html");
	
	}
    //进入积分商城时检查用户合法性
    public function ap_login()
    {
        $token = trim($_REQUEST['token']);
        if($token){
        		$aqj_token_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_token where token='".$token."'");
        		if($aqj_token_info){
        			//查询aqj_id是否已绑定过
	            $aqj_user=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."ap_partner_user where partner_user_id='".$aqj_token_info['iqj_user_id']."'");
	            //如果已经绑定过自动登录并进入积分商城页面
	            if($aqj_user && $aqj_user['user_id']!=0){
	                $xlc_user=$GLOBALS['db']->getRow("select mobile,user_pwd from ".DB_PREFIX."user where id=".$aqj_user['user_id']);
	                require_once APP_ROOT_PATH."system/libs/user.php";
	                auto_do_login_user($xlc_user['mobile'],md5($xlc_user['user_pwd']."_EASE_COOKIE"));
	                app_redirect(get_gopreview());
	            }else{
	                $GLOBALS['tmpl']->assign("aqj_id",$aqj_token_info['iqj_user_id']);
	                $GLOBALS['tmpl']->assign("aqj_m",$aqj_token_info['mobile']);
	                $GLOBALS['tmpl']->display("ap_login.html");
	                exit;
	            }
        		}else{
        			app_redirect(url_wap("index"));
        		}
        }else{
        	app_redirect(url_wap("index"));
        }
    }
	public function get_child($cate_list,$pid){
 			foreach($cate_list as $k=>$v)
			{
				if($v['id'] ==  $pid){
					if($v['pid'] > 0){
						$pid =$this->get_child($cate_list,$v['pid']) ;
						if($pid==$v['pid']){
							return $pid;
						}
					}
					else{
						return $pid;
					}
				}
			}
	}
    public function get_status_condition($v){
        /*      status=0  未开始
                status=1  已成功
                status=2  失败
                status=3  筹资中
                status=4  长期项目
               */
        $status_condition = "";
        switch($v){
            case "0"://未开始
                $status_condition = "(d.begin_time >".time().")";break;
            case "1"://筹资成功
                $status_condition = "((d.support_amount/d.limit_price)>=1)";break;
            case "2"://筹资失败
                $status_condition = "(((d.support_amount/d.limit_price)<1) and (d.end_time<".time().") and (d.end_time>0))";break;
            case "3"://筹资中
                $status_condition = "(((d.support_amount/d.limit_price)<1) and (d.end_time>".time().") and (d.begin_time < ".time()."))";break;
            case "4"://长期项目
                $status_condition = "(d.end_time = 0)";break;
        }
        return $status_condition;
    }
 }
?>