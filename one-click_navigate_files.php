<?php

/*if($_REQUEST['path'] == '') {
	$path = '';
} else {
	$path = fs::query_decode($_REQUEST['path']);
}
$path = fs::normalize_slashes($path);
if(isset($this->files_times_accessed[$path])) {
	$this->files_times_accessed[$path]++;
} else {
	$this->files_times_accessed[$path] = 1;
}
fs::save_files_times_accessed();
fs::set_file_operations_string();*/
//$this->file_operations_string = '';
$path = $_REQUEST['path'];
$fixed_drives_string = $_REQUEST['fixed_drives'];
$file_operations_string = '';
if($_REQUEST['cut'] == '') {
	
} else {
	//$this->file_operations_string .= '&cut=' . fs::query_decode($_REQUEST['cut']);
	$file_operations_string .= '&cut=' . $_REQUEST['cut'];
}
if($_REQUEST['copy'] == '') {
	
} else {
	//$this->file_operations_string .= '&copy=' . fs::query_decode($_REQUEST['copy']);
	$file_operations_string .= '&copy=' . $_REQUEST['copy'];
}

?>
<html>
    <head>
	<title>bigo fracto</title>
		<style type="text/css">
		p { margin: 0.2em; }
		</style>
		<script src="../infini/infiniquery.js"></script>
		<script src="../infini/gamequery.js"></script>
        <script>
            $(function(){
                
				// Global constants:
				var PLAYGROUND_WIDTH	= 600;
				var PLAYGROUND_HEIGHT	= 600;
				// does this even do anything?
				//var REFRESH_RATE		= 15;
				var REFRESH_RATE		= 1;

				$("#playground").playground({height: PLAYGROUND_HEIGHT, width: PLAYGROUND_WIDTH, keyTracker: true, mouseTracker: true, touchTracker: true});
				/*$("#playground").playground({height: 1000, width: 1000});*/
				
				// cardioid: 3d surface to 2d
				// would like something more elegant than merely 10 pixels buffer, probably
				// this is the function that controls most of the game logic 
				$.playground().registerCallback(function(){
					if(mouseisdown) {
						var overlay_divs = document.getElementsByClassName('entry');
						// what about the jump created when moving quickly then going back over the zone where moving slowly which allows more items to get off screen?
						// would maybe like the edges (however these would be determined) to not go past the center
						items_within_horizontal_bounds = 0;
						for (i = 0; i < overlay_divs.length; i++) {
							id = overlay_divs[i].id;
							newleft = parseInt($('#' + id).css('left')) - ($.gameQuery.mouseTracker.x - (PLAYGROUND_WIDTH / 2));
							if(newleft > 10 && newleft < PLAYGROUND_WIDTH - 10) {
								items_within_horizontal_bounds++;
							}
						}
						if(items_within_horizontal_bounds > 1) {
							for (i = 0; i < overlay_divs.length; i++) {
								id = overlay_divs[i].id;
								newleft = parseInt($('#' + id).css('left')) - ($.gameQuery.mouseTracker.x - (PLAYGROUND_WIDTH / 2));
								$('#' + id).css('left', newleft);
							}
						}
						items_within_vertical_bounds = 0;
						for (i = 0; i < overlay_divs.length; i++) {
							id = overlay_divs[i].id;
							newleft = parseInt($('#' + id).css('top')) - (Math.round($.gameQuery.mouseTracker.y) - (PLAYGROUND_HEIGHT / 2));
							if(newleft > 10 && newleft < PLAYGROUND_WIDTH - 10) {
								items_within_vertical_bounds++;
							}
						}
						if(items_within_vertical_bounds > 1) {
							for (i = 0; i < overlay_divs.length; i++) {
								id = overlay_divs[i].id;
								newleft = parseInt($('#' + id).css('top')) - (Math.round($.gameQuery.mouseTracker.y) - (PLAYGROUND_HEIGHT / 2));
								$('#' + id).css('top', newleft);
							}
						}
					}
					if(touchisdown) {
						var overlay_divs = document.getElementsByClassName('entry');
						items_within_horizontal_bounds = 0;
						for (i = 0; i < overlay_divs.length; i++) {
							id = overlay_divs[i].id;
							newleft = parseInt($('#' + id).css('left')) - ($.gameQuery.touchTracker.x - (PLAYGROUND_WIDTH / 2));
							if(newleft > 10 && newleft < PLAYGROUND_WIDTH - 10) {
								items_within_horizontal_bounds++;
							}
						}
						if(items_within_horizontal_bounds > 1) {
							for (i = 0; i < overlay_divs.length; i++) {
								id = overlay_divs[i].id;
								newleft = parseInt($('#' + id).css('left')) - ($.gameQuery.touchTracker.x - (PLAYGROUND_WIDTH / 2));
								$('#' + id).css('left', newleft);
							}
						}
						items_within_vertical_bounds = 0;
						for (i = 0; i < overlay_divs.length; i++) {
							id = overlay_divs[i].id;
							newleft = parseInt($('#' + id).css('top')) - (Math.round($.gameQuery.touchTracker.y) - (PLAYGROUND_HEIGHT / 2));
							if(newleft > 10 && newleft < PLAYGROUND_WIDTH - 10) {
								items_within_vertical_bounds++;
							}
						}
						if(items_within_vertical_bounds > 1) {
							for (i = 0; i < overlay_divs.length; i++) {
								id = overlay_divs[i].id;
								newleft = parseInt($('#' + id).css('top')) - (Math.round($.gameQuery.touchTracker.y) - (PLAYGROUND_HEIGHT / 2));
								$('#' + id).css('top', newleft);
							}
						}
					}
				}, REFRESH_RATE);
                
                $.playground().addGroup("overlay",{width: PLAYGROUND_WIDTH, height: PLAYGROUND_HEIGHT});
					
				//start_path = 'P:/Games/Age of Wonders III'; // javascript likes forward slashes
				//document.getElementById('path').innerHTML = start_path + '/';
				start_path = document.getElementById('path').innerHTML;
				fixed_drives_string = '<?php print($fixed_drives_string); ?>';
				fixed_drives_array = fixed_drives_string.split(',');
				//cut = '<?php print($_REQUEST['cut']); ?>';
				//copy = '<?php print($_REQUEST['copy']); ?>';
				// what about fixed_drives_string?
				zoom_current_x = 100;
				zoom_current_y = 0;
				id_counter = 0;
				zoom(start_path, zoom_current_x, zoom_current_y);
				function zoom(path, x, y) {
					$.post('readdir_forjs.php', { 'folder':path, 'fixed_drives':fixed_drives_string }, function(data){
						//alert('data in zoom: ' + data);
						var entries = data.split('	');
						entry_counter = 0;
						items_within_horizontal_bounds = 0;
						items_within_vertical_bounds = 0;
						while(entry_counter < entries.length) {
							$("#overlay").append('<div id="entry' + id_counter + '" class="entry" style="color: blue; border: 1px solid black; position: absolute; left: ' + x + '; top: ' + y + '; font-family: verdana, sans-serif;"></div>');
							document.getElementById('entry' + id_counter).style.textWrap="none";
							if(x > 10 && x < PLAYGROUND_WIDTH - 10) {
								items_within_horizontal_bounds++;
							}
							if(y > 10 && y < PLAYGROUND_HEIGHT - 10) {
								items_within_vertical_bounds++;
							}
							$('#entry' + id_counter).html(entries[entry_counter]);
							// need to consider files times accessed
							y += 25;
							entry_counter++;
							id_counter++;
						}
						hover_behavior();
					});
				}

				function hover_behavior() {
					$('.entry').mouseenter(function(evt){
						this_text = $(this).text();
						document.getElementById('hovered_item').innerHTML = this_text;
						$(this).css('background', '#999900');
						// remove currently displayed items
						$(this).attr('class', 'savedentry');
						saved_left = $(this).css('left');
						saved_left = saved_left.replace('px', '');
						saved_top = $(this).css('top');
						saved_top = saved_top.replace('px', '');
						entries_to_remove = $('#overlay div');
						for(counter2 = 0; counter2 < entries_to_remove.length; counter2++) {
							div_class = entries_to_remove[counter2].className;
							if(div_class.indexOf('entry') == 0) {
								entries_to_remove[counter2].remove();
							}
						}
						$(this).attr('class', 'entry');
						if(this_text == '..') {
							// go up one folder
							path_string = document.getElementById('path').innerHTML;
							parser_counter = path_string.length - 2; // skip the last slash
							while(parser_counter > 0 && path_string[parser_counter] !== ':') {
								if(path_string[parser_counter] == '/' || path_string[parser_counter] == '\\') {
									break;
								}
								parser_counter--;
							}
							document.getElementById('path').innerHTML = path_string.substr(0, parser_counter + 1);
							zoom(document.getElementById('path').innerHTML, parseInt(saved_left) + 100, parseInt(saved_top));
						} else {
							$.post('file_or_folder_forjs.php', { 'full_path': document.getElementById('path').innerHTML + this_text}, function(result){
								// if this is a folder, get the entries inside
								document.getElementById('path').innerHTML += '\\' + this_text;
								if(result == 'folder') {
									zoom(document.getElementById('path').innerHTML, parseInt(saved_left) + 100, parseInt(saved_top));
								} else {
									//document.getElementById('path').innerHTML += this_text;
									drive_counter = 0;
									while(drive_counter < fixed_drives_array.length) {
										drived_full_path = fixed_drives_array[drive_counter] + ':\\' + document.getElementById('path').innerHTML;
										$.post('file_exists_forjs.php', { 'full_path': drived_full_path}, function(exists_result){
											//alert(exists_result);
											if(exists_result != false) {
												window.location = 'do.php?action=open_file&path=' + exists_result + '&fixed_drives=' + fixed_drives_string + '<?php print($file_operations_string); ?>';
											}
										});
										drive_counter++;
									}
								}
							});
						}
					});
					$('.entry').mouseleave(function(evt){
						document.getElementById('hovered_item').innerHTML = '';
						$(this).css('background', '#ffffff');
					});
					// these touch events are not working
					$('.entry').touchenter(function(evt){
						this_text = $(this).text();
						document.getElementById('hovered_item').innerHTML = this_text;
						$(this).css('background', '#999900');
						// remove currently displayed items
						$(this).attr('class', 'savedentry');
						saved_left = $(this).css('left');
						saved_left = saved_left.replace('px', '');
						saved_top = $(this).css('top');
						saved_top = saved_top.replace('px', '');
						entries_to_remove = $('#overlay div');
						for(counter2 = 0; counter2 < entries_to_remove.length; counter2++) {
							div_class = entries_to_remove[counter2].className;
							//alert('div_class: ' + div_class);
							if(div_class.indexOf('entry') == 0) {
								entries_to_remove[counter2].remove();
							}
						}
						$(this).attr('class', 'entry');
						if(this_text == '..') {
							// go up one folder
							path_string = document.getElementById('path').innerHTML;
							parser_counter = path_string.length - 2; // skip the last slash
							while(parser_counter > 0 && path_string[parser_counter] !== ':') {
								if(path_string[parser_counter] == '/') {
									break;
								}
								parser_counter--;
							}
							document.getElementById('path').innerHTML = path_string.substr(0, parser_counter + 1);
							zoom(document.getElementById('path').innerHTML, parseInt(saved_left) + 100, parseInt(saved_top));
						} else {
							$.post('file_or_folder_forjs.php', { 'full_path': document.getElementById('path').innerHTML + this_text}, function(result){
								// if this is a folder, get the entries inside
								if(result == 'folder') {
									document.getElementById('path').innerHTML += this_text + '/';
									zoom(document.getElementById('path').innerHTML, parseInt(saved_left) + 100, parseInt(saved_top));
								} else {
									document.getElementById('path').innerHTML += this_text;
								}
							});
						}
					});
					$('.entry').touchleave(function(evt){
						document.getElementById('hovered_item').innerHTML = '';
						$(this).css('background', '#ffffff');
					});
				}
				// also fading of directories...
				
				var mouseisdown = false
				var touchisdown = false
				$($.gameQuery.playground).mousemove(function(evt){
					document.getElementById("mouse_coordinates").innerHTML = $.gameQuery.mouseTracker.x + ', ' + Math.round($.gameQuery.mouseTracker.y);
					evt.preventDefault()
				});
				$($.gameQuery.playground).mousedown(function(evt){
					mouseisdown = true
					document.getElementById("mouseisdown").innerHTML = 'true';
					document.getElementById("mouse_click_coordinates").innerHTML = $.gameQuery.mouseTracker.x + ', ' + Math.round($.gameQuery.mouseTracker.y);
					evt.preventDefault()
				});
				$($.gameQuery.playground).mouseup(function(evt){
					mouseisdown = false
					document.getElementById("mouseisdown").innerHTML = 'false';
					document.getElementById("mouse_release_coordinates").innerHTML = $.gameQuery.mouseTracker.x + ', ' + Math.round($.gameQuery.mouseTracker.y);
					document.getElementById("mouse_jump").innerHTML = ($.gameQuery.mouseTracker.x - (PLAYGROUND_WIDTH / 2)) + ', ' + (Math.round($.gameQuery.mouseTracker.y) - (PLAYGROUND_HEIGHT / 2));
					evt.preventDefault()
				});
				
				$($.gameQuery.playground).touchmove(function(evt){
					document.getElementById("touch_coordinates").innerHTML = $.gameQuery.touchTracker.x + ', ' + Math.round($.gameQuery.touchTracker.y);
					evt.preventDefault()
				});
				$($.gameQuery.playground).touchstart(function(evt){
					touchisdown = true
					document.getElementById("touchisdown").innerHTML = 'true';
					document.getElementById("touch_start_coordinates").innerHTML = $.gameQuery.touchTracker.x + ', ' + Math.round($.gameQuery.touchTracker.y);
					evt.preventDefault() // to avoid the the mouse event at the end of a touch sequence
				});
				$($.gameQuery.playground).touchend(function(evt){
					touchisdown = false
					document.getElementById("touchisdown").innerHTML = 'false';
					document.getElementById("touch_end_coordinates").innerHTML = $.gameQuery.touchTracker.x + ', ' + Math.round($.gameQuery.touchTracker.y);
					document.getElementById("touch_jump").innerHTML = ($.gameQuery.touchTracker.x - (PLAYGROUND_WIDTH / 2)) + ', ' + (Math.round($.gameQuery.touchTracker.y) - (PLAYGROUND_HEIGHT / 2));
					evt.preventDefault()
				});

                $.playground().startGame();
            });
        </script>
	
    </head>
    <body style="background: #CCCCCC;">
        <div style="display: none;">
		<h2>bigo fracto</h2>
		<p>clicking away from center should take the view in the direction clicked</p>
		<p>notice the difference that touch can get to difference touchstart or touchend coordinates without updating touchmove while mouse cannot get to new mousedown and nouseup coordinates without updating mousemove</p>
		<p>touch is harder to control. should accel scale with playground size?</p>
		<p>would like to dampen acceleration if we are past a point with no further possible navigation: not bad</p>
		<!--p>w,a,s,d moves player 1 (purple). i,j,k,l moves player 2 (orange).</p-->
        <p style="float: left; width: 350px;">Mouse (mousemove) coordinates: <span id="mouse_coordinates"></span><br>
		Mouse press (mousedown) coordinates: <span id="mouse_click_coordinates"></span><br>
		Mouse release (mouseup) coordinates: <span id="mouse_release_coordinates"></span><br>
		Mouse jump: <span id="mouse_jump"></span><br>
		mouseisdown: <span id="mouseisdown"></span></p>
		<p style="float: left; width: 350px;">Touch (touchmove) coordinates: <span id="touch_coordinates"></span><br>
		Touch press (touchstart) coordinates: <span id="touch_start_coordinates"></span><br>
		Touch release (touchend) coordinates: <span id="touch_end_coordinates"></span><br>
		Touch jump: <span id="touch_jump"></span><br>
		touchisdown: <span id="touchisdown"></span></p>
		<div style="clear: both;"></div>
		<p>Hovered item: <span id="hovered_item"></span></p>
		</div>
		<!--p>Path: <span id="path"><?php print(str_replace('\\', '/', $path)); ?></span></p-->
		<p>Path: <span id="path"><?php print($path); ?></span></p>
		<div style="clear: both;"></div>
		<div id="playground" style="background: white"></div>
    </body>
</html>