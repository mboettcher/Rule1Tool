<?php
class Plugin_Profiler extends Zend_Controller_Plugin_Abstract
{
	protected $time_start;
	protected $time_end;	
	
	public function __construct()
	{
	
	}
	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
		$this->time_start = microtime(true);
	}
	public function dispatchLoopShutdown()
	{
		$this->time_end = microtime(true);
		
		$this->writeExecutionTime();
	}
	protected function writeExecutionTime()
	{
		$time = $this->time_end - $this->time_start;
		$uri = $_SERVER["REQUEST_URI"];
//echo $time;
		$tbl = new LogProfilerModel();
		$data = array("time_execution" => $time,
						"uri" => $uri
		);
		$tbl->insert($data);
		
	}
	
}