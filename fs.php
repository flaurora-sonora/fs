<?php

class fs {

function __construct() {
	$this->initial_time = fs::getmicrotime();
	define('DS', DIRECTORY_SEPARATOR);
	//mb_internal_encoding("utf-8");
	$this->file_counter = 0;
	$this->folder_counter = 0;
	$this->foldersizes_to_be_calculated = 0;
	$this->current_folder_array = array();
	$this->contents = '';
	$this->files = '';
	// request variables
	$this->action = fs::get_by_request('action');
	$this->path = fs::get_by_request('path');
	$this->last_path = fs::get_by_request('last_path'); // used in browse. necessary?
	$this->restore_path = fs::get_by_request('restore_path');
	$this->file_to_extract = fs::get_by_request('file_to_extract');
	$this->fixed_drives_string = fs::get_by_request('fixed_drives');
	if($this->fixed_drives_string == '') {
		$fso = new COM('Scripting.FileSystemObject'); 
		$D = $fso->Drives; 
		$type = array("Unknown", "Removable", "Fixed", "Network", "CD-ROM", "RAM Disk");
		$array_fixed_drives = array();
		foreach($D as $d){ 
			$dO = $fso->GetDrive($d);
			if($dO->DriveType == 2) {
				$array_fixed_drives[] = $dO->DriveLetter;
			}
		}
		$this->fixed_drives_string = implode(',', $array_fixed_drives);
	}
	$this->fixed_drives = $this->fixed_drives_array = explode(',', $this->fixed_drives_string);
	$this->file_operations_string = '';
	$this->move = fs::get_by_request('move');
	$this->copy = fs::get_by_request('copy');
	if(strlen($this->move) > 0) {
		$this->file_operations_string .= '&move=' . $this->move;
	}
	if(strlen($this->copy) > 0) {
		$this->file_operations_string .= '&copy=' . $this->copy;
	}
	// configuration
	$this->theme = 'dark'; // default (light), dark
	$this->mode = 'icons'; // default (text), icons, 3d?
	if(fs::get_by_request('theme') != false) {
		// replace the line in this file and set the variable
		$fs_contents = file_get_contents('fs.php');
		$fs_contents = preg_replace('/\$this->theme = \'[^\']{0,}\';/is', '\$this->theme = \'' . fs::get_by_request('theme') . '\';', $fs_contents);
		//print('/$this->theme = \'[^\']{0,}\';/is, \$this->theme = \'' . fs::get_by_request('theme') . '\';: ');var_dump('/$this->theme = \'[^\']{0,}\';/is', '\$this->theme = \'' . fs::get_by_request('theme') . '\';');
		file_put_contents('fs.php', $fs_contents);
		$this->theme = fs::get_by_request('theme');
	}
	if(fs::get_by_request('mode') != false) {
		// replace the line in this file and set the variable
		$fs_contents = file_get_contents('fs.php');
		$fs_contents = preg_replace('/\$this->mode = \'[^\']{0,}\';/is', '\$this->mode = \'' . fs::get_by_request('mode') . '\';', $fs_contents);
		file_put_contents('fs.php', $fs_contents);
		$this->mode = fs::get_by_request('mode');
	}
	//print('$this->theme, $this->mode: ');var_dump($this->theme, $this->mode);
	$this->array_link_text = array(
	'file' => 'file', 
	'folder' => 'folder', 
	'up' => 'up one level', 
	'menu' =>  'back to menu', 
	'navigate' => 'navigate files', 
	'recursive' => 'recursive directory list',
	'one-click' => 'one-click navigate files',
	'fractal' => 'create fractal_zip container', 
	'extract' => 'extract all from fractal_zip container',
	'move' => 'move', 
	'copy' => 'copy', 
	'place' => 'place', 
	'delete' => 'delete', 
	'restore' => 'restore from backup', 
	'rename' => 'rename', 
	'keywords' => 'keywords navigate', 
	'backup' => 'add to backup list', 
	);
	//$this->directories_counter = 0;
	//$this->space_threshold = 0.05;
	$this->space_threshold = 0.10;
	$this->desktop_shortcut_path = 'C:\Users\<username>\Desktop\fs.lnk';
	$this->files_times_accessed = array();
	if(!file_exists('files_times_accessed.txt')) {
		file_put_contents('files_times_accessed.txt', '');
	}
	$contents = file_get_contents('files_times_accessed.txt');
	$lines = explode('
', $contents);
	foreach($lines as $line) {
		if(strlen($line) > 0) {
			$line_components = explode('	', $line);
			$this->files_times_accessed[$line_components[0]] = $line_components[1];
		}
	}
	fs::set_important_directories();
	$this->file_extension_properties = array(
	// extension => compressed, lossless compression
	// audio
	'xmf' => array(true, true),
	'amf' => array(true, true),
	'flac' => array(true, true),
	'alac' => array(true, true),
	'ape' => array(true, true),
	'ofr' => array(true, true),
	'tak' => array(true, true),
	'wv' => array(true, true),
	'tta' => array(true, true),
	'wmal' => array(true, true),
	'adpcm' => array(true, false),
	'atrac' => array(true, false),
	'ac3' => array(true, false),
	'dts' => array(true, false),
	'mp1' => array(true, false),
	'mp2' => array(true, false),
	'mp3' => array(true, false),
	'aac' => array(true, false),
	'wav' => array(false, false),
	// video
	'aasc' => array(true, true),
	'lagarith' => array(true, true),
	'avi' => array(true, false),
	'gvi' => array(true, false),
	'mp4' => array(true, false),
	'wmv' => array(true, false),
	'webm' => array(true, false),
	'flv' => array(true, false),
	'mkv' => array(true, false),
	'vob' => array(true, false),
	'ogv' => array(true, false),
	'ogg' => array(true, false),
	'drc' => array(true, false),
	'gifv' => array(true, false),
	'mov' => array(true, false),
	'drc' => array(true, false),
	'rm' => array(true, false),
	'rmvb' => array(true, false),
	'asf' => array(true, false),
	'amv' => array(true, false),
	'mpg' => array(true, false),
	'm4v' => array(true, false),
	'svi' => array(true, false),
	'3gp' => array(true, false),
	'yuv' => array(false, false),
	// images
	'ai' => array(true, true),
	'flif' => array(true, true),
	'png' => array(true, true),
	'svg' => array(true, true),
	'tga' => array(true, true),
	'gif' => array(true, false),
	'jpeg' => array(true, false),
	'tiff' => array(true, false),
	'pcx' => array(false, false),
	'bmp' => array(false, false),
	// data
	'fractalzip' => array(true, true),
	'fzc' => array(true, true),
	'fzsx' => array(true, true), // fzsx.php...
	'7z' => array(true, true),
	'ace' => array(true, true),
	'arc' => array(true, true),
	'arj' => array(true, true),
	'cab' => array(true, true),
	'lzh' => array(true, true),
	'dmg' => array(true, true),
	'pea' => array(true, true),
	'rar' => array(true, true),
	'zip' => array(true, true),
	'a' => array(false, false),
	'tar' => array(false, false),
	'tib' => array(false, false),
	'wim' => array(false, false),
	);
	print('<script src="jquery.min.js"></script> <!-- jquery 3.2.0 -->
<script type="text/javascript">
$(document).ready(function(){
	table = $(\'.DataTable\').DataTable( {
		responsive: true,
		paging: false,
		dom: "Bfrtip",
		buttons: [ "columnsToggle" ],
		// https://datatables.net/reference/option/language
		"language": {
            "search": "filter by string:",
        },
		/*"columnDefs": [
			{ "targets": 2, "sType": "numeric" }
		],*/
		sType: "numeric",
		/*info: false,
		order: [[ 3, asc ]]*/
		');
		if($this->action === 'navigate_files_recursive_list' || $this->action === 'keywords_navigate') {
			print('"order": []
');
		} else {
			print('"order": [[ 3, "asc" ], [ 0, "asc" ]]
');
		}
	print('	} );
	$(\'img.icon\').hover(function(){
		src = $(this).attr(\'src\');
		src = src.replace(\'_link\', \'_hover\');
		$(this).attr(\'src\', src);
		}, function(){
		src = $(this).attr(\'src\');
		src = src.replace(\'_hover\', \'_link\');
		$(this).attr(\'src\', src);
	});
	
	//alert("page has loaded");
	// won\'t hiding columns screw this code up?
	size_sum_cell = $("tfoot > tr > *")[2];
	//alert("size_sum_cell: " + size_sum_cell);
	size_sum_cell_html = size_sum_cell.innerHTML;
	//alert("size_sum_cell_html: " + size_sum_cell_html);
	foldersizes_to_be_calculated_position = size_sum_cell_html.indexOf(\'<span style="display: none;">foldersizes_to_be_calculated=\');
	foldersizes_to_be_calculated_length = size_sum_cell_html.substr(foldersizes_to_be_calculated_position + 58).indexOf("</span>");
	foldersizes_to_be_calculated = size_sum_cell_html.substr(foldersizes_to_be_calculated_position + 58, foldersizes_to_be_calculated_length);
	size_sum_position = size_sum_cell_html.indexOf(\'<span style="padding-right: 0px;">\');
	size_sum_length = size_sum_cell_html.substr(size_sum_position + 34).indexOf("</span>");
	size_sum = size_sum_cell_html.substr(size_sum_position + 34, size_sum_length);
	size_sum_minus_plus = parseInt(size_sum.substr(0, size_sum.length - 1));
	//alert("foldersizes_to_be_calculated: " + foldersizes_to_be_calculated);
	$.each($("td.foldersize"), function(index, value) {
		// <span style="padding-right: 0px;">0+</span><img src="size1.png">
		cell_contents = value.innerHTML;
		//alert(cell_contents + cell_contents.indexOf(\'<span style="padding-right: 0px;">0+</span>\'));
		if(cell_contents.indexOf(\'<span style="padding-right: 0px;">0+</span>\') == 0) {
			tr = $(this).parent();
			ths_in_tr = $("th", tr);
			path = ths_in_tr[0].innerHTML;
			path_position = path.indexOf("&amp;path=");
			path_length = path.substr(path_position + 10).indexOf("&amp;");
			path = path.substr(path_position + 10, path_length);
			//alert("found cell with 0+, path: " + path);
			$.post("set_foldersize_forjs.php", { "path": path, "fixed_drives": "' . $this->fixed_drives_string . '" }, function(set_foldersize_result){
				//alert("set_foldersize_result, $(this).html: " + set_foldersize_result + ", " + $(this).html);
				value.innerHTML = \'<span style="padding-right: 8px;">\' + set_foldersize_result + \'</span><img src="size\' + set_foldersize_result.length + \'.png">\';
				size_sum_minus_plus += parseInt(set_foldersize_result);
				foldersizes_to_be_calculated--;
				//alert("foldersizes_to_be_calculated: " + foldersizes_to_be_calculated);
				if(foldersizes_to_be_calculated === 0) {
					size_sum_minus_plus = String(size_sum_minus_plus);
					size_sum_cell.innerHTML = \'<span style="padding-right: 8px;">\' + size_sum_minus_plus + \'</span><img src="size\' + size_sum_minus_plus.length + \'.png">\';
					$.post("finalize_folders_forjs.php", { }, function(finalize_folders_result){
						//alert(finalize_folders_result);
						//table.draw(); // trying to get foldersizes to be caught by sorting
						//table.clear().draw(); // trying to get foldersizes to be caught by sorting
						// chose to optimize LOM rather than redrawing the table after adjusting foldersizes
					});
				} else {
					size_sum_minus_plus_string = String(size_sum_minus_plus);
					size_sum_cell.innerHTML = \'<span style="padding-right: 0px;">\' + size_sum_minus_plus_string + \'+</span><img src="size\' + size_sum_minus_plus_string.length + \'.png">\';
				}
			});
		}
	});
});
</script>
<link rel="stylesheet" type="text/css" href="DataTables/datatables.css" />
 
<script type="text/javascript" src="DataTables/dataTables.js"></script>


<!-- the next two were for the fancy date picker but it is unnecessary -->
<!--script src="jquery-ui-1.12.1.custom/jquery-ui.min.js"></script-->
<!--link rel="stylesheet" href="jquery-ui-1.12.1.custom/jquery-ui.min.css" /-->
<script src="date_filter.js"></script>

<style type="text/css">
details { display: inline; }
summary { /*padding: 0; margin: 0; padding-top: -1em; margin-top: -1em; position: relative; top: -1em; display: none;*/ }
img { /*padding: 0; margin: 0; position: relative; top: -9px; display: none;*/ }
td, th { /*height: 32px;*/ padding: 0; /*margin: 0; margin-top: -9px;*/ }
/*.icon { background-image: url(\'icons/hover.png\'); }
div img.icon { float: left; }
div.icon { float: left; }*/

table.dataTable { /*width: 100%; margin: 0 auto;*/ width: auto; margin: 0; }
table.dataTable thead th, table.dataTable thead td { /*padding: 10px 18px;*/ padding: 2px 11px 2px 2px; }
table.dataTable tfoot th, table.dataTable tfoot td { /*padding: 10px 18px 6px 18px;*/ padding: 2px 2px 2px 2px; }
table.dataTable tbody th, table.dataTable tbody td { /*padding: 8px 10px;*/ padding: 0 2px 0 2px; /* here would be where to retune for the size images */ }
table.dataTable.cell-border thead th, table.dataTable.cell-border thead td { /*border-top: 1px solid #fff;*/ border-right: 1px solid #fff; }
table.dataTable.cell-border tfoot th, table.dataTable.cell-border tfoot td { /*border-top: 1px solid #fff;*/ border-right: 1px solid #fff; }
table.dataTable td.dataTables_empty { /*text-align: center;*/ text-align: left; }
.dataTables_wrapper { /*clear: both;*/ clear: both; /*padding-top: 0; margin-top: 0;*/ top: -10px; }
button.dt-button, div.dt-button, a.dt-button { /*padding: 0.5em 1em;*/ padding: 0 0.5em; }
'); 
	if($this->theme === 'dark') {
		$this->size_background_url = 'size_background_dark.png';
		print('html, table, th, td { background: #181a21; color: #a6aaab; /* icon color: 343843 */ }
a { /*color: #0000ee;*/ color: #2bc9ee; }
a:visited { /*color: #551a8b;*/ color: #ff7900; color: #99ee88; color: #991188; color: #bb22aa; color: #a18; }
a:active { color: #e00; }
table.dataTable.cell-border thead th, table.dataTable.cell-border thead td { border-right: 1px solid #222; }
table.dataTable.cell-border tbody th, table.dataTable.cell-border tbody td { border-top: 1px solid #333; border-right: 1px solid #333; }
table.dataTable.cell-border tfoot th, table.dataTable.cell-border tfoot td { border-right: 1px solid #222; }
table.dataTable.cell-border tbody tr th:first-child, table.dataTable.cell-border tbody tr td:first-child { border-left: 1px solid #333; }
.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate { color: #999; }
a.icon:link { border-top: 9px solid #2bc9ee; border-right: 14px solid #2bc9ee; border-bottom: 0px solid #2bc9ee; border-left: 14px solid #2bc9ee; /* why 9?? */ }

button.dt-button,
div.dt-button,
a.dt-button {
  color: #a6aaab;
  background-color: #171717;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #303742 0%, #171717 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #303742 0%, #171717 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #303742 0%, #171717 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #303742 0%, #171717 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #303742 0%, #171717 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#303742", EndColorStr="#171717");
}
button.dt-button.disabled,
div.dt-button.disabled,
a.dt-button.disabled {
  color: #777;
  border: 1px solid #303030;
  background-color: #070707;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #000000 0%, #070707 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #000000 0%, #070707 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #000000 0%, #070707 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #000000 0%, #070707 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #000000 0%, #070707 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#000000", EndColorStr="#070707");
}
button.dt-button:active:not(.disabled), button.dt-button.active:not(.disabled),
div.dt-button:active:not(.disabled),
div.dt-button.active:not(.disabled),
a.dt-button:active:not(.disabled),
a.dt-button.active:not(.disabled) {
  background-color: #1e1e1e;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #0c0c0c 0%, #1e1e1e 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #0c0c0c 0%, #1e1e1e 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #0c0c0c 0%, #1e1e1e 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #0c0c0c 0%, #1e1e1e 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #0c0c0c 0%, #1e1e1e 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#0c0c0c", EndColorStr="#1e1e1e");
  box-shadow: inset 1px 1px 3px #666666;
}
button.dt-button:active:not(.disabled):hover:not(.disabled), button.dt-button.active:not(.disabled):hover:not(.disabled),
div.dt-button:active:not(.disabled):hover:not(.disabled),
div.dt-button.active:not(.disabled):hover:not(.disabled),
a.dt-button:active:not(.disabled):hover:not(.disabled),
a.dt-button.active:not(.disabled):hover:not(.disabled) {
  box-shadow: inset 1px 1px 3px #666666;
  background-color: #222222;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #141414 0%, #222222 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #141414 0%, #222222 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #141414 0%, #222222 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #141414 0%, #222222 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #141414 0%, #222222 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#141414", EndColorStr="#222222");
}
button.dt-button:hover:not(.disabled),
div.dt-button:hover:not(.disabled),
a.dt-button:hover:not(.disabled) {
  border: 1px solid #999;
  background-color: #202020;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #060606 0%, #202020 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #060606 0%, #202020 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #060606 0%, #202020 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #060606 0%, #202020 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #060606 0%, #202020 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#060606", EndColorStr="#202020");
}
button.dt-button:focus:not(.disabled),
div.dt-button:focus:not(.disabled),
a.dt-button:focus:not(.disabled) {
  border: 1px solid #bd9361;
  text-shadow: 0 1px 0 #3c210e;
  background-color: #855316;
  /* Fallback */
  background-image: -webkit-linear-gradient(top, #42210c 0%, #855316 100%);
  /* Chrome 10+, Saf5.1+, iOS 5+ */
  background-image: -moz-linear-gradient(top, #42210c 0%, #855316 100%);
  /* FF3.6 */
  background-image: -ms-linear-gradient(top, #42210c 0%, #855316 100%);
  /* IE10 */
  background-image: -o-linear-gradient(top, #42210c 0%, #855316 100%);
  /* Opera 11.10+ */
  background-image: linear-gradient(to bottom, #42210c 0%, #855316 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr="#42210c", EndColorStr="#855316");
}

');
	} else {
		$this->size_background_url = 'size_background.png';
		print('
a.icon:link { border-top: 9px solid #00e; border-right: 14px solid #00e; border-bottom: 0px solid #00e; border-left: 14px solid #00e; /* why 9?? */ }
');
	}
	print('

img.icon { /*border: 2px solid green;*/ position: relative; top: 4px; /*left: 28px;*/ /*left: -28px;*/ /* why 4?? */ }
a.hover { /*border: 2px solid blue;*/ position: relative; /*left: -28px;*/ margin-left: -28px; padding-top: 9px; /* why 9?? */ }
a.hover:hover { background-image: url(\'icons/hover.png\'); }
a.hover:active { background-image: url(\'icons/red.png\'); }

</style>
');
	//print('$this->theme, $this->mode: ');var_dump($this->theme, $this->mode);
	print('<div style="float: right;">');
	if(is_file($this->path)) {
		$action = 'open_file';
	} else {
		$action = 'navigate_files';
	}
	if($this->theme === 'dark') {
		print('theme: dark <a href="do.php?action=' . $action . '&path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . '&theme=light">light</a><br>');
	} else {
		print('theme: <a href="do.php?action=' . $action . '&path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . '&theme=dark">dark</a> light<br>');
	}
	if($this->mode === 'icons') {
		print('mode: <a href="do.php?action=' . $action . '&path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . '&mode=text">text</a> icons');
	} else {
		print('mode: text <a href="do.php?action=' . $action . '&path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . '&mode=icons">icons</a>');
	}
	print('</div>');
}

function set_backup_directories() {
	$this->backup_directories = array(
	'C:/Backup',
	);
}

function set_important_directories() {
	$this->important_directories = array(

	'C:\Windows\System32\notepad.exe',
	
	);
}

function is_an_important_directory($directory_to_test) {
	foreach($this->important_directories as $important_directory) {
		if(fs::normalize_slashes($important_directory) === fs::normalize_slashes($directory_to_test)) {
			return true;
		}
	}
	return false;
}

function restore_from_backup() {
	// a good restore function would actually consider which folders were full backups rather than brute forcing
	// it would also not wastefully restore sequentially from backup directories redundant backup copies that were made at the same time
	// and would put the proper date modified on folders as well
	if($this->restore_path == '') {
		fatal_error('restore_path not properly specified.');
	}
	$drive_restore_path = fs::drive_path_from_path($this->restore_path);
	$drive_marker_position = strpos($this->restore_path, ':' . DS);
	if($drive_marker_position !== false) {
		$driveless_restore_path = substr($this->restore_path, $drive_marker_position + 2);
	} else {
		$driveless_restore_path = $this->restore_path;
	}
	fs::set_backup_directories();
	$this->backup_directories = fs::normalize_slashes($this->backup_directories);
	foreach($this->backup_directories as $index1 => $value1) {
		$handle1 = opendir($value1);
		while(($entry = readdir($handle1)) !== false) {
			if($entry === '.' || $entry === '..') {
				
			} elseif(is_dir($value1 . DS . $entry)) {
				foreach($this->fixed_drives_array as $fixed_drive) {
					$possible_backup_path = $value1 . DS . $entry . DS . $fixed_drive . DS . $driveless_restore_path;
					//print('$possible_backup_path, fs::file_extension($driveless_restore_path), file_exists($possible_backup_path), is_dir($possible_backup_path): ');var_dump($possible_backup_path, fs::file_extension($driveless_restore_path), file_exists($possible_backup_path), is_dir($possible_backup_path));
					if(fs::file_extension($driveless_restore_path) !== false && file_exists($possible_backup_path)) { // it is a file and there is a backup of it
						//print('here37586970<br>');
						fs::recursive_restore($possible_backup_path, $drive_restore_path);
					} elseif(fs::file_extension($driveless_restore_path) === false && is_dir($possible_backup_path)) { // it is a directory and there is a backup of it
						//print('here37586971<br>');
						fs::recursive_restore($possible_backup_path, $drive_restore_path);
					}
				}
			}
		}
		closedir($handle1);
	}
	//$drive_marker_position = strpos($this->restore_path, ':' . DS);
	if($drive_marker_position === false) {
		print(substr($drive_restore_path, 0, $drive_marker_position + 2) . '<a href="do.php?action=navigate_files&path=' . fs::query_encode($this->restore_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '">' . $this->restore_path . '</a> successfully restored from backup. ' . fs::icon_code('menu', 'do.php'));
	} else {
		print('<a href="do.php?action=navigate_files&path=' . fs::query_encode($this->restore_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '">' . $this->restore_path . '</a> successfully restored from backup. ' . fs::icon_code('menu', 'do.php'));	
	}
}

function backup_important_files() {
	// should consider whether the date of the backup or the modified date of what is being backed up should be used
	//print('Backing up important files...');exit(0);
	fs::set_backup_directories();
	//fs::set_important_directories();
	$this->backup_directories = fs::normalize_slashes($this->backup_directories);
	
	/*print('<table>
<tr>
<th>Original</th>
<th>Copy</th>
</tr>');*/
	foreach($this->backup_directories as $index1 => $value1) {
		$notes = 'This was a full backup of:
';
		if(is_dir($value1 . DS . date("Y-m-d"))) {
			print('Hmm, seems like a backup was already done today since ' . $value1 . DS . date("Y-m-d") . ' already exists; stopping.');exit(0);
		}
		foreach($this->important_directories as $index2 => $value2) {
			$value2 = fs::normalize_slashes($value2);
			fs::recursive_copy($value2, $value1 . DS . date("Y-m-d") . DS . str_replace(':' . DS, DS, $value2));
			//exit(0);
			$notes .= $value2 . '
';
		}
		file_put_contents($value1 . DS . date("Y-m-d") . DS . 'notes.txt', $notes);
	}
	//print('</table>');
	print('Important files successfully backed up. ' . fs::icon_code('menu', 'do.php'));
}

function clean_backup_folders() {
	fs::set_backup_directories();
	foreach($this->backup_directories as $index1 => $value1) {
		fs::recurse_clean_backup_folders($value1);
	}
	print('Backup folders successfully cleaned. ' . fs::icon_code('menu', 'do.php'));
}

function differentially_backup_important_files() {
	// should consider whether the date of the backup or the modified date of what is being backed up should be used
	//print('Differentially backing up important files...');exit(0);
	fs::set_backup_directories();
	//fs::set_important_directories();
	$this->backup_directories = fs::normalize_slashes($this->backup_directories);
	
	/*print('<table>
<tr>
<th>Original</th>
<th>Copy</th>
</tr>');*/
	foreach($this->backup_directories as $index1 => $value1) {
		$notes = 'This was a differential backup of:
';
		if(is_dir($value1 . DS . date("Y-m-d"))) {
			print('Hmm, seems like a backup was already done today since ' . $value1 . DS . date("Y-m-d") . ' already exists; stopping.');exit(0);
		}
		$this->array_dates = array();
		$dir = opendir($value1);
		while(false !== ($entry = readdir($dir))) {
			if(($entry != '.') && ($entry != '..')) {
				//var_dump($value1 . DS . $entry);
				if(is_dir($value1 . DS . $entry)) {
					preg_match('/[0-9\-]+/is', $entry, $date_component_matches);
					//var_dump($date_component_matches);
					if(strlen($entry) === strlen($date_component_matches[0])) {
						$this->array_dates[] = $entry;
					}
				}
			}
		}
		closedir($dir);
		$this->array_dates = array_reverse($this->array_dates);
		//var_dump($this->array_dates);exit(0);
		foreach($this->important_directories as $index2 => $value2) {
			$value2 = fs::normalize_slashes($value2);
			//print('$value2, str_replace(\':\' . DS, DS, $value2): ');var_dump($value2, str_replace(':' . DS, DS, $value2));
			fs::differential_recursive_copy($value2, $value1 . DS . '{date}' . DS . str_replace(':' . DS, DS, $value2));
			//exit(0);
			$notes .= $value2 . '
';
		}
		file_put_contents($value1 . DS . date("Y-m-d") . DS . 'notes.txt', $notes);
	}
	//print('</table>');
	print('Important files successfully differentially backed up. ' . fs::icon_code('menu', 'do.php'));
}

function normalize_slashes($string) {
	$string = str_replace('/', DS, $string);
	$string = str_replace('\\', DS, $string);
	return $string;
}

function recurse_clean_backup_folders($folder) {
    if(preg_match('/\-[0-9]{8}/is', $folder) || preg_match('/\-[0-9]{4}-[0-9]{2}-[0-9]{2}/is', $folder) || preg_match('/\- Copy/is', $folder)) {
		//chmod($folder, 0777);
		fs::recursiveChmod($folder);
		unlink($folder);
		print($folder . ' was deleted.<br>');
	} else {
		$dir = opendir($folder);
		while(false !== ($entry = readdir($dir))) {
			if(($entry != '.') && ($entry != '..')) {
				if(is_dir($folder . DS . $entry)) {
					fs::recurse_clean_backup_folders($folder . DS . $entry);
				}
			}
		}
		closedir($dir); 
	}
}

function analyze_drives_free_space_old() {
	$counter = 65;
	while($counter < 91) {
		$drive = chr($counter) . ':';
		if(is_dir($drive)) {
			print('Scanning drive ' . $drive . '<br>');
			//var_dump(disk_free_space($drive), disk_total_space($drive));
			$free_space = disk_free_space($drive);
			$total_space = disk_total_space($drive);
			print('Free space: ' . $free_space . ' bytes<br>');
			print('Total space: ' . $total_space . ' bytes<br>');
			if($free_space / $total_space < $this->space_threshold) {
				print('<span style="color: red;">This disk is low on space.</span><br>');
			}
			print('<br>');
		}
		$counter++;
	}
}

function test001() {
	//print('hi');exit(0);
	//fopen('C:\Windows\System32\notepad.exe', 'r');
	//shell_exec('C:\Windows\System32\notepad.exe'); // works in session 0
	//system('C:\Windows\System32\notepad.exe'); // works in session 0
	//exec('C:\Windows\System32\notepad.exe'); // works in session 0
	//exec('test002.bat'); // works in session 0
	//exec('C:\Windows\System32\SoundRecorder.exe'); // works in session 0
	//passthru('C:\Windows\System32\notepad.exe'); // works in session 0
	
	//exec('start /b C:\Windows\System32\notepad.exe'); // works in session 0
	
	//try {
	//$shell = new COM("WScript.Shell");
	//$shell->run('start /b C:\Windows\System32\notepad.exe', 0, false);
	//$command = 'C:\Windows\System32\notepad.exe';
	//$command = 'start /b C:\Windows\System32\notepad.exe';
	//$shell->run($command, 0, false);
	/*} catch (Exception $e) { 
	var_dump($e->getMessage(), $e->getCode()); 
	}
	*/
	//$shell->run('start /b C:\Windows\System32\notepad.exe', 0, false);
	//exec('start /b ' . $notepad);
	fs::recursive_move('D:\Audio\Bands', 'S:\Audio\Bands');
}

function anchor_encode($string) {
	$string = str_replace(' ', '%20', $string);
	return fs::query_encode($string);
}

function anchor_decode($string) {
	$string = str_replace('%20', ' ', $string);
	return fs::query_decode($string);
}

function query_encode($string) {
	//var_dump(urlencode('&'));exit(0);
	$string = str_replace('&', '%26', $string);
	return $string;
}

function query_decode($string) {
	$string = str_replace('%26', '&', $string);
	return $string;
}

function print_breadcrumb($path, $open_file = false) { // alias
	return fs::print_segmented_path($path, $open_file);
}

function print_segmented_path($path, $open_file = false) {
	if(strpos($path, ':') !== false) {
		$path = substr($path, strpos($path, ':') + 2);
	}
	//print('driveless $path: ');var_dump($path);
	$path = str_replace('\\', DS, $path);
	$path = str_replace('/', DS, $path);
	$split_path_array = explode(DS, $path);
	print('&rarr; ');
	foreach($split_path_array as $index => $entry) {
		if($index === sizeof($split_path_array) - 1) {
			if($open_file) {
				print($entry);
			} else {
				$path_up_to_index = fs::get_path_up_to_index($path, $index);
				$path_up_to_index_plus_one = fs::get_path_up_to_index($path, $index + 1);
				print('<a href="do.php?action=navigate_files&path=' . fs::query_encode($path_up_to_index) . '&fixed_drives=' . $this->fixed_drives_string . '#' . fs::anchor_encode(substr($path_up_to_index_plus_one, fs::strpos_last($path_up_to_index_plus_one, DS) + 1)) . '">' . $entry . '</a>');
			}
		} else {
			$path_up_to_index = fs::get_path_up_to_index($path, $index);
			$path_up_to_index_plus_one = fs::get_path_up_to_index($path, $index + 1);
			print('<a href="do.php?action=navigate_files&path=' . fs::query_encode($path_up_to_index) . '&fixed_drives=' . $this->fixed_drives_string . '#' . fs::anchor_encode(substr($path_up_to_index_plus_one, fs::strpos_last($path_up_to_index_plus_one, DS) + 1)) . '">' . $entry . '</a>');
			print(' &rarr; ');
		}
	}
	print('<br>
');
}

function icon_code($function, $link) {
	if($this->mode === 'icons') {
		//return '<div class="icon"><a href="' . $link . '"><img class="icon" src="icons/' . $function . '_' . $this->theme . '_link.png" title="' . $this->array_link_text[$function] . '" /></a></div>'; // no space
		return '<a class="icon" href="' . $link . '"></a><a class="hover" href="' . $link . '"><img class="icon" src="icons/' . $function . '_' . $this->theme . '_link.png" title="' . $this->array_link_text[$function] . '" /></a>'; // no space
	} else {
		return '<a href="' . $link . '">' . $this->array_link_text[$function] . '</a> '; // space after
	}
}

function place() {
	if(strlen($this->move) > 0) {
		fs::recursive_move($this->move, $this->path . substr($this->move, fs::strpos_last($this->move, DS)));
	} elseif(strlen($this->copy) > 0) {
		fs::recursive_copy($this->copy, $this->path . substr($this->copy, fs::strpos_last($this->copy, DS)));
	} else {
		print('$this->move, $this->copy: ');var_dump($this->move, $this->copy);
		fs::fatal_error('unknown file operation');
	}
	//exit(0);
	print('<meta http-equiv="refresh" content="0; url=do.php?action=navigate_files&path=' . fs::query_encode(substr($this->path, 3)) . '&fixed_drives=' . $this->fixed_drives_string . '" />');
}

function navigate_files() {
	fs::init_LOM(); // took 0.68 seconds, takes 0.005 since it doesn't completely generate_LOM or even at all
	//$this->debug_foldersizes_counter = 0;
	// notice that this function is expecting preceding slashes
	if(isset($this->files_times_accessed[$this->path])) {
		$this->files_times_accessed[$this->path]++;
	} else {
		$this->files_times_accessed[$this->path] = 1;
	}
	fs::save_files_times_accessed();
	fs::print_segmented_path($this->path);
	$array_entries = array();
	$first_drive_wherein_this_folder_exists = false;
	foreach($this->fixed_drives_array as $fixed_drive) {
		$compound_path = $fixed_drive . ':' . DS . $this->path;
		if(is_dir($compound_path)) {
			$dir = opendir($compound_path);
			//var_dump($fixed_drive, $compound_path, $this->path, $dir);
			while(false !== ($entry = readdir($dir))) {
				if(($entry != '.') && ($entry != '..')) {
					$full_path = $compound_path . DS . $entry;
					$full_path = str_replace(DS . DS, DS, $full_path);
					//var_dump($full_path);
					if(isset($array_entries[$entry])) {
						$array_full_paths = $array_entries[$entry];
						$array_full_paths[] = $full_path;
						//$count = $array_entries[$entry][0];
						//$count++;
						$array_entries[$entry] = $array_full_paths;
						if($first_drive_wherein_this_folder_exists === false) {
							$first_drive_wherein_this_folder_exists = $fixed_drive;
						}
					} else {
						$array_entries[$entry] = array($full_path);
						if($first_drive_wherein_this_folder_exists === false) {
							$first_drive_wherein_this_folder_exists = $fixed_drive;
						}
					}
				}
			}
			closedir($dir);
		}
		// could later generalize this sort of code if there is a need beyond the mess that is windows program files organization
		if(strpos($this->path, DS . 'Program Files (x86)') !== false) {
			$compound_path = $fixed_drive . ':' . str_replace(DS . 'Program Files (x86)', DS . 'Program Files', $this->path);
			if(is_dir($compound_path)) {
				$dir = opendir($compound_path);
				while(false !== ($entry = readdir($dir))) {
					if(($entry != '.') && ($entry != '..')) {
						$full_path = $compound_path . DS . $entry;
						$full_path = str_replace(DS . DS, DS, $full_path);
						if(isset($array_entries[$entry])) {
							$array_full_paths = $array_entries[$entry];
							$array_full_paths[] = $full_path;
							$array_entries[$entry] = $array_full_paths;
							if($first_drive_wherein_this_folder_exists === false) {
								$first_drive_wherein_this_folder_exists = $fixed_drive;
							}
						} else {
							$array_entries[$entry] = array($full_path);
							if($first_drive_wherein_this_folder_exists === false) {
								$first_drive_wherein_this_folder_exists = $fixed_drive;
							}
						}
					}
				}
				closedir($dir);
			}
		} elseif(strpos($this->path, DS . 'Program Files') !== false) {
			$compound_path = $fixed_drive . ':' . str_replace(DS . 'Program Files', DS . 'Program Files (x86)', $this->path);
			if(is_dir($compound_path)) {
				$dir = opendir($compound_path);
				while(false !== ($entry = readdir($dir))) {
					if(($entry != '.') && ($entry != '..')) {
						$full_path = $compound_path . DS . $entry;
						$full_path = str_replace(DS . DS, DS, $full_path);
						if(isset($array_entries[$entry])) {
							$array_full_paths = $array_entries[$entry];
							$array_full_paths[] = $full_path;
							$array_entries[$entry] = $array_full_paths;
							if($first_drive_wherein_this_folder_exists === false) {
								$first_drive_wherein_this_folder_exists = $fixed_drive;
							}
						} else {
							$array_entries[$entry] = array($full_path);
							if($first_drive_wherein_this_folder_exists === false) {
								$first_drive_wherein_this_folder_exists = $fixed_drive;
							}
						}
					}
				}
				closedir($dir);
			}
		}
	}
	ksort($array_entries);
	//var_dump($array_entries);
	fs::print_entries_table($array_entries);
	/*$up_level_path = $full_path;
	$up_level_path = substr($up_level_path, strpos($up_level_path, ':') + 1);
	$up_level_path = substr($up_level_path, 0, fs::strpos_last($up_level_path, DS));
	$up_level_path = substr($up_level_path, 0, fs::strpos_last($up_level_path, DS));
	var_dump($full_path, $up_level_path);*/
	//$up_level_path = $this->path;
	//$up_level_path = substr($up_level_path, 0, fs::strpos_last($up_level_path, DS));
	//var_dump($this->path, $up_level_path);
	// notice that the full path is needed for create_fractal_zip_container and restore_from_backup to refer to a drive?
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('recursive', 'do.php?action=navigate_files_recursive_list&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('fractal', 'do.php?action=create_fractal_zip_container&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('restore', 'do.php?action=restore_from_backup&restore_path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string)) .
	fs::icon_code('keywords', 'do.php?action=keywords_navigate&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string);
	if($this->move != false || $this->copy != false) {
		print(fs::icon_code('place', 'do.php?action=place&path=' . fs::query_encode($first_drive_wherein_this_folder_exists . ':' . DS . $this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	}
}

function print_entries_table($array_entries, $caption = false) {
	print('<!--form style="margin-top: 4px;">
filter from date: <input type="text" id="from_date" name="from_date" onkeyup="from_date_update()" />
to date: <input type="text" id="to_date" name="to_date" onkeyup="to_date_update()" /> (does not update information in the table; just shows/hides)
</form-->
<p id="date_filter" style="margin-top: 4px;">filter date modified
    <span id="date-label-from" class="date-label">from: </span><input class="date_range_filter date" type="text" id="datepicker_from" onkeyup="from_date_update()" />
    <span id="date-label-to" class="date-label">to: </span><input class="date_range_filter date" type="text" id="datepicker_to" onkeyup="to_date_update()" />
</p>
<table class="DataTable cell-border" cellspacing="0" style="text-align: left;">
');
	if($caption !== false) {
		print('<caption style="text-align: left;">' . $caption . '</caption>');
	}
	print('<thead>
<th scope="col">name</th>
<th scope="col">date&nbsp;modified<!-- (created, accessed, moved)--></th>
<th scope="col">size&nbsp;(bytes)</th>
<th scope="col">extension<!-- file or folder?--></th>
<th scope="col">times&nbsp;accessed</th>
<!--th scope="col">metadata</th-->
<th scope="col">name&nbsp;score<!--: comparing to similar filenames, how much extraneous stuff is in filename, proper naming convention for movies and such--></th>
<th scope="col">contents&nbsp;score<!--: , anticipating content-based filesystems--></th>
<th scope="col">compression&nbsp;score<!--: by characteristics of extension, by looking at internal file compression parameters--></th>
<th scope="col">actions</th>
</thead>
<tbody>');
	// fileatime, filectime, filegroup, fileinode, filemtime, fileowner, fileperms, filesize, filetype
	$size_sum = 0;
	$times_accessed_sum = 0;
	$number_of_folders = 0;
	$number_of_files = 0;
	if(sizeof($array_entries) > 0) {
		foreach($array_entries as $entry => $entry_array) {
			//$item_is_a_folder = false;
			//$item_is_a_file = false;
			$full_path = $entry_array[0];
			//print('$full_path: ');var_dump($full_path);
			if(is_dir($full_path)) {
				//print('$full_path of folder: ');var_dump($full_path);
				//$item_is_a_folder = true;
				$driveless_path = fs::driveless($full_path);
				$size = fs::foldersize($full_path);
				$timestamp = filemtime($full_path);
				fs::print_folder_row($full_path, $this->path, $driveless_path, $size, $timestamp, $entry);
				$number_of_folders++;
				$times_accessed_sum += fs::file_times_accessed($driveless_path);
			} elseif(is_file($full_path)) {
				//print('$full_path of file: ');var_dump($full_path);
				//$item_is_a_file = true;
				$size = fs::find_filesize($full_path);
				$timestamp = filemtime($full_path);
				fs::print_file_row($full_path, $size, $timestamp, $entry);
				$number_of_files++;
				$times_accessed_sum += fs::file_times_accessed($full_path);
			} else { // not recognized as file or folder... then we have to work pretty hard to guess (usually due to strange characters)
				//print('$full_path of unknown: ');var_dump($full_path);
				if(strpos($full_path, '.') === false) {
					//$item_is_a_folder = true;
					$driveless_path = fs::driveless($full_path);
					$size = '?';
					$timestamp = '?';
					fs::print_folder_row($full_path, $this->path, $driveless_path, $size, $timestamp, $entry);
					$number_of_folders++;
					$times_accessed_sum += fs::file_times_accessed($driveless_path);
				} elseif(strpos(fs::file_extension($full_path), '?') !== false) {
					//$item_is_a_folder = true;
					$driveless_path = fs::driveless($full_path);
					$size = '?';
					$timestamp = '?';
					fs::print_folder_row($full_path, $this->path, $driveless_path, $size, $timestamp, $entry);
					$number_of_folders++;
					$times_accessed_sum += fs::file_times_accessed($driveless_path);
				} else {
					//$item_is_a_file = true;
					$size = '?';
					$timestamp = '?';
					fs::print_file_row($full_path, $size, $timestamp, $entry);
					$number_of_files++;
					$times_accessed_sum += fs::file_times_accessed($full_path);
				}
			}
			$size_sum += $size;
			/*if($item_is_a_folder) {
				//$size = fs::find_filesize($full_path);
				$driveless_path = fs::driveless($full_path);
				$size = fs::foldersize($full_path);
				$timestamp = filemtime($full_path);
				fs::print_folder_row($full_path, $this->path, $driveless_path, $size, $timestamp, $entry);
				$number_of_folders++;
				$times_accessed_sum += fs::file_times_accessed($driveless_path);
			} elseif($item_is_a_file) {
				$size = fs::find_filesize($full_path);
				$timestamp = filemtime($full_path);
				fs::print_file_row($full_path, $size, $timestamp, $entry);
				$number_of_files++;
				$size_sum += $size;
				$times_accessed_sum += fs::file_times_accessed($full_path);
			} else {
				fs::fatal_error('could not determine whether ' . $full_path  . ' is a file or folder.');
			}*/
		}
	}
	print('</tbody>
<tfoot>
<tr>
<th scope="row" style="font-weight: normal;">');
	if($number_of_folders === 1) {
		if($number_of_files === 1) {
			print('1&nbsp;folder and 1&nbsp;file');
		} elseif($number_of_files > 0) {
			print('1&nbsp;folder and ' . $number_of_files . '&nbsp;files');
		} else {
			print('1&nbsp;folder');
		}
	} elseif($number_of_folders > 0) {
		if($number_of_files === 1) {
			print($number_of_folders . '&nbsp;folders and 1&nbsp;file');
		} elseif($number_of_files > 0) {
			print($number_of_folders . '&nbsp;folders and ' . $number_of_files . '&nbsp;files');
		} else {
			print($number_of_folders . '&nbsp;folders');
		}
	} else {
		if($number_of_files === 1) {
			print('1&nbsp;file');
		} elseif($number_of_files > 0) {
			print($number_of_files . '&nbsp;files');
		}
	}
	print('</th>
<td style="text-align: left; font-family: monospace;"></td>
');
	$padding_right = 0;
	//print('$this->foldersizes_to_be_calculated: ');var_dump($this->foldersizes_to_be_calculated);
	if($this->foldersizes_to_be_calculated > 0) {
		$size_sum .= '+';
	} else {
		$padding_right = 8;
	}
	print('<td style="text-align: right; font-family: monospace; background-position: right top; background-image: url(\'' . $this->size_background_url . '\');" title="sum of sizes in this folder"><span style="display: none;">foldersizes_to_be_calculated=' . $this->foldersizes_to_be_calculated . '</span><span style="padding-right: ' . $padding_right . 'px;">' . $size_sum . '</span>' . fs::size_ideogram($size_sum) . '</td>
<td style="text-align: left;"></td>
<td style="text-align: right;" title="times accessed sum">' . $times_accessed_sum . '</td>
<!--td>metadata</td-->
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
<td style="text-align: right;"></td>
<td style="text-align: right;"><details><summary>new folder</summary>
<form action="new_folder.php" method="get">
<input type="hidden" name="path" value="' . fs::query_encode(substr($full_path, 0, fs::strpos_last($full_path, DS))) . '" />
<input type="hidden" name="fixed_drives" value="' . $this->fixed_drives_string . '" />
<input type="hidden" name="file_operations_string" value="' . $this->file_operations_string . '" />
<input type="text" name="new_folder_name" />
<input type="submit" value="make folder" />
</form>
</details></td>
</tr>
</tfoot>
</table>');
	//fs::dump_total_time_taken();
}

function print_folder_row($full_path, $path, $driveless_path, $size, $timestamp, $entry) {
	$folder = substr($full_path, fs::strpos_last($full_path, DS) + 1);
	print('<tr>
<th id="' . fs::anchor_encode($folder) . '" scope="row" style="font-weight: normal; text-align: left; white-space: nowrap;"><a href="do.php?action=navigate_files&path=' . fs::query_encode($driveless_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '">' . $entry . '</a></th>
');
	if($timestamp === '?') {
		print('<td style="text-align: left; font-family: monospace;">' . $timestamp . '</td>
');
	} else {
		/*print('<td class="date_modified" style="text-align: left; font-family: monospace;"><span style="display: none;">' . $timestamp . 'T</span>' . date('Y-m-d', $timestamp) . '&nbsp;' . date('h:i', $timestamp) . '&nbsp;' . date('A', $timestamp) . '</td>
');*/
		print('<td class="date_modified" style="text-align: left; font-family: monospace;"><span style="display: none;">' . $timestamp . 'T</span>' . date('Y-m-d', $timestamp) . '&nbsp;' . date('H:i', $timestamp) . '</td>
');
	}
	$padding_right = 0;
	if(strpos($size, '+') === false) {
		$padding_right = 8;
	}
	print('<td class="foldersize" style="text-align: right; font-family: monospace; background-position: right top; background-image: url(\'' . $this->size_background_url . '\');"><span style="padding-right: ' . $padding_right . 'px;">' . $size . '</span>' . fs::size_ideogram($size) . '</td>
<td style="text-align: left;"></td>
<td style="text-align: right;">' . fs::file_times_accessed($driveless_path) . '</td>
<!--td>metadata</td-->
<td style="text-align: right;">' . fs::get_name_score_string($full_path) . '</td>
<td style="text-align: right;">' . fs::get_contents_score_string($full_path) . '</td>
<td style="text-align: right;">' . fs::get_compression_score_string($full_path) . '</td>
<td style="white-space: nowrap; padding: 0;">' . fs::icon_code('move', 'do.php?action=navigate_files&path=' . fs::query_encode($path) . '&fixed_drives=' . $this->fixed_drives_string . '&move=' . fs::query_encode($full_path)) . fs::icon_code('copy', 'do.php?action=navigate_files&path=' . fs::query_encode($path) . '&fixed_drives=' . $this->fixed_drives_string . '&copy=' . fs::query_encode($full_path)) . fs::icon_code('delete', 'do.php?action=delete_item&refresh_action=' . $this->action . '&path=' . $full_path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	if(!fs::is_an_important_directory($full_path)) {
		print(fs::icon_code('backup', 'do.php?action=add_to_backup_list&refresh_action=' . $this->action . '&path=' . $full_path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	}
	print('<details><summary>rename</summary>
<form action="rename.php" method="get">
<input type="hidden" name="path" value="' . fs::query_encode(substr($full_path, 0, fs::strpos_last($full_path, DS))) . '" />
<input type="hidden" name="fixed_drives" value="' . $this->fixed_drives_string . '" />
<input type="hidden" name="file_operations_string" value="' . $this->file_operations_string . '" />
<input type="hidden" name="existing_name" value="' . $entry . '" />
<input type="text" name="new_name" value="' . $entry . '" />
<input type="submit" value="rename" />
</form>
</details></td>
</tr>');
}

function print_file_row($full_path, $size, $timestamp, $entry) {
	$file = substr($full_path, fs::strpos_last($full_path, DS) + 1);
	print('<tr>
<th id="' . fs::anchor_encode($file) . '" scope="row" style="font-weight: normal; text-align: left; white-space: nowrap;"><a href="do.php?action=open_file&path=' . fs::query_encode($full_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '">' . $entry . '</a></th>
');
	if($timestamp === '?') {
		print('<td style="text-align: left; font-family: monospace;">' . $timestamp . '</td>
');
	} else {
		/*print('<td class="date_modified" style="text-align: left; font-family: monospace;"><span style="display: none;">' . $timestamp . 'T</span>' . date('Y-m-d', $timestamp) . '&nbsp;' . date('h:i', $timestamp) . '&nbsp;' . date('A', $timestamp) . '</td>
');*/
		print('<td class="date_modified" style="text-align: left; font-family: monospace;"><span style="display: none;">' . $timestamp . 'T</span>' . date('Y-m-d', $timestamp) . '&nbsp;' . date('H:i', $timestamp) . '</td>
');
	}
	//$padding_right = 0;
	//if(strpos($size, '+') === false) {
	//	$padding_right = 8;
	//}
	print('
<td style="text-align: right; font-family: monospace; background-position: right top; background-image: url(\'' . $this->size_background_url . '\');"><span style="padding-right: 8px;">' . $size . '</span>' . fs::size_ideogram($size) . '</td>
<td style="text-align: left;">' . fs::file_extension($full_path) . '</td>
<td style="text-align: right;">' . fs::file_times_accessed($full_path) . '</td>
<!--td>metadata</td-->
<td style="text-align: right;">' . fs::get_name_score_string($full_path) . '</td>
<td style="text-align: right;">' . fs::get_contents_score_string($full_path) . '</td>
<td style="text-align: right;">' . fs::get_compression_score_string($full_path) . '</td>
<td style="white-space: nowrap; padding: 0;">' . fs::icon_code('move', 'do.php?action=open_file&path=' . fs::query_encode($full_path) . '&fixed_drives=' . $this->fixed_drives_string . '&move=' . fs::query_encode($full_path)) . fs::icon_code('copy', 'do.php?action=open_file&path=' . fs::query_encode($full_path) . '&fixed_drives=' . $this->fixed_drives_string . '&copy=' . fs::query_encode($full_path)) . fs::icon_code('delete', 'do.php?action=delete_item&refresh_action=' . $this->action . '&path=' . $full_path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	if(!fs::is_an_important_directory($full_path)) {
		print(fs::icon_code('backup', 'do.php?action=add_to_backup_list&refresh_action=' . $this->action . '&path=' . $full_path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	}
	print('<details><summary>rename</summary>
<form action="rename.php" method="get">
<input type="hidden" name="path" value="' . fs::query_encode(substr($full_path, 0, fs::strpos_last($full_path, DS))) . '" />
<input type="hidden" name="fixed_drives" value="' . $this->fixed_drives_string . '" />
<input type="hidden" name="file_operations_string" value="' . $this->file_operations_string . '" />
<input type="hidden" name="existing_name" value="' . $entry . '" />
<input type="text" name="new_name" value="' . $entry . '" />
<input type="submit" value="rename" />
</form>
</details></td>
</tr>');
}

function foldersize($drive_path) {
	// need to make this much more efficient... we're just getting an attribute value
	//$path = fs::normalize_slashes($this->path);
	$path = fs::driveless($drive_path);
	if(!isset($this->path_query)) {
		$this->path_folders = explode(DS, $this->path);
		$this->path_query = 'folders';
		foreach($this->path_folders as $path_folder) {
			$this->last_path_query = '_folder@name=' . $this->folders->enc($path_folder);
			$this->path_query .= $this->last_path_query;
		}
		$this->last_path_query = substr($this->last_path_query, 1);
		//$this->foldersize_counter = 0; // debug
	}
	//if($this->foldersize_counter > 2) {
	//	$foldersize = '0+';
	//} else {
	// 17 seconds; uses unoptimized LOM
	/*$query = 'folders';
	foreach($path_folders as $path_folder) {
		$query .= '_folder@name=' . $this->folders->enc($path_folder);
	}
	$folder = $this->folders->get_tagged($query);
	//print('$query, $folder: ');var_dump($query, $folder);
	foreach($this->fixed_drives_array as $fixed_drive_index => $fixed_drive) {
		//$drive_path = $fixed_drive . ':' . DS . $path;
		if(is_dir($drive_path)) {
			if(filemtime($drive_path) == $this->folders->get_attribute('modified', $folder)) {
				$foldersize = $this->folders->get_attribute('size', $folder);
				break;
			} else {
				$foldersize = '0+';
			}
		}
	}*/
	// 0.005 seconds; leave it to AJAP, since at least that is multithreaded. but the problem is data being inputted to the table isn't caught for sorting purposes
	//$foldersize = '0+';
	// 0.010 seconds; placeholder, mainly to show that init_LOM takes another 0.005 seconds
	//$foldersize = 'test string';
	// 0.010 seconds; placeholder, just to show sortable
	/*$debug_foldersizes_array = array(143396105, 3073152, 138951458, 3705144, 4365168, 15240270);
	$foldersize = $debug_foldersizes_array[$this->debug_foldersizes_counter];
	$this->debug_foldersizes_counter++;*/
	// 0.011 seconds; uses preg but is incorrect since it doesn't handle nesting
	/*$query = '<folders[^<>]*?>';
	foreach($path_folders as $path_folder_index => $path_folder) {
		if($path_folder_index === sizeof($path_folders) - 1) {
			$query .= '.*?(<folder[^<>]*? name="' . $path_folder . '"[^<>]*?>)';
		} else {
			$query .= '.*?<folder[^<>]*? name="' . $path_folder . '"[^<>]*?>';
		}
	}
	preg_match('/' . $query . '/is', $this->folders->code, $matches);
	$last_folder = $matches[1];
	preg_match('/ size="([^"]+)"/is', $last_folder, $size_matches);
	$foldersize = $size_matches[1];*/
	//print('$query, $this->folders->code, $matches, $size_matches: ');var_dump($query, $this->folders->code, $matches, $size_matches);
	// 1.080 seconds; uses preg correctly but may be slower
	//print('$this->folders->code: ');var_dump($this->folders->code);
	/*$query = '<folders[^<>]*?>';
	preg_match('/' . $query . '/is', $this->folders->code, $matches, PREG_OFFSET_CAPTURE);
	$tag_string = $this->folders->get_tag_string($this->folders->code, 'folders', $matches[0][1]);
	//print('$query, $tag_string, $matches: ');var_dump($query, $tag_string, $matches);
	foreach($path_folders as $path_folder_index => $path_folder) {
		$query = '<folder[^<>]*? name="' . $path_folder . '"[^<>]*?>';
		preg_match('/' . $query . '/is', $tag_string, $matches, PREG_OFFSET_CAPTURE, 1);
		$tag_string = $this->folders->get_tag_string($tag_string, 'folder', $matches[0][1]);
		//print('$query, $tag_string, $matches: ');var_dump($query, $tag_string, $matches);
	}
	preg_match('/ size="([^"]+)"/is', $tag_string, $size_matches);
	$foldersize = $size_matches[1];*/
	//print('$tag_string, $size_matches: ');var_dump($tag_string, $size_matches);
	// ? seconds; LOM with optimized get_tag_string?
	// 0.068 seconds; parser (does no tagname matching, does not handle nesting)
	/*$offset = strlen('<folders>');
	$path_folder_index = 0;
	$parsing_tag = false;
	while($offset < strlen($this->folders->code)) {
		if($parsing_tag) {
			if($this->folders->code[$offset] === '>') {
				if(strpos($code_piece, ' name="' . $path_folders[$path_folder_index] . '"') !== false) {
					$path_folder_index++;
					if($path_folder_index === sizeof($path_folders)) {
						preg_match('/ size="([^"]+)"/is', $code_piece, $size_matches);
						$foldersize = $size_matches[1];
						break;
					}
				}
				$parsing_tag = false;
				$code_piece = '';
				$offset++;
				continue;
			}
		} else {
			if($this->folders->code[$offset] === '<') {
				$parsing_tag = true;
			}
		}
		$code_piece .= $this->folders->code[$offset];
		$offset++;
	}*/
	// 0.013 seconds; preg-parser hybrid (get angle brackets) (does no tagname matching, does not handle nesting)
	/*preg_match_all('/[<>]/', $this->folders->code, $matches, PREG_OFFSET_CAPTURE);
	//print('$matches: ');var_dump($matches);
	$index = 2; // skip <folders>
	$path_folder_index = 0;
	while($index < sizeof($matches[0])) {
		if($this->folders->code[$matches[0][$index][1] + 1] === '/') {
			
		} else { // opening tag
			$opening_angle_bracket_position = $matches[0][$index][1];
			$closing_angle_bracket_position = $matches[0][$index + 1][1];
			$opening_tag_string = substr($this->folders->code, $opening_angle_bracket_position, $closing_angle_bracket_position - $opening_angle_bracket_position);
			//print('$opening_tag_string: ');var_dump($opening_tag_string);
			if(strpos($opening_tag_string, ' name="' . $path_folders[$path_folder_index] . '"') !== false) {
				$path_folder_index++;
				if($path_folder_index === sizeof($path_folders)) {
					preg_match('/ size="([^"]+)"/is', $opening_tag_string, $size_matches);
					$foldersize = $size_matches[1];
					break;
				}
			}
		}
		$index += 2;
	}*/
	// 0.011 seconds; preg-parser hybrid (get tags) (does no tagname matching, does not handle nesting)
	/*preg_match_all('/<[^<>]+>/', $this->folders->code, $matches, PREG_OFFSET_CAPTURE);
	//print('$matches: ');var_dump($matches);
	$index = 1; // skip <folders>
	$path_folder_index = 0;
	while($index < sizeof($matches[0])) {
		if($this->folders->code[$matches[0][$index][1] + 1] === '/') {
			
		} else { // opening tag
			$opening_tag_string = $matches[0][$index][0];
			//print('$opening_tag_string: ');var_dump($opening_tag_string);
			if(strpos($opening_tag_string, ' name="' . $path_folders[$path_folder_index] . '"') !== false) {
				$path_folder_index++;
				if($path_folder_index === sizeof($path_folders)) {
					preg_match('/ size="([^"]+)"/is', $opening_tag_string, $size_matches);
					$foldersize = $size_matches[1];
					break;
				}
			}
		}
		$index++;
	}*/
	// ? seconds; preg-parser hybrid (get tags and text in between)
	// ? seconds; test whether using associative array from generate_LOM or getting attributes from the string is more efficient
	// ? seconds; LOM using multi-step parsing (tags then further information like attributes and tagname)
	// ? seconds; LOM with discretional parsing (don't go out of depth)
	// 0.033 seconds; dynamically processed LOM that is only expanded as much as needed: brilliant! turning the disadvantage of a hierarchical filesystem into an advantage? in a way
	/*$code = $this->folders->code;
	if(strpos($code, '<folders>') !== false) {
		$path_folder_index = 0;
		$expanded_LOM = $this->folders->expand($code, strlen('<folders>'));
		//print('$expanded_LOM: ');var_dump($expanded_LOM);
		$code = $expanded_LOM[1][0];
		$result = preg_match('/<folder[^<>]*? name="' . $path_folders[$path_folder_index] . '"[^<>]*?>/', $code, $matches, PREG_OFFSET_CAPTURE);
		$offset_to_add = $expanded_LOM[0][1] + strlen($expanded_LOM[0][0]);
		$path_folder_index++;
		//print('$result, $matches: ');var_dump($result, $matches);
		$offset = $matches[0][1] + strlen($matches[0][0]);
		while($result) {
			$expanded_LOM = $this->folders->expand($code, $offset, $offset_to_add);
			//print('$expanded_LOM: ');var_dump($expanded_LOM);
			$code = $expanded_LOM[1][0];
			$result = preg_match('/<folder[^<>]*? name="' . $path_folders[$path_folder_index] . '"[^<>]*?>/', $code, $matches, PREG_OFFSET_CAPTURE);
			$path_folder_index++;
			if($path_folder_index === sizeof($path_folders)) {
				$selected_opening_tag_string = $matches[0][0];
				//print('$selected_opening_tag_string when getting foldersize: ');var_dump($selected_opening_tag_string);
				preg_match('/ size="([^"]+)"/is', $selected_opening_tag_string, $size_matches);
				$foldersize = $size_matches[1];
				break;
			}
			$offset_to_add = $expanded_LOM[0][1] + strlen($expanded_LOM[0][0]);
			$offset = $matches[0][1] + strlen($matches[0][0]);
			//print('$offset, $path_folders[$path_folder_index], $result, $matches: ');var_dump($offset, $path_folders[$path_folder_index], $result, $matches);
		}
	}*/
	// 0.040 seconds; uses optimized LOM
	/*$path_folders = explode(DS, $path);
	$query = 'folders';
	foreach($path_folders as $path_folder) {
		$query .= '_folder@name=' . $this->folders->enc($path_folder);
	}
	$folder = $this->folders->get_tagged($query);
	//print('$query, $folder: ');var_dump($query, $folder);
	if(filemtime($drive_path) == $this->folders->get_attribute('modified', $folder)) {
		$foldersize = $this->folders->get_attribute('size', $folder);
	} else {
		$foldersize = '0+';
	}*/
	// 0.057 seconds; uses context
	$nested_path = substr($path, strlen($this->path));
	if($nested_path[0] === DS) {
		$nested_path = substr($nested_path, 1);
	}
	$nested_path_folders = explode(DS, $nested_path);
	$query = '';
	// should we force proper nesting (by including the parent in the query)? we have to, but the potential problem is when the nested path is completely intact deeper in the folder structure
	foreach($nested_path_folders as $nested_path_folder) {
		$query .= '_folder@name=' . $this->folders->enc($nested_path_folder);
	}
	//$query = substr($query, 1);
	//print('$this->path, $this->path_query, $this->last_path_query, $path, $nested_path, $query: ');var_dump($this->path, $this->path_query, $this->last_path_query, $path, $nested_path, $query);
	//$var1 = $this->folders->get($this->path_query); // experiment
	//$folder = $this->folders->get_tagged($query, false, false);
	//$folder = $this->folders->get_tagged($this->last_path_query . '_' . $query, $this->path_query); // this was correct but slower
	$folder = $this->folders->get_tagged($query, $this->path_query); // this was incorrect but faster, now it's correct also
	//print('$query, $folder, $this->folders->context: ');var_dump($query, $folder, $this->folders->context);
	if(filemtime($drive_path) == $this->folders->get_attribute('modified', $folder)) {
		$foldersize = $this->folders->get_attribute('size', $folder);
	} else {
		$foldersize = '0+';
	}
	// ? seconds; others
	// could make LOM an extension of preg return type by making tag_array [2]
	// under what conditions would we collapse an area of the LOM that was expanded? does this concept of expanding/collapsing obsolesce context?
	//exit(0);
	//$this->foldersize_counter++; // debug
	//} // debug
	if($foldersize === '0+') {
		$this->foldersizes_to_be_calculated++;
		// set_foldersize?? leave it up to AJAP, I think
	}
	//print($this->path . ' has foldersize ' . $foldersize . ' bytes.<br>');
	return $foldersize;
}

function keywords_navigate() {
	fs::init_LOM();
	// wanting to do something like find all folders with folders in their paths named the keywords provided and sort by the closeness of these folders in their paths
	// round brackets () delimit a user-defined keyword
	//print('$_REQUEST: ');var_dump($_REQUEST);
	$keywords_string = fs::get_by_request('keywords');
	//print('$keywords_string: ');var_dump($keywords_string);
	$keywords_string = trim($keywords_string);
	print('<form action="do.php?action=keywords_navigate&fixed_drives=' . $this->fixed_drives_string . '&keywords=' . $keywords_string . '" method="post">
keywords: <input type="text" name="keywords" value="' . $keywords_string . '" size="100" /> angle brackets <> are used to define a keyword with spaces in it<br>
<input type="submit" value="keywords navigate" />
</form>');
	//$keywords_string = preg_replace('/\(([^\(\)\s]+)\s+([^\(\)\s]+)\)/is', '$1<joiner>$2', $keywords_string);
	//$keywords = explode(' ', $keywords_string);
	//foreach($keywords as $keyword_index => $keyword) {
	//	$keywords[$keyword_index] = str_replace('<joiner>', ' ', $keyword);
	//}
	// use a little parser instead of preg
	$keywords = array();
	$offset = 0;
	$bracket_depth = 0;
	$keyword = '';
	while($offset < strlen($keywords_string)) {
		if($keywords_string[$offset] === ' ') {
			if($bracket_depth === 0) {
				$keywords[] = $keyword;
				$keyword = '';
			} else {
				$keyword .= $keywords_string[$offset];
			}
		} elseif($keywords_string[$offset] === '<') {
			$bracket_depth++;
		} elseif($keywords_string[$offset] === '>') {
			$bracket_depth--;
			if($bracket_depth === 0) {
				$keywords[] = $keyword;
				$keyword = '';
			} else {
				$keyword .= $keywords_string[$offset];
			}
		} else {
			$keyword .= $keywords_string[$offset];
		}
		$offset++;
	}
	if(strlen($keyword) > 0) {
		$keywords[] = $keyword;
	}
	//print('$keywords: ');var_dump($keywords);
	// path => distance from root, keywords matched, depth
	// keywords matched > distance from root > depth
	// hmm, compare: ..x..............x..x.. to ........x..x..x........
	// so instead, we have: path => distance from root, keywords matched, depth
	// keywords matched > depth - distance from root > distance from root
	// interesting to consider that keywords navigating on a hierarchical filesystem is more complicated in the scoring required and that were files in a keywords filesystem, the scoring would be as simple as the fraction of relevant keywords out of all keywords on a given file.
	// therefore if we wanted to make ends meet, aside from this keywords navigation we'd need a files.xml containing files with their keyword associations
	// probably a fractal filesystem is the ultimate solution
	//print('$this->folders->LOM: ');$this->folders->var_dump_full($this->folders->LOM);
	/*
	0 => node type: text or tag; 0 = text, 1 = tag
	1 => text string if node type is text, tag array if node type is tag
		0 => tag name
		1 => attributes array; an associative array
		2 => tag type; 0 = opening, 1 = closing, 2 = self-closing, 3 = DOCTYPE, 4 = CDATA, 5 = comment, 6 = programming instruction, 7 = ASP
		3 => block tag; true or false
	2 => offset
	*/
	//$LOM = array_slice($this->folders->LOM, 2, sizeof($this->folders->LOM) - 3); // skipping the opening <folders> tag is what's important
	//print('$LOM: ');$this->folders->var_dump_full($LOM);
	$paths_data = array();
	$keyword_indices = array();
	$distance_from_root = 0;
	$depth = 0;
	$maximum_depth = 0;
	$path_array = array();
	//foreach($LOM as $index => $value) {
	//print('$this->folders->depths: ');$this->folders->var_dump_full($this->folders->depths);
	$skipped_first = false;
	foreach($this->folders->depths as $depth_offset => $depth) {
		if(!$skipped_first) {// skipping the opening <folders> tag
			$skipped_first = true;
			continue;
		}
		//if($value[0] === 0) { // text node
		//	
		//} else { // tag node
			//if($value[1][2] === 0) { // opening tag
				
			//} elseif($value[1][2] === 1) { // closing tag
		if($this->folders->code[$depth_offset + 1] === '/') { // closing tag
			//break;
			foreach($keyword_indices as $keyword_string => $keyword_depth) {
				if($keyword_depth === $depth) {
					unset($keyword_indices[$keyword_string]);
				}
			}
			//print('$path_array before unset: ');var_dump($path_array);
			unset($path_array[sizeof($path_array) - 1]);
			$path_array = array_values($path_array);
			//print('$path_array after unset: ');var_dump($path_array);
			//$depth--;
		} else { // opening tag
			//$depth++;
			if($depth > $maximum_depth) {
				$maximum_depth = $depth;
			}
			//$attributes_array = $value[1][1];
			//$folder_name = $attributes_array['name'];
			$opening_tag_string = substr($this->folders->code, $depth_offset, strpos($this->folders->code, '>', $depth_offset) - $depth_offset);
			//print('$opening_tag_string: ');var_dump($opening_tag_string);
			preg_match('/ name="([^"]+)"/', $opening_tag_string, $matches);
			$folder_name = $matches[1];
			//print('$folder_name: ');var_dump($folder_name);
			$path_array[] = $folder_name;
			foreach($keywords as $keyword) {
				//if($folder_name === $keyword && !isset($keyword_indices[$keyword])) {
				if(strtolower($folder_name) === strtolower($keyword) && !isset($keyword_indices[$keyword])) { // case-insensitive comparison
					if(sizeof($keyword_indices) === 0) {
						$distance_from_root = $depth;
					}
					$keyword_indices[$keyword] = $depth;
					$paths_data[implode(DS, $path_array)] = array($distance_from_root, sizeof($keyword_indices), $depth);
					break;
				}
			}
		}
		//}
		//if($depth_offset === 1024) { // debug
		//	break;
		//}
	}
	//print('$paths_data: ');var_dump($paths_data);
	/*if(isset($array_entries[$path])) {
		$array_entries[$path][] = $drive_path;
	} else {
		$array_entries[$path] = array($drive_path);
	}
	print_entries_table($array_entries, $caption = false)*/
	// this depth of looping might be quite inefficient. nvm
	/*$keywords_matched_counter = sizeof($keywords);
	while($keywords_matched_counter > 0) {
		foreach($paths_data as $path => $path_data) {
			
		}
		$keywords_matched_counter--;
	}*/
	if($maximum_depth > sizeof($keywords)) {
		$scale_power = $maximum_depth + 2;
	} else {
		$scale_power = sizeof($keywords) + 2;
	}
	$scored_paths = array();
	foreach($paths_data as $path => $path_data) {
		$distance_from_root = $path_data[0];
		$keywords_matched = $path_data[1];
		$depth = $path_data[2];
		$score = ($keywords_matched * pow($scale_power, 2)) + (($scale_power - $depth - $distance_from_root) * $scale_power) + ($scale_power - $distance_from_root);
		$scored_paths[$score][] = $path;
	}
	//print('$scored_paths: ');var_dump($scored_paths);
	ksort($scored_paths);
	$scored_paths = array_reverse($scored_paths);
	// alphabetical sorting?
	foreach($scored_paths as $score => $paths) {
		foreach($paths as $path) {
			$array_entries[$path] = array(fs::drived($path));
		}
	}
	//print('$array_entries: ');var_dump($array_entries);
	fs::print_entries_table($array_entries);
	print(
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
}

function driveless($path) {
	if($path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) {
		//print('$path, substr($path, 3) in driveless: ');var_dump($path, substr($path, 3));
		return substr($path, 3);
	}
	return $path;
}

function file_times_accessed($path) {
	//print('$path, $this->files_times_accessed[$path] in file_times_accessed: ');var_dump($path, $this->files_times_accessed[$path]);
	return $this->files_times_accessed[$path];
}

function save_files_times_accessed() {
	//print('$this->files_times_accessed in save_files_times_accessed: ');var_dump($this->files_times_accessed);
	$contents = '';
	if(sizeof($this->files_times_accessed) > 0) {
		foreach($this->files_times_accessed as $path => $number) {
			$contents .= $path . '	' . $number . '
';
		}
	}
	if(substr($contents, strlen($contents) - 1 - strlen('
')) === '
') {
		// trim the last newline
		$contents = substr($contents, 0, strlen($contents) - strlen('
'));
	}
	file_put_contents('files_times_accessed.txt', $contents);
}

function size_ideogram($size) {
	$size = str_replace('+', '', $size);
	return '<img src="size' . strlen($size) . '.png" />';
}

function get_name_score_string($path) {
	// comparing to similar filenames, how much extraneous stuff is in filename, proper naming convention for movies and such
	// also consider that this is probably where sequential filenames should be checked for; but how to report on this?
	// I guess aiming for an eventual recursive fix filename function based on name score
	//print('$path in get_name_score_string: ');var_dump($path);
	if(strpos($path, 'Video' . DS) !== false) {
		$last_folder = substr($path, fs::strpos_last($path, DS) + 1);
		$containing_folder = substr($path, 0, fs::strpos_last($path, DS));
		$containing_folder_last_folder = substr($containing_folder, fs::strpos_last($containing_folder, DS) + 1);
		//print('$containing_folder_last_folder in get_name_score_string: ');var_dump($containing_folder_last_folder);
		$score = 0;
		$score_description = 'video file with';
		preg_match_all('/\([0-9]{4}\)/is', $last_folder, $year_matches);
		if($containing_folder_last_folder === 'Telesensation') { // not very generalized...
			if(sizeof($year_matches[0]) === 1) {
				$score_description .= ' proper year indication and';
				$score++;
			} else {
				$score_description .= ' improper year indication and';
			}
		} else {
			$score++;
		}
		preg_match_all('/[\[\]\{\}+]/is', $last_folder, $extraneous_symbol_matches);
		if(sizeof($extraneous_symbol_matches[0]) === 0) {
			$score_description .= ' no extraneous symbols and';
			$score++;
		} else {
			$score_description .= ' extraneous symbols and';
		}
		$substring_problems_exist = false;
		$handle = opendir($containing_folder);
		while(($entry = readdir($handle)) !== false) {
			if($entry === '.' || $entry === '..' || $entry === $last_folder) {
				
			} else {
				if(strpos($entry, $last_folder) !== false) {
					$substring_problems_exist = true;
					$score_description .= ' a substring collision between ' . $entry . ' and ' . $last_folder;
					break;
				}
				if(strpos($last_folder, $entry) !== false) {
					$substring_problems_exist = true;
					$score_description .= ' a substring collision between ' . $last_folder . ' and ' . $entry;
					break;
				}
			}
		}
		closedir($handle);
		if(!$substring_problems_exist) {
			$score_description .= ' no substring problems';
			$score++;
		}
		return '<span style="font-weight: bold; color: #' . fs::red_to_black_spectrum($score, 3) . ';" title="name score of ' . $score . ' by ' . $score_description . '">' . $score . '</span>';
	}
	return '';
}

function get_contents_score_string($path) {
	// anticipating content-based filesystems. maybe also file format rating; does this overlap with compression score? some file formats are inherently better than others... when being tamper-resistant is thrown out as a consideration
	// mp4 sucks for video formats: need the whole file before being able to play it, as opposed to .mkv and others...
	// some sort of checksum for duplication and other purposes?
	// correction of text in formats with known text components
	return '1';
}

function get_compression_score_string($path) {
	// by characteristics of extension, by looking at internal file compression parameters
	$extension = substr(fs::file_extension($path), 1);
	if(isset($this->file_extension_properties[$extension])) {
		$score = 0;
		$score_description = '';
		foreach($this->file_extension_properties[$extension] as $index => $property) {
			if($property) {
				if($index === 0) { // compressed
					$score_description .= 'compressing';
				}
				if($index === 1) { // lossless
					$score_description = 'losslessly ' . $score_description;
				}
				$score++;
			}
		}
		if(strlen($score_description) === 0) {
			$score_description = 'non-compressing';
		}
		return '<span style="font-weight: bold; color: #' . fs::red_to_black_spectrum($score, 3) . ';" title="compression score of ' . $score . ' by ' . $score_description . ' file format">' . $score . '</span>';
	}
	return '';
}

function strpos_last($haystack, $needle) {
	//print('$haystack, $needle: ');var_dump($haystack, $needle);
	if(strlen($needle) === 0) {
		return false;
	}
	$len_haystack = strlen($haystack);
	$len_needle = strlen($needle);		
	$pos = strpos(strrev($haystack), strrev($needle));
	if($pos === false) {
		return false;
	}
	return $len_haystack - $pos - $len_needle;
}

function open_file() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	} else {
		fs::print_segmented_path($this->path, true);
		//file_put_contents('fs.bat', 'start "' . $this->path . '"');
		//function symlink($target, $link) {
        //if (! substr($link, -4, '.lnk'))
        /*$target = $this->path;
		$target = substr($target, 0, fs::strpos_last($target, DS));
        $link = $target . '/fs.lnk';
        $shell = new COM('WScript.Shell');
        $shortcut = $shell->createshortcut($link);
		$shortcut->targetpath = $target;
        $shortcut->save();*/
		/*$target = $this->path;
		$link = 'fs_symlink.lnk';
		symlink($target, $link);

		echo readlink($link);*/
		//var_dump($this->path);
		fs::create_lnk_file($this->path);
		/*$target = $this->path; // This is the file that already exists
		$target = 'C:\Windows\System32\notepad.exe'; // This is the file that already exists
		$link = 'newfile.ext'; // This the filename that you want to link it to

		link($target, $link);*/
		fs::init_fractal_zip();
		if(fs::file_extension($this->path) === $this->fractal_zip->fractal_zip_container_file_extension) {
			fs::open_fractal_zip_container();
		}
	}
	if(isset($this->files_times_accessed[$this->path])) {
		$this->files_times_accessed[$this->path]++;
	} else {
		$this->files_times_accessed[$this->path] = 1;
	}
	fs::save_files_times_accessed();
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('delete', 'do.php?action=delete_file&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('restore', 'do.php?action=restore_from_backup&restore_path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	fs::init_fractal_zip();
	if(fs::file_extension($this->path) === $this->fractal_zip->fractal_zip_container_file_extension) {
		print(
		fs::icon_code('extract', 'do.php?action=extract_all_from_fractal_zip_container&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
	} else {
		print('<br>');
	}
}

function delete_file() {
	// how does this work without a full path (including a drive letter)??
	if($this->path == '') {
		print('Path not properly specified.<br>');
	} else {
		unlink($this->path);
		print($this->path . ' deleted.<br>');
	}
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('restore', 'do.php?action=restore_from_backup&restore_path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
}

function delete_item() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	} else {
		fs::recursive_delete($this->path);
		//print($this->path . ' deleted.<br>');
	}
	$driveless_path = substr($this->path, strpos($this->path, DS) + 1);
	print('<meta http-equiv="refresh" content="0; url=do.php?action=' . fs::get_by_request('refresh_action') . '&path=' . fs::query_encode(substr($driveless_path, 0, fs::strpos_last($driveless_path, DS))) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '" />');
}

function add_to_backup_list() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	} else {
		$fs_contents = file_get_contents('fs.php');
		// tricksy little keying by using preg_replace instead of str_replace so that this line doesn't replace on itself
		$fs_contents = preg_replace('/\$this->imp[o]rtant_direct[o]ries = array\(/', '$this->' . 'important_directories =' . ' array(\'' . $this->path . '\',
', $fs_contents);
		file_put_contents('fs.php', $fs_contents);
	}
	$driveless_path = substr($this->path, strpos($this->path, DS) + 1);
	print('<meta http-equiv="refresh" content="0; url=do.php?action=' . fs::get_by_request('refresh_action') . '&path=' . fs::query_encode(substr($driveless_path, 0, fs::strpos_last($driveless_path, DS))) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '" />');
}

function extract_file() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	}
	if($this->file_to_extract == '') {
		print('file_to_extract not properly specified.<br>');
	}
	//$drive_path = fs::drive_path_from_path($this->path);
	fs::init_fractal_zip();
	$this->fractal_zip->extract_file_from_container($this->path, $this->file_to_extract);
	fs::create_lnk_file($this->file_to_extract);
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
}

function extract_all_from_fractal_zip_container() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	}
	//$drive_path = fs::drive_path_from_path($this->path);
	fs::init_fractal_zip();
	//$this->fractal_zip->open_container_allowing_individual_extraction($drive_path);
	$this->fractal_zip->extract_container($this->path);
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
}

function open_fractal_zip_container() {
	// list the files and allow each to be extracted
	if($this->path == '') {
		print('Path not properly specified.<br>');
	}
	//$drive_path = fs::drive_path_from_path($this->path);
	fs::init_fractal_zip();
	//$this->fractal_zip->open_container_allowing_individual_extraction($drive_path);
	$this->fractal_zip->open_container_allowing_individual_extraction($this->path);
	/*$up_level_path = fs::get_up_level_path($this->path);
	print('
	<a href="do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1)) . '">Up one level</a> 
	<a href="do.php">Back to menu</a> 
	<a href="do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '">Navigate Files</a> ');*/
}

function create_fractal_zip_container() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	}
	// tricky issue: since we are reading files over many drives, which drive should the created fractal_zip file end up on?
	$drive_path = fs::drive_path_from_path($this->path);
	fs::init_fractal_zip();
	$this->fractal_zip->zip_folder($drive_path);
	//$this->fractal_zip->open_container_allowing_individual_extraction($drive_path . $this->fractal_zip->fractal_zip_container_file_extension);
	//$this->fractal_zip->zip_folder($this->path);
	//$this->fractal_zip->open_container_allowing_individual_extraction($this->path . $this->fractal_zip->fractal_zip_container_file_extension);
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string));
}

function drive_path_from_path($path) {
	//print('$path, $this->fixed_drives_string: ');var_dump($path, $this->fixed_drives_string);
	if(strpos($path, ':' . DS) !== false) {
		return $path;
	}
	/*$found_path = false;
	$working_path = $path;
	while($found_path === false && strpos($working_path, DS) !== false) {
		foreach($this->fixed_drives_array as $fixed_drive) {
			if(is_dir($fixed_drive . ':' . DS . $working_path)) {
				if($found_path !== false) {
					fs::fatal_error('Which drive is implied when multiple drives have the needed folder structure is undetermined');
				}
				$found_path = $fixed_drive;
			}
		}
		$working_path = substr($working_path, 0, fs::strpos_last($working_path, DS));
		//print('$working_path: ');var_dump($working_path);
	}
	if($found_path === false) {
		fs::fatal_error('Which drive is implied when no drives have the needed folder structure is undetermined');
	}
	$drive_path = $found_path . ':' . DS . $path;*/
	$drive_path = $this->fixed_drives_array[0] . ':' . DS . $path;
	$drive_path_counter = 0;
	while($drive_path_counter < sizeof($this->fixed_drives_array) && !is_dir($drive_path)) {
		$drive_path = $this->fixed_drives_array[$drive_path_counter] . substr($drive_path, 1);
		$drive_path_counter++;
	}
	$modified = filemtime($drive_path);
	// check for the unlikely identical path on a different drive with newer date modified
	while($drive_path_counter < sizeof($this->fixed_drives_array)) {
		$potential_drive_path = $this->fixed_drives_array[$drive_path_counter] . substr($drive_path, 1);
		if(is_dir($potential_drive_path) && filemtime($potential_drive_path) > $modified) {
			$modified = filemtime($potential_drive_path);
			$drive_path = $potential_drive_path;
		}
		$drive_path_counter++;
	}
	return $drive_path;
}

function drived($path) { // alias
	return fs::drive_path_from_path($path);
}

function drive_path($path) { // alias
	return fs::drive_path_from_path($path);
}

function browse() {
	if($this->path == '') {
		print('Path not properly specified.<br>');
	} else {
		$contents = file_get_contents($this->path);
		//var_dump($this->path, $this->last_path, $contents);
		if(strlen($contents) == 0) {
			$this->path = $this->path . $this->last_path;
			$contents = file_get_contents($this->path);
		}
		if(substr_count($this->path, DS) < 3) {
			$site_root = $this->path;
		} else {
			//$site_root = substr($this->path, 0, fs::strpos_nth($this->path, DS, 3) + 1);
			$site_root = substr($this->path, 0, fs::strpos_nth($this->path, DS, 3)); // omit the last slash
		}
		//print('$site_root: ');var_dump($site_root);exit(0);
		preg_match_all('/<(link|script|img)([^<>]*?) (href|src)=("|\')([^"\']*?)\4([^<>]*?)>/is', $contents, $matches);
		//var_dump($matches);exit(0);
		foreach($matches[0] as $index => $value) {
			$this->path_in_code = $matches[5][$index];
			if($this->path_in_code[0] === DS) {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . $site_root . $this->path_in_code . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			} elseif(strpos($this->path_in_code, '://') === false) {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . $this->path . $this->path_in_code . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			} else {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . $this->path_in_code . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			}
		}
		
		preg_match_all('/ style="([^"]*?)url\(\'([^\']*?)\'\)([^"]*?)"/is', $contents, $matches);
		//var_dump($matches);exit(0);
		foreach($matches[0] as $index => $value) {
			$this->path_in_code = $matches[2][$index];
			if($this->path_in_code[0] === DS) {
				$contents = str_replace($value, ' style="' . $matches[1][$index] . 'url(\'' . $site_root . $this->path_in_code . "')" . $matches[3][$index] . '"', $contents);
			} elseif(strpos($this->path_in_code, '://') === false) {
				$contents = str_replace($value, ' style="' . $matches[1][$index] . 'url(\'' . $this->path . $this->path_in_code . "')" . $matches[3][$index] . '"', $contents);
			} else {
				$contents = str_replace($value, ' style="' . $matches[1][$index] . 'url(\'' . $this->path_in_code . "')" . $matches[3][$index] . '"', $contents);
			}
		}
		
		preg_match_all('/<(a|form)([^<>]*?) (href|action)=("|\')([^"\']*?)\4([^<>]*?)>/is', $contents, $matches);
		//var_dump($matches);exit(0);
		foreach($matches[0] as $index => $value) {
			$this->path_in_code = $matches[5][$index];
			if($this->path_in_code[0] === DS) {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . 'do.php?action=browse&path=' . $site_root . $this->path_in_code . '&last_path=' . $this->last_path . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			} elseif(strpos($this->path_in_code, '://') === false) {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . 'do.php?action=browse&path=' . $this->path . $this->path_in_code . '&last_path=' . $this->last_path . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			} else {
				$contents = str_replace($value, '<' . $matches[1][$index] . $matches[2][$index] . ' ' . $matches[3][$index] . '=' . $matches[4][$index] . 'do.php?action=browse&path=' . $this->path_in_code . '&last_path=' . $this->last_path . $matches[4][$index] . $matches[6][$index] . '>', $contents);
			}
		}
		
		/*$contents = preg_replace('/<link([^<>]*?) href="([^"]*?)"([^<>]*?)>/is', '<link$1 href="' . $this->path . '$2"$3>', $contents);
		$contents = preg_replace('/<script([^<>]*?) src="([^"]*?)"([^<>]*?)>/is', '<script$1 src="' . $this->path . '$2"$3>', $contents);
		$contents = preg_replace('/<form([^<>]*?) action="([^"]*?)"([^<>]*?)>/is', '<form$1 action="' . $this->path . '$2"$3>', $contents);
		$contents = preg_replace('/<a([^<>]*?) href="([^"]*?)"([^<>]*?)>/is', '<a$1 href="do.php?action=browse&path=' . $this->path . '&last_path=' . $this->last_path . '$2"$3>', $contents);
		$contents = preg_replace('/([^<]..|<[^s].|..[^p])an ([bcdfghlmnpqrstvxyz])/is', '$1a $2', $contents);*/
		
		$contents = str_replace('people', 'persons', $contents);
		$contents = str_replace('imo', 'in my opinion', $contents);
		$contents = str_replace('your a', 'you\'re a', $contents);
		$contents = str_replace('cum', 'ejaculating', $contents);
		print($contents);exit(0);
	}
	print('<a href="do.php?action=browse&path=' . fs::query_encode($this->path) . '">Up one level</a>');
}

function create_lnk_file($path) {
	//var_dump(chr(0x4C));exit(0);
	// 0x00 . 0x00 . 0x00 . 0x00 .
	/*
	$filename = substr($path, fs::strpos_last($up_level_path, DS) + 1);
	
	$file_length = chr(0xA0) . chr(0x86) . chr(0x00) . chr(0x00);
	$long_name = $filename;
	$short_name = strtoupper($long_name);
	$header = 
	chr(0x4C) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x01) . chr(0x04) . chr(0x02) . chr(0x00) .
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0xC0) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x46) .
	
	chr(0x3F) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x20) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0xC0) . chr(0x0E) . chr(0x82) . chr(0xD5) .
	chr(0xC1) . chr(0x20) . chr(0xBE) . chr(0x01) .
	chr(0x00) . chr(0x08) . chr(0xBF) . chr(0x46) .
	chr(0xD5) . chr(0x20) . chr(0xBE) . chr(0x01) .
	chr(0x00) . chr(0x47) . chr(0xAA) . chr(0xEC) .
	chr(0xEC) . chr(0x15) . chr(0xBE) . chr(0x01) .
	
	$file_length .
	
	chr(0x05) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x01) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x46) . chr(0x06) . chr(0x00) . chr(0x00) .
	
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00);
	
	$item_ID_list = 
	
	chr(0x2A) . chr(0x00);
	
	$first_item = 
	
	chr(0x28) . chr(0x00) .
	
	chr(0x32) . chr(0x00) .
	
	$file_length .
	
	chr(0x76) . chr(0x25) . chr(0x71) . chr(0x3E) .
	
	chr(0x20) . chr(0x00) .
	
	$long_name .
	
	chr(0x00) . 
	
	$short_name .
	
	chr(0x00);
	
	$last_item = 
	
	chr(0x00) . chr(0x00);
	
	$file_location_info = 
	
	chr(0x74) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x1C) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x03) . chr(0x00) . chr(0x00) . chr(0x00) .
	
	chr(0x1C) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x34) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x40) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x5F) . chr(0x00) . chr(0x00) . chr(0x00);
	
	$local_volume_table = 
	
	chr(0x18) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x03) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0xD0) . chr(0x07) . chr(0x33) . chr(0x3A) .
	chr(0x10) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x44) . chr(0x52) . chr(0x49) . chr(0x56) . chr(0x45) . chr(0x20) . chr(0x43) . chr(0x00) .
	chr(0x43) . chr(0x3A) . chr(0x5C) . chr(0x57) . chr(0x49) . chr(0x4E) . chr(0x44) . chr(0x4F) . chr(0x57) . chr(0x53) . chr(0x5C) . chr(0x00);

	$network_volume_table = 
	
	chr(0x1F) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x02) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x14) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) .
	chr(0x00) . chr(0x00) . chr(0x02) . chr(0x00) .
	chr(0x5C) . chr(0x5C) . chr(0x4A) . chr(0x45) . chr(0x53) . chr(0x53) . chr(0x45) . chr(0x5C) . chr(0x57) . chr(0x44) . chr(0x00) .
	chr(0x44) . chr(0x65) . chr(0x73) . chr(0x6B) . chr(0x74) . chr(0x6F) . chr(0x70) . chr(0x5C) . chr(0x62) . chr(0x65) . chr(0x73) . chr(0x74) . chr(0x5F) . chr(0x37) . chr(0x37) . chr(0x33) . chr(0x2E) . chr(0x6D) . chr(0x69) . chr(0x64) . chr(0x00);
	
	$description_string = 
	
	chr(0x06) . chr(0x00) .
	'fs_lnk';
	
	$relative_path_string = '.\\' . $long_name;
	$relative_path_string_length = chr(strlen($relative_path_string));
	$relative_path = 
	$relative_path_string_length . chr(0x00) .
	$relative_path_string;
	
	$working_directory_string = substr($path, 0, fs::strpos_last($up_level_path, DS));
	$working_directory_string_length = chr(strlen($working_directory_string));
	$working_directory = 
	$working_directory_string_length . chr(0x00) .
	$working_directory_string;
	
	$command_line_arguments = 
	
	chr(0x06) . chr(0x00) .
	chr(0x2F) . chr(0x63) . chr(0x6C) . chr(0x6F) . chr(0x73) . chr(0x65);
	
	$icon_file_string = 'C:\WINDOWS\Mplayer.exe';
	$icon_file_string_length = chr(strlen($icon_file_string));
	$icon_file = 
	$icon_file_string_length .
	$icon_file_string;
	
	$ending_stuff = 
	
	chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00);
	
	$lnk_content = 
	$header .
	$item_ID_list .
	$first_item .
	$last_item .
	$file_location_info .
	$local_volume_table .
	$description_string .
	$relative_path .
	$working_directory .
	$command_line_arguments .
	$icon_file .
	$ending_stuff;
	
	*/
	
	/*$target = $path;
	$target = substr($target, 0, fs::strpos_last($target, DS));
	$link = $target . '/fs.lnk';
	$shell = new COM('WScript.Shell');
	$shortcut = $shell->createshortcut($link);
	$shortcut->targetpath = $target;
	$shortcut->save();
	
	
	
	$WshShell = New-Object -comObject WScript.Shell
	$Shortcut = $WshShell.CreateShortcut("$Home\Desktop\ColorPix.lnk")
	$Shortcut.TargetPath = "C:\Program Files (x86)\ColorPix\ColorPix.exe"
	$Shortcut.Save()

	you can create a powershell script save as set-shortcut.ps1 in your $pwd

	param ( [string]$SourceExe, [string]$DestinationPath )

	$WshShell = New-Object -comObject WScript.Shell
	$Shortcut = $WshShell.CreateShortcut($DestinationPath)
	$Shortcut.TargetPath = $SourceExe
	$Shortcut.Save()

	and call it like this

	Set-ShortCut "C:\Program Files (x86)\ColorPix\ColorPix.exe" "$Home\Desktop\ColorPix.lnk"*/
	
	
	// see http://msdn.microsoft.com/en-us/library/xk6kst2k%28v=vs.84%29.aspx
	/*
	set WshShell = WScript.CreateObject("WScript.Shell")
	strDesktop = WshShell.SpecialFolders("Desktop")
	set oShellLink = WshShell.CreateShortcut(strDesktop & "\Shortcut Script.lnk")
	oShellLink.TargetPath = WScript.ScriptFullName
	oShellLink.WindowStyle = 1
	oShellLink.Hotkey = "CTRL+SHIFT+F"
	oShellLink.IconLocation = "notepad.exe, 0"
	oShellLink.Description = "Shortcut Script"
	oShellLink.WorkingDirectory = strDesktop
	oShellLink.Save
	*/
	
	$shell = new COM('WScript.Shell');
	$shortcut = $shell->createshortcut($this->desktop_shortcut_path);
	$shortcut->targetpath = $path;
	$working_directory = substr($path, 0, fs::strpos_last($path, DS));
	$shortcut->workingdirectory = $working_directory;
	//$shortcut->Hotkey = 'CTRL+SHIFT+F';
	$shortcut->save();
	print($this->desktop_shortcut_path . ' updated.<br>');
}

function redistribute_files() {
	$fso = new COM('Scripting.FileSystemObject'); 
	$D = $fso->Drives; 
	$type = array("Unknown", "Removable", "Fixed", "Network", "CD-ROM", "RAM Disk");
	$array_drives_low_on_space = array();
	$array_drives_with_extra_space = array();
	foreach($D as $d){ 
		$dO = $fso->GetDrive($d);
		/*$s = ''; 
		if($dO->DriveType == 3) { 
			$n = $dO->Sharename; 
		} elseif($dO->IsReady) { 
			$n = $dO->VolumeName; 
		} else { 
			$n = "[Drive not ready]"; 
		}*/
		if($dO->DriveType == 2) {
			if($dO->FreeSpace / $dO->TotalSize < $this->space_threshold) {
				//$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize) . ' <span style="color: red;">This disk is low on space.</span>'; 
				$space_that_needs_to_be_freed = $dO->TotalSize * $this->space_threshold - $dO->FreeSpace;
				$array_drives_low_on_space[$dO->DriveLetter] = $space_that_needs_to_be_freed;
			} else {
				//$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize);
				$array_drives_with_extra_space[$dO->DriveLetter] = 0;
			}
		} elseif($dO->DriveType == 4 && $dO->IsReady) {
			//$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize);
			$array_drives_with_extra_space[$dO->DriveLetter] = 0;
		}
		//echo "Drive " . $dO->DriveLetter . ": - " . $type[$dO->DriveType] . " - " . $n . $s . "<br>"; 
	}
	//print('$array_drives_low_on_space: ');var_dump($array_drives_low_on_space);
	//print('$array_drives_with_extra_space: ');var_dump($array_drives_with_extra_space);exit(0);
	$array_moves = array();
	foreach($array_drives_low_on_space as $drive => $space_that_needs_to_be_freed) {
		if($space_that_needs_to_be_freed > 0) {
			print('Looking for folders that could be moved on ' . $drive . ':<br>');
			$dir = opendir($drive . ':');
			$found_solution = false;
			while(false !== ($entry = readdir($dir))) {
				if(($entry != '.') && ($entry != '..') && ($entry != 'Backup')) {
					$path = $drive . ':' . DS . $entry;
					//var_dump($entry);
					//var_dump($drive . ':/' . $entry);
					if(is_dir($path)) {
						$fO = $fso->GetFolder($path);
						if($fO->size > $space_that_needs_to_be_freed) { // then we at least have to find another drive to accomodate this content
							//print('Moving ' . $entry . ' would fix the low space problem on drive ' . $drive . ':.<br>');
							foreach($array_drives_with_extra_space as $healthy_drive => $zero) {
								$hdO = $fso->GetDrive($healthy_drive);
								if(($hdO->FreeSpace - $fO->size) / $hdO->TotalSize > $this->space_threshold) {
									//print('Moving ' . $path . ' to ' . $healthy_drive . ': would fix the low space problem on drive ' . $drive . ':.<br>');
									$moving_string = 'Moving ';
									$potential_moves_array = array();
									$path_dir = opendir($path);
									while(false !== ($entry = readdir($path_dir))) {
										if(($entry != '.') && ($entry != '..')) {
											$full_path = $path . DS . $entry;
											//var_dump($full_path);
											if(is_dir($full_path)) {
												$fO2 = $fso->GetFolder($full_path);
												$space_that_needs_to_be_freed -= $fO2->size;
												$moving_string .= $full_path . ' and<br>';
												$potential_moves_array[] = array($full_path, $drive, $healthy_drive);
												if($space_that_needs_to_be_freed <= 0) {
													$moving_string .= ' to ' . $healthy_drive . ': will fix the low space problem on drive ' . $drive . ':.<br>';
													foreach($potential_moves_array as $potential_move_array) {
														$array_moves[] = $potential_move_array;
													}
													print($moving_string);
													$found_solution = true;
													break 3;
												}
											}
										}
									}
									closedir($path_dir);
								}
							}
						}
					}
				}
			}
			if(!$found_solution) { // we might have to do complex replacements...
				print('Either there is not enough space on all drives to accomodate moving files from ' . $drive . ' to fix its being low on space problem or this program was not smart enough to find the solution.');exit(0);
			}
			closedir($dir);
		}
	}
	foreach($array_moves as $index => $move_array) {
		
		//var_dump($move_array);
		$src = $move_array[0];
		$dest = $move_array[2] . substr($src, 1);
		fs::recursive_move($src, $dest);
		rmdir($src);
		//exit(0);
		
		// be careful with this (copying and deleting large numbers of files)
		// also stuff like japanese characters and read-only files are still not handled
	}
	print('Files successfully redistributed.<br>');
	print(fs::icon_code('menu', 'do.php'));
}

function analyze_drives() {
	$fso = new COM('Scripting.FileSystemObject'); 
	$D = $fso->Drives; 
	$type = array("Unknown", "Removable", "Fixed", "Network", "CD-ROM", "RAM Disk"); 
	foreach($D as $d){ 
		$dO = $fso->GetDrive($d); 
		$s = ''; 
		if($dO->DriveType == 3) { 
			$n = $dO->Sharename; 
		} elseif($dO->IsReady) { 
			$n = $dO->VolumeName; 
		} else { 
			$n = "[Drive not ready]"; 
		}
		if($dO->DriveType == 2) {
			if($dO->FreeSpace / $dO->TotalSize < $this->space_threshold) {
				$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize) . ' <span style="color: red;">This disk is low on space.</span>'; 
			} else {
				$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize);
			}
		} elseif($dO->DriveType == 4 && $dO->IsReady) {
			$s = " - " . fs::file_size($dO->FreeSpace) . " free of: " . fs::file_size($dO->TotalSize);
		}
		echo "Drive " . $dO->DriveLetter . ": - " . $type[$dO->DriveType] . " - " . $n . $s . "<br>"; 
	}
	/*$dir = 'S:\Video\Telesensation';
	$handle = opendir($dir);
	while(false !== ($entry = readdir($handle))) {
		if($entry === '.' || $entry === '.') {
			
		} else {
			$full_path = $dir . '\\' . $entry;
			var_dump($full_path);print('<br>');
			if(filesize($full_path) === 802867959) {
				print('Found one!');exit(0);
			}
		}
	}
	closedir($handle);
	fs::recursive_file_list_to_array($dir);
	//var_dump($this->files);
	foreach($this->files as $full_path) {
		if(filesize($full_path) > 770000000 && filesize($full_path) < 830000000) {
			//print('Found one!');exit(0);
			print($full_path . '<br>');
		}
	}
	// find movies without a year indicated
	// find duplicated files (notice that this would require an array with millions of entries unless skillfully handled)
	*/
	print(fs::icon_code('menu', 'do.php'));
}

function file_size($size) { 
	$filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"); 
	return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes'; 
}

function find_filesize($file) {
    // exec is slow
	/*if(substr(PHP_OS, 0, 3) == "WIN") {
        exec('for %I in ("'.$file.'") do @echo %~zI', $output);
        return $output[0];
    }*/
	$file_size = filesize($file);
	$true_size = $file_size >= 0 ? $file_size : 4*1024*1024*1024 + $file_size;
    return $true_size;
}

function mkdir_to_root_old($path) {
	//print('$path: ');var_dump($path);
	if(substr_count($path, DS) > 0) {
		$DS = DS;
	} else {
		$DS = '\\';
	}
	$pathy = substr($path, 0, fs::strpos_last($path, $DS));
	//print('DS: ');var_dump($DS);
	//print('$pathy: ');var_dump($pathy);
	if(strpos($path, '.') === false || fs::strpos_last($path, '.') < fs::strpos_last($path, $DS)) {
		$dirs_to_make = array($path);
	} else {
		$dirs_to_make = array();
	}
	//while(!is_dir($pathy) && $previous_pathy !== $pathy) {
	while(!is_dir($pathy)) {
		//$previous_pathy = $pathy;
		$dirs_to_make[] = $pathy;
		$pathy = substr($pathy, 0, strlen($pathy) - 1);
		$pathy = substr($pathy, 0, fs::strpos_last($pathy, $DS));
		//print('$pathy in while: ');var_dump($pathy);
	}
	//print('$dirs_to_make: ');var_dump($dirs_to_make);exit(0);
	$dirs_to_make = array_reverse($dirs_to_make);
	foreach($dirs_to_make as $dir) {
		mkdir($dir);
		//print('made dir: ' . $dir . '<br>');
	}
}

function mkdir_to_root($path) {
	return fs::build_directory_structure_for($path);
}

function build_directory_structure_for($filename) {
	//print('$filename: ');var_dump($filename);
	$folders = explode(DS, $filename);
	//print('$folders: ');var_dump($folders);
	$folder_string = '';
	foreach($folders as $index => $folder_name) {
		//print('$folder_string: ');var_dump($folder_string);
		if($index === sizeof($folders) - 1) {
			break;
		}
		$folder_string .= $folder_name . DS;
		if(!is_dir($folder_string)) {
			mkdir($folder_string);
		}
	}
}

function recursiveChmod($path, $filePerm=0644, $dirPerm=0755) {
	// Check if the path exists
	if(!file_exists($path)) {
		return(false);
	}
	// See whether this is a file
	if(is_file($path)) {
		// Chmod the file with our given filepermissions
		chmod($path, $filePerm);
	} elseif(is_dir($path)) { // If this is a directory...
		// Then get an array of the contents
		$foldersAndFiles = scandir($path);
		// Remove "." and ".." from the list
		$entries = array_slice($foldersAndFiles, 2);
		// Parse every result...
		foreach($entries as $entry) {
			// And call this function again recursively, with the same permissions
			if(is_dir($path . DS . $entry)) {
				fs::recursiveChmod($path . DS . $entry, $filePerm, $dirPerm);
			} else {
				chmod($path . DS . $entry, $dirPerm);
			}
		}
		// When we are done with the contents of the directory, we chmod the directory itself
		chmod($path, $dirPerm);
	}
	// Everything seemed to work out well, return true
	return(true);
}

function recursive_delete($src) {
    if(is_file($src)) {
		unlink($src);
	} else {
		$dir = opendir($src);
		while(false !== ($entry = readdir($dir))) {
			if(($entry != '.') && ($entry != '..')) {
				if(is_dir($src . DS . $entry)) {
					fs::recursive_delete($src . DS . $entry);
				} else {
					unlink($src . DS . $entry);
				}
			}
		}
		closedir($dir);
		rmdir($src);
	}
}

function recursive_move($src, $dst) {
	//print('$src, $dst in recursive_move: ');var_dump($src, $dst);
	fs::mkdir_to_root($dst);
    if(is_file($src)) {
		copy($src, $dst);
		unlink($src);
		//print('move-place file ' . $src . ' to ' . $dst . '<br>');exit(0);
	} else {
		$dir = opendir($src);
		//if(!is_dir($dst)) {
		//	mkdir($dst, 0, true);
		//}
		while(false !== ($entry = readdir($dir))) {
			if(($entry != '.') && ($entry != '..')) {
				if(is_dir($src . DS . $entry)) {
					//print('recurse in recursive_move on ' . $src . DS . $entry . '<br>');
					fs::recursive_move($src . DS . $entry, $dst . DS . $entry);
				} else {
					fs::mkdir_to_root($dst . DS . $entry);
					copy($src . DS . $entry, $dst . DS . $entry);
					unlink($src . DS . $entry);
					//print('move-place folder ' . $src . DS . $entry . ' to ' . $dst . DS . $entry . '<br>');exit(0);
				}
				//chmod($src . DS . $entry, 0777);
				//chown($src . DS . $entry, 666);
			}
		}
		closedir($dir);
		rmdir($src);
	}
}

function recursive_copy($src, $dst) {
	//print('$src, $dst: ');var_dump($src, $dst);
	fs::mkdir_to_root($dst);
	if(is_file($src)) {
		copy($src, $dst);
	} else {
		$dir = opendir($src);
		//@mkdir($dst);
		//if(!is_dir($dst)) {
		//	mkdir($dst, 0, true);
		//}
		while(false !== ($entry = readdir($dir))) {
			/*if($this->file_counter === 100) {
				exit(0);
			}*/
			if(($entry != '.') && ($entry != '..')) {
				if(is_dir($src . DS . $entry)) {
					//mkdir($dst . DS . $entry);
					fs::recursive_copy($src . DS . $entry, $dst . DS . $entry);
				} else {
					fs::mkdir_to_root($dst . DS . $entry);
					//print("copy($src . DS . $entry, $dst . DS . $entry)<br>");
					/*print('<tr>
<td>' . $src . DS . $entry . '</td>
<td>' . $dst . DS . $entry . '</td>
</tr>
');*/ // careful using this since it will overload firefox pretty quickly as the number of files increases
					copy($src . DS . $entry, $dst . DS . $entry);
					//preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/is', $src, $date_matches);
					//if(sizeof($date_matches[0]) > 0) {
					//	exit(0);
					//}
					//exit(0);
					//$this->file_counter++;
					//exit(0);
				}
			}
		}
		closedir($dir);
	}
}

function differential_recursive_copy($src, $dst) {
	fs::mkdir_to_root(str_replace('{date}', date("Y-m-d"), $dst));
    if(is_file($src)) {
		copy($src, str_replace('{date}', date("Y-m-d"), $dst));
	} else {
		$dir = opendir($src);
		//@mkdir($dst);
		//if(!is_dir(str_replace('{date}', date("Y-m-d"), $dst))) {
		//	mkdir(str_replace('{date}', date("Y-m-d"), $dst), 0, true);
		//}
		while(false !== ($entry = readdir($dir))) {
			if(($entry != '.') && ($entry != '..')) {
				if(is_dir($src . DS . $entry)) {
					fs::differential_recursive_copy($src . DS . $entry, $dst . DS . $entry);
				} else {
					//print('File: ' . $src . DS . $entry . '<br>');
					$file_has_a_backup = false;
					foreach($this->array_dates as $index => $value) {
						if(file_exists(str_replace('{date}', $value, $dst) . DS . $entry)) { // found a previously backed up version
							//print('here164856001<br>');
							//print('found a previously backed up version: ' . str_replace('{date}', $value, $dst) . DS . $entry . '<br>');
							//var_dump(filemtime($src . DS . $entry), filemtime(str_replace('{date}', $value, $dst) . DS . $entry), fs::find_filesize($src . DS . $entry), fs::find_filesize(str_replace('{date}', $value, $dst) . DS . $entry));
							if(filemtime(str_replace('{date}', $value, $dst) . DS . $entry) >= filemtime($src . DS . $entry) && fs::find_filesize($src . DS . $entry) === fs::find_filesize(str_replace('{date}', $value, $dst) . DS . $entry)) { // no need to back it up
								//print('here164856002<br>');
							} else {
								//print('here164856003<br>');
								//print('$src . DS . $entry, str_replace(\'{date}\', date("Y-m-d"), $dst) . DS . $entry: ');var_dump($src . DS . $entry, str_replace('{date}', date("Y-m-d"), $dst) . DS . $entry);
								fs::mkdir_to_root(str_replace('{date}', date("Y-m-d"), $dst) . DS . $entry);
								copy($src . DS . $entry, str_replace('{date}', date("Y-m-d"), $dst) . DS . $entry);
							}
							$file_has_a_backup = true;
							break;
						}
					}
					if(!$file_has_a_backup) {
						//print('here164856004<br>');
						fs::mkdir_to_root(str_replace('{date}', date("Y-m-d"), $dst) . DS . $entry);
						copy($src . DS . $entry, str_replace('{date}', date("Y-m-d"), $dst) . DS . $entry);
					}
					//exit(0);
				}
			}
		}
		closedir($dir);
	}
}

function recursive_restore($src, $dst) {
	fs::mkdir_to_root($dst);
    if(is_file($src)) {
		//print('filemtime($src), filemtime($dst): ');var_dump(filemtime($src), filemtime($dst));
		if(!is_file($dst) || filemtime($src) >= filemtime($dst)) {
			print($dst . ' restored from ' . $src . '.<br>');
			copy($src, $dst);
			touch($dst, filemtime($src));
		}
	} else {
		$handle = opendir($src);
		while(false !== ($entry = readdir($handle))) {
			if(($entry != '.') && ($entry != '..')) {
				fs::recursive_restore($src . DS . $entry, $dst . DS . $entry);
			}
		}
		closedir($handle);
	}
}

function smartCopy($source, $dest, $options = array('folderPermission' => 0755, 'filePermission' => 0755)) {
	$result = false;
	if(is_file($source)) {
		if($dest[strlen($dest) - 1] == DS) {
			if(!file_exists($dest)) {
				cmfcDirectory::makeAll($dest, $options['folderPermission'], true);
			}
			$__dest = $dest . "/" . basename($source);
		} else {
			$__dest = $dest;
		}
		$result = copy($source, $__dest);
		chmod($__dest, $options['filePermission']);
	} elseif(is_dir($source)) {
		if($dest[strlen($dest) - 1] == DS) {
			if($source[strlen($source) - 1] == DS) {
				// Copy only contents
			} else {
				// Change parent itself and its contents
				$dest = $dest.basename($source);
				@mkdir($dest);
				chmod($dest, $options['filePermission']);
			}
		} else {
			if($source[strlen($source) - 1] == DS) {
				//Copy parent directory with new name and all its content
				@mkdir($dest, $options['folderPermission']);
				chmod($dest, $options['filePermission']);
			} else {
				//Copy parent directory with new name and all its content
				@mkdir($dest, $options['folderPermission']);
				chmod($dest, $options['filePermission']);
			}
		}
		$dirHandle = opendir($source);
		while($file = readdir($dirHandle)) {
			if($file!="." && $file!="..") {
				if(!is_dir($source . "/" . $file)) {
					$__dest = $dest . "/" . $file;
				} else {
					$__dest = $dest . "/" . $file;
				}
				echo "$source/$file ||| $__dest<br />";
				$result = fs::smartCopy($source . "/" . $file, $__dest, $options);
			}
		}
		closedir($dirHandle);
	} else {
		$result=false;
	}
	return $result;
} 

function navigate_files_recursive_list() {
	fs::init_LOM();
	fs::print_segmented_path($this->path);
	$array_entries = fs::recursive_get_entries($this->path, $array_entries);
	/*$counted_folder = false;
	foreach($this->fixed_drives_array as $fixed_drive) {
		$drive_path = $fixed_drive . ':' . DS . $path;
		//print('$drive_path in recursive_list: ');var_dump($drive_path);
		if(is_dir($drive_path)) {
			print($drive_path . '<br>
');
			if(!$counted_folder) {
				$this->folder_counter++;
				$counted_folder = true;
			}
			$handle = opendir($drive_path);
			while(false !== ($entry = readdir($handle))) {
				if($entry == '.' || $entry == '..') {
					continue;
				}
				$Entry = $drive_path . DS . $entry;
				if(is_dir($Entry)) {
					fs::recursive_list($path . DS . $entry);
				} else {
					print($Entry . '<br>
');
					$this->file_counter++;
				}
			}
			closedir($handle);
		} elseif(is_file($drive_path)) {
			print($drive_path . '<br>
');
			$this->file_counter++;
		}
	}*/
	//fs::recursive_list($this->path);
	//print($this->file_counter . ' total files in ' . $this->folder_counter . ' folders.<br>');
	fs::print_entries_table($array_entries, 'recursive directory list');
	$up_level_path = fs::get_up_level_path($this->path);
	print(
	fs::icon_code('up', 'do.php?action=navigate_files&path=' . fs::query_encode($up_level_path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string . '#' . fs::anchor_encode(substr($this->path, fs::strpos_last($this->path, DS) + 1))) .
	fs::icon_code('menu', 'do.php') .
	fs::icon_code('recursive', 'do.php?action=navigate_files_recursive_list&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('navigate', 'do.php?action=navigate_files&path=&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('one-click', 'one-click_navigate_files.php?path=' . $this->path . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('fractal', 'do.php?action=create_fractal_zip_container&path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string) .
	fs::icon_code('restore', 'do.php?action=restore_from_backup&restore_path=' . fs::query_encode($this->path) . '&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string)) .
	fs::icon_code('keywords', 'do.php?action=keywords_navigate&fixed_drives=' . $this->fixed_drives_string . $this->file_operations_string);
}

function recursive_get_entries($path, $array_entries) {
	$counted_folder = false;
	foreach($this->fixed_drives_array as $fixed_drive) {
		$drive_path = $fixed_drive . ':' . DS . $path;
		if(is_dir($drive_path)) {
			/*print($drive_path . '<br>
');*/
			if(isset($array_entries[$path])) {
				//$array_full_paths = $array_entries[$path];
				//$array_full_paths[] = $drive_path;
				//$array_entries[$path][] = $array_full_paths;
				$array_entries[$path][] = $drive_path;
			} else {
				$array_entries[$path] = array($drive_path);
			}
			if(!$counted_folder) {
				$this->folder_counter++;
				$counted_folder = true;
			}
			$handle = opendir($drive_path);
			while(false !== ($entry = readdir($handle))) {
				if($entry == '.' || $entry == '..') {
					continue;
				}
				$full_path = $drive_path . DS . $entry;
				$full_path = str_replace(DS . DS, DS, $full_path);
				if(is_dir($full_path)) {
					$array_entries = fs::recursive_get_entries($path . DS . $entry, $array_entries);
				} else {
					/*print($full_path . '<br>
');*/
					$driveless_full_path = fs::driveless($full_path);
					if(isset($array_entries[$driveless_full_path])) {
						//$array_full_paths = $array_entries[$entry];
						//$array_full_paths[] = $full_path;
						//$array_entries[$entry] = $array_full_paths;
						$array_entries[$driveless_full_path][] = $full_path;
					} else {
						$array_entries[$driveless_full_path] = array($full_path);
					}
					$this->file_counter++;
				}
			}
			closedir($handle);
		} elseif(is_file($drive_path)) {
			/*print($drive_path . '<br>
');*/
			if(isset($array_entries[$path])) {
				//$array_full_paths = $array_entries[$path];
				//$array_full_paths[] = $drive_path;
				//$array_entries[$path] = $array_full_paths;
				$array_entries[$path][] = $drive_path;
			} else {
				$array_entries[$path] = array($drive_path);
			}
			$this->file_counter++;
		}
	}
	return $array_entries;
}

function recursive_file_list($directory) {
	/*if($this->file_counter > 100) {
		print('recursive_file_list stopped since more than 100 files were listed.');
		var_dump($this->file_counter);
		exit(0);
	}*/
	if(is_dir($directory)) {
		$d = dir($directory);
		while(FALSE !== ($entry = $d->read())) {
			if($entry == '.' || $entry == '..') {
				continue;
			}
			$Entry = $directory . DS . $entry;
			if(is_dir($Entry)) {
				//print("folder: " . $Entry . "\r\n<br>");
				$this->folder_counter++;
				fs::recursive_file_list($Entry);
				continue;
			} else {
				print($Entry . "\r\n<br>");
				$this->file_counter++;
			}
		}
		$d->close();
	}
}

function recursive_file_list_to_array($directory) {
	if(is_dir($directory)) {
		$d = dir($directory);
		while(FALSE !== ($entry = $d->read())) {
			if($entry == '.' || $entry == '..') {
				continue;
			}
			$Entry = $directory . DS . $entry;
			if(is_dir($Entry)) {
				$this->folder_counter++;
				fs::recursive_file_list_to_array($Entry);
				continue;
			} else {
				//print($Entry . "\r\n<br>");
				$this->files[] = $Entry;
				$this->file_counter++;
			}
		}
		$d->close();
	}
}

function recursive_list($path) {
	//if($this->file_counter > 100) {
	//	print('recursive_list stopped since more than 100 files were listed.');
	//	var_dump($this->file_counter);
	//	exit(0);
	//}
	//print('$path in recursive_list: ');var_dump($path);
	//print('$fixed_drives in recursive_list: ');var_dump($fixed_drives);
	$counted_folder = false;
	foreach($this->fixed_drives_array as $fixed_drive) {
		$drive_path = $fixed_drive . ':' . DS . $path;
		//print('$drive_path in recursive_list: ');var_dump($drive_path);
		if(is_dir($drive_path)) {
			print($drive_path . '<br>
');
			if(!$counted_folder) {
				$this->folder_counter++;
				$counted_folder = true;
			}
			$handle = opendir($drive_path);
			while(false !== ($entry = readdir($handle))) {
				if($entry == '.' || $entry == '..') {
					continue;
				}
				$Entry = $drive_path . DS . $entry;
				if(is_dir($Entry)) {
					fs::recursive_list($path . DS . $entry);
				} else {
					print($Entry . '<br>
');
					$this->file_counter++;
				}
			}
			closedir($handle);
		} elseif(is_file($drive_path)) {
			print($drive_path . '<br>
');
			$this->file_counter++;
		}
	}
}

function smart_array_merge($array1, $array2) {
	foreach($array2 as $value) {
		$array1[] = $value;
	}
	return $array1;
}

function transit_intensity_map_from_file() {
	$contents = file_get_contents("counted_folders.txt");
	$counted_folders_array = explode("\r\n", $contents);
	$counted_folders_array2 = array();
	$biggest_intensity = 0;
	foreach($counted_folders_array as $counted_folder) {
		$counted_folder2 = explode("\t", $counted_folder);
		if($counted_folder2[1] > $biggest_intensity) {
			$biggest_intensity = $counted_folder2[1];
		}
		$counted_folders_array2[] = $counted_folder2;
	}
	print("<p>");
	foreach($counted_folders_array2 as $counted_folder3) {
		$op1 = 50 + 450 * (bcdiv(log($counted_folder3[1], 10), log($biggest_intensity, 10), 3));
		print('<span style="font-size: ' . $op1 . '%;">' . $counted_folder3[0] . '</span> ');
	}
	print("</p>");
}

function get_counted_permutations_of_folder_relations_from_file() {
	$contents = file_get_contents("permutations.txt");
	$array_permutations = explode("\r\n", $contents);
	$counted_array_permutations = fs::counted_from_redundant_array($array_permutations);
	$counted_folders_permutations_contents = '';
	foreach($counted_array_permutations as $permutation => $count) {
		$counted_folders_permutations_contents .= $permutation . "\t" . $count . "\r\n";
	}
	file_put_contents("counted_permutations.txt", $counted_folders_permutations_contents);
}

function get_permutations_of_folder_relations_from_file_iteratively() {
	while(true) {
		if(fs::get_permutations_of_folder_relations_from_file()) {
		
		} else {
			break;
		}
	}
}

function get_permutations_of_folder_relations_from_file() {
	$contents = file_get_contents("directories.txt");
	$contents_array = explode("\r\n", $contents);
	$initial_directories_counter = $directories_counter = file_get_contents("directories_counter.txt");
	$array_permutations = array();
	$return = true;
	while($directories_counter - $initial_directories_counter < 1000) {
		if(!isset($contents_array[$directories_counter])) {
			$return = false;
			break;
		}
		$permutations = fs::get_permutations_of_folder_relations(explode("/", $contents_array[$directories_counter]));
		//$array_permutations = array_merge($array_permutations, $permutations);
		$array_permutations = fs::smart_array_merge($array_permutations, $permutations);
		$directories_counter++;
	}
	//$this->directories_counter = $directories_counter;
	file_put_contents("directories_counter.txt", $directories_counter);
	$permutations_contents = file_get_contents("permutations.txt");
	foreach($array_permutations as $permutation) {
		$permutations_contents .= implode("/", $permutation) . "\r\n";
	}
	file_put_contents("permutations.txt", $permutations_contents);
	return $return;
}

function get_permutations_of_folder_relations($folders) {
	$permutations = array();
	$index3 = 0;
	//print("Message0010<br>\r\n");
	while($index3 < sizeof($folders)) {
		//print("Message0011<br>\r\n");
		foreach($folders as $index => $folder) {
			//print("Message0012<br>\r\n");
			if($index3 + $index >= sizeof($folders)) {
				break;
			}
			$index2 = $index;
			//$index2 = $index3 + $index;
			$mid_step = array();
			while($index2 < sizeof($folders)) {
				if($index2 + $index3 >= sizeof($folders)) {
					break;
				}
				//print("Message0013<br>\r\n");
				$mid_step[] = $folders[$index2];
				$index2++;
			}
			$permutations[] = $mid_step;
		}
		$index3++;
	}
	return $permutations;
}

function counted_from_redundant_array($array) {
	//print("message1<br>\r\n");
	$counted_array = array();
	foreach($array as $index => $value) {
		//print("message2<br>\r\n");
		//foreach($counted_array as $index2 => $value2) {
		//	//print("message3<br>\r\n");
		//	if($value === $value2[0]) {
		//		$counted_array[$index2][1]++;
		//		continue 2;
		//	}
		//}
		if(isset($counted_array[$value])) {
			$counted_array[$value]++;
		} else {
			$counted_array[$value] = 1;
		}
	}
	return $counted_array;
}

function create_folders_array($directory) {
	$this->contents = file_get_contents("directories.txt");
	$array_directories = explode("\r\n", $this->contents);
	$array_folders = array();
//	$multi_array_folders = array();
	$array_permutations = array();
	foreach($array_directories as $index => $directory) {
//		$part_working_on = &$multi_array_folders;
		$folders = explode("/", $directory);
		//foreach($folders as $folder) {
		//	$array_folders[] = $folder;
//			if(isset($part_working_on[$folder])) {
//			
//			} else {
//				$part_working_on[$folder] = array();
//			}
//			$part_working_on = &$part_working_on[$folder];
		//}
		$permutations = fs::get_permutations_of_folder_relations($folders);
		$array_permutations = array_merge($array_permutations, $permutations);
	}
	//$counted_array_folders = fs::counted_from_redundant_array($array_folders);
//	$counted_array_permutations = fs::counted_from_redundant_array($array_permutations);
	//$array_folders = array_unique($array_folders);
//	$array_permutations = array_unique($array_permutations);
	//print("array_folders: ");var_dump($array_folders);print("<br>\r\n");
//	print("multi_array_folders: ");var_dump($multi_array_folders);print("<br>\r\n");
//	print("array_permutations: ");var_dump($array_permutations);print("<br>\r\n");
//	print("counted_array_folders: ");var_dump($counted_array_folders);print("<br>\r\n");
//	print("counted_array_permutations: ");var_dump($counted_array_permutations);print("<br>\r\n");
	//$folders_contents = '';
	//foreach($array_folders as $folder) {
	//	$folders_contents .= $folder . "\r\n";
	//}
	//file_put_contents("folders.txt", $folders_contents);
	$permutations_contents = '';
	foreach($array_permutations as $permutation) {
		$permutations_contents .= implode("/", $permutation) . "\r\n";
	}
	file_put_contents("permutations.txt", $permutations_contents);
	//$counted_folders_contents = '';
	//foreach($counted_array_folders as $folder => $count) {
	//	$counted_folders_contents .= $folder . "\t" . $count . "\r\n";
	//}
	//file_put_contents("counted_folders.txt", $counted_folders_contents);
//	$counted_folders_permutations_contents = '';
//	foreach($counted_array_permutations as $permutation => $count) {
//		$counted_folders_permutations_contents .= $permutation . "\t" . $count . "\r\n";
//	}
//	file_put_contents("counted_permutations.txt", $counted_folders_permutations_contents);
}

function write_folder_paths_to_file($directory) {
	$this->list_folder_paths($directory);
	file_put_contents("directories.txt", $this->contents);
}

function list_folder_paths($directory) {
	if(is_dir($directory)) {
		if(!dir($directory)) {
			print('<h1 style="color: red;">Error 0001</h1>');
			return;
		}
		$d = dir($directory);
		//if(!$d->read()) {
		//	print('<h1 style="color: red;">Error 0002</h1>');
		//}
		while(FALSE !== ($entry = $d->read())) {
			if($entry == '.' || $entry == '..') {
				continue;
			}
			$Entry = $directory . DS . $entry;
			if(is_dir($Entry)) {
				$this->current_folder_array[] = $entry;
				fs::list_folder_paths($Entry);
				continue;
			}
		}
		$this->current_folder_array_less_last_folder = array();
		$folder_path = $directory . "/";
		foreach($this->current_folder_array as $index => $folder) {
			$folder_path .= $folder . "/";
			if($index < sizeof($this->current_folder_array) - 1) {
				$this->current_folder_array_less_last_folder[] = $folder;
			}
		}
		$this->current_folder_array = $this->current_folder_array_less_last_folder;
		//print("folder: " . $directory . "\r\n<br>");
		$this->contents .= $directory . "\r\n";
		$this->folder_counter++;
		$d->close();
	}
}

function strpos_nth($haystack, $needle, $n) {
	$counter = 0;
	//$substr = $haystack;
	while($counter < $n) {
		$strpos = strpos($haystack, $needle, $strpos + 1);
		//$substr = substr($haystack, $strpos + 1);
		$counter++;
	}
	return $strpos;
}

function filename_minus_extension($string) {
	return substr($string, 0, fs::strpos_last($string, '.'));
}

function file_extension($string) {
	if(strpos($string, '.') === false || fs::strpos_last($string, '.') < fs::strpos_last($string, DS)) {
		return false;
	}
	$file_extension = substr($string, fs::strpos_last($string, '.'));
	if(strpos($file_extension, ' ') !== false || strpos($file_extension, '[') !== false || strpos($file_extension, ']') !== false || strpos($file_extension, '(') !== false || strpos($file_extension, ')') !== false || strpos($file_extension, '-') !== false) { // should we use preg_match?
		return false;
	}
	return $file_extension;
}

function shortpath($string) {
	return substr($string, fs::strpos_last($string, DS));
}

function init_fractal_zip() {
	if(!include_once('..' . DS . 'fractal_zip' . DS . 'fractal_zip.php')) {
		print('<a href="https://github.com/flaurora-sonora/fractal_zip">fractal_zip</a> is required');exit(0);
	}
	$this->fractal_zip = new fractal_zip();
}

function init_LOM() {
	if(!is_file('folders.xml')) {
		file_put_contents('folders.xml', '<folders></folders>');
	}
	if(!include('..' . DS . 'LOM' . DS . 'O.php')) {
		print('<a href="https://www.phpclasses.org/package/10594-PHP-Extract-information-from-XML-documents.html">LOM</a> is required');exit(0);
	}
	//$this->folders = new O('folders.xml', false); // don't use context in an effort to go faster; big mistake
	$this->folders = new O('folders.xml');
	$serialized_folders = serialize($this->folders);
	file_put_contents('serialized_folders.txt', $serialized_folders);
}

function get_up_level_path($path) {
	$up_level_path = $path;
	if(strpos($up_level_path, ':') !== false) {
		$up_level_path = substr($up_level_path, strpos($up_level_path, ':') + 2);
	}
	$up_level_path = substr($up_level_path, 0, fs::strpos_last($up_level_path, DS));
	return $up_level_path;
}

function get_path_up_to_index($path, $index) {
	$counter = 0;
	$processed_index = -1;
	$new_up_to_index_path = '';
	while($counter < strlen($path)) {
		if($path[$counter] === '/' || $path[$counter] === '\\') {
			$processed_index++;
			if($processed_index === $index) {
				break;
			}
		}
		$new_up_to_index_path .= $path[$counter];
		$counter++;
	}
	//print('$index, $new_up_to_index_path: ');var_dump($index, $new_up_to_index_path);
	return $new_up_to_index_path;
}

function color_spectrum_point($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
    $l = (300 * $value / $maximum) + 400;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 400.0 && $l < 410.0) {
		$t = ($l - 400.0) / (410.0 - 400.0);
		$red_component = (0.33 * $t) - (0.20 * $t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$red_component = 0.14 - (0.13 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$red_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 595.0 && $l < 650.0) {
		$t = ($l - 595.0) / (650.0 - 595.0);
		$red_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	} elseif($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$red_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	}
	if($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	}
	if($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$blue_component = (2.20 * $t) - (1.50 * $t * $t);
	} elseif($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$blue_component = 0.7 - ($t) + (0.30 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	return $red_component . $green_component . $blue_component;
}

function black_to_red_spectrum($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
    $l = (300 * $value / $maximum) + 400;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 400.0 && $l < 410.0) {
		$t = ($l - 400.0) / (410.0 - 400.0);
		$red_component = (0.33 * $t) - (0.20 * $t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$red_component = 0.14 - (0.13 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$red_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 595.0 && $l <= 700.0) {
		$t = ($l - 595.0) / (700.0 - 595.0);
		$red_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	}/* elseif($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$red_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	}*/
	if($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	}
	if($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$blue_component = (2.20 * $t) - (1.50 * $t * $t);
	} elseif($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$blue_component = 0.7 - ($t) + (0.30 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	return $red_component . $green_component . $blue_component;
}

function inverse_color_spectrum_point($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
    $l = (300 * $value / $maximum) + 400;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 400.0 && $l < 410.0) {
		$t = ($l - 400.0) / (410.0 - 400.0);
		$red_component = (0.33 * $t) - (0.20 * $t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$red_component = 0.14 - (0.13 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$red_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 595.0 && $l < 650.0) {
		$t = ($l - 595.0) / (650.0 - 595.0);
		$red_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	} elseif($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$red_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	}
	if($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	}
	if($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$blue_component = (2.20 * $t) - (1.50 * $t * $t);
	} elseif($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$blue_component = 0.7 - ($t) + (0.30 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = 255 - $red_component;
	$green_component = 255 - $green_component;
	$blue_component = 255 - $blue_component;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	return $red_component . $green_component . $blue_component;
}

function reverse_color_spectrum_point($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
	// pretty sure this code wasn't properly reversed also (or maybe just looking at it in the reverse way brings issues to light)
    $l = (300 * $value / $maximum) + 400;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$blue_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	} elseif($l >= 595.0 && $l < 650.0) {
		$t = ($l - 595.0) / (650.0 - 595.0);
		$blue_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$blue_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$blue_component = 0.14 - (0.13 * $t * $t);
	} elseif($l >= 400.0 && $l < 410.0) {
		$t = ($l - 400.0) / (410.0 - 400.0);
		$blue_component = (0.33 * $t) - (0.20 * $t * $t);
	}
	if($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	}
	if($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$red_component = 0.7 - ($t) + (0.30 * $t * $t);
	} elseif($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$red_component = (2.20 * $t) - (1.50 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	return $red_component . $green_component . $blue_component;
}

function red_to_black_spectrum($value, $maximum) {
	return fs::front_trimmed_reverse_color_spectrum_point($value, $maximum);
}

function front_trimmed_reverse_color_spectrum_point($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
	// pretty sure this code wasn't properly reversed also (or maybe just looking at it in the reverse way brings issues to light)
    $l = (250 * $value / $maximum) + 450;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$blue_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	} elseif($l >= 595.0 && $l < 650.0) {
		$t = ($l - 595.0) / (650.0 - 595.0);
		$blue_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$blue_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$blue_component = 0.14 - (0.13 * $t * $t);
	}
	if($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	}
	if($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$red_component = 0.7 - ($t) + (0.30 * $t * $t);
	} elseif($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$red_component = (2.20 * $t) - (1.50 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	if($this->theme === 'dark' && $red_component === '00' && $green_component === '00' && $blue_component === '00') {
		$red_component = 'a6';
		$green_component = 'aa';
		$blue_component = 'ab';
	}
	return $red_component . $green_component . $blue_component;
}

function reverse_inverse_color_spectrum_point($value, $maximum) {
	// would really prefer to use toroidal math instead of this empirical approach...
	// pretty sure this code wasn't properly reversed also (or maybe just looking at it in the reverse way brings issues to light)
    $l = (300 * $value / $maximum) + 400;
	//print('$l: ');var_dump($l);
	$red_component = 0.0;
	$green_component = 0.0;
	$blue_component = 0.0;
	if($l >= 400.0 && $l < 410.0) {
		$t = ($l - 400.0) / (410.0 - 400.0);
		$blue_component = (0.33 * $t) - (0.20 * $t * $t);
	} elseif($l >= 410.0 && $l < 475.0) {
		$t = ($l - 410.0) / (475.0 - 410.0);
		$blue_component = 0.14 - (0.13 * $t * $t);
	} elseif($l >= 545.0 && $l < 595.0) {
		$t = ($l - 545.0) / (595.0 - 545.0);
		$blue_component = (1.98 * $t) - ($t * $t);
	} elseif($l >= 595.0 && $l < 650.0) {
		$t = ($l - 595.0) / (650.0 - 595.0);
		$blue_component = 0.98 + (0.06 * $t) - (0.40 * $t * $t);
	} elseif($l >= 650.0 && $l < 700.0) {
		$t = ($l - 650.0) / (700.0 - 650.0);
		$blue_component = 0.65 - (0.84 * $t) + (0.20 * $t * $t);
	}
	if($l >= 415.0 && $l < 475.0) {
		$t = ($l - 415.0) / (475.0 - 415.0);
		$green_component = (0.80 * $t * $t);
	} elseif($l >= 475.0 && $l < 590.0) {
		$t = ($l - 475.0) / (590.0 - 475.0);
		$green_component = 0.8 + (0.76 * $t) - (0.80 * $t * $t);
	} elseif($l >= 585.0 && $l < 639.0) {
		$t = ($l - 585.0) / (639.0 - 585.0);
		$green_component = 0.84 - (0.84 * $t);
	}
	if($l >= 400.0 && $l < 475.0) {
		$t = ($l - 400.0) / (475.0 - 400.0);
		$red_component = (2.20 * $t) - (1.50 * $t * $t);
	} elseif($l >= 475.0 && $l < 560.0) {
		$t = ($l - 475.0) / (560.0 - 475.0);
		$red_component = 0.7 - ($t) + (0.30 * $t * $t);
	}
	//print('$red_component, $green_component, $blue_component mid-function: ');var_dump($red_component, $green_component, $blue_component);
	$red_component *= 255;
	$green_component *= 255;
	$blue_component *= 255;
	$red_component = 255 - $red_component;
	$green_component = 255 - $green_component;
	$blue_component = 255 - $blue_component;
	$red_component = dechex(round($red_component));
	if(strlen($red_component) < 2) {
		$red_component = '0' . $red_component;
	}
	$green_component = dechex(round($green_component));
	if(strlen($green_component) < 2) {
		$green_component = '0' . $green_component;
	}
	$blue_component = dechex(round($blue_component));
	if(strlen($blue_component) < 2) {
		$blue_component = '0' . $blue_component;
	}
	return $red_component . $green_component . $blue_component;
}

function fatal_error($message) { 
	print('<span style="color: red;">' . $message . '</span>');exit(0);
}

function warning($message) { 
	print('<span style="color: orange;">' . $message . '</span><br>');
}

function fatal_error_once($string) {
	if(!isset($this->printed_strings[$string])) {
		print('<span style="color: red;">' . $string . '</span>');exit(0);
		$this->printed_strings[$string] = true;
	}
	return true;
}

function warning_if($string, $count) {
	if($count > 1) {
		fs::warning($string);
	}
}

function warning_once($string) {
	if(!isset($this->printed_strings[$string])) {
		print('<span style="color: orange;">' . $string . '</span><br>');
		$this->printed_strings[$string] = true;
	}
	return true;
}

function get_by_request($variable) {
	if($_REQUEST[$variable] == '') {
		//warning($variable . ' not properly specified.<br>');
		return false;
	} else {
		$variable = fs::normalize_slashes(fs::query_decode($_REQUEST[$variable]));
	}
	return $variable;
}

function getmicrotime() {
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}

function dump_total_time_taken() {
	$time_spent = fs::getmicrotime() - $this->initial_time;
	print('Total time spent doing filesystem operations: ' . $time_spent . ' seconds.<br>');
}

}

?>