<script type="text/javascript" src="assets/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript" src="assets/js/jquery.validate.js"></script> 
<script type="text/javascript" src="assets/js/jquery.nyroModal.js"></script>
<link type="text/css" href="assets/css/nyroModal.full.css" rel="stylesheet" /> 
<script type="text/javascript">
$(document).ready (function(){
	$('#userinfo tr:odd').addClass('odd');

	$('a.changepwd').nyroModal();

});

function changepassword(id){
	alert(id);
}

function disableuser(id){
	alert(id);
}

function deluser(id){
	alert(id);
}
</script>

<style type="text/css">
tr.odd {
	background-color: #eee;
}

span.userinfo_column {
	display:inline-block;
}
</style>


<ul>
<li>
<span class="userinfo_column" style="width:20%";>用户名</span>
<span class="userinfo_column" style="width:20%";>角色</span>
<span class="userinfo_column" style="width:20%";>操作</span>
</li>
<?php
foreach($users as $user){
	switch($user['role_id']){
	case "4":
		$role_name = "管理员";
		break;
	case "2":
		$role_name = "安全员";
		break;
	case "1":
		$role_name = "审计员";
		break;
	}
?>
<li>
<span class="userinfo_column" style="width:20%;"><?php echo $user['name']; ?></span>
<span class="userinfo_column" style="width:10%;"><?php echo $role_name; ?></span>
<span class="userinfo_column" style="width:10%;">
	<a href="user/changepwd/<?php echo $user['id']; ?>" class="changepwd" title="修改密码"><span class="ui-icon ui-icon-key"></span></a>
</span>
<span class="userinfo_column" style="width:10%;">
	<a href="user/del/<?php echo $user['id']; ?>" class="del" title="删除用户"><span class="ui-icon ui-icon-trash"></span></a>
</span>
</li>

<?php
}
?>
</ul>


