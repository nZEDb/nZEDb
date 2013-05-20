/**
 * ajax_group_status()
 *
 * @param id        group id
 * @param status    0 = deactive, 1 = activate
 */
function ajax_group_status(id, what)
{
    // no caching of results
    var rand_no = Math.random();
    if (what != undefined)
    {
        $.ajax({
          url       : WWW_TOP + '/admin/ajax_group-edit.php?rand=' + rand_no,
          data      : { group_id: id, group_status: what },
          dataType  : "html",
          success   : function(data)
          {
              $('div#message').html(data);
              $('div#message').show('fast', function() {});

              // switch some links around
              if (what == 0) {
                  $('td#group-' + id).html('<a href="javascript:ajax_group_status('+ id +', 1)" class="group_deactive">Activate</a>');
              }
              else {
                  $('td#group-' + id).html('<a href="javascript:ajax_group_status('+ id +', 0)" class="group_active">Deactivate</a>');
              }

              // fade.. mm
              $('#message').fadeOut(5000);
          },
          error: function(xhr,err,e) { alert( "Error in ajax_group_status: " + err ); }
        });
    }
    else
    {
        alert('Weird.. what group id are looking for?');
    }
}

/**
 * ajax_group_delete()
 *
 * @param id        group id
 */
function ajax_group_delete(id)
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_group-edit.php?action=2&rand=' + rand_no,
	  data      : { group_id: id},
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#grouprow-'+id).fadeOut(2000);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_group_delete: " + err ); }
	});
}

/**
 * ajax_group_reset()
 *
 * @param id        group id
 */
function ajax_group_reset(id)
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_group-edit.php?action=3&rand=' + rand_no,
	  data      : { group_id: id},
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#grouprow-'+id).fadeTo(2000, 0.5);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_group_reset: " + err ); }
	});
}

/**
 * ajax_group_purge()
 *
 * @param id        group id
 */
function ajax_group_purge(id)
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_group-edit.php?action=4&rand=' + rand_no,
	  data      : { group_id: id},
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#grouprow-'+id).fadeTo(2000, 0.5);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_group_purge: " + err ); }
	});
}

/**
 * ajax_all_reset()
 *
 * 
 */
function ajax_all_reset()
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_group-edit.php?action=5&rand=' + rand_no,
	  data      :  "All groups reset.",
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#grouprow-'+id).fadeTo(2000, 0.5);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_all_reset: " + err ); }
	});
}

/**
 * ajax_all_purge()
 *
 * 
 */
function ajax_all_purge()
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_group-edit.php?action=6&rand=' + rand_no,
	  data      : "All groups purged",
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#grouprow-'+id).fadeTo(2000, 0.5);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_all_purge: " + err ); }
	});
}

/**
 * ajax_binaryblacklist_delete()
 *
 * @param id        binary id
 */
function ajax_binaryblacklist_delete(id)
{
    // no caching of results
    var rand_no = Math.random();
	$.ajax({
	  url       : WWW_TOP + '/admin/ajax_binaryblacklist-list.php?action=2&rand=' + rand_no,
	  data      : { bin_id: id},
	  dataType  : "html",
	  success   : function(data)
	  {
		  $('div#message').html(data);
		  $('div#message').show('fast', function() {});
		  $('#row-'+id).fadeOut(2000);
		  $('#message').fadeOut(5000);
	  },
	  error: function(xhr,err,e) { alert( "Error in ajax_binaryblacklist_delete: " + err ); }
	});
}

jQuery(function($){

$('#regexGroupSelect').change(function() {
  document.location="?group=" + $("#regexGroupSelect option:selected").attr('value');
});

// misc
$('.confirm_action').click(function(){ return confirm('Are you sure?'); });

});


