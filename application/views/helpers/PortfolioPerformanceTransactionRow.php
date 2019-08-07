<?php
class View_Helper_PortfolioPerformanceTransactionRow extends Zend_View_Helper_Abstract
{
	public function portfolioPerformanceTransactionRow($transaction, $currency)
	{
		if(!isset($transaction["type"])) 
			$transaction["type"] = "DEPOT";	
		
		$date = new Zend_Date($transaction["date"]);
		
		if(isset($transaction["quotesObject"]))
		{
			
			$signals = $transaction["quotesObject"]->getIndikatorSignal($this->view->user);
			
			$signalsPrint = 
			$this->view->indikatorSignalDiv($signals["SMA"][0]["name"], $signals["SMA"][0]["lastSignalDateDates"], $signals["SMA"][0]["lastSignal"])
			 .$this->view->indikatorSignalDiv($signals["MACD"][0]["name"], $signals["MACD"][0]["lastSignalDateDates"], $signals["MACD"][0]["lastSignal"])
			 .$this->view->indikatorSignalDiv($signals["STO"][0]["name"], $signals["STO"][0]["lastSignalDateDates"], $signals["STO"][0]["lastSignal"])
			 ;
	
		}
		else 
		{
			$signalsPrint = "";
		}
		if($transaction["type"] == "DEPOT")
		{
			if($this->view->user->getUserId() == Zend_Registry::get("UserObject")->getUserId())
			{
				$salelink = "<br/>".$this->view->link(
				$this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(),
				'username' => $this->view->user->getNickname(),
				'PID' => $this->view->portfolio->getId(),
				'CID' => $transaction["company_id"],
				'anzahl_def' => $transaction["anzahl"],
				'type_def' => "sell"),
			 "user_portfolio_transaction_add"), $this->view->translate("Verkaufen &raquo;"),"abutton darkgrey small addTransaction");
				
			}
			else 
				$salelink = "";
			$editlink = "";
			$delete = "";
		}
		else 
		{
			$salelink = "";
			if($this->view->user->getUserId() == Zend_Registry::get("UserObject")->getUserId())
			{
				$editlink = "".$this->view->link(
				$this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(),
				'username' => $this->view->user->getNickname(),
				'PID' => $this->view->portfolio->getId(),
				'TID' => $transaction["tid"]),
			 "user_portfolio_transaction_edit"), $this->view->translate("Bearbeiten &raquo;"),"abutton grey small editTransaction");
				
				$delete = $this->view->link($this->view->url(array(
										"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
										"username" => $this->view->user->getNickname(),
										"PID" => $this->view->portfolio->getId(),
										"TID" => $transaction["tid"]
												), "user_portfolio_transaction_remove"), $this->view->image("closeDeleteIcon_02.png", $this->view->translate("entfernen")), "watchlistStockRemove");
				
			}
			else
			{
				$editlink = "";
				$delete = "";
			}
				

				
		}
		
		
		if(isset($transaction["integrity"]) && $transaction["integrity"] == false)
		{
			$integrityErrorClass = "integrityError";
			$integrityErrorTooltip = "Diese Transaktion ist nicht plausibel.";	
		}
		else 
		{
			$integrityErrorClass = "";
			$integrityErrorTooltip = "";
		}
		
		if(!isset($transaction["wert"])) $transaction["wert"] = "";	
		if(isset($transaction["ertragWert"])) 
		{
			if($transaction["ertragProzent"])
				$ertrag = $this->view->toNumber($transaction["ertragWert"],2, true)
					.' '
					.$currency
					.'<br/><small>'
					.$this->view->toNumber($transaction["ertragProzent"]*100,2, true)
					.' %</small>';
			else
				$ertrag = $this->view->toNumber($transaction["ertragWert"],2, true)
					.' '
					.$currency;
		}
		else
			$ertrag = "";
			
		if(isset($transaction["einstandsWert"]))	
			$einstandsWert = '<br/><span class="small">'.$this->view->toNumber($transaction["einstandsWert"],2).' '.$currency.'</span>';
		else
			$einstandsWert = "";
			
		if(isset($transaction["quotesObject"]) && $transaction["companyMainMarketId"])
			{
				$chart = $this->view->link($this->view->url(array(
											"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
											"CID" => $transaction["company_id"]
													), "api_charturls"), 
													$this->view->image("chartIcon.png", "Chart"), "watchlistShowChartBtn");
			}
			else 
				$chart = "";
			
				
		return '
		<tr class="'.$integrityErrorClass.'" title="'.$integrityErrorTooltip.'">
			<td class="'.$transaction["type"].'" title="'.$transaction["type"].'"></td>
			<td>'.$date->get("dd.MM.yyyy").'</td>
			<td>'.$this->view->link(
								$this->view->url(array(
											"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
											"isin" => $transaction["companyIsin"]
										), "stock"),
										$transaction["companyName"].'<br/><small>'.$transaction["companyIsin"].'</small>').'</td>
			<td class="nowrap">'.$this->view->toNumber($transaction["price"],2).' '.$currency.'<br/><small>'.$transaction["anzahl"].' St.</small></td>
			<td class="nowrap">'.$this->view->toNumber($transaction["aktienWert"],2).' '.$currency.'<br/><small>'.$this->view->toNumber($transaction["gebuehren"],2).' '.$currency.'</small></td>
			<td class="nowrap">'.$this->view->toNumber($transaction["gesamtWert"],2).' '.$currency.$einstandsWert.'</td>
			<td class="nowrap">'.$ertrag.'</td>
			<td>'.$signalsPrint.$salelink.$editlink.'</td>
			<td>'.$chart.$delete.'</td>
		</tr>
		';
		
	}
}