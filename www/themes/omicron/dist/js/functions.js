function cart_notify() {
	var n = noty({
		text        : '<div class="alert alert-success"><p><strong>Release added to cart!</p></div>',
	    layout      : 'topCenter', //or left, right, bottom-right...
	    theme       : 'bootstrap',
	    maxVisible  : 10,
	    timeout: 3000,
	});
}

function notify(message, position) {
	var n = noty({
		text        : '<div class="alert alert-success"><p><strong>' + message + '</p></div>',
	    layout      : position, //or left, right, bottom-right...
	    theme       : 'bootstrap',
	    maxVisible  : 10,
	    timeout: 3000,
	});
}

$('.cartadd').click(function(e){
	if ($(this).hasClass('icon_cart_clicked')) return false;
	var guid = $(".guid").attr('id').substring(4);
	$.post( SERVERROOT + "cart?add=" + guid, function(resp){
		$(e.target).addClass('icon_cart_clicked').attr('title','Added to Cart');
		cart_notify();
	});
	return false;
});


$('.sabsend').click(function(e){
	if ($(this).hasClass('icon_sab_clicked')) return false;

	var guid = $(".guid").attr('id').substring(4);
	var nzburl = SERVERROOT + "sendtoqueue/" + guid;

	$.post(nzburl, function(resp){
		$(e.target).addClass('icon_sab_clicked').attr('title','Added to Queue');
		notify('Release added to queue', 'topCenter');
	});
	return false;
});

$('.getsend').click(function(e){
	if ($(this).hasClass('icon_nzbget_clicked')) return false;

	var guid = $(".guid").attr('id').substring(4);
	var nzburl = SERVERROOT + "sendtoqueue/" + guid;

	$.post(nzburl, function(resp){
		$(e.target).addClass('icon_nzbget_clicked').attr('title','Added to Queue');
		notify('NZB sent to NZBGet', 'topCenter');
	});
	return false;
});


$('.vortexsend').click(function(event)
{
	if ($(this).hasClass('icon_nzbvortex_clicked')) return false;
	var guid = $(".guid").attr('id').substring(4);

	if (guid && guid.length > 0)
	{
		$.ajax
		({
			url: SERVERROOT + 'nzbvortex?addQueue='+ guid +'&isAjax',
			cache: false
		}).done(function(html)
		{
			var message = 'Added ' + guid + ' to queue.';
			$(event.target).addClass('icon_nzbvortex_clicked').attr('title', message);
			notify(message, 'topCenter');
		}).fail(function(response)
		{
			alert(response.responseText);
		});
	}
	return false;
});

$('.nntmux_check_all').change(function(){
	if($(this).attr('checked'))
	{
		$(".nzb_check").attr('checked',$(this).attr('checked'));
	} else {
		$(".nzb_check").removeAttr('checked');
	}
});

$('.report_check_all').change(function(){
	if($(this).attr('checked'))
	{
		$(".nzb_check").attr('checked',$(this).attr('checked'));
		$(".rid").attr('checked',$(this).attr('checked'));
	} else {
		$(".nzb_check").removeAttr('checked');
	}
});

$('input.nntmux_multi_operations_download').click(function(){
	var newFormName = 'nzbmulti' + Math.round(+new Date()/1000);
	var newForm = $("<form />", {'action': SERVERROOT + 'getnzb?zip=1', 'method':'post', 'target': '_top', 'id':newFormName});
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		if ($(row).val()!="on")
			$("<input />", {'name':'id[]', 'value':$(row).val(), 'type':'hidden'}).appendTo(newForm);
	});
	newForm.appendTo($('body'));
	console.log(newForm)
	$('#'+newFormName).submit();
});

$('input.nntmux_multi_operations_cart').click(function(){
	var guids = new Array();
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		var guid = $(row).val();
		var $cartIcon = $(row).parent().parent().children('td.icons').children('.icon_cart');
		if (guid && !$cartIcon.hasClass('icon_cart_clicked')){
				$cartIcon.addClass('icon_cart_clicked').attr('title','Added to Cart');	// consider doing this only upon success
				guids.push(guid);
				cart_notify()
			}
			$(this).attr('checked', false);
		});
	$.post( SERVERROOT + "cart?add", { 'add': guids });
});

$('input.nntmux_multi_operations_sab').click(function(){
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		var $sabIcon = $(row).parent().parent().children('td.icons').children('.icon_sab');
		var guid = $(row).val();
		if (guid && !$sabIcon.hasClass('icon_sab_clicked')) {
			var nzburl = SERVERROOT + "sendtoqueue/" + guid;
			$.post( nzburl, function(resp){
				$sabIcon.addClass('icon_sab_clicked').attr('title','Added to Queue');
				notify('NZB added to queue', 'topCenter');
			});
		}
		$(this).attr('checked', false);
	});
});

$('input.nntmux_multi_operations_nzbget').click(function(){
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		var $nzbgetIcon = $(row).parent().parent().children('td.icons').children('.icon_nzbget');
		var guid = $(row).val();
		if (guid && !$nzbgetIcon.hasClass('icon_nzbget_clicked')) {
			var nzburl = SERVERROOT + "sendtoqueue/" + guid;
			$.post( nzburl, function(resp){
				$nzbgetIcon.addClass('icon_nzbget_clicked').attr('title','Added to Queue');
				notify('NZB added to queue', 'topCenter');
			});
		}
		$(this).attr('checked', false);
	});
});

$('input.nntmux_multi_operations_delete').click(function(){
	var ids = "";
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		if ($(row).val()!="on")
			ids += '&id[]='+$(row).val();
	});
	if (ids)
		if (confirm('Are you sure you want to delete the selected releases?')) {
			$.post(SERVERROOT + "ajax_release-admin?action=dodelete"+ids, function(resp){
				location.reload(true);
			});
		}
	});

$('input.nntmux_multi_operations_deletereport').click(function(){
	var ids = "";
	//var rids = "";
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		if ($(row).val()!="on")
			ids += '&id[]='+$(row).val();
			// rids += '&id[]='+$(row).attr("id");
	});
	if (ids)
		if (confirm('Are you sure you want to delete the selected releases?')) {
			$.post(SERVERROOT + "ajax_release-admin?action=dodelete"+ids, function(resp){
			});
			$.post(SERVERROOT + "ajax_report-admin?action=dodelete"+ids, function(resp){
			//	location.reload(true);
			});
		location.reload(true);
		}
	});

$('input.nntmux_multi_operations_rebuild').click(function(){
	var ids = "";
	$("INPUT[type='checkbox']:checked").each( function(i, row) {
		if ($(row).val()!="on")
			ids += '&id[]='+$(row).val();
	});
	if (ids)
		if (confirm('Are you sure you want to rebuild the selected releases?')) {
			$.post(SERVERROOT + "ajax_release-admin?action=dorebuild"+ids, function(resp){
				location.reload(true);
			});
		}
	});
