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
			<li><a href="#tabs-6">Administration</a></li>
		</ul>
		
		







	<!---- CURRENT LEASES --->
		
	<div id="tabs-1">
		<table class="leasetable">
			<thead>
				<tr>
					<th>Lease Expiration</th>
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
	# duid is the ipv6 name for the dhcp server see --dhcp-duid in manpages
	if( (strlen($line) > 0 && substr($line,0,1) == "#") || 
		(strlen($line) > 0 && substr($line,0,4) == "duid") || 
		ctype_space($line) || 
		$line=='' ){
		// this is a comment or whitespace line, do nothing.
	} else if( !in_array(sizeof($arr),array(4,5))) {
		log_error("lease_lines line @$line_num '" . trim($line) . "' (".str_hex($line).") is invalid",true);
		continue 1;
	} else {
		echo "
				<tr>
					<td>".date("$date_format",safeTime($arr[0]))."</td>
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
	if( empty(trim($line)) ){
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
		<form class="ajaxForm" method="POST" action="postdat.php?target=config">
		<ol>
			<li>
				<label for="interface">Interface: </label>
				<select name="interface">
					<?php
					foreach($interfaces as $interface){
						echo "<option";
						if($conf["interface"]==$interface){
							echo " selected";
						}
						echo ">";
						echo $interface;
						echo "</option>";
					}
					?>
				</select>
				
			</li>
			<li>
				<label for="domain">Domain: </label>
				<input type="text" name="domain" value="<?php echo $conf['domain']; ?>" title="domain for dns" />
			</li>
			<li>
				<label for="cache-size">Cache Size: </label>
				<input type="text" name="cache-size" value="<?php echo $conf['cache-size']; ?>" />
			</li>
		</ol>
		<fieldset>
			<legend><input type="checkbox" id="enable-dhcp" name="enable-dhcp"<?php
			if($conf['dhcp-range-start']){
				echo " checked";
			}
			?> />Enable DHCP</legend>
			<ol id="el-enable-dhcp">
				<li>
					<label for="dhcp-range-start">IPV4 DHCP Start: </label>
					<input type="text" name="dhcp-range-start" value="<?php echo $conf['dhcp-range-start']; ?>" />
				</li>
				<li>
					<label for="dhcp-range-end">IPV4 DHCP End: </label>
					<input type="text" name="dhcp-range-end" value="<?php echo $conf['dhcp-range-end']; ?>" />
				</li>
				<li>
					<label for="dhcp-default-leasetime">DHCP Default Leasetime:</label>
					<input type="text" name="dhcp-default-leasetime" value="<?php echo $conf['dhcp-default-leasetime']; ?>" />
				</li>
				<i>DHCPv6 Range should consist of the last 64 bits of the address, the first 64 bits will be constructed from the interface address. <br />
				Easiest would be something like `::100` and `::1dd`</i>
				<li>
					<label for="dhcp-range-start-6">IPV6 DHCP Start: </label>
					<input type="text" name="dhcp-range-start-6" value="<?php echo $conf['dhcp-range-start-6']; ?>" />
				</li>
				<li>
					<label for="dhcp-range-end">IPV6 DHCP End: </label>
					<input type="text" name="dhcp-range-end-6" value="<?php echo $conf['dhcp-range-end-6']; ?>" />
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
			</ol>
		</fieldset>
		<div class="floatwrap">
			<button style="margin-left: 220px;" class="button" id="send_config">Save & Update</button>
		</div>
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
		log_error("dhcphosts config line @$line_num '$line' (".str_hex($line).") is invalid",true);
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
		<div class="floatwrap">
			<button class="button convertTable" id="send_dhcpleases">Save & Update</button>
			<div class="helpmsg">To edit, double click a table cell, when done press the [enter] key, then click "Save and Update"</div>
		</div>

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
		log_error("hostmap config line @$line_num '$line' (".str_hex($line).") is invalid",true);
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
		<div class="floatwrap">
			<button class="button convertTable" id="send_hostmap">Save & Update</button>
			<div class="helpmsg">To edit, double click a table cell, when done press the [enter] key, then click "Save and Update"</div>
		</div>

		
	</div>








	<!--- ADMINISTRATION --->
	

	
	
	
	
	<div id="tabs-6">

		<form class="ajaxForm" action="postdat.php?target=zipreceive" method="post" enctype="multipart/form-data">
			<input type="file" name="zippedConfig" id="zippedConfig"><br />
			<i>This hould be a Zip File of a previously backed up configuration from dnsmasq-qpkg<br />
			Containing:<br />
			<ul>
				<li>dnsmasq.conf</li>
				<li>dnsmasq_dhcphosts.conf</li>
				<li>dnsmasq_hostmap.conf</li>
			</ul>
			</i>
			<div class="floatwrap">
				<button class="button">Upload</button>
			</div>
		</form>

		<hr />
		
		<div class="floatwrap">
			<a class="button" href="zipconfig.php">Download Backup Config</a>
		</div>
		<hr />
		
		<div id="dnsmasq_status">

		</div>
		<script>
			function checkStatus(){
				if(document.getElementById('tabs-6').style.display != 'none'){
					getAjax("getstatus.php",updateStatus);
				}
				setTimeout(checkStatus, 5000);
			}
			function updateStatus(text){
				text = text.trim()
				console.log("text=["+text+"]")
				if(text == 'true'){
					document.getElementById('dnsmasq_status').innerHTML='<span class="green">RUNNING</span>'
				} else if (text = 'false' ){
					document.getElementById('dnsmasq_status').innerHTML='<span class="red">STOPPED</span>'
				}
			}
			checkStatus();
		</script>
		<hr />
						
		<div class="floatwrap">
			<form id="dnsmasq_start" class="ajaxForm" method="POST" action="postdat.php?target=start">
				<button class="button" id="start">Start Dnsmasq</button>
			</form>
			<form id="dnsmasq_stop" class="ajaxForm" method="POST" action="postdat.php?target=stop">
				<button style="margin-left: 20px;" class="button" id="stop">Stop Dnsmasq</button>
			</form>
		</div>




 
 
</body>
</html>
