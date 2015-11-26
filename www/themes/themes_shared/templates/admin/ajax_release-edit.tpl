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
					var formData = $('#release');
					var postUrl = formData.attr('action');
					$.post(postUrl, '&' + formData.serialize(), function(data){ $('#updatePanel').html(data) });
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
					<td><label for="anidbid">AniDB Id:</label></td>
					<td>
						<input id="anidbid" class="short" name="anidbid" type="text" value="{$release.anidbid}" />
					</td>
				</tr>
				<tr>
					<td><label for="imdbid">IMDB Id:</label></td>
					<td>
						<input id="imdbid" class="short" name="imdbid" type="text" value="{$release.imdbid}" />
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