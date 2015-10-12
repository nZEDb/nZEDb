<div class="header">
	<h2>Download > <strong>Queue</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ NZB
		</ol>
	</div>
</div>
{if $error == ''}
{if $page->settings->getSetting('sabintegrationtype') > 0 || $user.queuetype == 2}
<p style="text-align:center;">
	The following queue is pulled from
	<a href="{$serverURL|escape:"htmlall"}">{$serverURL|escape:"htmlall"}</a>.
	<br/>
	{if $page->settings->getSetting('sabintegrationtype') == 2 || $user.queuetype == 2}Edit your queue settings in
		<a href="{$smarty.const.WWW_TOP}/profileedit">your profile</a>
		.{/if}
</p>
<div class="sab_queue"></div>
{if $user.queuetype == 2}
{literal}
	<script type="text/javascript">
		function getQueue() {
			var rand_no = Math.random();
			$.ajax({
				url: "nzbgetqueuedata?id=" + rand_no,
				cache: false,
				success: function (html) {
					$(".sab_queue").html(html);
					setTimeout("getQueue()", 2500);
				},
				error: function () {
					$(".sab_queue").html("<p style='text-align:center;'>Could not contact your queue. <a href=\"javascript:location.reload(true)\">Refresh</a></p>");
				},
				timeout: 5000
			});
		}
	</script>
{/literal}
{else}
{literal}
	<script type="text/javascript">
		function getQueue() {
			var rand_no = Math.random();
			$.ajax({
				url: "sabqueuedata?id=" + rand_no,
				cache: false,
				success: function (html) {
					$(".sab_queue").html(html);
					setTimeout("getQueue()", 2500);
				},
				error: function () {
					$(".sab_queue").html("<p style='text-align:center;'>Could not contact your queue. <a href=\"javascript:location.reload(true)\">Refresh</a></p>");
				},
				timeout: 5000
			});
		}
	</script>
{/literal}
{/if}
<body onLoad="getQueue();">
{else}
<p style="text-align:center;">The {$queueType} queue has been disabled by the administrator.</p>
{/if}
{else}
<p style="text-align:center;">{$error}</p>
{/if}