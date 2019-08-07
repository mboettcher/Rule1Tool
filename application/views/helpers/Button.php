<?php
class View_Helper_Button extends Zend_View_Helper_Abstract
{
	public function button($text, $url, $attr = null)
	{
		$attributs = "";
		if(is_array($attr))
		{
			foreach ($attr as $attribute => $value)
				$attributs .= ' '.$attribute.'="'.$value.'"'; 
		}
		$button = '<div'.$attributs.'>
				<a href="'.$url.'">
					<div class="button_75">
						<p class="inner">'.$text.'</p>
					</div>
				</a>
				</div>';
		return $button;
	}
}