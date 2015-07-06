// event bindings
jQuery(function ($) {

    // browse.tpl, search.tpl -- show icons on hover
    var orig_opac = $('table.data tr').children('td.icons').children('div.icon').css('opacity');
    $('table.data tr').hover(
        function () { $(this).children('td.icons').children('div.icon').css('opacity', 1); },
        function () { $(this).children('td.icons').children('div.icon').css('opacity', orig_opac); }
    );

    $(".forumpostsubmit").click(function (e) {
        if ($.trim($("#addMessage").val()) === "" || $.trim($("#addSubject").val()) === "") {
            alert("Please enter a subject and message.");
            return false;
        }
    });

    $(".forumreplysubmit").click(function (e) {
        if ($.trim($("#addReply").val()) === "") {
            alert("Please enter a message.");
            return false;
        }
    });

    $(".check").click(function (e) {
        if (!$(e.target).is('input')) {
            $(this).children(".nzb_check").prop('checked', !$(this).children(".nzb_check").prop('checked'));
        }
    });

    $(".descmore").click(function (e) {
        $(".descinitial").hide();
        $(".descfull").show();
        return false;
    });

    $('.nzb_check_all').change(function () {
        $('table#browsetable tr td input:checkbox').prop('checked', $(this).prop('checked'));
    });

    $('.nzb_check_all_season').change(function () {
        var season = $(this).attr('name');
        $('table.data tr td input:checkbox').each(function (i, row) {
            if ($(row).attr('name') === season) {
                $(row).prop('checked', !$(row).prop('checked'));
            }
        });
    });

    // browse.tpl, search.tpl
    $('.icon_cart').click(function (e) {
        if ($(this).hasClass('icon_cart_clicked')) {
            return false;
        }
        var guid = $(this).parent().parent().attr('id').substring(4);
        $.post(SERVERROOT + "cart?add=" + guid, function (resp) {
            $(e.target).addClass('icon_cart_clicked').attr('title', 'Added to Cart');

            $.pnotify({
                title: 'ADDED!',
                text: 'Its now in your Cart! ^_^',
                type: 'success',
                animate_speed: 'fast',
                icon: 'icon-info-sign'
            });

        });
        return false;
    });
    $('.icon_sab').click(function (e) { // replace with cookies?
        if ($(this).hasClass('icon_sab_clicked')) {
            return false;
        }
        var guid = $(this).parent().parent().attr('id').substring(4);
        var nzburl = SERVERROOT + "sendtoqueue/" + guid;

        $.post(nzburl, function (resp) {
            $(e.target).addClass('icon_sab_clicked').attr('title', 'Added to Queue');

            $.pnotify({
                title: 'ADDED TO QUEUE!',
                text: 'Its now in the queue!! ^_^',
                type: 'info',
                animate_speed: 'fast',
                icon: 'icon-info-sign'
            });
        });
        return false;
    });

    $('.sendtocouch').click(function(e){
        e.preventDefault();
        $.get($(this).attr('rel'));

        $.pnotify({
            title: 'ADDED TO COUCHPOTATO!',
            text: 'Its now on your wanted list! ^_^',
            type: 'info',
            animate_speed: 'fast',
            icon: 'icon-info-sign'
        });
    });

    // viewnzb.tpl,
    $('.icon_nzb_cart').click(function (e) {
        if ($(this).hasClass('icon_cart_clicked')) {
            return false;
        }
        var guid = $(this).parent().attr('id');
        $.post(SERVERROOT + "cart?add=" + guid, function (resp) {
            $(e.target).addClass('icon_cart_clicked').attr('title', 'Added to Cart');

            $.pnotify({
                title: 'ADDED!',
                text: 'Its now in your Cart! ^_^',
                type: 'success',
                animate_speed: 'fast',
                icon: 'icon-info-sign'
            });
        });
        return false;
    });
    $('.icon_nzb_sab').click(function (e) { // replace with cookies?
        if ($(this).hasClass('icon_sab_clicked')) {
            return false;
        }
        var guid = $(this).parent().attr('id');
        var nzburl = SERVERROOT + "sendtoqueue/" + guid;

        $.post(nzburl, function (resp) {
            $(e.target).addClass('icon_sab_clicked').attr('title', 'Added to Queue');

            $.pnotify({
                title: 'ADDED TO QUEUE!',
                text: 'Its now in the queue!! ^_^',
                type: 'info',
                animate_speed: 'fast',
                icon: 'icon-info-sign'
            });

        });
        return false;
    });
    $("table.data a.modal_nfo").colorbox({     // NFO modal
        href: function () { return $(this).attr('href') + '&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth: "800px", innerHeight: "90%", initialWidth: "800px", initialHeight: "90%", speed: 0, opacity: 0.7
    });
    // Screenshot modal
    $("table.data a.modal_prev").colorbox({maxWidth: "800px", maxHeight: "800x"});

    $("table.data a.modal_imdb").colorbox({    // IMDB modal
        href: function () { return SERVERROOT + "movie/" + $(this).attr('name').substring(4) + '&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth: "800px", innerHeight: "450px", initialWidth: "800px", initialHeight: "450px", speed: 0, opacity: 0.7
    }).click(function () {
        $('#colorbox').removeClass().addClass('cboxMovie');
    });

    $("table.data a.modal_xxx").colorbox({    // XXX modal
        href: function () { return SERVERROOT + "xxxmodal/" + $(this).attr('name').substring(4) + '&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth: "600px", innerHeight: "717px", initialWidth: "600px", initialHeight: "717px", speed: 0, opacity: 0.7
    }).click(function () {
        $('#colorbox').removeClass().addClass('cboxXXX');
    });

    $("table.data a.modal_music").colorbox({     // Music modal
        href: function () { return SERVERROOT + "musicmodal/"+$(this).attr('name').substring(4)+'&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
    }).click(function () {
        $('#colorbox').removeClass().addClass('cboxMusic');
    });
    $("table.data a.modal_console").colorbox({     // Console modal
        href: function () { return SERVERROOT + "consolemodal/"+$(this).attr('name').substring(4)+'&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
    }).click(function () {
        $('#colorbox').removeClass().addClass('cboxConsole');
    });
    $("table.data a.modal_book").colorbox({    // Book modal
        href: function () { return SERVERROOT + "bookmodal/"+$(this).attr('name').substring(4)+'&modal'; },
        title: function () { return $(this).parent().parent().children('a.title').text(); },
        innerWidth:"800px", innerHeight:"450px", initialWidth:"800px", initialHeight:"450px", speed:0, opacity:0.7
    }).click(function () {
        $('#colorbox').removeClass().addClass('cboxBook');
    });


    $('#nzb_multi_operations_form').submit(function () {return false;});
    $('button.nzb_multi_operations_download').click(function () {
        var ids = "";
        $("table.data INPUT[type='checkbox']:checked").each( function (i, row) {
            if ($(row).val()!="on")
                ids += $(row).val()+',';
        });
        ids = ids.substring(0,ids.length-1);
        if (ids)
            window.location = SERVERROOT + "getnzb?zip=1&id="+ids;
    });
    $('button.nzb_multi_operations_cart').click(function () {
        var guids = new Array();
        $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
            var guid = $(row).val();
            var $cartIcon = $(row).parent().parent().children('td.icons').children('.icon_cart');
            if (guid && !$cartIcon.hasClass('icon_cart_clicked')){
                $cartIcon.addClass('icon_cart_clicked').attr('title','Added to Cart');  // consider doing this only upon success
                guids.push(guid);
                $.pnotify({
                    title: 'ADDED!',
                    text: 'Its now in your Cart! ^_^',
                    type: 'success',
                    animate_speed: 'fast',
                    icon: 'icon-info-sign'
                });
            }
            $(this).prop('checked', false);
        });
        $.post( SERVERROOT + "cart?add=" + guids);
    });
    $('button.nzb_multi_operations_sab').click(function () {
        $("table.data INPUT[type='checkbox']:checked").each( function(i, row) {
            var $sabIcon = $(row).parent().parent().children('td.icons').children('.icon_sab');
            var guid = $(row).val();
            if (guid && !$sabIcon.hasClass('icon_sab_clicked')) {
                var nzburl = SERVERROOT + "sendtoqueue/" + guid;
                $.post( nzburl, function(resp){
                    $sabIcon.addClass('icon_sab_clicked').attr('title','Added to Queue');
                    $.pnotify({
                            title: 'ADDED TO QUEUE!',
                            text: 'Its now in the queue!! ^_^',
                            type: 'info',
                            animate_speed: 'fast',
                            icon: 'icon-info-sign'
                        });
                });
            }
            $(this).prop('checked', false);
        });
    });
    //front end admin functions
    $('button.nzb_multi_operations_edit').click(function () {
        var ids = "";
        $("table.data INPUT[type='checkbox']:checked").each( function (i, row) {
            if ($(row).val()!="on")
                ids += '&id[]='+$(row).val();
        });
        if (ids)
            $('button.nzb_multi_operations_edit').colorbox({
                href: function () { return SERVERROOT + "ajax_release-admin?action=edit"+ids+"&from="+encodeURIComponent(window.location); },
                title: 'Edit Release',
                innerWidth:"400px", innerHeight:"250px", initialWidth:"400px", initialHeight:"250px", speed:0, opacity:0.7
            });
    });
    $('button.nzb_multi_operations_delete').click(function () {
        var ids = "";
        $("table.data INPUT[type='checkbox']:checked").each( function (i, row) {
            if ($(row).val()!="on")
                ids += '&id[]='+$(row).val();
        });
        if (ids)
            if (confirm('Are you sure you want to delete the selected releases?')) {
                $.post(SERVERROOT + "ajax_release-admin?action=dodelete"+ids, function (resp) {
                    window.location = window.location;
                });
            }
    });
    //cart functions
    $('button.nzb_multi_operations_cartdelete').click(function () {
        var ids = [];
        $("table.data INPUT[type='checkbox']:checked").each( function (i, row) {
            if ($(row).val()!="on")
                ids.push($(row).val());
        });
        if (ids)
        {
            if (confirm('Are you sure you want to delete the selected releases from your cart?')) {
                $.post( SERVERROOT + "cart?delete", { 'delete': ids }, function (resp) {
                    window.location = window.location;
                });
            }
        }
    });

    // headermenu.tpl
    $('#headsearch')
        .focus(function () {if(this.value == 'Enter keywords') this.value = '';})
        .blur (function () {if(this.value === '') this.value = 'Enter keywords';});
    $('#headsearch_form').submit(function () {
        $('headsearch_go').trigger('click');
        return false;
    });
    $('#headsearch_go').click(function () {
        if ($('#headsearch').val() && $('#headsearch').val() != 'Enter keywords')
        {
            document.location= WWW_TOP + "/search/" + $('#headsearch').val() + ($("#headcat").val()!=-1 ? "?t="+$("#headcat").val() : "");
        }
    });

    // search.tpl
    $('#search_search_button').click(function () {
        if ($('#search').val())
            document.location=WWW_TOP + "/search/" + $('#search').val() + ($("#search_cat").val()!=-1 ? "?t="+$("#search_cat").val() : "");
        return false;
    });

    // login.tpl, register.tpl, search.tpl, searchraw.tpl
    if ($('#username').length)
        $('#username').focus();
    if ($('#search').length)
        $('#search').focus();

    // viewfilelist.tpl
    $('#viewfilelist_download_selected').click(function () {
        if ($('#fileform input:checked').length)
            $('#fileform').trigger('submit');
        return false;
    });

    // misc
    $('.confirm_action').click(function () { return confirm('Are you sure?'); });

    // mmenu
    $('.mmenu').click(function () {
        document.location=$(this).children('a').attr('href');
        return false;
    });

    // mmenu_new
    $('.mmenu_new').click(function () {
        window.open($(this).children('a').attr('href'));
        return false;
    });

    // searchraw.tpl, viewfilelist.tpl -- checkbox operations
    // selections
    var last1, last2;
    $(".checkbox_operations .select_all").click(function () {
        $("table.data INPUT[type='checkbox']").prop('checked', true).trigger('change');
        return false;
    });
    $(".checkbox_operations .select_none").click(function () {
        $("table.data INPUT[type='checkbox']").prop('checked', false).trigger('change');
        return false;
    });
    $(".checkbox_operations .select_invert").click(function () {
        $("table.data INPUT[type='checkbox']").each( function () {
            $(this).prop('checked', !$(this).prop('checked')).trigger('change');
        });
        return false;
    });
    $(".checkbox_operations .select_range").click(function () {
        if (last1 && last2 && last1 < last2)
            $("table.data INPUT[type='checkbox']").slice(last1,last2).prop('checked', true).trigger('change');
        else if (last1 && last2)
            $("table.data INPUT[type='checkbox']").slice(last2,last1).prop('checked', true).trigger('change');
        return false;
    });
    $('table.data td.check INPUT[type="checkbox"]').click(function (e) {
        // range event interaction -- see further above
        var rowNum = $(e.target).parent().parent()[0].rowIndex ;
        if (last1) last2 = last1;
        last1 = rowNum;

        // perform range selection
        if (e.shiftKey && last1 && last2) {
            if (last1 < last2)
                $("table.data INPUT[type='checkbox']").slice(last1,last2).prop('checked', true).trigger('change');
            else
                $("table.data INPUT[type='checkbox']").slice(last2,last1).prop('checked', true).trigger('change');
        }
    });
    $('table.data a.data_filename').click(function (e) { // click filenames to select
        // range event interaction -- see further above
        var rowNum = $(e.target).parent().parent()[0].rowIndex ;
        if (last1) last2 = last1;
        last1 = rowNum;

        var $checkbox = $('table.data tr:nth-child('+(rowNum+1)+') td.selection INPUT[type="checkbox"]');
        $checkbox.prop('checked', !$checkbox.prop('checked'));

        return false;
    });


    // show/hide invite form
    $('#lnkSendInvite').click(function ()
    {
        $('#divInvite').slideToggle('fast');
    });

    // send an invite
    $('#frmSendInvite').submit(function ()
    {
        var inputEmailto = $("#txtInvite").val();
        if (isValidEmailAddress(inputEmailto))
        {

            // no caching of results
            var rand_no = Math.random();
            $.ajax({
                url         : WWW_TOP + '/ajax_profile?action=1&rand=' + rand_no,
                data        : { emailto: inputEmailto},
                dataType    : "html",
                success     : function (data)
                {
                $("#txtInvite").val("");
                $('#divInvite').slideToggle('fast');
                $("#divInviteSuccess").text(data).show();
                $("#divInviteError").hide();
                },
                error: function (xhr,err,e) { alert( "Error in ajax_profile: " + err ); }
            });
        }
        else
        {
            $("#divInviteSuccess").hide();
            $("#divInviteError").text("Invalid email").show();
        }
        return false;
    });

    // movie.tpl
    $('.mlmore').click(function () {    // show more movies
        $(this).parent().parent().hide();
        $(this).parent().parent().parent().children(".mlextra").show();
        return false;
    });

    // lookup tmdb for a movie
    $('#frmMyMovieLookup').submit(function ()
    {
        var movSearchText = $("#txtsearch").val();
        // no caching of results
        var rand_no = Math.random();
        $.ajax({
            url         : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
            data        : { id: movSearchText},
            dataType    : "html",
            success     : function (data)
            {
            $("#divMovResults").html(data);
            },
            error: function (xhr,err,e) { alert( "Error in ajax_mymovies: " + err ); }
        });

        return false;
    });


    // file list tooltip
    $(".rarfilelist").each(function () {
        var guid = $(this).children('img').attr('alt');
        $(this).qtip({
            content: {
                title: {
                    text: 'rar archive contains...'
                },
                text: 'loading...', // The text to use whilst the AJAX request is loading
                ajax: {
                 url: SERVERROOT + 'ajax_rarfilelist', // URL to the local file
                 type: 'GET', // POST or GET
                 data: { id: guid }, // Data to pass along with your request
                 success: function (data, status) {
                    this.set('content.text', data);
                 }
                }
            },
            position: {
                my: 'top right',
                at: 'bottom left'
            },
            style: {
                classes: 'qtip-dark qtip-shadow qtip-rounded',
                width: { max: 500 },
                tip: { // Now an object instead of a string
                    corner: 'topRight', // We declare our corner within the object using the corner sub-option
                    size: {
                        x: 8, // Be careful that the x and y values refer to coordinates on screen, not height or width.
                        y : 8 // Depending on which corner your tooltip is at, x and y could mean either height or width!
                    }
                }
            }
        });
    });

    // seriesinfo tooltip
    $(".seriesinfo").each(function () {
        var guid = $(this).attr('title');
        $(this).qtip({
            content: {
                title: {
                    text: 'episode info...'
                },
                text: 'loading...', // The text to use whilst the AJAX request is loading
                ajax: {
                 url: SERVERROOT + 'ajax_tvinfo', // URL to the local file
                 type: 'GET', // POST or GET
                 data: { id: guid }, // Data to pass along with your request
                 success: function (data, status) {
                    this.set('content.text', data);
                 }
                }
            },
            style: {
                classes: 'qtip-dark qtip-shadow qtip-rounded'
            }
        });
    });

    // mediainfo tooltip
    $(".mediainfo").each(function () {
        var guid = $(this).attr('title');
        $(this).qtip({
            content: {
                title: {
                    text: 'extended media info...'
                },
                text: 'loading...', // The text to use whilst the AJAX request is loading
                ajax: {
                 url: SERVERROOT + 'ajax_mediainfo', // URL to the local file
                 type: 'GET', // POST or GET
                 data: { id: guid }, // Data to pass along with your request
                 success: function (data, status) {
                    this.set('content.text', data);
                 }
                }
            },
            style: {
                classes: 'qtip-dark qtip-shadow qtip-rounded',
                width: { max: 500 },
                tip: { // Now an object instead of a string
                    corner: 'topLeft', // We declare our corner within the object using the corner sub-option
                    size: {
                        x: 8, // Be careful that the x and y values refer to coordinates on screen, not height or width.
                        y : 8 // Depending on which corner your tooltip is at, x and y could mean either height or width!
                    }
                }
            }
        });
    });

    // preinfo tooltip
    $(".preinfo").each(function() {
        var preid = $(this).attr('title');
        $(this).qtip({
            content: {
              title: {
                  text: 'PreDB info...'
              },
              text: 'loading...', // The text to use whilst the AJAX request is loading
              ajax: {
                 url: SERVERROOT + 'ajax_preinfo', // URL to the local file
                 type: 'GET', // POST or GET
                 data: { id: preid }, // Data to pass along with your request
                 success: function(data, status) {
                    this.set('content.text', data);
                 }
              }
            },
            style: {
                classes: 'qtip-dark qtip-shadow qtip-rounded',
                width: { max: 500 },
                tip: { // Now an object instead of a string
                    corner: 'topLeft', // We declare our corner within the object using the corner sub-option
                    size: {
                        x: 8, // Be careful that the x and y values refer to coordinates on screen, not height or width.
                        y : 8 // Depending on which corner your tooltip is at, x and y could mean either height or width!
                    }
                }
            }
        });
    });
    // titleinfo tooltip
	$(".titleinfo").each(function() {
		var guid = $(this).attr('title');
	  	$(this).qtip({
			content: {
			  title: {
				  text: 'Release info...'
			  },
			  text: 'loading...', // The text to use whilst the AJAX request is loading
			  ajax: {
			     url: SERVERROOT + 'ajax_titleinfo', // URL to the local file
			     type: 'GET', // POST or GET
			     data: { id: guid }, // Data to pass along with your request
			     success: function(data, status) {
			        this.set('content.text', data);
			     }
			  }
			},
			style: {
				classes: 'qtip-dark qtip-shadow qtip-rounded',
				width: { max: 500 },
				tip: { // Now an object instead of a string
					corner: 'topLeft', // We declare our corner within the object using the corner sub-option
					size: {
				    	x: 8, // Be careful that the x and y values refer to coordinates on screen, not height or width.
				    	y : 8 // Depending on which corner your tooltip is at, x and y could mean either height or width!
				 	}
				}
			}
		});
	});
});


$.extend({ // http://plugins.jquery.com/project/URLEncode
URLEncode:function (c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
    while(x<c.length){var m=r.exec(c.substr(x));
    if(m!==null && m.length>1 && m[1]!==''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
URLDecode:function (s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
    while((m=r.exec(o))!==null && m.length>1 && m[1]!==''){b=parseInt(m[1].substr(1),16);
    t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
});


function isValidEmailAddress(emailAddress)
{
    var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
    return pattern.test(emailAddress);
}

function mymovie_del(imdbid, btn)
{
        var rand_no = Math.random();
        $.ajax({
            url         : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
            data        : { del: imdbid},
            dataType    : "html",
            success     : function (data)
            {
                $(btn).hide();
                $(btn).prev("a").show();
            },
            error: function (xhr,err,e) {    }
        });

        return false;
}

function mymovie_add(imdbid, btn)
{
        $(btn).hide();
        $(btn).next("a").show();

        var rand_no = Math.random();
        $.ajax({
            url         : WWW_TOP + '/ajax_mymovies?rand=' + rand_no,
            data        : { add: imdbid},
            dataType    : "html",
            success     : function (data)
            {
            },
            error: function (xhr,err,e) {    }
        });

        return false;
}


