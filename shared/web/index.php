<?php include("functions.php") ?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>dnsmasq webui <?php echo date ("F d Y H:i:s.", filemtime("index.php")); ?></title>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script type="text/javascript" src="tableedit.js"></script>
	<script type="text/javascript" src="jquery.tabletojson.js"></script>
	<script type="text/javascript" src="jquery.form.js"></script>
	<script type="text/javascript" src="localscript.js"></script>
	<link rel="stylesheet" href="style.css">

</head>
<body>
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">Current Leases</a></li>
			<li><a href="#tabs-2">Log</a></li>
			<li><a href="#tabs-3">Config</a></li>
			<li><a href="#tabs-4">DHCP Hosts</a></li>
			<li><a href="#tabs-5">Static Hostnames</a></li>
		</ul>
		
		







	<!---- CURRENT LEASES --->
		
	<div id="tabs-1">
		<table class="leasetable">
			<thead>
				<tr>
					<th>Date Assigned</th>
					<th>MAC Address</th> 
					<th>IP Address</th> 
					<th>Hostname</th> 
					<th>Client ID</th> 
				</tr>
			</thead>
			<tbody>
<?php
foreach($lease_lines as $line_num => $line){
	$arr=explode(" ",$line);
	
	# ctype_space checks for \n\r\t
	if( (strlen($line) > 0 && substr($line,0,1) == "#") || ctype_space($line) || $line=='' ){
		// this is a comment or whitespace line, do nothing.
	} else if( !in_array(sizeof($arr),array(4,5))) {
		log_error("lease_lines line @$line_num '$line' (".str_hex($line).") is invalid");
		continue 1;
	} else {
		echo "
				<tr>
					<td>".date("$date_format",$arr[0])."</td>
					<td>".$arr[1]."</td>
					<td>".$arr[2]."</td>
					<td>".$arr[3]."</td>
					<td>".$arr[4]."</td>
				</tr>
		";
	}

	
}
?>
			</tbody>
		</table>
	</div>







	<!---- LOGTABLE --->

	<div id="tabs-2">
		<table class="logtable">
<?php

foreach($log_lines as $line_num => $line){
	if(!$line){
		continue;
	}
	$pos = stripos($line,": ");
	if($pos !== FALSE){
		$date = substr($line,0,$pos);
		$text = substr($line,$pos+2);
	} else {
		$text = $line;
	}
	echo "
			<tr>
				<td ".(isset($date)?"":"colspan=\"2\"").">".(isset($date)?wordwrap($date,35,"<br />",true):$text)."</td>";
	if(isset($date)){
		echo "
				<td>".$text."</td>";
	}
	unset($date);
	echo "
			</tr>";
}

?>
		</table>
	</div>
	
	
	
	
	
	
	
	
	<!---- CONFIG --->
	
	<div id="tabs-3">
		<form class="ajaxForm" method="POST" action="/dnsmasq/postdat.php?target=config">
		<ol>
			<li>
				<label for="domain">Domain: </label>
				<input type="text" name="domain" value="<?php echo $conf['domain']; ?>" title="domain for dns" />
			</li>
			<li>
				<label for="cache-size">Cache Size: </label>
				<input type="text" name="cache-size" value="<?php echo $conf['cache-size']; ?>" />
			</li>
			<li>
				<label for="dhcp-range-start">DHCP Start: </label>
				<input type="text" name="dhcp-range-start" value="<?php echo $conf['dhcp-range-start']; ?>" />
			</li>
			<li>
				<label for="dhcp-range-end">DHCP End: </label>
				<input type="text" name="dhcp-range-end" value="<?php echo $conf['dhcp-range-end']; ?>" />
			</li>
			<li>
				<label for="dhcp-default-leasetime">DHCP Default Leasetime:</label>
				<input type="text" name="dhcp-default-leasetime" value="<?php echo $conf['dhcp-default-leasetime']; ?>" />
			</li>
			<li>
				<label for="dhcp-option-router">DHCP Router IP:</label>
				<input type="text" name="dhcp-option-router" value="<?php echo $conf['dhcp-option-router']; ?>" />
			</li>
			<li>
				<label for="dhcp-option-ntp-ipv6">DHCP IPv6 NTP:</label>
				<input type="text" name="dhcp-option-ntp-ipv6" value="<?php echo $conf['dhcp-option-ntp-ipv6']; ?>" />
			</li>
			<li>
				<label for="dhcp-option-ntp">DHCP NTP: </label>
				<input type="text" name="dhcp-option-ntp" value="<?php echo $conf['dhcp-option-ntp']; ?>" />
			</li>
			<li>
				<button style="margin-left: 220px;" class="button" id="send_config">Save & Update</button>
			</li>
		</ol>
		</form>
	</div>
	
	
	
	
	
	
	
	
	
	
	<!---- STATIC LEASES --->

	
	
	
	
	<div id="tabs-4">
		<table class="editableTable" id="table_dhcpleases">
			<thead>
				<tr>
					<th data-override="mac">MAC</th>
					<th data-override="ip_addr">IP Address</th>
					<th data-override="hostname">Hostname</th> 
					<th data-override="leasetime">Leasetime<br />(m/minutes,h/hours,d/days)<br />(optional)</th> 
				</tr>
			</thead>
			<tbody>
<?php

foreach($dhcphosts_lines as $line_num => $line){
	$arr=explode(",",$line);
	
	# ctype_space checks for \n\r\t
	if( (strlen($line) > 0 && substr($line,0,1) == "#") || ctype_space($line) || $line=='' ){
		// this is a comment or whitespace line, do nothing.
	} else if( !in_array(sizeof($arr),array(3,4))) {
		# not a comment or whitespace the line doesn't have 3,4 commas, error
		log_error("dhcphosts config line @$line_num '$line' (".str_hex($line).") is invalid");
		continue 1;
	} else {
		echo "
			<tr>
				<td>".$arr[0]."</td>
				<td>".$arr[1]."</td>
				<td>".$arr[2]."</td>
				<td>".$arr[3]."</td>
			</tr>
		";
	}

	
}

?>
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				<tr>
			</tbody>
		</table>
<!--
		<button class="button addRow" id="addrow_dhcpleases">Add Lease</button>
-->
		<button class="button convertTable" id="send_dhcpleases">Save & Update</button>
	</div>
	
	
	
	<!---- STATIC HOSTS --->

	
	
	
	
	<div id="tabs-5">
		<table id="table_hostmap" class="editableTable">
			<thead>
				<tr>
					<th data-override="ip_addr">IP Address</th>
					<th data-override="hostname">Hostname</th> 
					<th data-override="fqdn">FQDN (optional)</th> 
				</tr>
			</thead>
			<tbody>
<?php

foreach($hostmap_lines as $line_num => $line){
	# there can be multiple consecutive tabs (or spaces) in the config - filter out whitespace.
	$arr=array_values(array_filter(explode(" ",str_replace(array("\t",",")," ",$line))));

	# ctype_space checks for \n\r\t
	if( (strlen($line) > 0 && substr($line,0,1) == "#") || ctype_space($line) || $line=='' ){
		// this is a comment or whitespace line, do nothing.
	} else if( !in_array(sizeof($arr),array(2,3))) {
		# not a comment or whitespace the line doesn't have 2 or 3 commas, error
		log_error("hostmap config line @$line_num '$line' (".str_hex($line).") is invalid");
		continue 1;
	} else {
		echo "
			<tr>
				<td>".$arr[0]."</td>
				<td>".$arr[1]."</td>
				<td>".$arr[2]."</td>
			</tr>
		";
	}

	
}

?>
				<tr>
					<td></td>
					<td></td>
					<td></td>
				<tr>
			</tbody>
		</table>
		<button class="button convertTable" id="send_hostmap">Save & Update</button>
		<script>
		</script>
		
	</div>

 
 
</body>
</html>
