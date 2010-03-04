<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Ipconfig_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$old_ip = trim(shell_exec("/sbin/ifconfig eth0|grep inet\ addr |awk '{print $2}' |cut -d ':' -f 2"));
		$old_netmask = trim(shell_exec("/sbin/ifconfig eth0|grep inet\ addr |awk '{print $4}' |cut -d ':' -f 2"));	
		$old_gateway = trim(shell_exec("/sbin/route -n |grep UG |awk '{print $2}'"));

		$data[] = array('ip' => $old_ip, 'netmask' => $old_netmask, 'gateway' => $old_gateway);

		$this->respFetch(0,1,1,$data);
	}

	function update(){
		$interfaces = "/dom/etc/network/interfaces";

		$new_ip = $this->req->data->ip;
		$new_netmask = $this->req->data->netmask;
		$new_gateway = $this->req->data->gateway;

		$mount_cmd = "/bin/mount /dom";
		$umount_cmd = "/bin/umount /dom";

		exec($mount_cmd,$output,$result);

		if($result !== 0 && $result !== 32){
			$message = "无法挂载存储卡设备";
			$this->respFailed($message);
			exit();
		}elseif (!file_exists($interfaces)) {
			exec($umount_cmd,$output,$result);

			$message = "无法找到网卡配置文件";
			$this->respFailed($message);
			exit();
		}

		$ip_cmd = "sed -i '6s/.*/	address $new_ip/' $interfaces";
		$netmask_cmd = "sed -i '7s/.*/	netmask $new_netmask/' $interfaces";
		$gateway_cmd = "sed -i '8s/.*/	gateway $new_gateway/' $interfaces";

		$ifconfig_cmd = "/usr/sbin/ipconfig.sh $new_ip $new_netmask $new_gateway";

		exec($ip_cmd,$output,$ip_result);
		exec($netmask_cmd,$output,$netmask_result);
		exec($gateway_cmd,$output,$gateway_result);

		if($ip_result === 0 && $netmask_result === 0 && $gateway_result === 0){
			shell_exec($umount_cmd);
			shell_exec($ifconfig_cmd);
			$this->respOk($this->req->data_array);	
		}else{
			$message = "操作失败";
			$this->respFailed($message);
		}

		return;	
	}
}
