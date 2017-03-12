<div class="well well-sm">
	<h1>{$page->title}</h1>
	<a class="btn btn-success" href="{$smarty.const.WWW_TOP}/posters-list.php"><i class="fa fa-arrow-left"></i> Go back</a>
	<form action="{$SCRIPT_NAME}?action=submit" method="POST">
		<table class="input data table table-striped responsive-utilities jambo-table">
			<tr>
				<td>Poster name:</td>
				<td>
					<input type="hidden" name="id" value="{$poster->id}" />
					<input id="poster" class="long" name="poster" type="text" value={if isset($poster->poster)}"{$poster->poster}"{else}""{/if} />
					<div class="hint">Name of the MultiGroup poster</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input class="btn btn-default" type="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
</div>
