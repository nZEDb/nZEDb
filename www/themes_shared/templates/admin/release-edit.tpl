<h1>{$page->title}</h1>
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	<input type="hidden" name="from" value="{$from}" />
	<table class="input">
		<tr>
			<td><label for="name">Original Name:</label></td>
			<td>
				<input type="hidden" name="id" value="{$release.id}" />
				<input id="name" class="long" name="name" type="text" value="{$release.name|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="searchname">Search Name:</label></td>
			<td>
				<input id="searchname" class="long" name="searchname" type="text" value="{$release.searchname|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="fromname">From Name:</label></td>
			<td>
				<input id="fromname" class="long" name="fromname" type="text" value="{$release.fromname|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="category">Category:</label></td>
			<td>
				{html_options id="category" name=category options=$catlist selected=$release.categoryid}
			</td>
		</tr>
		<tr>
			<td><label for="totalpart">Parts:</label></td>
			<td>
				<input id="totalpart" class="short" name="totalpart" type="text" value="{$release.totalpart}" />
			</td>
		</tr>
		<tr>
			<td><label for="grabs">Grabs:</label></td>
			<td>
				<input id="grabs" class="short" name="grabs" type="text" value="{$release.grabs}" />
			</td>
		</tr>
		<tr>
			<td><label for="videos_id">Video Id:</label></td>
			<td>
				<input id="videos_id" class="short" name="videos_id" type="text" value="{$release.videos_id}" />
			</td>
		</tr>
		<tr>
			<td><label for="tv_episodes_id">TV Episode Id:</label></td>
			<td>
				<input id="tv_episodes_id" class="short" name="tv_episodes_id" type="text" value="{$release.tv_episodes_id}" />
			</td>
		</tr>
		<tr>
			<td><label for="imdbid">IMDB Id:</label></td>
			<td>
				<input id="imdbid" class="short" name="imdbid" type="text" value="{$release.imdbid}" />
			</td>
		</tr>
		<tr>
			<td><label for="anidbid">AniDB Id:</label></td>
			<td>
				<input id="anidbid" class="short" name="anidbid" type="text" value="{$release.anidbid}" />
			</td>
		</tr>
		<tr>
			<td>Group:</td>
			<td>
				{$release.group_name}
			</td>
		</tr>
		<tr>
			<td><label for="size">Size:</label></td>
			<td>
				<input id="size" class="long" name="size" type="text" value="{$release.size}" />
			</td>
		</tr>
		<tr>
			<td><label for="postdate">Posted Date:</label></td>
			<td>
				<input id="postdate" class="long" name="postdate" type="text" value="{$release.postdate}" />
			</td>
		</tr>
		<tr>
			<td><label for="adddate">Added Date:</label></td>
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