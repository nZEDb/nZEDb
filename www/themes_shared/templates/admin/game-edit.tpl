<h1>{$page->title}</h1>
<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="post">
	<input type="hidden" name="id" value="{$game.id}" />
	<table class="input">
		<tr>
			<td><label for="title">Title:</label></td>
			<td>
				<input id="title" class="long" name="title" type="text" value="{$game.title|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="asin">Game ID:</label></td>
			<td>
				<input id="asin" name="asin" type="text" value="{$game.asin|escape:'htmlall'}" />
				<br />
				(Game ID of the url posted below, if unknown put 0)
			</td>
		</tr>
		<tr>
			<td><label for="url">URL:</label></td>
			<td>
				<input id="url" class="long" name="url" type="text" value="{$game.url|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="trailerurl">Trailer URL:</label></td>
			<td>
				<input id="trailerurl" class="long" name="trailerurl" type="text" value="{$game.trailer|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="publisher">Publisher:</label></td>
			<td>
				<input id="publisher" class="long" name="publisher" type="text" value="{$game.publisher|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="releasedate">Release Date:</label></td>
			<td>
				<input id="releasedate" name="releasedate" type="text" value="{$game.releasedate|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="esrb">Rating:</label></td>
			<td>
				<input id="esrb" class="short" name="esrb" type="text" value="{$game.esrb|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="genre_list">Genre:</label></td>
			<td>
				<select id="genre_list" name="genre" />
				{foreach from=$genres item=gen}
					<option {if $gen.id == $game.genre_id}selected="selected"{/if} value="{$gen.id}">{$gen.title|escape:'htmlall'}</option>
				{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="cover">Cover Image:</label></td>
			<td>
				<input type="file" id="cover" name="cover" />
				{if $game.cover == 1}
					<img style="max-width:200px; display:block;" src="{$smarty.const.WWW_TOP}/../covers/games/{$game.id}.jpg" alt="" />
				{/if}
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
