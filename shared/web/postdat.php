<?php
include("functions.php");

$json = file_get_contents('php://input');
$arr = json_decode($json,true);


log_error("============================================\n\n");

log_error(print_r($json,true)."\n");


log_error(print_r($arr,true)."\n");


log_error("============================================\n\n");

switch($_GET['target']){	
	case "hostmap":
		log_error("HOSTMAP CONFIG");
		
		$file = sprintf("%-'#40s%-'#20s%-'#20s\n","# IP ", " HOSTNAME ", " FQDN ");

		foreach($arr as $line){
			$file .= sprintf("%-40s%-20s%s\n",$line["ip_addr"],$line["hostname"],$line["fqdn"]);
		}		
		rename($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_hostmap.conf",$qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_hostmap.conf.bck");
		file_put_contents($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_hostmap.conf",$file);		
	break;
	case "dhcpleases":
		log_error("DHCPHOSTS CONFIG");
		
		$file = sprintf("%-'#20s%-'#20s%-'#20s%-'#20s\n","# MAC ", " IP ADDR ", " HOSTNAME ", " LEASETIME ");

		foreach($arr as $line){
			$file .= sprintf("%s%s%s%s\n",$line["mac"].",",$line["ip_addr"].",",$line["hostname"],($line["leasetime"]?",":"").$line["leasetime"]);
		}		
		rename($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_dhcphosts.conf",$qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_dhcphosts.conf.bck");
		file_put_contents($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_dhcphosts.conf",$file);
	break;
	case "config":
		$file = <<<EOF
# this tells dnsmasq to never pass short names to the upstream DNS servers. If the name is not in the local /etc/hosts file then “not found” will be returned.
domain-needed
# ignore /etc/hosts file
no-hosts

# dont use resolv.conf, use these nameservers instead
no-resolv
no-poll
server=8.8.8.8
server=8.8.4.4
server=2001:4860:4860::8888
server=2001:4860:4860::8844

# make this the authoritative dhcp server for my network
dhcp-authoritative
EOF;
		$file .= "\n";
		// only set the following if the user has a domain configured.
		if($_POST['domain']){
			$file .= "domain=".$_POST['domain']."\n";
			$file .= "# add domain to all hosts\n";
			$file .= "expand-hosts\n";
		}
		$file .= "cache-size=".$_POST['cache-size']."\n";
		$file .= "dhcp-range=".$_POST['dhcp-range-start'].",".$_POST['dhcp-range-end'].",".$_POST['dhcp-default-leasetime']."\n";
		$file .= "dhcp-option=option:router,".$_POST['dhcp-option-router']."\n";
		$file .= "dhcp-option=option6:ntp-server,[".$_POST['dhcp-option-ntp-ipv6']."]\n";
		$file .= "dhcp-option=option:ntp-server,".$_POST['dhcp-option-ntp']."\n";
		copy($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.conf",$qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.conf.bck");
		file_put_contents($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.conf",$file);
	break;
}
log_error("\n".print_r($file,true)."\n");

$restart_result = shell_exec("ssh -i /share/CACHEDEV1_DATA/.qpkg/dnsmasq/id_rsa_npw -o StrictHostKeyChecking=no admin@localhost \"/etc/init.d/dnsmasq.sh restart\"");

log_error("\n******** restart_reload_result ********\n".print_r($restart_result,true)."\n");




