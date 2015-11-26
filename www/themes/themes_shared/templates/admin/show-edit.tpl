<h1>{$page->title}</h1>
<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="POST">
	<input type="hidden" name="from" value="{$smarty.get.from}" />
	<table class="input">
		<tr>
			<td><label for="id">Videos Id:</label></td>
			<td>
				<input id="id" class="medium" name="id" type="text" value="{$show.id}" />
				<input type="hidden" name="id" value="{$show.id}" />
				<div class="hint">The numeric Video Id.  Changing this is not recommended.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tvdb">TVDB Id:</label></td>
			<td>
				<input id="tvdb" class="medium" name="tvdb" type="text" value="{$show.tvdb}" />
				<input type="hidden" name="tvdb" value="{$show.tvdb}" />
				<div class="hint">The numeric TVDB Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tvmaze">TVMaze Id:</label></td>
			<td>
				<input id="tvmaze" class="medium" name="tvmaze" type="text" value="{$show.tvmaze}" />
				<input type="hidden" name="tvmaze" value="{$show.tvmaze}" />
				<div class="hint">The numeric TVMaze Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tmdb">TMDB Id:</label></td>
			<td>
				<input id="tmdb" class="medium" name="tmdb" type="text" value="{$show.tmdb}" />
				<input type="hidden" name="tmdb" value="{$show.tmdb}" />
				<div class="hint">The numeric TMDB Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="trakt">Trakt Id:</label></td>
			<td>
				<input id="trakt" class="medium" name="trakt" type="text" value="{$show.trakt}" />
				<input type="hidden" name="trakt" value="{$show.trakt}" />
				<div class="hint">The numeric Trakt Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="tvrage">TvRage Id:</label></td>
			<td>
				<input id="tvrage" class="medium" name="tvrage" type="text" value="{$show.tvrage}" />
				<input type="hidden" name="tvrage" value="{$show.tvrage}" />
				<div class="hint">The numeric TvRage Show Id.</div>
			</td>
		</tr>
		<tr>
			<td><label for="imdb">IMDB Id:</label></td>
			<td>
				<input id="imdb" class="medium" name="imdb" type="text" value="{$show.imdb}" />
				<input type="hidden" name="imdb" value="{$show.imdb}" />
				<div class="hint">The numeric IMDB Show Id.</div>
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