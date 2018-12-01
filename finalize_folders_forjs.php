<?php

define('DS', DIRECTORY_SEPARATOR);
if(!include('..' . DS . 'LOM' . DS . 'O.php')) {
	print('<a href="https://www.phpclasses.org/package/10594-PHP-Extract-information-from-XML-documents.html">LOM</a> is required');exit(0);
}
$folders = new O('folders.xml');
$folder_actions = file_get_contents('folder_actions.txt');
$actions = explode('
', $folder_actions);
$actions = array_unique($actions);
//print('$actions: ');var_dump($actions);exit(0);
foreach($actions as $action) {
	//print('here256470<br>');
	if(strlen($action) === 0) {
		//print('here256471<br>');
	} else {
		//print('here256472<br>');
		$action_pieces = explode('	', $action);
		//print('$action_pieces: ');var_dump($action_pieces);
		if($action_pieces[0] === 'new_') { // new_	$path_folder	not here yet	$last_query
			//print('here256473<br>');
			$folders->new_('<folder name="' . $action_pieces[1] . '" timesaccessed="' . $action_pieces[2] . '"></folder>', $action_pieces[3]);
		} elseif($action_pieces[0] === 'set_attribute') { // set_attribute	$modified	$foldersize	serialize($folder)
			//print('here256474<br>');
			$folders->set_attribute('modified', $action_pieces[1], $action_pieces[3]);
			$folders->set_attribute('size', $action_pieces[2], $action_pieces[3]);
			//break; // debug
			//if(strpos($action_pieces[3], '03') !== false) {
			//	break;
			//}
		} else {
			print('unknown action');exit(0);
		}
		//print('$folders->code: ');$folders->var_dump_full($folders->code);
	}
}
// what about looking for orphaned folders (from filesystem actions outside of fs)? whose job is it to clean this up?
$folders->save();
file_put_contents('folder_actions.txt', '');

?>