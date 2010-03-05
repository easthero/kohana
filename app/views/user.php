<script type="text/javascript">
$(document).ready (function(){
	$('#userinfo tr:odd').addClass('odd');
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
</style>

<table border="1" id="userinfo">
<tr>
	<th>用户名</th>
	<th>角色</th>
	<th colspan="3">操作</th>
</tr>
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

	echo "<tr>";
	echo "<td>" . $user['name'] . "</td>";
	echo "<td>" . $role_name . "</td>";
	echo "<td><a href='javascript:changepassword(" . $user['id'] . ")'>修改密码</a></td>";
	echo "<td><a href='javascript:disableuser(" . $user['id'] . ")'>禁用用户</a></td>";
	echo "<td><a href='javascript:deluser(" . $user['id'] . ")'>删除用户</a></td>";
	echo "</tr>";
}
?>
</table>
