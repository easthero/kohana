<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">	
	<title>系统首页</title>
<script type="text/javascript" src="assets/js/jquery.js"></script>
<script type="text/javascript" src="assets/js/jquery-ui.js"></script> 
<link type="text/css" href="assets/css/jquery-ui.css" rel="stylesheet" /> 
<script type="text/javascript">
$(document).ready (function(){
	$(function() {
		$("#tabs").tabs();
	});
});
</script>
</head>
<body>

<div id="wrap">

<div id="head">
<p>banner</p>
<!-- <img src="assets/images/logo.png"></img> -->
<form action="login/out" method="post">
<input type="submit" value="注销" />
</form>
</div>


<div id="tabs">
<ul>
<li><a href="dashboard">系统状态</a></li>
<?php
switch($role_id){
case "4":
	echo '<li><a href="setting">设置</a></li>';
	echo '<li><a href="user">用户管理</a></li>';
	break;
case "2":
	echo '<li><a href="log">日志</a></li>';
	echo '<li><a href="user">用户管理</a></li>';
	break;
case "1":
	echo '<li><a href="event">事件</a></li>';
	echo '<li><a href="report">报表</a></li>';
	break;
}
?>
</ul>
</div>

<div id="footer">
<p>公司名称 版本信息 版权信息</p>
</div>

</div>
</body>
</html>
