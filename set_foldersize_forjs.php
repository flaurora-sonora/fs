<?php

define('DS', DIRECTORY_SEPARATOR);
$path = $_REQUEST['path'];
$path = urldecode($path);
$path = html_entity_decode($path);
$fixed_drives_string = $_REQUEST['fixed_drives'];
$GLOBALS['fixed_drives_array'] = explode(',', $fixed_drives_string);
include('..' . DS . 'LOM' . DS . 'O.php');
/*if(!is_file('folders.xml')) {
	file_put_contents('folders.xml', '<folders></folders>');
}
// will probably benefit from saving this LOM object (serialized?) instead of always recalculating it
// currently, since we are doing parallel processing as AJAP comes across folders to get the size of, only the last save() will work since it is reading in folders.xml before the previous folders have updated it
$GLOBALS['folders'] = new O('folders.xml');*/
$GLOBALS['folders'] = unserialize(file_get_contents('serialized_folders.txt'));
$GLOBALS['new_folder_actions'] = '';
print(set_foldersize($path));
$folder_actions = file_get_contents('folder_actions.txt');
file_put_contents('folder_actions.txt', $folder_actions . $GLOBALS['new_folder_actions']);

function set_foldersize($path) {
	// we assume that we don't need to normalize_slashes since it's effectively an internal function
	// assuming driveless $path
	$path_folders = explode(DS, $path);
	$added_new_folder_for_path = false;
	$foldersize = 0;
	$last_query = $query = 'folders';
	$drive_path = $GLOBALS['fixed_drives_array'][0] . ':';
	foreach($path_folders as $path_folder_index => $path_folder) {
		$query .= '_folder@name=' . $GLOBALS['folders']->enc($path_folder);
		$drive_path .= DS . $path_folder;
		$drive_path_counter = 0;
		while($drive_path_counter < sizeof($GLOBALS['fixed_drives_array']) && !is_dir($drive_path)) {
			$drive_path = $GLOBALS['fixed_drives_array'][$drive_path_counter] . substr($drive_path, 1);
			$drive_path_counter++;
		}
		$modified = filemtime($drive_path);
		// check for the unlikely identical path on a different drive with newer date modified
		while($drive_path_counter < sizeof($GLOBALS['fixed_drives_array'])) {
			$potential_drive_path = $GLOBALS['fixed_drives_array'][$drive_path_counter] . substr($drive_path, 1);
			if(is_dir($potential_drive_path) && filemtime($potential_drive_path) > $modified) {
				$modified = filemtime($potential_drive_path);
				$drive_path = $potential_drive_path;
			}
			$drive_path_counter++;
		}
		$modified = (string)$modified;
		$folder = $GLOBALS['folders']->get_tagged($query);
		if(sizeof($folder) === 0) {
			//$folder = $GLOBALS['folders']->new_('<folder name="' . $path_folder . '" timesaccessed="not here yet"></folder>', $last_query);
			$GLOBALS['new_folder_actions'] .= '
new_	' . $path_folder . '	not here yet	' . $last_query;
			if($path_folder_index === sizeof($path_folders) - 1) {
				$added_new_folder_for_path = true;
			}
		} else {
			if(is_dir($drive_path) && $path_folder_index === sizeof($path_folders) - 1) {
				$existing_modified = $GLOBALS['folders']->get_attribute('modified', $folder);
				if($existing_modified === $modified) {
					return $GLOBALS['folders']->get_attribute('size', $folder);
				}
			}
		}
		// name, modified, size, times_accessed, anything else? contains ex. 341 Files, 57 Folders ?
		$last_query = $query;
		//$this->folder_counter++;
	}
	if(is_dir($drive_path)) {
		$handle = opendir($drive_path);
		while(false !== ($entry = readdir($handle))) {
			if($entry == '.' || $entry == '..') {
				continue;
			}
			$full_path = $drive_path . DS . $entry;
			$full_path = str_replace(DS . DS, DS, $full_path);
			if(is_dir($full_path)) {
				$foldersize += set_foldersize($path . DS . $entry);
			} else {
				$foldersize += find_filesize($full_path);
				//$this->file_counter++;
			}
		}
		closedir($handle);
	} elseif(is_file($drive_path)) {
		$foldersize += find_filesize($drive_path);
		//$this->file_counter++;
	}
	if(strpos($folder[0][0], 'size="') !== false && strpos($folder[0][0], 'size="') < strpos($folder[0][0], '>')) {
		
	} else {
		//$folder = $GLOBALS['folders']->set_attribute('modified', $modified, $folder);
		//$folder = $GLOBALS['folders']->set_attribute('size', $foldersize, $folder);
		$GLOBALS['new_folder_actions'] .= '
set_attribute	' . $modified . '	' . $foldersize . '	' . $last_query;
	}
	return $foldersize;
}

function find_filesize($file) {
	$file_size = filesize($file);
	$true_size = $file_size >= 0 ? $file_size : 4*1024*1024*1024 + $file_size;
    return $true_size;
}

?>