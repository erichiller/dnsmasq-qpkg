<?php

$date_format = "M j H:i:s";
$QPKG_NAME="dnsmasq";
$CONF="/etc/config/qpkg.conf";
$installPath = trim(shell_exec('/sbin/getcfg '.$QPKG_NAME.' Install_Path -d "" -f '.$CONF));
# ensure $installPath exists
if(!is_dir($installPath)){
	die($QPKG_NAME." Install Path ($installPath) not found");
}
$log_path = $installPath."/dnsmasq.log";
$log_lines = array_reverse(file($log_path));
$lease_lines = file($installPath."/dnsmasq.leases");
$dhcphosts_lines = file($installPath."/dnsmasq_dhcphosts.conf");
$hostmap_lines = file($installPath."/dnsmasq_hostmap.conf");
$TIME_ADJUST = FALSE;
$interfaces=array_values(preg_grep("/^(eth|br).$/",scandir("/sys/class/net/")));
$ipv6 = (trim(shell_exec('/sbin/getcfg Network IPV6 -d "" -f /etc/config/uLinux.conf')) === 'TRUE'?true:false);

// load main config
$conf_lines = file($installPath."/dnsmasq.conf");
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
						if(preg_match("/^constructor\:.+$/",$vals[2])){
							# presence of constructor means it is ipv6
							$conf["dhcp-range-start-6"] = $vals[0];
							$conf["dhcp-range-end-6"] = $vals[1];
						} else {
							# else it is ipv4
							$conf["dhcp-range-start"] = $vals[0];
							$conf["dhcp-range-end"] = $vals[1];
							$conf["dhcp-default-leasetime"] = $vals[2];
						}
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


function log_error($text,$echo = FALSE){
	global $log_path,$date_format,$TIME_ADJUST;
	if(!is_null($text)){
		$str = date($date_format,safeTime())." dnsmasq_webui: ".$text;
	}
	if(isset($str)){
		if($echo){
			echo $str."<br \><br \>";
		}

		file_put_contents($log_path,$str."\n",FILE_APPEND);
	}
}
function str_hex($string){
	return "\\x".substr(chunk_split(bin2hex($string),2,"\\x"),0,-2);
}

function getStatus(){
	return ("true"===trim(shell_exec('if [ `ps | grep "dnsmasq" | grep -v "grep" | wc -l` == 1 ]; then echo true; else echo false;fi;'))?true:false);
}

function safeTime($time = FALSE){
	global $TIME_ADJUST;
	if($time === FALSE){
		$time = time();
	}
	if($TIME_ADJUST === FALSE){
		$system_timezone = exec("date +%z");
		$php_timezone = date("O");
		if(strcmp($system_timezone,$php_timezone)){
			$TIME_ADJUST = intval($php_timezone) - intval($system_timezone);
			$TIME_ADJUST = (($TIME_ADJUST / 100) * 3600);
			$str .= "DATE MISMATCH: system timezone=".$system_timezone."; php timezone=".date("O")."; adjusting by " . $TIME_ADJUST . "sec in the web ui automatically; to set your php timezone, see php settings under web server settings";
			log_error($str);
		} else {
			$TIME_ADJUST = 0;
		}
	}
	if($TIME_ADJUST != 0){
		$time = $time - $TIME_ADJUST;
	}
	return $time;
}



