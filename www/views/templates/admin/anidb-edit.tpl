 
<h1>{$page->title}</h1>

<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="POST">

<input type="hidden" name="from" value="{$smarty.get.from}" />

<table class="input">

<tr>
	<td><label for="anidbID">AniDB Id</label>:</td>
	<td>
		<input id="anidbID" class="long" name="anidbID" type="text" value="{$anime.anidbID|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="title">Anime Name</label>:</td>
	<td>
		<input id="title" class="long" name="title" type="text" value="{$anime.title|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="type">Type</label>:</td>
	<td>
		<input id="type" class="long" name="type" type="text" value="{$anime.type|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="startdate">Start date</label>:</td>
	<td>
		<input id="startdate" class="long" name="startdate" type="text" value="{$anime.startdate|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="enddate">End date</label>:</td>
	<td>
		<input id="enddate" class="long" name="enddate" type="text" value="{$anime.enddate|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="related">Related</label>:</td>
	<td>
		<input id="related" class="long" name="related" type="text" value="{$anime.related|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="creators">Creators</label>:</td>
	<td>
		<input id="creators" class="long" name="creators" type="text" value="{$anime.creators|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="description">Description</label>:</td>
	<td>
		<textarea id="description" name="description">{$anime.description|escape:'htmlall'}</textarea>
	</td>
</tr>

<tr>
	<td><label for="rating">Rating</label>:</td>
	<td>
		<input id="rating" class="long" name="rating" type="text" value="{$anime.rating|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="categories">Categories</label>:</td>
	<td>
		<input id="categories" class="long" name="categories" type="text" value="{$anime.categories|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="characters">Characters</label>:</td>
	<td>
		<input id="characters" class="long" name="characters" type="text" value="{$anime.characters|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="epnos">Episode numbers</label>:</td>
	<td>
		<input id="epnos" class="long" name="epnos" type="text" value="{$anime.epnos|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="airdates">Episode air dates</label>:</td>
	<td>
		<input id="airdates" class="long" name="airdates" type="text" value="{$anime.airdates|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="episodetitles">Episode titles</label>:</td>
	<td>
		<input id="episodetitles" class="long" name="episodetitles" type="text" value="{$anime.episodetitles|escape:'htmlall'}" />
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