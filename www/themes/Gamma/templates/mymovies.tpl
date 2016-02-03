<h2>{$page->title}</h2>

<div class="alert-info">
	<p>
		Using 'My Movies' you can search for movies, and add them to a wishlist. If the movie becomes available it will be added to an <a href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss Feed</a> you can use to automatically download. <br>
		You can <a href="{$smarty.const.WWW_TOP}/mymoviesedit">Manage Your Movie List</a> to remove old items.
	</p>
</div>

<div class="navbar">
	<div class="navbar-inner">
		<form id="frmMyMovieLookup" class="navbar-form pull-left">
			<div class="input-append">
				<input class="input-xlarge" type="text" id="txtsearch" placeholder="Movie Title or IMDB Id" />
				<input id="btnsearch" class="btn btn-success" type="submit" value="Search" />
			</div>
		</form>
	</div>
</div>

<div id="divMovResults">
</div>
