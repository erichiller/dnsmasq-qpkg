<?php

$date_format = "M j H:i:s";
$qpkg_conf = parse_ini_file("/etc/config/qpkg.conf",true);
$log_path = $qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.log";
$log_lines = file($log_path);
$lease_lines = file($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.leases");
$dhcphosts_lines = file($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_dhcphosts.conf");
$hostmap_lines = file($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq_hostmap.conf");

// load main config
$conf_lines = file($qpkg_conf["dnsmasq"]["Install_Path"]."/dnsmasq.conf");
foreach($conf_lines as $line_num => $line){
	# there can be multiple consecutive tabs (or spaces) in the config - filter out whitespace.
	$arr=explode("=",$line);
	$arr[0] = trim($arr[0]);
	if(isset($arr[1])){
		$arr[1] = trim($arr[1]);
	}

	# ctype_space checks for \n\r\t
	if( (strlen($line) > 0 && substr($line,0,1) == "#") || ctype_space($line) || $line=='' ){
		// this is a comment or whitespace line, do nothing.
	} else if( !in_array(sizeof($arr),array(1,2))) {
		# not a comment or whitespace;
		log_error("config line @$line_num '$line' (".str_hex($line).") is invalid");
		continue 1;
	} else {
		switch($arr[0]){
			case "server":
				$key="server";
				$conf[$key][(isset($conf[$key])?sizeof($conf[$key]):0)]=$arr[1];
			break;
			case "dhcp-option":
				$key="dhcp-option";
				$conf[$key][(isset($conf[$key])?sizeof($conf[$key]):0)]=$arr[1];
				$option = explode(",",$arr[1]);
				switch($option[0]){
					case "option:router":
						$conf["dhcp-option-router"] = $option[1];
					break;
					case "option6:ntp-server":
						$conf["dhcp-option-ntp-ipv6"] = trim($option[1],"[]");
					break;
					case "option:ntp-server":
						$conf["dhcp-option-ntp"] = $option[1];
					break;
				}
			break;
			default:
				switch($arr[0]){
					case "dhcp-range":
						$vals = explode(",",$arr[1]);
						$conf["dhcp-range-start"] = $vals[0];
						$conf["dhcp-range-end"] = $vals[1];
						$conf["dhcp-default-leasetime"] = $vals[2];
					break;
				}
				if(isset($arr[1])){
					$conf[$arr[0]] = $arr[1];
				} else {
					$conf[$arr[0]] = TRUE;
				}
			break;
		}
	}
}


function log_error($text){
	global $log_path,$date_format;
	$str = date($date_format)." [dnsmasq_webui]: ".$text;
	$system_timezone = exec("date +%z");
	if(strcmp($system_timezone,date("O"))){
		$str .= "\n DATE MISMATCH: system timezone=".$system_timezone."; php timezone=".date("O")."\n";
	}
	echo $str."<br \><br \>";
	file_put_contents($log_path,$str."\n",FILE_APPEND);
}
function str_hex($string){
	return "\\x".substr(chunk_split(bin2hex($string),2,"\\x"),0,-2);
}




