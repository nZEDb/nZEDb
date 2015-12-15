
// event bindings
jQuery(function($){


	$(".forumpostsubmit").click(function(e) {
		if ($.trim($("#addMessage").val()) == "" || $.trim($("#addSubject").val()) == "")
		{
			alert ("Please enter a subject and message.");
			return false;
		}
	});

	$(".forumreplysubmit").click(function(e) {
		if ($.trim($("#addReply").val()) == "")
		{
			alert ("Please enter a message.");
			return false;
		}
	});

	$(".check").click(function(e) {
		if (!$(e.target).is('input'))
			$(this).children(".nzb_check").attr('checked', !$(this).children(".nzb_check").attr('checked'));
	});

	$(".descmore").click(function(e) {
		$(this).prev(".descinitial").hide();
		$(this).next(".descfull").show();
		$(this).hide();
		return false;
	});

	$('.nzb_check_all').change(function(){
		if($(this).attr('checked'))
		{
			$('table.data tr td input:checkbox:visible').attr('checked',$(this).attr('checked'));
		} else {
			$('table.data tr td input:checkbox').removeAttr('checked');
		}
    });

	$('.nzb_check_all_season').change(function(){
		var season = $(this).attr('name');
		$('table.data tr td input:checkbox:visible').each( function(i, row) {
			if ($(row).attr('name') == season) {
				$(row).attr('checked', !$(row).attr('checked'));
			}
		});
	});


/* SMALL GREY ICONS CART - DOWNLOAD - SAB */
	// browse.tpl, search.tpl
	$('.icon_cart').click(function(e){
		if ($(this).hasClass('icon_cart_clicked')) return false;
		var guid = $(this).parent().parent().parent().parent().attr('id').substring(4);
		$.post( SERVERROOT + "cart?add=" + guid, function(resp){
			$(e.target).addClass('icon_cart_clicked').attr('title','Added to Cart');

	        $.pnotify({
		        title: 'ADDED!',
		        text: 'Its now in your Download Basket',
		        type: 'success',
		        icon: 'fa-icon-info-sign'
		    });

		});
		return false;
	});
	$('.icon_sab').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_sab_clicked')) return false;

		var guid = $(this).parent().parent().parent().parent().attr('id').substring(4);
		var nzburl = SERVERROOT + "sendtosab/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_sab_clicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO SAB!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});
	$('.icon_sabNZBinfo').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_sabNZBinfo_clicked')) return false;

		var guid = $(this).attr('id').substring(4);
		var nzburl = SERVERROOT + "sendtosab/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_sabNZBinfo_clicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO NZBGET!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});
	$('.icon_sabMovieinfo').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_sabMovieinfo_clicked')) return false;

		var guid = $(this).attr('id');
		var nzburl = SERVERROOT + "sendtosab/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_sabMovieinfo_clicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO NZBGET!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});
	$('.icon_nzbget').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_nzbget_clicked')) return false;

		var guid = $(this).parent().parent().parent().parent().attr('id').substring(4);
		var nzburl = SERVERROOT + "sendtonzbget/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_nzbget_clicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO NZBGET!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});
	$('.icon_nzbgetNZBinfo').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_nzbgetNZBinfoclicked')) return false;

		var guid = $(this).attr('id').substring(4);
		var nzburl = SERVERROOT + "sendtonzbget/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_nzbgetNZBinfoclicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO NZBGET!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});
	$('.icon_nzbgetMovieinfo').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_nzbgetMovieinfo_clicked')) return false;

		var guid = $(this).attr('id');
		var nzburl = SERVERROOT + "sendtonzbget/" + guid;

		$.post(nzburl, function(resp){
			$(e.target).addClass('icon_nzbgetMovieinfo_clicked').attr('title','Added to Queue');

	        $.pnotify({
		        title: 'ADDED TO NZBGET!',
		        text: 'Its now in the Queue',
		        type: 'info',
		        icon: 'fa-icon-info-sign'
		    });
		});
		return false;
	});

	// viewnzb.tpl,
	$('.icon_nzb_cart').click(function(e){
		if ($(this).hasClass('icon_cart_clicked')) return false;
		var guid = $(this).parent().attr('id');
		$.post( SERVERROOT + "cart?add=" + guid, function(resp){
			$(e.target).addClass('icon_cart_clicked').attr('title','Added to Cart');

	        $.pnotify({
		        title: 'ADDED!',
		        text: 'Its now in your Cart! ^_^',
		        type: 'success',
		        icon: 'fa-icon-info-sign'
		    });
	    });
		return false;
	});
/* END OFF - SMALL GREY ICONS CART - DOWNLOAD - SAB */

/* MODAL */
	$("table.data a.modal_nfo").colorbox({	 // NFO modal
		href: function(){ return $(this).attr('href') +'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"90%", initialWidth:"800px", initialHeight:"90%", speed:0, opacity:0.7
	});
	// Screenshot modal
	$("table.data a.modal_prev").colorbox({scrolling:false, maxWidth:"800px", maxHeight:"450px"});

	$("table.data a.modal_imdb").colorbox({	 // IMDB modal
		href: function(){ return SERVERROOT + "movie/"+$(this).attr('name').substring(4)+'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
	}).click(function(){
		$('#colorbox').removeClass().addClass('cboxMovie');
	});
    $("a.badge-trailer").colorbox({	 // IMDB trailer modal
        href: function(){ return SERVERROOT + "movietrailer/"+$(this).attr('name').substring(4)+'&modal'; },
        title: function(){ return $(this).parent().parent().children('a.title').text(); },
        innerWidth:"1280px", innerHeight:"720px", initialWidth:"1280px", initialHeight:"720px", speed:0, opacity:0.7
    }).click(function(){
            $('#colorbox').removeClass().addClass('cboxMovie');
        });


	$("table.data a.modal_music").colorbox({	 // Music modal
		href: function(){ return SERVERROOT + "musicmodal/"+$(this).attr('name').substring(4)+'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
	}).click(function(){
		$('#colorbox').removeClass().addClass('cboxMusic');
	});
	$("table.data a.modal_console").colorbox({	 // Console modal
		href: function(){ return SERVERROOT + "consolemodal/"+$(this).attr('name').substring(4)+'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
	}).click(function(){
		$('#colorbox').removeClass().addClass('cboxConsole');
	});
	$("table.data a.modal_book").colorbox({	 // Book modal
		href: function(){ return SERVERROOT + "bookmodal/"+$(this).attr('name').substring(4)+'&modal'; },
		title: function(){ return $(this).parent().parent().children('a.title').text(); },
		innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
	}).click(function(){
		$('#colorbox').removeClass().addClass('cboxBook');
	});
/* END OFF - MODAL */


/* nzb_multi_operations_form */
	$('#nzb_multi_operations_form').submit(function(){return false;});
	$('input.nzb_multi_operations_download').click(function(){
		var newFormName = 'nzbmulti' + Math.round(+new Date()/1000);
		var newForm = $("<form />", {'action': SERVERROOT + 'getnzb?zip=1', 'method':'post', 'target': '_top', 'id':newFormName});
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	if ($(row).val()!="on")
		    	$("<input />", {'name':'id[]', 'value':$(row).val(), 'type':'hidden'}).appendTo(newForm);
	    });
	    newForm.appendTo($('body'));
	    $('#'+newFormName).submit();
	});
	$('input.nzb_multi_operations_cart').click(function(){
		var guids = new Array();
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	var guid = $(row).val();
	    	var $cartIcon = $(row).parent().parent().children('td.icons').children('.icon_cart');
			if (guid && !$cartIcon.hasClass('icon_cart_clicked')){
				$cartIcon.addClass('icon_cart_clicked').attr('title','Added to Cart');	// consider doing this only upon success
				guids.push(guid);
				$.pnotify({
					title: 'ADDED!',
					text: 'Its now in your Cart! ^_^',
					type: 'success',
					icon: 'fa-icon-info-sign'
				});
			}
			$(this).attr('checked', false);
		});
		$.post( SERVERROOT + "cart?add", { 'add': guids });
	});
	$('input.nzb_multi_operations_sab').click(function(){
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	var $sabIcon = $(row).parent().parent().children('td.icons').children('.icon_sab');
	    	var guid = $(row).val();
			if (guid && !$sabIcon.hasClass('icon_sab_clicked')) {
				var nzburl = SERVERROOT + "sendtosab/" + guid;
				$.post( nzburl, function(resp){
					$sabIcon.addClass('icon_sab_clicked').attr('title','Added to Queue');
               				$.pnotify({
                			        title: 'ADDED TO SAB!',
                			        text: 'Its now in the queue!! ^_^',
                			        type: 'info',
                			        icon: 'fa-icon-info-sign'
               			        });
				});
			}
			$(this).attr('checked', false);
		});
	});
	$('input.nzb_multi_operations_nzbget').click(function(){
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	var $nzbgetIcon = $(row).parent().parent().children('td.icons').children('.icon_nzbget');
	    	var guid = $(row).val();
			if (guid && !$nzbgetIcon.hasClass('icon_nzbget_clicked')) {
				var nzburl = SERVERROOT + "sendtonzbget/" + guid;
				$.post( nzburl, function(resp){
					$nzbgetIcon.addClass('icon_nzbget_clicked').attr('title','Added to Queue');
               				$.pnotify({
                			        title: 'ADDED TO NZBGET!',
                			        text: 'Its now in the queue!! ^_^',
                			        type: 'info',
                			        icon: 'fa-icon-info-sign'
               			        });
				});
			}
			$(this).attr('checked', false);
		});
	});

	//front end admin functions
	$('input.nzb_multi_operations_edit').click(function(){
		var ids = "";
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	if ($(row).val()!="on")
		    	ids += '&id[]='+$(row).val();
	    });
	    if (ids)
			$('input.nzb_multi_operations_edit').colorbox({
				href: function(){ return SERVERROOT + "ajax_release-admin?action=edit"+ids+"&from="+encodeURIComponent(window.location); },
				title: 'Edit Release',
				innerWidth:"400px", innerHeight:"250px", initialWidth:"400px", initialHeight:"250px", speed:0, opacity:0.7
			});
	});
	$('input.nzb_multi_operations_delete').click(function(){
		var ids = "";
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	if ($(row).val()!="on")
		    	ids += '&id[]='+$(row).val();
	    });
	    if (ids)
			if (confirm('Are you sure you want to delete the selected releases?')) {
				$.post(SERVERROOT + "ajax_release-admin?action=dodelete"+ids, function(resp){
					window.location = window.location;
				});
			}
	});
	$('input.nzb_multi_operations_rebuild').click(function(){
		var ids = "";
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	if ($(row).val()!="on")
		    	ids += '&id[]='+$(row).val();
	    });
	    if (ids)
			if (confirm('Are you sure you want to rebuild the selected releases?')) {
				$.post(SERVERROOT + "ajax_release-admin?action=dorebuild"+ids, function(resp){
					window.location = window.location;
				});
			}
	});
	//cart functions
	$('input.nzb_multi_operations_cartdelete').click(function(){
		var ids = new Array();
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	if ($(row).val()!="on")
		    	ids.push($(row).val());
	    });
	    if (ids)
	    {
			if (confirm('Are you sure you want to delete the selected releases from your cart?')) {
				$.post( SERVERROOT + "cart?delete", { 'delete': ids }, function(resp){
					window.location = window.location;
				});
			}
		}
	});
	$('input.nzb_multi_operations_cartsab').click(function(){
		var ids = new Array();
	    $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
	    	var guid = $(row).val();
			var nzburl = SERVERROOT + "sendtosab/" + guid;
			$.post( nzburl, function(resp){
               			$.pnotify({
                		        title: 'ADDED TO SAB!',
                		        text: 'Its now in the queue!! ^_^',
                		        type: 'info',
                		        icon: 'fa-icon-info-sign'
                		 });
			});
	    });
	});

/* END OFF - nzb_multi_operations_form */

	// headermenu.tpl
	$('#headsearch')
		.focus(function(){if(this.value == 'Enter keywords') this.value = ''; else this.select(); })
		.blur (function(){if(this.value == '') this.value = 'Enter keywords';});
	$('#headsearch_form').submit(function(){
		$('#headsearch_go').trigger('click');
		return false;
	});
	$('#headsearch_go').click(function(){
		if ($('#headsearch').val() && $('#headsearch').val() != 'Enter keywords')
		{

			var sText = $('#headsearch').val();
			var sCat = ($("#headcat").val()!=-1 ? "?t="+$("#headcat").val() : "");
			document.location= WWW_TOP + "/search/" +  sText + sCat;
		}
	});

	// search.tpl
	$('#search_search_button').click(function(){
		if ($('#search').val())
			document.location=WWW_TOP + "/search/" + $('#search').val() + ($("#search_cat").val()!=-1 ? "?t="+$("#search_cat").val() : "");
		return false;
	});

	$('#search')
		.focus(function(){this.select(); })

	// searchraw.tpl
	$('#searchraw_search_button').click(function(){
		if ($('#search').val())
			document.location=WWW_TOP + "/searchraw/" + $('#search').val();
		return false;
	});
	$('#searchraw_download_selected').click(function(){
		if ($('#dl input:checked').length)
			$('#dl').trigger('submit');
		return false;
	});

	// login.tpl, register.tpl, search.tpl, searchraw.tpl
	if ($('#username').length)
		$('#username').focus();
	if ($('#search').length)
		$('#search').focus();

	// viewfilelist.tpl
	$('#viewfilelist_download_selected').click(function(){
		if ($('#fileform input:checked').length)
			$('#fileform').trigger('submit');
		return false;
	});

	// misc
	$('.confirm_action').click(function(){ return confirm('Are you sure?'); });

	// play audio preview
	$('.audioprev').click(function(){
		var a = document.getElementById($(this).next('audio').attr('ID'));
		if (a != null) {
			if ($(this).text() == "Listen") {
				a.play();
				$(this).text("Stop");
			}
			else {
				a.pause();
				a.currentTime = 0;
				$(this).text("Listen");
			}
		}

		a.addEventListener('ended', function () {
		 	$(this).prev().text("Listen");
		} );

		return false;
		});

	// mmenu
	$('.mmenu').click(function(){
		document.location=$(this).children('a').attr('href');
		return false;
	});

	// mmenu_new
	$('.mmenu_new').click(function(){
		window.open($(this).children('a').attr('href'));
		return false;
	});

	// searchraw.tpl, viewfilelist.tpl -- checkbox operations
	// selections
	var last1, last2;
	$(".checkbox_operations .select_all").click(function(){
	    $("table.data INPUT[type='checkbox']").attr('checked', true).trigger('change');
		return false;
	});
	$(".checkbox_operations .select_none").click(function(){
	    $("table.data INPUT[type='checkbox']").attr('checked', false).trigger('change');
		return false;
	});
	$(".checkbox_operations .select_invert").click(function(){
	    $("table.data INPUT[type='checkbox']").each( function() {
	        $(this).attr('checked', !$(this).attr('checked')).trigger('change');
	    });
		return false;
	});
	$(".checkbox_operations .select_range").click(function(){
		if (last1 && last2 && last1 < last2)
	    	$("table.data INPUT[type='checkbox']").slice(last1,last2).attr('checked', true).trigger('change');
		else if (last1 && last2)
	    	$("table.data INPUT[type='checkbox']").slice(last2,last1).attr('checked', true).trigger('change');
		return false;
	});
	$('table.data td.check INPUT[type="checkbox"]').click(function(e) {
	    // range event interaction -- see further above
		var rowNum = $(e.target).parent().parent()[0].rowIndex ;
	    if (last1) last2 = last1;
		last1 = rowNum;

		// perform range selection
		if (e.shiftKey && last1 && last2) {
			if (last1 < last2)
		    	$("table.data INPUT[type='checkbox']").slice(last1,last2).attr('checked', true).trigger('change');
			else
		    	$("table.data INPUT[type='checkbox']").slice(last2,last1).attr('checked', true).trigger('change');
		}
	});
	$('table.data a.data_filename').click(function(e) { // click filenames to select
	    // range event interaction -- see further above
		var rowNum = $(e.target).parent().parent()[0].rowIndex ;
	    if (last1) last2 = last1;
		last1 = rowNum;

		var $checkbox = $('table.data tr:nth-child('+(rowNum+1)+') td.selection INPUT[type="checkbox"]');
		$checkbox.attr('checked', !$checkbox.attr('checked'));

		return false;
	});


	// show/hide invite form
	$('#lnkSendInvite').click(function()
	{
		$('#divInvite').slideToggle('fast');
		$("#lnkSendInvite").hide();
	});

	$('#lnkCancelInvite').click(function()
	{
		$('#divInvite').slideToggle('fast');
		$("#lnkSendInvite").show();
	});

	// send an invite
	$('#frmSendInvite').submit(function()
	{
		var inputEmailto = $("#txtInvite").val();
		if (isValidEmailAddress(inputEmailto))
		{

			// no caching of results
			var rand_no = Math.random();
			$.ajax({
			  url       : WWW_TOP + '/ajax_profile?action=1&rand=' + rand_no,
			  data      : { emailto: inputEmailto},
			  dataType  : "html",
			  success   : function(data)
			  {
				$("#txtInvite").val("");
				$('#frmSendInvite').slideToggle('fast');
				$("#divInviteSuccess").text(data).show();
				$("#divInviteError").hide();
				setTimeout(function() {
					$("#divInviteSuccess").slideToggle(400);
					$('#divInvite').slideToggle('fast');
					$('#frmSendInvite').slideToggle('fast');

				}, 3500);
				$("#lnkSendInvite").show();
			  },
			  error: function(xhr,err,e) { alert( "Error in ajax_profile: " + err ); }
			});
		}
		else
		{
			$("#divInviteSuccess").hide();
			$("#divInviteError").text("Invalid email").show();
			setTimeout(function() {
					$("#divInviteError").slideToggle(400);

				}, 3500);
		}
		return false;
	});

	// movie.tpl
	$('.mlmore').click(function(){	// show more movies
		$(this).parent().parent().hide();
		$(this).parent().parent().parent().children(".mlextra").show();
		return false;
	});



	// lookup tmdb for a movie
	$('#frmMyMovieLookup').submit(function()
	{
		var movSearchText = $("#txtsearch").val();
		// no caching of results
		var rand_no = Math.random();
		$.ajax({
		  url       : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
		  data      : { id: movSearchText},
		  dataType  : "html",
		  success   : function(data)
		  {
			$("#divMovResults").html(data);
		  },
		  error: function(xhr,err,e) { alert( "Error in ajax_mymovies: " + err ); }
		});

		return false;
	});

	// file list tooltip
	$(".rarfilelist").each(function() {
		var guid = $(this).children('img').attr('alt');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'rar archive contains...'
			  },
			  text: 'loading...',
			  ajax: {
			     url: SERVERROOT + 'ajax_rarfilelist',
			     type: 'GET',
			     data: { id: guid },
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			position: {
				my: 'top right',
				at: 'bottom left'
			},
			style: {
			    classes: 'ui-tooltip-newznab',
				width: { max: 500 },
				tip: {
	        		corner: 'topRight',
	        		size: {
	                	x: 8,
	                	y : 8
	             	}
				}
			}
		});
	});

	// seriesinfo tooltip

	// seriesinfo tooltip
	$(".seriesinfo").each(function() {
		var guid = $(this).attr('title');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'Episode Info'
			  },
			  text: 'loading...',
			  ajax: {
			     url: SERVERROOT + 'ajax_tvinfo',
			     type: 'GET',
			     data: { id: guid },
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			style: {
			  classes: 'ui-tooltip-newznab'
			}
		});
	});



	// mediainfo tooltip
	$(".mediainfo").each(function() {
		var guid = $(this).attr('title');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'Extended Media Info'
			  },
			  text: 'loading...',
			  ajax: {
			     url: SERVERROOT + 'ajax_mediainfo',
			     type: 'GET',
			     data: { id: guid },
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			style: {
				classes: 'ui-tooltip-newznab',
				width: { max: 500 },
				tip: {
					corner: 'topLeft',
					size: {
				    	x: 8,
				    	y : 8
				 	}
				}
			}
		});
	});

	// preinfo tooltip
	$(".preinfo").each(function() {
		var searchname = $(this).attr('title');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'Pre Info'
			  },
			  text: 'loading...',
			  ajax: {
			     url: SERVERROOT + 'ajax_preinfo',
			     type: 'GET',
			     data: { searchname: searchname },
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			style: {
			  classes: 'ui-tooltip-newznab'
			}
		});
	});
	// prehashinfo tooltip
	$(".prehashinfo").each(function() {
		var prehashID = $(this).attr('title');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'Prehash info'
			  },
			  text: 'loading...', // The text to use whilst the AJAX request is loading
			  ajax: {
			     url: SERVERROOT + 'ajax_prehashinfo', // URL to the local file
			     type: 'GET', // POST or GET
			     data: { ID: prehashID }, // Data to pass along with your request
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
		});
	});
});


$.extend({ // http://plugins.jquery.com/project/URLEncode
URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
  while(x<c.length){var m=r.exec(c.substr(x));
    if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
URLDecode:function(s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
  while((m=r.exec(o))!=null && m.length>1 && m[1]!=''){b=parseInt(m[1].substr(1),16);
  t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
});


function isValidEmailAddress(emailAddress)
{
	var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
	return pattern.test(emailAddress);
}

function mymovie_del(imdbID, btn)
{
		var rand_no = Math.random();
		$.ajax({
		  url       : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
		  data      : { del: imdbID},
		  dataType  : "html",
		  success   : function(data)
		  {
				$(btn).hide();
				$(btn).prev("a").show();
		  },
		  error: function(xhr,err,e) {  }
		});

		return false;
}

function mymovie_add(imdbID, btn)
{
		$(btn).hide();
		$(btn).next("a").show();

		var rand_no = Math.random();
		$.ajax({
		  url       : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
		  data      : { add: imdbID},
		  dataType  : "html",
		  success   : function(data)
		  {
		  },
		  error: function(xhr,err,e) {  }
		});

		return false;
}


//reset users api counts
function resetapireq(uid, type)
{
	$.post( SERVERROOT + "ajax_resetusergrabs-admin?id=" + uid + "&action=" + type, function(resp){ });
}

function getQueue()
{
    $.ajax({
        url: "queuedata?id=" + $.now(),
        cache: false,
        success: function(html)
        {
            $(".sab_queue").html(html);
            setTimeout("getQueue()", 2500);
        },
        error: function ()
        {
            $(".sab_queue").html("Could not contact your queue. <a href=\"javascript:location.reload(true)\">Refresh</a>");
        },
        timeout:5000
    });
}

function getNzbGetQueue()
{
    $.ajax({
        url: "queuedata?type=nzbget&id=" + $.now(),
        cache: false,
        success: function(html)
        {
            $(".nzbget_queue").html(html);
            setTimeout("getNzbGetQueue()", 2500);
        },
        error: function ()
        {
            $(".nzbget_queue").html("Could not contact your queue. <a href=\"javascript:location.reload(true)\">Refresh</a>");
        },
        timeout:5000
    });
}

function getHistory()
{
    $.ajax({
        url: "queuedata?type=history&id=" + $.now(),
        cache: false,
        success: function(html)
        {
            $(".sab_history").html(html);
            setTimeout("getHistory()", 10000);
        },
        error: function ()
        {
            //$(".sab_history").html("Could not contact your queue. <a href=\"javascript:location.reload(true)\">Refresh</a>");
        },
        timeout:5000
    });
}