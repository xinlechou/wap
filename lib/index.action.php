<?php
class indexModule{
	public function index()
	{		
		$root = array();
		$root['response_code'] = 1;

		$root['kf_phone'] = $GLOBALS['m_config']['kf_phone'];//客服电话
		$root['kf_email'] = $GLOBALS['m_config']['kf_email'];//客服邮箱
		
 		//关于我们(填文章ID)
		$root['about_info'] = intval($GLOBALS['m_config']['about_info']);
		$root['version'] = VERSION; //接口版本号int
		$root['page_size'] = PAGE_SIZE;//默认分页大小
		$root['program_title'] = $GLOBALS['m_config']['program_title'];
		$root['site_domain'] = str_replace("/mapi", "", SITE_DOMAIN.APP_ROOT);//站点域名;
		$root['site_domain'] = str_replace("http://", "", $root['site_domain']);//站点域名;
		$root['site_domain'] = str_replace("https://", "", $root['site_domain']);//站点域名;
		
		/*虚拟的累计项目总个数，支持总人数，项目支持总金额*/ 
	 	$virtual_effect = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal where is_effect = 1 and is_delete=0");
	 	$virtual_person =  $GLOBALS['db']->getOne("select sum((support_count+virtual_person)) from ".DB_PREFIX."deal_item");
	 	$virtual_money =  $GLOBALS['db']->getOne("select sum((support_count+virtual_person)*price) from ".DB_PREFIX."deal_item");

	 	$root['virtual_effect'] = $virtual_effect;//项目总个数
		$root['virtual_person'] = $virtual_person;//累计支持人
		$root['virtual_money'] =number_format($virtual_money,2);//筹资总金额
	
	    /*虚拟的累计项目总个数，支持总人数，项目支持总金额 结束*/
	    /*取6个推荐项目*/
		$index_list = $GLOBALS['db']->getAll(" select * from ".DB_PREFIX."deal where is_effect=1 and is_recommend=1 order by sort desc limit 0,6");
		$deal_list = array();
		$time = time();
		foreach($index_list as $k=>$v)
		{
			if ($v['image'] != ''){
				$v['image'] = get_abs_img_root_wap(get_spec_image($v['image'],900,480,1));
			}
			if($v['start_time']>$time){
				$tmp_start_day = ceil(($v['start_time'] - $time)/86400);
				$v['show'] = "还有".$tmp_start_day."天开始";
			}elseif($time>$v['end_time']){
				$v['show'] = "已过期";
			}else{
				$tmp_end_day = ceil(($v['end_time'] - $time)/86400);
				$v['show'] = "剩余".$tmp_end_day."天";
			}
			$deal_list[] = $v;
		}
 		$GLOBALS['tmpl']->assign('deal_list',$deal_list);

		/*取3个专题*/
		$topic_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."zhuanti where state=1 order by sort desc limit 0,3");
        //获取所有分类
        $cates = getCateList();
 		$GLOBALS['tmpl']->assign('topic_list',$topic_list);
        $GLOBALS['tmpl']->assign('cates',$cates);
        $GLOBALS['tmpl']->display("index.html");
	}
    public function ajaxTopic(){
        $start = $_REQUEST['s'];$len = 3;
        $topic_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."zhuanti where state=1 order by sort desc limit ".$start.",".$len);
        if(empty($topic_list)){
            $result = array("status"=>0,"msg"=>"没有了");
        }else{
            $result = array("status"=>1,"msg"=>$topic_list);
        };
        ajax_return(json_encode($result));
    }
}

?>