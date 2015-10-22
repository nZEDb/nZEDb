<div id="updatePanel">
	{if $success}
		<h4>Successfully updated!</h4>
		{if $from != ''}
			<script type="text/javascript">
				window.location = "{$from}";
			</script>
		{/if}
	{else}
		{literal}
			<script type="text/javascript">
				$('#release').submit(function(){return false;});
				$('#save').click(function() {
					var formData = $('#release').serialize();
					$.post($('#release').attr('action') + '&' + formData, function(resp){
						$('#updatePanel').html(resp);
					});
					location.reload();
				});
			</script>
		{/literal}
		<form id="release" action="{$smarty.const.WWW_TOP}/ajax_release-admin/?action=doedit" method="get">
			{foreach from=$idArr item=id}
				<input type="hidden" name="id[]" value="{$id}" />
			{/foreach}
			<input type="hidden" name="from" value="{$from}" />
			<table class="input">
				<tr>
					<td><label for="category">Category:</label></td>
					<td>
						{html_options id="category" name=category options=$catlist selected=$release.categoryid}
					</td>
				</tr>
				<tr>
					<td><label for="grabs">Grabs:</label></td>
					<td>
						<input id="grabs" class="short" name="grabs" type="text" value="{$release.grabs}" />
					</td>
				</tr>
				<tr>
					<td><label for="videosid">Video Id:</label></td>
					<td>
						<input id="videosid" class="short" name="videosid" type="text" value="{$release.videos_id}" />
					</td>
				</tr>
				<tr>
					<td><label for="episodesid">TV Episode Id:</label></td>
					<td>
						<input id="episodesid" class="short" name="episodesid" type="text" value="{$release.tv_episodes_id}" />
					</td>
				</tr>
				<tr>
					<td><label for="anidbID">AniDB Id:</label></td>
					<td>
						<input id="anidbID" class="short" name="anidbID" type="text" value="{$release.anidbid}" />
					</td>
				</tr>
				<tr>
					<td><label for="imdbID">IMDB Id:</label></td>
					<td>
						<input id="imdbID" class="short" name="imdbID" type="text" value="{$release.imdbid}" />
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input type="submit" value="Save" id="save" />
					</td>
				</tr>
			</table>
		</form>
	{/if}
</div>