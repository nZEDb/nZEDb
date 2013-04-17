 
<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<input type="hidden" name="from" value="{$smarty.get.from}" />

<table class="input">

<tr>
	<td><label for="name">Original Name</label>:</td>
	<td>
		<input type="hidden" name="id" value="{$release.ID}" />
		<input id="name" class="long" name="name" type="text" value="{$release.name|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="searchname">Search Name</label>:</td>
	<td>
		<input id="searchname" class="long" name="searchname" type="text" value="{$release.searchname|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="fromname">From Name</label>:</td>
	<td>
		<input id="fromname" class="long" name="fromname" type="text" value="{$release.fromname|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="category">Category</label>:</td>
	<td>
		{html_options id="category" name=category options=$catlist selected=$release.categoryID}
	</td>
</tr>

<tr>
	<td><label for="totalpart">Parts</label>:</td>
	<td>
		<input id="totalpart" class="short" name="totalpart" type="text" value="{$release.totalpart}" />
	</td>
</tr>

<tr>
	<td><label for="grabs">Grabs</label>:</td>
	<td>
		<input id="grabs" class="short" name="grabs" type="text" value="{$release.grabs}" />
	</td>
</tr>

<tr>
	<td><label for="rageID">Tv Rage Id</label>:</td>
	<td>
		<input id="rageID" class="short" name="rageID" type="text" value="{$release.rageID}" />
	</td>
</tr>

<tr>
	<td><label for="episode">AniDB Id</label>:</td>
	<td>
		<input id="anidbID" class="short" name="anidbID" type="text" value="{$release.anidbID}" />
	</td>
</tr>

<tr>
	<td><label for="seriesfull">Series Full</label>:</td>
	<td>
		<input id="seriesfull" class="long" name="seriesfull" type="text" value="{$release.seriesfull}" />
	</td>
</tr>

<tr>
	<td><label for="season">Season</label>:</td>
	<td>
		<input id="season" class="short" name="season" type="text" value="{$release.season}" />
	</td>
</tr>

<tr>
	<td><label for="episode">Episode</label>:</td>
	<td>
		<input id="episode" class="short" name="episode" type="text" value="{$release.episode}" />
	</td>
</tr>

<tr>
	<td><label for="episode">IMDB Id</label>:</td>
	<td>
		<input id="imdbID" class="short" name="imdbID" type="text" value="{$release.imdbID}" />
	</td>
</tr>

<tr>
	<td>Group:</td>
	<td>
		{$release.group_name}
	</td>
</tr>

<tr>
	<td><label for="size">Size</label>:</td>
	<td>
		<input id="size" class="long" name="size" type="text" value="{$release.size}" />
	</td>
</tr>

<tr>
	<td><label for="postdate">Posted Date</label>:</td>
	<td>
		<input id="postdate" class="long" name="postdate" type="text" value="{$release.postdate}" />
	</td>
</tr>

<tr>
	<td><label for="adddate">Added Date</label>:</td>
	<td>
		<input id="adddate" class="long" name="adddate" type="text" value="{$release.adddate}" />
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
