<h1>{$page->title}</h1>

<p>
Using 'My Movies' you can search for movies, and add them to a wishlist. If the movie becomes available it will be added to an <a href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}">Rss Feed</a> you can use to automatically download. You can <a href="{$smarty.const.WWW_TOP}/mymoviesedit">Manage Your Movie List</a> to remove old items.
</p>
<center>
<table class="rndbtn" border="0" cellpadding="2" cellspacing="0">
<tr>
	<th>Movie Title or IMDB Id</th>
</tr>
<tr>
	<td>
		<form id="frmMyMovieLookup">
			<input type="text" id="txtsearch" />
			<input id="btnsearch" type="submit" value="Search" />
		</form>
	</td>
</tr>
</table>
</center>
<div id="divMovResults">
</div>


