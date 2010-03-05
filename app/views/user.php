<table border="1">
<tr>
	<th>ID</th>
	<th>用户名</th>
	<th>角色</th>
	<th>状态</th>
</tr>
<?php
foreach($users as $user){
	echo "<tr>";
	echo "<td>" . $user['id'] . "</td>";
	echo "<td>" . $user['name'] . "</td>";
	echo "<td>" . $user['role_id'] . "</td>";
	echo "<td>" . $user['active'] . "</td>";
	echo "</tr>";
}
?>
</table>
