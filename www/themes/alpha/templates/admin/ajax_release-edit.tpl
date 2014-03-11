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

				});
			</script>
		{/literal}

		<form class="form-horizontal" id="release" action="{$smarty.const.WWW_TOP}/ajax_release-admin/?action=doedit" method="get">

			{foreach from=$idArr item=id}
				<input type="hidden" name="id[]" value="{$id}" />
			{/foreach}
			<input type="hidden" name="from" value="{$from}" />
			<div class="form-group">
				<label for="category" class="col-lg-2 control-label">Category:</label>
				<div class="col-lg-10">
					{html_options id="category" class="form-control" name=category options=$catlist selected=$release.categoryid}
				</div>
			</div>
			<div class="form-group">
				<label for="grabs" class="col-lg-2 control-label">Grabs:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="grabs" name="grabs" value="{$release.grabs}">
				</div>
			</div>
			<div class="form-group">
				<label for="rageID" class="col-lg-2 control-label">Tv Rage Id:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="rageID" name="rageID" value="{$release.rageid}">
				</div>
			</div>
			<div class="form-group">
				<label for="anidbID" class="col-lg-2 control-label">AniDB Id:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="anidbID" name="anidbID" value="{$release.anidbid}">
				</div>
			</div>
			<div class="form-group">
				<label for="season" class="col-lg-2 control-label">Season:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="season" name="season" value="{$release.season}">
				</div>
			</div>
			<div class="form-group">
				<label for="episode" class="col-lg-2 control-label">Episode:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="episode" name="episode" value="{$release.episode}">
				</div>
			</div>
			<div class="form-group">
				<label for="imdbID" class="col-lg-2 control-label">IMDB Id:</label>
				<div class="col-lg-10">
					<input type="text" class="form-control short" id="imdbID" name="imdbID" value="{$release.imdbid}">
				</div>
			</div>
			<input type="submit" class="btn btn-default" value="Save" id="save">
		</form>
	{/if}

</div>