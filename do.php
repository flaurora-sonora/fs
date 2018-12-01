<?php



//$directory = "c:/anna-font";
//$directory = "D:";
//$directory = "D:/Install";
//$directory = "1";
//$directory = "D:\Profiles";


//var_dump($fs->file_counter);exit(0);
//$fs->recursive_directory_list($directory);
//$fs->list_folder_paths($directory);
//$fs->write_folder_paths_to_file($directory);
//$fs->create_folders_array($directory);
//$fs->get_permutations_of_folder_relations_from_file_iteratively();
//$fs->get_counted_permutations_of_folder_relations_from_file();
//$fs->transit_intensity_map_from_file();
//print("this->file_counter: ");var_dump($fs->file_counter);print("<br>\r\n");
//print("this->folder_counter: ");var_dump($fs->folder_counter);print("<br>\r\n");

$action = $_REQUEST['action'];
if($action == false) {
	//print('Action is false; cannot proceed.');exit(0);
	print('<ul>
<li><a href="do.php?action=backup_important_files">Backup Important Files</a></li>
<li><a href="do.php?action=differentially_backup_important_files">Differentially Backup Important Files</a></li>
<li><a href="do.php?action=clean_backup_folders">Clean Backup Folders</a></li>
<li><a href="do.php?action=redistribute_files">Redistribute Files</a></li>
<li><a href="do.php?action=analyze_drives">Analyze Drives</a></li>
<li><a href="do.php?action=navigate_files">Navigate Files</a></li>
<li><a href="do.php?action=browse&path=https://duckduckgo.com">Browse Internet</a></li>
</ul>');
} else {
	include('fs.php');
	$fs = new fs();
	$res = call_user_func(array($fs, $action));
}



?>