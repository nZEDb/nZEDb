 
<h1>{$page->title}</h1>

<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="post">

<input type="hidden" name="id" value="{$console.ID}" />

<table class="input">

<tr>
	<td><label for="title">Title</label>:</td>
	<td>
		<input id="title" class="long" name="title" type="text" value="{$console.title|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="asin">ASIN</label>:</td>
	<td>
		<input id="asin" name="asin" type="text" value="{$console.asin|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="url">URL</label>:</td>
	<td>
		<input id="url" class="long" name="url" type="text" value="{$console.url|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="salesrank">Sales Rank</label>:</td>
	<td>
		<input id="salesrank" class="short" type="text" name="salesrank" value="{$console.salesrank|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="platform">Platform</label>:</td>
	<td>
		<input id="platform" class="long" name="platform" type="text" value="{$console.platform|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="publisher">Publisher</label>:</td>
	<td>
		<input id="publisher" class="long" name="publisher" type="text" value="{$console.publisher|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="releasedate">Release Date</label>:</td>
	<td>
		<input id="releasedate" name="releasedate" type="text" value="{$console.releasedate|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="esrb">Rating</label>:</td>
	<td>
		<input id="esrb" class="short" name="esrb" type="text" value="{$console.esrb|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="genre">Genre</label>:</td>
	<td>
		<select id="genre" name="genre">
		{foreach from=$genres item=gen}
			<option {if $gen.ID == $console.genreID}selected="selected"{/if} value="{$gen.ID}">{$gen.title|escape:'htmlall'}</option>
		{/foreach}
		</select>
	</td>
</tr>

<tr>
	<td><label for="cover">Cover Image</label>:</td>
	<td>
		<input type="file" id="cover" name="cover" />
		{if $console.cover == 1}
			<img style="max-width:200px; display:block;" src="{$smarty.const.WWW_TOP}/../covers/console/{$console.ID}.jpg" alt="" />
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