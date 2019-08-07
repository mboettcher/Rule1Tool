function createConfirmDialog(question, title, yestext, notext, yescallback, nocallback)
{
	//Ruft abhängig von der Browserumgebung einen passenden Modal auf
	if(layoutEnviroment == "mobile")
	{
		if(confirm(question))
		{
			//YES
			if(yescallback != null)
			{
				if(typeof yescallback == "function")
				{
					yescallback();
				}
				else if(typeof window[yescallback] == "function")
				{
					window[yescallback]();
				}
				else
				{
					//FEHLER
				}
			}
		}
		else
		{
			//NO
			if(nocallback != null)
			{
				if(typeof nocallback == "function")
				{
					nocallback();
				}
				else if(typeof window[nocallback] == "function")
				{
					window[nocallback]();
				}
				else
				{
					//FEHLER
				}
			}
			
		}	
	}
	else if(layoutEnviroment == "standard")
	{
		//Buttons müssen vorher definiert werden, damit Bezeichner aus Variablen kommen können
		var buttons = {};
		//YES
		buttons[yestext] = function() {
			$(this).dialog('destroy'); //nicht nur schließen sondern komplett entfernen, da sonst probleme bei mehrfachen aufruf
			if(yescallback != null)
			{
				if(typeof yescallback == "function")
				{
					yescallback();
				}
				else if(typeof window[yescallback] == "function")
				{
					window[yescallback]();
				}
				else
				{
					//FEHLER
				}
			}
		};
		//NO
		buttons[notext] = function() {
			$(this).dialog('close'); 
			//NO
			if(nocallback != null)
			{
				if(typeof nocallback == "function")
				{
					nocallback();
				}
				else if(typeof window[nocallback] == "function")
				{
					window[nocallback]();
				}
				else
				{
					//FEHLER
				}
			}
		}; 
		
		//var $dialog = $('<div id="dialog"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+question+'</p></div>');
		
		$("#dialog").dialog('destroy');
		$("#dialog").html("<p>"+question+"</p>").dialog({
			bgiframe: true,
			resizable: false,
			modal: true,
			title: title,
			width: 300,
			show:'clip',
			hide:'clip',
			closeOnEscape: true,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.7
			},
			buttons: buttons
		}).dialog("open");
		
	}
}
