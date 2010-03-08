<style type="text/css">
.error-message {
	color: red;
}
</style>
<script type="text/javascript">
	$(document).ready (function(){
		$('#changepwd_form').validate({
			rules:{
				password: {required:true, minlength:6},
				repeatedpassword: {required:true, minlength:6, equalTo:"#password"},
			},
			messages:{
				password: " 最小长度6位 ",
				repeatedpassword: " 两次密码输入不相同 ",
			},
			errorClass: "error-message",
			submitHandler: function(form){
				var req = $("#changepwd_form").serialize();
				$.post("user/changepwd", req, function(data){
					var status = data.response.status;
					if(status == 0){
						$('#result').text('密码修改成功');
					}else{
						var message = data.response.data;
						$('#result').text(message);
					}
				});
			},
		});
})

</script>
<form id="changepwd_form">
	<ul>
		<li>
		<label for="password">密码</label>
		<input type="password" name="password" id="password"></input>
		</li>
		<li>
		<label for="repeatedpassword">确认密码</label>
		<input type="password" name="repeatedpassword" id="repeatedpassword"></input>
		</li>
		<input type="hidden" name="id" value="<?php echo $id; ?>"</input> 
		<li>
		<input type="submit" id="changepwd_submit" value="提交"></input>
		<input type="button" class="nyroModalClose" value="关闭页面"></input>
		</li>
	</ul>
</form>
<div id="result"></div>
