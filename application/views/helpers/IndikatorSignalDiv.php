<?php
class View_Helper_IndikatorSignalDiv extends Zend_View_Helper_Abstract
{
	public function indikatorSignalDiv($name, $date, $signal, $type = "site", $signalHighlight = false)
	{
		$title = $name.' '.$this->view->translate("vom").' '.$date;
		$img = "";
		if(!isset($signal))
		{
			$class = "none";
			$title = $this->view->translate("Kein Signal verfügbar.");
			if ($type == "mail")
				$img = $this->view->image('indikatorSignalPointNone.png', $class, $title, null, 0, 14, 13);
		}
		elseif($signal == "b")
		{
			$class = "buy";
			if ($type == "mail")
				$img = $this->view->image('indikatorSignalPointGreen.png', $class, $title, null, 0, 14, 13);
		}
		elseif($signal == "s")
		{
			$class = "sell";
			if ($type == "mail")
				$img = $this->view->image('indikatorSignalPointRed.png', $class, $title, null, 0, 14, 13);
		}
		
		if ($type == "mail")
		{
			if($signalHighlight)
			{
				return '<div style="background-color:#ffa200;float:left;height:13px;">'.$img.'</div>';
			}
			else 
				return '<div style="float:left;">'.$img.'</div>';
		}

		
		return '<div title="'.$title.'" class="indikatorSignal '.$class.'">'.$img.'</div>';
	}
}