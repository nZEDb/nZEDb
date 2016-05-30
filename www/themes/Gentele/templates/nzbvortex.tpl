<div class="header">
	<h2>NZBVortex > <strong>Queue</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ NZB
		</ol>
	</div>
</div>
{if $weHasVortex}
	<div id="vortex-error"
		 style="display: none; background-color: #767A9E; text-align: center; padding: 5px; color: #eee"></div>
	<div id="vortex">
	</div>
{literal}
	<script type="text/javascript">
		var timer = 0;
		function getOverview() {
			$.ajax
			({
				url: WWW_TOP + '/nzbvortex?getOverview&isAjax',
				cache: false
			}).done(function (html) {
				$("#vortex").html(html);
				timer = setTimeout(getOverview, 2500);
			}).fail(function (response) {
				$('#vortex').hide();
				$('#vortex-error').show();
				$('#vortex-error').html(response.responseText);
				clearTimeout(timer);
			});
		}
		getOverview();
	</script>
{/literal}
{else}
	<p>Make sure you've entered API key and server URL under profile settings.</p>
{/if}