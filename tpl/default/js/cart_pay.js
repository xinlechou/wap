$(document).ready(function(){
	bind_pay_form();
});



function bind_pay_form()
{
	var max_pay = $("#pay_form").find("input[name='max_pay']").val();
	var max_credit = $("#pay_form").find("input[name='max_credit']").val();
	var max_val = parseFloat(max_pay)<parseFloat(max_credit)?parseFloat(max_pay):parseFloat(max_credit);
	$("#pay_form").find("input[name='credit']").bind("keyup blur",function(){
		var money = $(this).val();
		if(isNaN(money)||parseFloat(money)<=0)
		{
			$("#pay_form").find("input[name='credit']").val("0");
		}
		else
		{
			if(parseFloat(money)>max_val)
			{
				$("#pay_form").find("input[name='credit']").val(max_val);
			}
			if(parseFloat(money)>=max_pay)
			{
				$("#pay_form").find("input[name='payment']:checked").attr("checked",false);
			}
		}
	});
	
	$("#submit").bind("click",function(){
		$("#pay_form").submit();
	});
	$("#pay_form").bind("submit",function(){
		var max_pay = $("input[name='max_pay']").val();
 		var max_credit = $("input[name='max_credit']").val();
		var max_val = parseFloat(max_pay)<parseFloat(max_credit)?parseFloat(max_pay):parseFloat(max_credit);


		var money = $("input[name='credit']").val();
		 
		if(!isNaN(money))
		{
			if(parseFloat(money)<max_pay)
			{
				if($("input[name='payment']:checked").length==0)
				{
					$.showErr("请选择支付方式");
					return false;
				}	
			}
		}
		else{
			
			if($("input[name='payment']:checked").length==0)
				{
					$.showErr("请选择支付方式");
					return false;
				}	
		}
		
		
		if($("input[name='is_address']").val()==1){
			var consignee = $("input[name='consignee_id']").val();
			if(!isNaN(consignee))
			{
				if($("input[name='consignee_id']:checked").length==0)
				{
					$.showErr("请选择配送方式");
					return false;
				}	
			}else{
				if($("input[name='consignee']").val()==''){
					$.showErr("请填写收件人");
					return false;
				}
				if($("#province option:selected").val()==0){
					$.showErr("请选择省份");
					return false;
				}
				if($("#city option:selected").val()==0){
					$.showErr("请选择城市");
					return false;
				}
				if($("#address").val()==''){
					$.showErr("请填写详细地址");
					return false;
				}
				if($("input[name='zip']").val()==''){
					$.showErr("请填写邮编");
					return false;
				}
				var pattern = /^[0-9]{6}$/;
				if(!pattern.test($("input[name='zip']").val())) 
				{ 
				    alert('请输入有效的邮编！'); 
				    return false; 
				} 
				if($("input[name='mobile']").val()==''){
					$.showErr("请填写手机");
					return false;
				}
				var myreg = /^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
				if(!myreg.test($("input[name='mobile']").val())) 
				{ 
				    alert('请输入有效的手机号码！'); 
				    return false; 
				} 
			}
		}
		
		
		//show_pay_tip();
		return true;
	
		
	});
}

function show_pay_tip()
{
	var html =  '<div class="pay_tip_box"><div class="empty_tip" style="font-size:14px;">请您在新打开的网银或第三方支付页面上完成付款</div><div class="blank"></div>'+
				'<div class="choose">付款后请选择</div><div class="blank"></div><div class="blank"></div>'+
				'<div class="ui-button green" id="check_payment" rel="green">'+
				'<div>'+
				'	<span>支付成功</span>'+
				'</div>'+
				'</div>	'+
				'<div class="ui-button blue" id="choose_payment" rel="blue" style="margin-left:5px;">'+
				'<div>'+
				'	<span>支付遇到问题</span>'+
				'</div>'+
				'</div> </div><div class="blank"></div>	';
	$.weeboxs.open(html, {boxid:'pay_tip',contentType:'text',showButton:false, showCancel:false, showOk:false,title:'提示',width:450,type:'wee'});

	$("#choose_payment").bind("click",function(){
		close_pop();
	});
	$("#check_payment").bind("click",function(){
		location.href = $("#back_url").val();
	});
}