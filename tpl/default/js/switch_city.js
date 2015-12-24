//切换地区
$(document).ready(function(){
		$("#province").live("change",function(){
			load_city();
            var v = $(this).val();
            $("#provinceValue").html(v);
            $("#area").attr('data-province',v);
		});
        $("#city").live("change",function(){
            var v = $(this).val();
            $("#area").attr('data-city',v);
            $("#cityValue").html(v)
        })
});
	
function load_city()
{
		var id = $("#province").find("option:selected").attr("rel");
		
		var evalStr="regionConf.r"+id+".c";

		if(id==0)
		{
			var html = "<option value=''>请选择城市</option>";
		}
		else
		{
			var regionConfs=eval(evalStr);
			evalStr+=".";
			var html = "<option value=''>请选择城市</option>";
			for(var key in regionConfs)
			{
				html+="<option value='"+eval(evalStr+key+".n")+"' rel='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
			}
		}
		$("#city").html(html);
        $("#cityValue").html('请选择城市');
}