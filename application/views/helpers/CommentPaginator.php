<?php
class View_Helper_CommentPaginator extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt eine KommentarBox zurück
	 *
	 * @param Zend_Paginator Paginator
	 * @return STRING
	 */
	public function commentPaginator($paginator, $type)
	{
		$retrn = "";
		if (count($paginator))
		{
			foreach($paginator as $item)
			{
				$retrn .= $this->view->printComment($item);
			}
		}
		if($type == "company")
			$path = 'stocks/paginatorCommentsCompany.phtml';
		else 	
			$path = 'stocks/paginatorCommentsAnalysis.phtml';
		$retrn .= $this->view->paginationControl($paginator,
	                             'Sliding',
	                             $path);
		
		return $retrn;
	}
}
