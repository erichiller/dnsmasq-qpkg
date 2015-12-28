<?php
include("functions.php");
$RESTART=true;

$json = file_get_contents('php://input');
$arr = json_decode($json,true);


log_error("============================================\n\n");

log_error(print_r($json,true)."\n");

log_error(print_r($arr,true)."\n");

log_error("============================================\n\n");


switch($_GET['target']){
	case "zipreceive":
		$fileName = $_FILES["zippedConfig"]["tmp_name"];
		$zip = new ZipArchive; 
		if (!$zip->open($fileName)){
			exit("Error reading zip-archive!");
			$RESTART=false;
		}
		if( $zip->numFiles != 3){
			$zip->close();
			exit("Invalid Backup Format, expected 3 files, found ".$zip->numFiles);
			$RESTART=false;
		}
		for($i = 0; $i < $zip->numFiles; $i++){
			if(in_array($zip->getNameIndex($i),array(
			'dnsmasq.conf',
			'dnsmasq_dhcphosts.conf',
			'dnsmasq_hostmap.conf'
			)
			)){
				//valid lets export now
				$zip->extractTo($installPath."/");
				echo "Config has been restored";
			} else {
				//invalid filename
				echo 'invalid filename found in the zipped backup';
				$RESTART=false;
			}
			$zip->close();
		} 
	break;
	case "start":
		$result = shell_exec("ssh -i /share/CACHEDEV1_DATA/.qpkg/dnsmasq/id_rsa_npw -o StrictHostKeyChecking=no admin@localhost \"/etc/init.d/dnsmasq.sh start\"");

		log_error("\n******** start_result ********\n".print_r($result,true)."\n");

		header('Content-Type: text/javascript; charset=utf8');
		if(strpos($result,"is now running") !== FALSE){
			echo "Start: SUCCESS";
		} else {
			echo "Start: FAILURE";
		}
		$RESTART=false;
	break;
	case "stop":
		$result = shell_exec("ssh -i /share/CACHEDEV1_DATA/.qpkg/dnsmasq/id_rsa_npw -o StrictHostKeyChecking=no admin@localhost \"/etc/init.d/dnsmasq.sh stop\"");

		log_error("\n******** stop_result ********\n".print_r($result,true)."\n");

		header('Content-Type: text/javascript; charset=utf8');
		if(strpos($result,"Stopping") !== FALSE){
			echo "Stop: SUCCESS";
		} else {
			echo "Stop: FAILURE";
		}
		$RESTART=false;
	break;
	case "hostmap":
		log_error("HOSTMAP CONFIG");
		
		$file = sprintf("%-'#40s%-'#20s%-'#20s\n","# IP ", " HOSTNAME ", " FQDN ");

		foreach($arr as $line){
			$file .= sprintf("%-40s%-20s%s\n",$line["ip_addr"],$line["hostname"],$line["fqdn"]);
		}
		file_put_contents($installPath."/dnsmasq_hostmap.conf.test",$file);
		chmod($installPath."/dnsmasq_hostmap.conf.test",0664);
		exec($installPath."/dnsmasq --test -C ".$installPath."/dnsmasq.conf --dhcp-hostsfile=".$installPath."/dnsmasq_dhcphosts.conf --addn-hosts=".$installPath."/dnsmasq_hostmap.conf.test &> /dev/null",$output,$exitCode);
		if(!$exitCode){
			rename($installPath."/dnsmasq_hostmap.conf",$installPath."/dnsmasq_hostmap.conf.bck");
			chmod($installPath."/dnsmasq_hostmap.conf.bck",0664);
			rename($installPath."/dnsmasq_hostmap.conf.test",$installPath."/dnsmasq_hostmap.conf");
			chmod($installPath."/dnsmasq_hostmap.conf",0664);
		} else {
			$RESTART=false;
			echo "Configuration Invalid; Restart aborted.";
		}
	break;
	case "dhcpleases":
		log_error("DHCPHOSTS CONFIG");
		
		$file = sprintf("%-'#20s%-'#20s%-'#20s%-'#20s\n","# MAC ", " IP ADDR ", " HOSTNAME ", " LEASETIME ");

		foreach($arr as $line){
			$file .= sprintf("%s%s%s%s\n",$line["mac"].",",$line["ip_addr"].",",$line["hostname"],($line["leasetime"]?",":"").$line["leasetime"]);
		}		

		file_put_contents($installPath."/dnsmasq_dhcphosts.conf.test",$file);
		chmod($installPath."/dnsmasq_dhcphosts.conf.test",0664);
		exec($installPath."/dnsmasq --test -C ".$installPath."/dnsmasq.conf --dhcp-hostsfile=".$installPath."/dnsmasq_dhcphosts.conf.test --addn-hosts=".$installPath."/dnsmasq_hostmap.conf &> /dev/null",$output,$exitCode);
		if(!$exitCode){
			rename($installPath."/dnsmasq_dhcphosts.conf",$installPath."/dnsmasq_dhcphosts.conf.bck");
			chmod($installPath."/dnsmasq_dhcphosts.conf.bck",0664);
			rename($installPath."/dnsmasq_dhcphosts.conf.test",$installPath."/dnsmasq_dhcphosts.conf");
			chmod($installPath."/dnsmasq_dhcphosts.conf",0664);
		} else {
			$RESTART=false;
			echo "Configuration Invalid; Restart aborted.";
		}
	break;
	case "config":
		$file = "";
		$file = "interface=".$_POST['interface']."\n";;
		$file .= <<<EOF
# this ensures dnsmasq only binds to the interfaces configured
bind-interfaces
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
		if($ipv6 && $_POST['dhcp-range-start-6'] && $_POST['dhcp-range-end-6']){
			#enable ipv6
			$file .= "enable-ra\n";
			$file .= "dhcp-range=".$_POST['dhcp-range-start-6'].",".$_POST['dhcp-range-end-6'].",constructor:".$_POST['interface'].",ra-names\n";
		}
		file_put_contents($installPath."/dnsmasq.conf.test",$file);
		chmod($installPath."/dnsmasq.conf.test",0664);
		exec($installPath."/dnsmasq --test -C ".$installPath."/dnsmasq.conf.test --dhcp-hostsfile=".$installPath."/dnsmasq_dhcphosts.conf.test --addn-hosts=".$installPath."/dnsmasq_hostmap.conf.test &> /dev/null",$output,$exitCode);
		if(!$exitCode){
			copy($installPath."/dnsmasq.conf",$installPath."/dnsmasq.conf.bck");
			chmod($installPath."/dnsmasq.conf.bck",0664);
			copy($installPath."/dnsmasq.conf.test",$installPath."/dnsmasq.conf");
			chmod($installPath."/dnsmasq.conf",0664);
		} else {
			$RESTART=false;
			echo "Configuration Invalid; Restart aborted.";
		}
	break;
}
// restart dnsmasq
if($RESTART){
	log_error("\n".print_r($file,true)."\n");

	$restart_result = shell_exec("ssh -i /share/CACHEDEV1_DATA/.qpkg/dnsmasq/id_rsa_npw -o StrictHostKeyChecking=no admin@localhost \"/etc/init.d/dnsmasq.sh restart\"");

	log_error("\n******** restart_reload_result ********\n".print_r($restart_result,true)."\n");

	header('Content-Type: text/javascript; charset=utf8');
	if(strpos($restart_result,"is now running") !== FALSE && getStatus()){
		echo "Config & Restart: SUCCESS";
	} else {
		echo "Config & Restart: FAILURE";
	}
}





