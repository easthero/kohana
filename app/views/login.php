<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">	
	<title>用户登录页面</title>
<script type="text/javascript" src="assets/js/jquery.js"></script>
<script type="text/javascript" src="assets/js/jquery-ui.js"></script> 
<script type="text/javascript" src="assets/js/jquery.validate.js"></script> 
<link type="text/css" href="assets/css/jquery-ui.css" rel="stylesheet" /> 
<style type="text/css">
.error-message {
	color: red;
}
</style>

<script type="text/javascript">
$(document).ready (function(){
	$('#loginform').validate({
		rules:{
			username: "required",
			password: "required",
		},
		messages:{
			username: " * ",
			password: " * ",
		},
		errorClass: "error-message",
		submitHandler: function(form){
			var req = $("#loginform").serialize();
			$.post("login/validate", req, function(data){
				var status = data.response.status;
				if (status == -1){
					message = data.response.data;
					$('#response').text(message);
				}else{
					location.href='main';
				}
			});
		},
	});

});
</script>

</head>
<body>
<form id="loginform">
<fieldset>
<legend>登录</legend>
<ul>
<li>
	<label for="username">用户名</label>
	<input class="required" type="text" name="username" id="username" />
</li>
<li>
	<label for="password">密码</label>
	<input class="required" type="password" name="password" id="password" />
</li>
</ul>
<ul>
<li>
	<input type="submit" id="submit" class="submit" value="登录">
</li>
</ul>
</fieldset>
</form>
<span id="response"></span>
</body>
</html>
