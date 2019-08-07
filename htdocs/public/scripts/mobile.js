
addEventListener("load", function(event)
  {
   setTimeout(function(){window.scrollTo(0, 1);}, 100);
  }, false);
  

  $(document).ready(function() {
	  $(".tablesorter").tablesorter(); 
	  
  	//$.iPhone.hideURLbar(); // This will hide the URL bar when the page first appears.
  	//window.scrollTo(0, 1);
  	numberMenuItems = getNumberMenuItems();
  	currentMenuItem = getActiveMenuItem();
  	$("#headerMenu ul li").each(function()
  			{
  				$(this).hide();
  			});
  	$("#headerMenu ul li:eq("+currentMenuItem+")").show();
  	
  	$("#headerMenuScrollRight").click(function(){
  		
  		var currItem = $("#headerMenu ul li:eq("+currentMenuItem+")");
  		currItem.animate({marginLeft: "-133px"}, 200, "linear", function()
  	      	{
  	    	  currItem.hide();
  	    	  currItem.css("marginLeft", "0px")
  	    	  
  	    	  if(currentMenuItem+1 == numberMenuItems)
  	  			currentMenuItem = 0;
  	  		  else
  	  			currentMenuItem++;

  	    	  var nextItem = $("#headerMenu ul li:eq("+currentMenuItem+")");
  	    	  nextItem.css("marginLeft", "133px");
  	    	  nextItem.show();
  	    	  nextItem.animate({marginLeft: "0px"},200);
  		    } );
  		});

  	$("#headerMenuScrollLeft").click(function(){
  		
  		var currItem = $("#headerMenu ul li:eq("+currentMenuItem+")");
  		currItem.animate({marginLeft: "133px"}, 200, "linear", function()
  	      	{
  	    	  currItem.hide();
  	    	  currItem.css("marginLeft", "0px")
  	    	  
  	    	  if(currentMenuItem == 0)
  				currentMenuItem = numberMenuItems-1;
  			  else
  				currentMenuItem--;

  	    	  var nextItem = $("#headerMenu ul li:eq("+currentMenuItem+")");
  	    	  nextItem.css("marginLeft", "-133px");
  	    	  nextItem.show();
  	    	  nextItem.animate({marginLeft: "0px"},200);
  		    } );
  		});


  	$("#headerLogOut").click(function(event)
  		{
  			createConfirmDialog("Wirklich abmelden?", 
  					"Abmelden", 
  					null, 
  					null, 
  					null, 
  					function(){event.preventDefault(); /* Linkklick verhindern */});
  		});
  	
  });

  function getNumberMenuItems()
  {
  	return $("#headerMenu ul li").length;
  }
  function getActiveMenuItem()
  {
  	n = $("#headerMenu ul li").index($("#headerMenu ul li.active"));
  	if(n < 0)//wenn nicht gefunden
  		n = 0;
  	return n;
  }
