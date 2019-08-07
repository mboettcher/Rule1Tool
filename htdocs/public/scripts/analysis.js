$(document).ready(function() {
	addEventHandlerAnalysisShow();
	addEventHandlerAnalysisCalculation();
});

var calcColorHighlight = "#ffa200";
var calcColorNonHighlight = "#FFFFFF";
var calcColorHeadline = "#f4f4f4";

function addEventHandlerAnalysisShow()
{
	$("#analysis_head_analysisauswahl table tr td.star").click(
			function () { 
				var el = $(this);//Element sichern für Funktion
				var url = baseUrl+"/analysis/set-favourite";
				showLoading();
				$.getJSON(url, 
						{"CID":$(this).attr("CID"), "AID":$(this).attr("AID"),"UID":$(this).attr("UID")},  
						function(data){		
							
							if(data.success == true)
							{
								//Stern umsetzen
								//alle on's ins off's umwandeln
								$("#analysis_head_analysisauswahl table td.star").removeClass("on").addClass("off");
								//on beim aktuellen setzen
								el.removeClass("off").addClass("on");
								
								//Parent-Seite aktualisieren
								isin = $("#isin h2", parent.document.body).text();
								//Rule1Info holen
								
								$("#rule1_info", parent.document.body).load(baseUrl+"/stock/"+isin+" #rule1_info", 
											null, function(){
								
									//AnalyseThread holen
									$("#stock_comments_analysis", parent.document.body).load(baseUrl+"/stock/"+isin+" #stock_comments_analysis", 
											null, function(){
										//Thickbox neu initialisieren
										parent.window.tb_init('#analysisShowFrameButton');//pass where to apply thickbox
										

									});
								});
									
							}
							else
							{
								//FEHLER!
								
							}
							$.jGrowl(messagesToString(data.messages), {life:5000});
							hideLoading();
						}
				);
			});
	
}

function addEventHandlerAnalysisCalculation()
{
	$("#analysis_refresh").click(function (event) { 
	
		data = {CID: $("#analysis_company_id").val(), 
				AID: $("#analysis_analysis_id").val(), 
				my_estimated_growth_testvalue: $("#my_estimated_growth_testvalue").val(),
				my_future_kgv_testvalue: $("#my_future_kgv_testvalue").val(),
				my_eps_testvalue: $("#my_eps_testvalue").val()};
		
		//$("#analysis_calculation").html("loading...");
		
		$("#analysis_calculation").load(baseUrl+"/analysis/show #analysis_calculation", 
				data, addEventHandlerAnalysisCalculation);
		
		event.preventDefault(); //Verhindern, dass der Link ausgefuehrt wird
	 });
	
	$("#my_estimated_growth_testvalue, #my_future_kgv_testvalue, #my_eps_testvalue").blur(function (event) { 
		
		data = {CID: $("#analysis_company_id").val(), 
				AID: $("#analysis_analysis_id").val(), 
				my_estimated_growth_testvalue: $("#my_estimated_growth_testvalue").val(),
				my_future_kgv_testvalue: $("#my_future_kgv_testvalue").val(),
				my_eps_testvalue: $("#my_eps_testvalue").val()};
		
		//$("#analysis_calculation").html("loading...");
		
		$("#analysis_calculation").load(baseUrl+"/analysis/show #analysis_calculation", 
				data, addEventHandlerAnalysisCalculation);
		
		event.preventDefault(); //Verhindern, dass der Link ausgefuehrt wird
	 });
	
	 /* KGV-Box */
	 $("#futurekgv_rule1growth_head,#futurekgv_rule1growth_number").mouseover(function (event) { 
		 calcHighlight($("#rule1growth td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#rule1growth td"));
	 });
	 
	 
	 /* Zahlenbox */
	 $("#futureeps_currenteps").mouseover(function (event) { 
		 calcHighlight($("#currenteps_head,#currenteps_number"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#currenteps_head,#currenteps_number"));
	 });
	 $("#futureeps_rule1growth").mouseover(function (event) { 
		 calcHighlight($("#rule1growth td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#rule1growth td"));
	 });
	 
	 /* MOS-Box */
	 $("#mos_futurekgv").mouseover(function (event) { 
		 calcHighlight($("#futurekgv td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#futurekgv td"));
	 });
	 $("#mos_futureeps").mouseover(function (event) { 
		 calcHighlight($("#futureeps td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#futureeps td"));
	 }); 
	 $("#mos_stickerprice").mouseover(function (event) { 
		 calcHighlight($("#mos_stickerprice_head td,#mos_stickerprice_content td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#mos_stickerprice_head td,#mos_stickerprice_content td"));
	 });
	 $("#mos_rateofreturn").mouseover(function (event) { 
		 calcHighlight($("#rateofreturn_head,#rateofreturn_number"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#rateofreturn_head,#rateofreturn_number"));
	 });
	 $("#mos_mos").mouseover(function (event) { 
		 calcHighlight($("#mos_mos_head td,#mos_mos_content td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#mos_mos_head td,#mos_mos_content td"));
	 });
	 
	 /* Payback Time Box */
	 $(".paybacktime_eps").mouseover(function (event) { 
		 calcHighlight($("#currenteps_head,#currenteps_number"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#currenteps_head,#currenteps_number"));
	 });
	 $("#paybacktime_sticker").mouseover(function (event) { 
		 calcHighlight($("#mos_mos_head td,#mos_mos_content td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#mos_mos_head td,#mos_mos_content td"));
	 });
	 $("#paybacktime_mos").mouseover(function (event) { 
		 calcHighlight($("#mos_mos_mos_head td,#mos_mos_mos_content td"));
	 }).mouseout(function (event) { 
		 calcUnHighlight($("#mos_mos_mos_head td,#mos_mos_mos_content td"));
	 });
	 
	 $("#paybacktime_price, #paybacktimeTitleInfo").simpletooltip(false);

	 $("#analysis_loading").colorBlend([{param:"background-color", fromColor:"#ffa200", toColor:"#b9b9b9",duration:8000,cycles:0}]);

}

function calcHighlight(element)
{
	element.not(".active").stop().animate({backgroundColor:calcColorHighlight}, 500);
}
function calcUnHighlight(element)
{
	element.not(".active").not(".headline").stop().animate({backgroundColor:calcColorNonHighlight}, 500,"linear", 
			 function(){element.not(".active").css("backgroundColor","");});
	element.not(".active").filter(".headline").stop().animate({backgroundColor:calcColorHeadline}, 500,"linear", 
			 function(){element.filter(".headline").css("backgroundColor","");});
}