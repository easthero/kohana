<?php
/*
 * 本文件用于定义各个配置表的信息,其格式如下 
 * $表名 = array(
 * '字段名' => array('req请求中的字段名','字段类型','是否必填字段,只有add操作才校验是否填写','用于校验的正则表达式','中文别名','错误消息')
 * );
 * 其中pattern和error可不填写,如没有pattern则表示不需要对该字段进行校验,比如名称不需要校验
 * alias也可不填写,如不填写,则表示在日志中不出现该字段
 */

$id_pattern = '/^\d+$/';
$name_pattern = '/^.+$/';
$word_pattern = '/^\w+$/';
$email_pattern =  '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/';
$ip_pattern =  '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';
$port_pattern = '/^\d{1,5}$/';
$loginreq_pattern = '/^\/.+$/';
$ipgroup_pattern = '/^[\d\.\-;]+$/';
$time_pattern = '/^(\d\d:){2}\d\d$/';

$this->sensor_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array('name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '审计网探名称','error' => '必须填写'),
		'ip' => array('name' => 'ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必填字段,且格式须符合IP地址规范', 'alias' => 'IP地址'),
		'model' => array('name' => 'model', 'type' => 'char', 'notnull' => 1, 'pattern' => $word_pattern, 'alias' => '型号')
		);

$this->auditsite_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0,'pattern' => $id_pattern, 'error' => '必须为数字'),	
		'name' => array('name' => 'name', 'type' => 'char','notnull' => 1, 'alias' => '应用名称','error' => '必须填写'),
		'sensor_id' => array('name' => 'sensor_id', 'type' => 'int', 'notnull' => 0,'pattern' => $id_pattern, 'error' => '必须为数字')		
		);

$this->appservice_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'audit_site_id' => array('name' => 'audit_site_id', 'type' => 'int', 'notnull' => 1,'pattern' => $id_pattern, 'error' => '必填字段,且必须为数字', 'alias' => '应用ID'),
		'service_type' => array('name' => 'service_type','type' => 'int','notnull' => 1, 'pattern' => $id_pattern, 'error' => '必填字段','alias' => '服务类型'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '服务名称'),
		'ip' => array( 'name' => 'ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必填字段,且必须符合IP地址规范', 'alias' => 'IP地址'),
		'port' => array( 'name' => 'port', 'type' => 'int', 'notnull' => 1, 'pattern' => $port_pattern, 'error' => '必填字段,必须为数字', 'alias' => '端口'),
		'login_req' => array( 'name' => 'login_req', 'type' => 'char', 'notnull' => 0, 'pattern' => $loginreq_pattern, 'error' => '格式错误,必须以左斜杠打头', 'alias' => '登录请求'),
		'user_field' => array( 'name' => 'user_field', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '用户名字段'),
		'session_field' => array( 'name' => 'session_field', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '会话字段')
		);	

$this->dbservice_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'audit_site_id' => array( 'name' => 'audit_site_id', 'type' => 'int', 'notnull' => 1, 'pattern' => $id_pattern, 'error' => '必填字段,必须为数字', 'alias' => '应用ID'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '服务名称', ),
		'service_type' => array( 'name' => 'service_type', 'type' => 'int', 'notnull' => 1, 'pattern' => $id_pattern,'error' => '必填字段', 'alias' => '服务类型'),
		'ip' => array( 'name' => 'ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必填字段,且必须符合IP地址规范', 'alias' => 'IP地址'),
		'port' => array( 'name' => 'port', 'type' => 'int', 'notnull' => 1, 'pattern' => $port_pattern, 'error' => '必填字段,且必须为数字', 'alias' => '端口'),
		'login_req' => array( 'name' => 'login_req', 'type' => 'char', 'notnull' => 0, 'pattern' => $loginreq_pattern, 'error' => '格式错误,必须以左斜杠打头', 'alias' => '登录请求'),
		'user_field' => array( 'name' => 'user_field', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '用户名字段'),
		'session_field' => array( 'name' => 'session_field', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '会话字段')
		);

$this->ipgroup_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => 'IP地址组名称'),
		'members' => array( 'name' => 'members', 'type' => 'char', 'notnull' => 1, 'pattern' => $ipgroup_pattern, 'error' => '必填字段,正确的格式如 192.168.1.1-192.168.1.5;192.168.1.3', 'alias' => '成员')
		);

$this->usergroup_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '用户组名称'),
		'members' => array( 'name' => 'members', 'type' => 'char', 'notnull' => 1, 'pattern' => $name_pattern, 'error' => '必填字段,格式错误', 'alias' => '成员')
		);

$this->responseemail_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '电子邮件响应名称'),
		'email' => array( 'name' => 'email', 'type' => 'char', 'notnull' => 1, 'pattern' => $email_pattern, 'error' => '必填字段,且必须符合电子邮件格式', 'alias' => '电子邮件')
		);

$this->responsesyslog_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => 'syslog响应名称'),
		'syslogd_ip' => array( 'name' => 'syslogd_ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必填字段,且必须符合IP地址规范', 'alias' => 'IP地址'),
		'syslogd_port' => array( 'name' => 'syslogd_port', 'type' => 'int', 'notnull' => 1, 'pattern' => $port_pattern, 'error' => '必填字段,且必须为数字', 'alias' => '端口'),
		'ident' => array( 'name' => 'ident', 'type' => 'char', 'notnull' => 1, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => 'ident'),
		'facility' => array( 'name' => 'facility', 'type' => 'char', 'notnull' => 1, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => 'facility')						
		);

$this->responsesnmp_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => 'snmp响应名称'),
		'receiver_ip' => array( 'name' => 'receiver_ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必填字段,且必须符合IP地址规范', 'alias' => 'snmp发送地址'),
		'community' => array( 'name' => 'community', 'type' => 'char', 'notnull' => 1, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '共同体名称')
		);

$this->policy_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '策略名称'),	
		'service_id' => array( 'name' => 'service_id', 'type' => 'int', 'notnull' => 1, 'pattern' => $id_pattern, 'error' => '必填字段,且必须为数字', 'alias' => '审计服务ID'),
		'priority' => array('name' => 'priority', 'type' => 'int', 'notnull' => 0),
		'criticality' => array( 'name' => 'criticality', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必填字段,且必须为数字', 'alias' => '危急程度'),
		'time_start' => array( 'name' => 'time_start', 'type' => 'int', 'notnull' => 1,  'error' => '必须为数字', 'alias' => '开始时间'),
		'time_stop' => array( 'name' => 'time_stop', 'type' => 'int', 'notnull' => 1, 'error' => '必须为数字', 'alias' => '结束时间'),
		'monday' => array( 'name' => 'monday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期一'),
		'tuesday' => array( 'name' => 'tuesday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期二'),
		'wednesday' => array( 'name' => 'wednesday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期三'),
		'thursday' => array( 'name' => 'thursday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期四'),
		'friday' => array( 'name' => 'friday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期五'),
		'saturday' => array( 'name' => 'saturday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期六'),
		'sunday' => array( 'name' => 'sunday', 'type' => 'bool', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '星期日'),
		'user_group_id' => array( 'name' => 'app_user_group_id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '用户组ID'),
		'ip_group_id' => array( 'name' => 'app_ip_group_id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => 'IP组ID'),
		'operations' => array( 'name' => 'db_operations', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '操作类型'),
		'object' => array( 'name' => 'db_object', 'type' => 'char', 'notnull' => 0, 'pattern' => $word_pattern, 'error' => '格式错误', 'alias' => '操作对象'),
		'keyword' => array( 'name' => 'app_keyword', 'type' => 'char', 'notnull' => 0, 'alias' => '关键字'),	
		);

$this->user_info = array(
		'id' => array('name' => 'id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字'),
		'name' => array( 'name' => 'name', 'type' => 'char', 'notnull' => 1, 'alias' => '用户名'),	
		'password' => array( 'name' => 'password', 'type' => 'char', 'notnull' => 1),	
		'role_id' => array('name' => 'role_id', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '角色ID'),
		'restrict_ip' => array( 'name' => 'restrict_ip', 'type' => 'char', 'notnull' => 0, 'pattern' => $ip_pattern, 'error' => '必须符合IP地址规范', 'alias' => '受限制的IP'),
		'permited_ip' => array( 'name' => 'permited_ip', 'type' => 'char', 'notnull' => 0, 'pattern' => $ip_pattern, 'error' => '必须符合IP地址规范', 'alias' => '允许的IP'),
		'active' => array('name' => 'active', 'type' => 'int', 'notnull' => 0, 'pattern' => $id_pattern, 'error' => '必须为数字', 'alias' => '状态'),
		);

$this->login_info = array(
		'username' => array('name' => 'username', 'type' => 'char', 'notnull' => 1, 'error' => '必须填写'),
		'password' => array('name' => 'password', 'type' => 'char', 'notnull' => 1, 'error' => '必须填写'),
		);

$this->ipconfig_info = array(
		'ip' => array('name' => 'ip', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必须符合IP地址规范'),
		'netmask' => array('name' => 'netmask', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必须符合IP地址规范'),
		'gateway' => array('name' => 'gateway', 'type' => 'char', 'notnull' => 1, 'pattern' => $ip_pattern, 'error' => '必须符合IP地址规范')
		);
?>
