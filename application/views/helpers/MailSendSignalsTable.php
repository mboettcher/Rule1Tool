<?php
class View_Helper_MailSendSignalsTable extends Zend_View_Helper_Abstract
{
	public function mailSendSignalsTable($content)
	{	
		return '<table cellspacing=1 border=0 width="100%" >
<thead>
<tr>
	<th style="text-align:left;	border-bottom:1px solid #333;">'.$this->view->translate("Aktie").'</th>
	<th style="text-align:left;	border-bottom:1px solid #333;">'.$this->view->translate("Kurs").'</th>
	<th style="text-align:left;	border-bottom:1px solid #333;">'.$this->view->translate("Änderung").'</th>
	<th style="text-align:left;	border-bottom:1px solid #333;">'.$this->view->translate("MOS").'</th>
	<th style="text-align:left;	border-bottom:1px solid #333;"></th>
	<td style="text-align:left;	border-bottom:1px solid #333;"></td>
</tr>
</thead>
<tbody>'
		.$content.'
</tbody>
</table>';
	}
}