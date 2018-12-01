<?php

$full_path = $_REQUEST['full_path'];
if(is_file($full_path)) {
	print($full_path);
}
return false;

?>