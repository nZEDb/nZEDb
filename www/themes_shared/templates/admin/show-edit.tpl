<h1>{$page->title}</h1>
<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="POST">
	<input type="hidden" name="from" value="{$smarty.get.from}" />
	<table class="input">
		<tr>
			<td><label for="id">Videos Id:</label></td>
			<td>
				<input id="id" class="short" name="id" type="text" value="{$show.id}" />
				<input type="hidden" name="id" value="{$show.id}" />
				<div class="hint">The numeric TV Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tvdb">TVDB Id:</label></td>
			<td>
				<input id="tvdb" class="short" name="tvdb" type="text" value="{$show.tvdb}" />
				<input type="hidden" name="tvdb" value="{$show.tvdb}" />
				<div class="hint">The numeric TVDB Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="trakt">Trakt Id:</label></td>
			<td>
				<input id="trakt" class="short" name="trakt" type="text" value="{$show.trakt}" />
				<input type="hidden" name="trakt" value="{$show.trakt}" />
				<div class="hint">The numeric Trakt Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tvrage">TvRage Id:</label></td>
			<td>
				<input id="tvrage" class="short" name="tvrage" type="text" value="{$show.tvrage}" />
				<input type="hidden" name="tvrage" value="{$show.tvrage}" />
				<div class="hint">The numeric TvRage Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="title">Show Name:</label></td>
			<td>
				<input id="title" class="long" name="title" type="text" value="{$show.title|escape:'htmlall'}" />
				<div class="hint">The title of the TV show.</div>
			</td>
		</tr>
		<tr>
			<td><label for="summary">Summary:</label></td>
			<td>
				<textarea id="summary" name="summary">{$show.summary|escape:'htmlall'}</textarea>
			</td>
		</tr>
		<tr>
			<td><label for="publisher">Publisher:</label></td>
			<td>
				<input id="publisher" class="long" name="publisher" type="text" value="{$show.publisher|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
		<tr>
			<td><label for="countries_id">Show countries_id:</label></td>
			<td>
				<input id="countries_id" name="countries_id" type="text" value="{$show.countries_id|escape:'htmlall'}" maxlength="2" />
				<div class="hint">The countries_id for the TV show.</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Save" />
			</td>
		</tr>
	</table>
</form>