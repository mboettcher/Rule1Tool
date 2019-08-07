<?php
ini_set('display_errors',0); //Keine Fehler ausgeben
if(isset($_REQUEST["key"]) && isset($_REQUEST["url"]) && $_REQUEST["key"] == "1bd81d8a271f16531dab718de30b82e2")
{
	$file = str_replace(" ", "+", $_REQUEST["url"]);
	
	//if(is_readable($file))
		$output = readfile ($file);
	//else
		//$output = false;
		
	if($output)
		echo $output;
	else
		header("HTTP/1.1 404 Not Found");
}
else
	header("HTTP/1.1 404 Not Found");
?>