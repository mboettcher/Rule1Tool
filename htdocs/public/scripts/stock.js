$(document).ready(function() {
	comments_company = $('#stock_comments_company');
	comments_analysis = $('#stock_comments_analysis');	
	stockComHeadPf1D = $('#stock_comment_head_pf_1_down');
	stockComHeadPf1L = $('#stock_comment_head_pf_1_left');
	stockComHeadPf2D = $('#stock_comment_head_pf_2_down');
	stockComHeadPf2L = $('#stock_comment_head_pf_2_left');	
	myVerticalSlide = $("div#stock_comment_body");
	stockCommentHeader = $('#stock_comment_header')
	
	myVerticalSlide.hide();
	comments_company.hide();
	comments_analysis.hide();

	commentBoxCompanyToggleInit();
	commentBoxAnalysisToggleInit();
	commentNewCompanyInit();
	commentNewAnalysisInit();
	
	addToWatchlist();
	
	newsShowAllInnerfadeToggle();
	
});
function newsShowAllInnerfadeToggle()
{
	$("#yahooNewsToggle").click(function(event)
			{
				var ele = $(".yahooNewsBox .inner");
				
				if(ele.height() == 80)
				{
					ele.scrollTop(0); //verhindern, das text aus dem Newsbereich rutscht
					//close
					ele.animate({ 
				        height: "22px"
				      }, 300, null, function()
				      {
				    	 

				    	  ele.css("overflow-y", "hidden");
				       } );
					$('#yahooNewsResults').innerfade(
							{ 
								timeout: 5000
							});
				}
				else
				{
					//show all
					//ele.height(80);
					ele.animate({ 
				        height: "80px"
				      }, 300, null, function(){ele.css("overflow-y", "scroll");} );

					
					var elements = $('#yahooNewsResults').children();
					
				    $('#yahooNewsResults').removeAttr("style").removeClass("innerfade");
				    for (var i = 0; i < elements.length; i++) {
				        $(elements[i]).removeAttr("style").show();
				    };
				}
					
			});
}
function addToWatchlist()
{
	$("#stockAddToWatchlist").click(function(event)
		{
			showLoading();
			$.post($("#stockAddToWatchlist").attr("href"), 
				{ },
				  function(data){
					
					$.jGrowl(messagesToString(data.messages), {life:5000, header:"AddToWatchlist"});
					
					hideLoading();
					
				  }, "json");
			

			event.preventDefault(); //Verhindern, dass der Link ausgefuehrt wird
		}
	);
}

function messagesToString(messages)
{
	var message = "";
	for(var i=0;i<messages.length;i++)
	{
		if(i!=0)
			message += " ";
		message += messages[i]["msg"];
	}
	
	return message;
}
function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}
function commentNewCompanyInit()
{
	$("input#stock_new_c_comment_send").click(function()
			{
				$("#stock_comments_company .stockNewCommentBox").hide();
				showLoading();
				$.post(baseUrl+"/groups/create-reply", 
					{ text: $("#stock_new_c_comment_text").val(), thread_id: $("#stock_comment_c_thread_id").val() },
					  function(data){
						if(data.success == true)
						{
							$("#stockCommentsCompany").prepend(data.reply).children(":first")
							.hide().css({'background-color' : '#ffa200'}).show()
							.animate({backgroundColor: '#FFFFFF'}, { queue:false, duration:3000 } )
							.children().hide()
							.fadeIn(3000);

						}
						
						$.jGrowl(messagesToString(data.messages), {life:5000});
						
						hideLoading();
						
					  }, "json");
				
			});
}
function commentNewAnalysisInit()
{
	$("input#stock_new_a_comment_send").click(function()
			{
				$("#stock_comments_analysis .stockNewCommentBox").hide();
				showLoading();
				$.post(baseUrl+"/groups/create-reply", 
					{ text: $("#stock_new_a_comment_text").val(), thread_id: $("#stock_comment_a_thread_id").val() },
					  function(data){
						if(data.success == true)
						{
							$("#stockCommentsAnalysis").prepend(data.reply).children(":first")
							.hide().css({'background-color' : '#ffa200'}).show()
							.animate({backgroundColor: '#FFFFFF'}, { queue:false, duration:3000 } )
							.children().hide()
							.fadeIn(3000);

						}

						$.jGrowl(messagesToString(data.messages), {life:5000});
						
						hideLoading();
						
					  }, "json");
				
			});
}
function commentBoxAnalysisToggleInit()
{
	$("div#stock_comments_analysis_toggle").click(function(){		
		commentBoxAnalysisToggle();
	});
}
function commentBoxAnalysisToggle()
{
	if($(myVerticalSlide).css("display") == "none")
	{
		comments_company.hide();
		comments_analysis.show();
		
		$(stockCommentHeader).removeClass().addClass('head head3');
	
		myVerticalSlide.slideToggle("normal");
		
		stockComHeadPf2L.hide();
		stockComHeadPf2D.show();

	}
	else
	{
		if(comments_analysis.css('display') != "none")
		{
				//SlideIN
					myVerticalSlide.slideToggle("normal", function(){ 
						$(stockCommentHeader).removeClass().addClass('head head1');

						comments_company.hide();
						comments_analysis.hide();
						
						stockComHeadPf2L.show();
						stockComHeadPf2D.hide();
				});

		}
		else
		{
			//ReiterWechsel
			comments_company.hide();
			comments_analysis.show();
			
			stockComHeadPf2L.hide();
			stockComHeadPf2D.show();
			stockComHeadPf1L.show();
			stockComHeadPf1D.hide();
			
			$(stockCommentHeader).removeClass().addClass('head head3');
		}
	}
}

function commentBoxCompanyToggleInit()
{
	$("div#stock_comments_company_toggle").click(function(){
		commentBoxCompanyToggle();
	});
}
function commentBoxCompanyToggle()
{
	if($(myVerticalSlide).css("display") == "none")
	{
		comments_company.show();
		comments_analysis.hide();
		
		$(stockCommentHeader).removeClass().addClass('head head2');
	
		myVerticalSlide.slideToggle("normal");
		
		stockComHeadPf1L.hide();
		stockComHeadPf1D.show();

	}
	else
	{
		if(comments_company.css('display') != "none")
		{
				//SlideIN
					myVerticalSlide.slideToggle("normal", function(){ 
						$(stockCommentHeader).removeClass().addClass('head head1');

						comments_company.hide();
						comments_analysis.hide();
						
						stockComHeadPf1L.show();
						stockComHeadPf1D.hide();
				});

		}
		else
		{
			//ReiterWechsel
			comments_company.show();
			comments_analysis.hide();
			
			stockComHeadPf1L.hide();
			stockComHeadPf1D.show();
			stockComHeadPf2L.show();
			stockComHeadPf2D.hide();
			
			$(stockCommentHeader).removeClass().addClass('head head2');
		}
	}
}

/*Yahoo Search URL
 * When you click the submit button, the query in the input box is passed to this
 * function. It creates a URL including the query, output and callback(what will
 * run once the response is recieved).
 */
function searchYahoo(query, language){
	var url="http://api.search.yahoo.com/NewsSearchService/V1/newsSearch?";
	url+= "appid=0rYuMKXV34Fql6Nl0261BNndehWwUgCRYpSTQ6CNeyNCnEA1cdFYnkxQ26go";
	url+= "&query=" +escape(query);
	url+= "&output=json";
	url+= "&callback=parseResponse";
	url+= "&language=" + language;
	getScript(url);
}
/*Generate Script Tag
 * The URL that was created above is passed to this function. It creates a script
 * tag that is then inserted into the page. This allows you to pull data from external
 * domains.
 */
function getScript(url){
	var scriptTag = document.createElement("script");
	scriptTag.setAttribute("type", "text/javascript");
	scriptTag.setAttribute("src", url);
	document.getElementsByTagName("head")[0].appendChild(scriptTag);
}
/*The callback function
 * A query has been sent to the server, the file has been generated with the results
 * is inserted into the header of the page. This callback function is run and extracts
 * the info you want and inserts it into the document using the DOM. Note the data
 * variable which contains the information to be formatted.
 */
function parseResponse(data) {
	var results = document.getElementById("yahooNewsResults");
	 
	while(results.hasChildNodes()){
		results.removeChild(results.lastChild);
	}
	
	var num = data.ResultSet.Result.length;
	if(num > 10)
		num = 10;
	
	for(i=0;i<num;i++){
		var yhTitle = data.ResultSet.Result[i].Title;
		var yhUrl = data.ResultSet.Result[i].Url;
		 
		var theHeader = document.createElement("li");
		
		var theUrl = document.createElement("a");
		theUrl.setAttribute("href", yhUrl);
		theUrl.setAttribute("target", "_blank");
		var theHeaderText = document.createTextNode(yhTitle);
		theUrl.appendChild(theHeaderText);
		
		theHeader.appendChild(theUrl);
		
		results.appendChild(theHeader);	
	}
	$('#yahooNewsResults').innerfade(
			{ 
				timeout: 5000
			}); 
	
}
function getSingleQuoteForTransaction(cid, pid, url)
{
	showLoading();
	$.getJSON(url, 
			{"CID":cid,"PID":pid},  
			function(data){	
				if(data.currency)
				{
					$("#price").val(data.closeNumber);
					$("#date").val(data.date);
					$(".depotTradeAdd .currency").text("in "+data.currency);
				}
				else
				{
					$("#price").val(0);
				}
				
				hideLoading();
			}
			);
}
function initTransaction(url1, type)
{
	$(document).ready(function() {
		if($("#company_id").val() && type != "edit")
			getSingleQuoteForTransaction($("#company_id").val(), $("#portfolio_id").val(), url1);
		
		$("select.flexselect").flexselect({onPicked: function(){	
			getSingleQuoteForTransaction($("#company_id").val(), $("#portfolio_id").val(), url1);		 
}});
		$(".buysell."+$("#type").val()).addClass("activ");
		//SELL/Buy Button initialisieren
		$(".buysell").click(function()
				{
			$(".buysell.activ").removeClass("activ");
			$(this).addClass("activ");
			
			//hiddenInput setzen
			if($(this).hasClass("buy"))
				$("#type").val("buy");
			else if($(this).hasClass("sell"))
				$("#type").val("sell");
				});
		


		$(function() {
			$("#date").datepicker({ dateFormat: 'yy-mm-dd', zIndex: 1000 });
		});
	});
}
function showTransactionWindow(ele, type, title, url)
{
	showLoading();
	
	$('#dialog').load($(ele).attr("href")+' #contentWrapForAjax', function()
		{		
		initTransaction(url, type);

		$("#dialog").dialog('destroy');
		$("#dialog")
			.dialog({
				bgiframe: true,
				stack: false,
				zIndex: 1,
				title: title,
				width: 225,
				height: 345,
				//modal:true,
				overlay: {
					backgroundColor: '#000',
					opacity: 0.7
				},
				show:'clip',
				hide:'clip'
				})
			.dialog("open");
		hideLoading();
		}
	);	
}
function loadChartData(url, days, callback)
{
	showLoading();
	$.getJSON(url, 
			{"PERIOD":days},  
			function(data){	
				if(data.charts)
				{
					printCharts(data.charts);
					
				}
				hideLoading();
				if(callback)
					callback();
			}
			);

}
function printCharts(urls)
{
	html = "";
	for (var _key in urls){
		html += '<img src="'+imgurl+urls[_key]+'"/>';
	}
	
	$(".stockChartsBody").hide()
	.html(html);
	$(".stockChartsBody").fadeIn(1000);
}
