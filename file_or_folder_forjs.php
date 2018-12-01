<?php

$full_path = $_REQUEST['full_path'];
//print('$full_path: ');var_dump($full_path);exit(0);
if(is_dir($full_path)) {
	print('folder');
} elseif(is_file($full_path)) {
	print('file');
} else { // not recognized as file or folder... then we have to work pretty hard to guess (usually due to strange characters)
	//print('$full_path of unknown: ');var_dump($full_path);
	if(strpos($full_path, '.') === false) {
		print('folder');
	} elseif(strpos(file_extension($full_path), '?') !== false) {
		print('folder');
	} else {
		print('file');
	}
}

function file_extension($string) {
	if(strpos($string, '.') === false || strpos_last($string, '.') < strpos_last($string, DIRECTORY_SEPARATOR)) {
		return false;
	}
	$file_extension = substr($string, strpos_last($string, '.'));
	if(strpos($file_extension, ' ') !== false || strpos($file_extension, '[') !== false || strpos($file_extension, ']') !== false || strpos($file_extension, '(') !== false || strpos($file_extension, ')') !== false || strpos($file_extension, '-') !== false) { // should we use preg_match?
		return false;
	}
	return $file_extension;
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

?>