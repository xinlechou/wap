
function upd_file(obj,file_id,uid)
{	
	$("input[name='"+file_id+"']").bind("change",function(){
		$(obj).hide();
		$("#share_div").show();
		  $.ajaxFileUpload
		   (
			   {
				    url:APP_ROOT+'/index.php?ctl=papercut&act=uploadimg&m='+m,
				    secureuri:false,
				    fileElementId:file_id,
				    dataType: 'json',
				    success: function (data, status)
				    {
				   		$(obj).show();
				   		$("#share_div").hide();
				   		if(data.status==1)
				   		{
				   			location.href = APP_ROOT+"/index.php?ctl=papercut&act=share&id="+data.papercut_id;
				   		}
				   		else
				   		{
				   			$.showErr(data.msg);
				   		}
				   		
				    },
				    error: function (data, status, e)
				    {
						$.showErr(data.responseText);;
				    	$(obj).show();
				   		$("#share_div").hide();
				    }
			   }
		   );
		  $("input[name='"+file_id+"']").unbind("change");
	});	
}