<?php
error_reporting(E_ALL|E_STRICT);
date_default_timezone_set('Europe/Berlin');
set_include_path('.' . PATH_SEPARATOR . '../library'
. PATH_SEPARATOR . '../application/models/'
. PATH_SEPARATOR . '../library/Custom/'
. PATH_SEPARATOR . '../application/library/'
. PATH_SEPARATOR . '../application/'
. PATH_SEPARATOR . get_include_path());

try
{
	include "Zend/Loader.php";
	require_once 'Rule1Tool.php';	
	$rule1tool = new Rule1Tool();
	$rule1tool->run();
} catch (Exception $exception) {
    include("../application/views/scripts/error/bootstrap_error.php");
}


