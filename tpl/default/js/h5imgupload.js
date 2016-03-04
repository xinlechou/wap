
function upd_file(obj,file_id,uid)
{	
	$("input[name='"+file_id+"']").bind("change",function(){
		$(obj).hide();
		$("#share_div").show();
		  $.ajaxFileUpload
		   (
			   {
				    url:upimgurl,
				    secureuri:false,
				    fileElementId:file_id,
				    dataType: 'json',
				    success: function (data, status)
				    {
				   		$(obj).show();
				   		$("#share_div").hide();
				   		if(data.status==1)
				   		{
				   			document.getElementById("h5img").src = data.img_url+"?r="+Math.random();
				   			$("#similar_id").val(data.similar_id);
				   			$("#state").val(1);
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