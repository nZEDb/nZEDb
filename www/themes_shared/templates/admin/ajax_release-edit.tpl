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
					<td><label for="rageID">Tv Rage Id:</label></td>
					<td>
						<input id="rageID" class="short" name="rageID" type="text" value="{$release.rageid}" />
					</td>
				</tr>
				<tr>
					<td><label for="anidbID">AniDB Id:</label></td>
					<td>
						<input id="anidbID" class="short" name="anidbID" type="text" value="{$release.anidbid}" />
					</td>
				</tr>
				<tr>
					<td><label for="season">Season:</label></td>
					<td>
						<input id="season" class="short" name="season" type="text" value="{$release.season}" />
					</td>
				</tr>
				<tr>
					<td><label for="episode">Episode:</label></td>
					<td>
						<input id="episode" class="short" name="episode" type="text" value="{$release.episode}" />
					</td>
				</tr>
				<tr>
					<td><label for="episode">IMDB Id:</label></td>
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