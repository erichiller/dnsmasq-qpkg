<?php

include("functions.php");

$file_names = array(
	'dnsmasq.conf',
	'dnsmasq_dhcphosts.conf',
	'dnsmasq_hostmap.conf'
	);
$archive_file_name=$QPKG_NAME.'_config_backup_'.date("ymd.His").'.zip';

$zip = new ZipArchive();
//create the file and throw the error if unsuccessful
if ($zip->open($archive_file_name, ZIPARCHIVE::CREATE )!==TRUE) {
	exit("cannot open <$archive_file_name>\n");
}
//add each of the files of $file_name array to archive
foreach($file_names as $files){
	error_log($installPath."/".$files);
	$zip->addFile($installPath."/".$files,$files);
}
$zip->close();

header("Content-type: application/zip"); 
header("Content-Disposition: attachment; filename=$archive_file_name");
header("Content-length: " . filesize($archive_file_name));
header("Pragma: no-cache"); 
header("Expires: 0"); 
readfile("$archive_file_name");


