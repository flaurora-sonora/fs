<?php

DEFINE('DS', DIRECTORY_SEPARATOR);
//$folder = 'P:' . DS . 'Games' . DS . 'Age of Wonders III';
$folder = $_REQUEST['folder'];
$fixed_drives_string = $_REQUEST['fixed_drives'];
$fixed_drives_array = explode(',', $fixed_drives_string);
//print('$folder, $fixed_drives_array: ');var_dump($folder, $fixed_drives_array);exit(0);
$array_entries = array();
foreach($fixed_drives_array as $fixed_drive) {
	$drived_folder = $fixed_drive . ':' . DS . $folder;
	if(is_dir($drived_folder)) {
		$handle = opendir($drived_folder);
		//print('$handle: ');var_dump($handle);exit(0);
		while(($entry = readdir($handle)) !== false) {
			//if($entry === '.' || $entry === '..') {
			if($entry === '.') {
				
			} else {
				$array_entries[$entry] = true;
			}
		}
		closedir($handle);
	}
}
//print('$array_entries: ');var_dump($array_entries);exit(0);
// sort?
$did_first = false;
foreach($array_entries as $entry => $true) {
	if($did_first) {
		print('	' . $entry);
	} else {
		$did_first = true;
		print($entry);
	}
}

?>