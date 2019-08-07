$(document).ready(function() {

	hideLoading();
	initFeedbackToggle();
	
	$(".tablesorter").tablesorter(); 

});

function getWatchlistAndPrint(wid, name, wurl)
{
	//erstmal leeren
	$(".homeTLayerNew.homeLayerStocks .left .body").empty();
	//loading anzeigen
	$("<div>loading...</div>").appendTo(".homeTLayerNew.homeLayerStocks .left .body");
	
	$.getJSON(url, 
			{"WID":wid},  
			function(data){	
				//erstmal leeren
				$(".homeTLayerNew.homeLayerStocks .left .body").empty();

				//namen setzen
				$("#indexWatchlistName").html("<a href='"+wurl+"'>"+name+"</a>");
				
				var i = 0;
				
				var length = 0;
				//.length geht nicht für assoziative Array... also manuell
				for (var _key in data.watchlist){
					length++;
				}

				var kursclass = "";
				for (var _key in data.watchlist){
					//genau 6 aktien holen
					//3 from top
					//from bottom
					if(data.watchlist[_key]["changeNumber"] > 0)
						kursclass = "plus";
					else
						kursclass = "minus";
					
					if(i <= 2 || i>=(length-3))
						$("<div class='stock'><a href='"+data.watchlist[_key]["url"]+"'>"+data.watchlist[_key]["name"]+"</a></div><div class='kurs "+kursclass+"'>"+data.watchlist[_key]["change"]+" %</div>").appendTo(".homeTLayerNew.homeLayerStocks .left .body");


					i++;
		        }
			}
			);
}

function initIndexWatchlistPrevNextButtons()
{
	//vorzurückbutton mit funktionalität belegen
	//Previous
	$("#indexWatchlistPrevNextButton a:first").click(function(event)
			{
				event.preventDefault();
				if(typeof watchlists[currentWatchlist-1] != "undefined")
				{
					currentWatchlist = currentWatchlist-1;
					getWatchlistAndPrint(watchlists[currentWatchlist]["id"], watchlists[currentWatchlist]["name"], watchlists[currentWatchlist]["url"]);
				}
				else
				{
					currentWatchlist = watchlists.length-1;
					getWatchlistAndPrint(watchlists[currentWatchlist]["id"], watchlists[currentWatchlist]["name"], watchlists[currentWatchlist]["url"]);
				}
			}
			);
	//Next
	$("#indexWatchlistPrevNextButton a:last").click(function(event)
			{
				event.preventDefault();
				if(typeof watchlists[currentWatchlist+1] != "undefined")
				{
					currentWatchlist = currentWatchlist+1;
					getWatchlistAndPrint(watchlists[currentWatchlist]["id"], watchlists[currentWatchlist]["name"], watchlists[currentWatchlist]["url"]);
				}
				else
				{
					currentWatchlist = 0;
					getWatchlistAndPrint(watchlists[currentWatchlist]["id"], watchlists[currentWatchlist]["name"], watchlists[currentWatchlist]["url"]);
				}
			}
			);	
}


function hideLoading()
{
	$("#rule1Loading").hide();
}
function showLoading()
{
	$("#rule1Loading").show();	
}
function initFeedbackToggle()
{
	//$("#feedbackToggle").click(function(){tb_show("Feedback", baseUrl+"/feedback/FRAMED/1/?TB_iframe=true&height=400&width=600", false);});
	
	$('#feedbackToggle').click(function(e) {
		e.preventDefault();
		showLoading();
		var $this = $(this);

        $('<iframe src="' + baseUrl + '/feedback/FRAMED/1" />').dialog({
            //title: ($this.attr('title')) ? $this.attr('title') : 'External Site',
            bgiframe: true,
            autoOpen: true,
            open: function(event, ui) { hideLoading();  },
            width: 620,
            height: 'auto',
            overflow: 'auto',
            position: 'top',
            dialogClass: 'frameDialog',
            //modal: true,
            resizable: true,
			autoResize: true,
            overlay: {
				backgroundColor: '#000',
				opacity: 0.7
			},
			show:'clip',
			hide:'clip'
        }).width(600).height(500);	
              
	});
	
}
//Thickbox erweiterung
function tb_unbindOverlay()
{
	//Verhindern, dass durch klick auf den Overlay das Fenster geschlossen wird
	 $("#TB_overlay").unbind();

}
function tb_bindOverlay()
{
	 $("#TB_overlay").click(tb_remove);

}

/** ZRSSFEED **/


(function($){var current=null;$.fn.rssfeed=function(url,options){var defaults={limit:10,header:true,titletag:'h4',date:true,content:true,snippet:true,showerror:true,errormsg:'',key:null};var options=$.extend(defaults,options);return this.each(function(i,e){var $e=$(e);if(!$e.hasClass('rssFeed'))$e.addClass('rssFeed');if(url==null)return false;var api="http://ajax.googleapis.com/ajax/services/feed/load?v=1.0&callback=?&q="+url;if(options.limit!=null)api+="&num="+options.limit;if(options.key!=null)api+="&key="+options.key;$.getJSON(api,function(data){if(data.responseStatus==200){_callback(e,data.responseData.feed,options);}else{if(options.showerror)

if(options.errormsg!=''){var msg=options.errormsg;}else{var msg=data.responseDetails;};$(e).html('<div class="rssError"><p>'+msg+'</p></div>');};});});};var _callback=function(e,feeds,options){if(!feeds){return false;}

var html='';var row='odd';if(options.header)

html+='<div class="rssHeader">'+'<a href="'+feeds.link+'" title="'+feeds.description+'">'+feeds.title+'</a>'+'</div>';html+='<div class="rssBody">'+'<ul>';for(var i=0;i<feeds.entries.length;i++){var entry=feeds.entries[i];var entryDate=new Date(entry.publishedDate);var pubDate=entryDate.toLocaleDateString()+' '+entryDate.toLocaleTimeString();html+='<li class="rssRow '+row+'">'+'<'+options.titletag+'><a href="'+entry.link+'" title="View this feed at '+feeds.title+'">'+entry.title+'</a></'+options.titletag+'>'

if(options.date)html+='<div>'+pubDate+'</div>'

if(options.content){if(options.snippet&&entry.contentSnippet!=''){var content=entry.contentSnippet;}else{var content=entry.content;}

html+='<p>'+content+'</p>'}

html+='</li>';if(row=='odd'){row='even';}else{row='odd';}}

html+='</ul>'+'</div>'

$(e).html(html);};})(jQuery);
