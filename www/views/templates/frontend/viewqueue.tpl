 
<h1>{$page->title}</h1>

<p>The following items are currently being download at <a href="{$sabserver|escape:"htmlall"}">{$sabserver|escape:"htmlall"}</a>. {if $page->site->sabintegrationtype == 2}Edit queue settings in <a href="{$smarty.const.WWW_TOP}/profileedit">your profile</a>.{/if}</p>

<div class="sab_queue"></div>

{literal}
<script type="text/javascript">

function getQueue()
{
	var rand_no = Math.random();

	$.ajax({
	  url: "queuedata?id=" + rand_no,
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

getQueue();

</script>
{/literal}
