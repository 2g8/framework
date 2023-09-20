<?php
$patterns = array_map(function ($path){return APP_PATH . $path;}, explode(',', $_SERVER['QUERY_STRING']));
$filename_lists = array_map('glob', $patterns);

$files = array();

foreach( $filename_lists as $filename_list )
	$files = array_merge($files, $filename_list);

	
foreach ( $files as &$file )
	$file = filemtime($file);
	
header('Last-Modified: '. date('r', @max($files)));
?>
