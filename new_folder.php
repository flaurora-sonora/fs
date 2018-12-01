<?php

/*if($_REQUEST['path'] == "") {
	$path = '';
} else {
	$path = fs::query_decode($_REQUEST['path']);
}*/
define('DS', DIRECTORY_SEPARATOR);
$path = $_REQUEST['path'];
$fixed_drives = $_REQUEST['fixed_drives'];
$file_operations_string = $_REQUEST['file_operations_string'];
$new_folder_name = $_REQUEST['new_folder_name'];
//print('$path, $fixed_drives, $new_folder_name: ');var_dump($path, $fixed_drives, $new_folder_name);
mkdir($path . DS . $new_folder_name);
//header('Location: do.php?action=navigate_files&path=' . $path . '&fixed_drives=' . $fixed_drives);
//exit(0);
print('<meta http-equiv="refresh" content="0; url=do.php?action=navigate_files&path=' . substr($path, strpos($path, DS) + 1) . '&fixed_drives=' . $fixed_drives . $file_operations_string . '" />');

?>
