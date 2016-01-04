<div class="page-header">
	<h1>{$page->title}</h1>
</div>

<p>
	Here you can choose RSS feeds from site categories. The feeds will present either decriptions or
	downloads of NZB files.
</p>

<ul>
	<li>
		Add this string to your feed URL to allow NZB downloads without logging in: <code>&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</code>
	</li>
	<li>
		To remove the NZB from your cart after download add this string to your feed URL: <code>&amp;del=1</code>
	</li>
	<li>
		To change the default link to download an NZB: <code>&amp;dl=1</code>
	</li>
	<li>
		To change the number of results (default is 25, max is 100) returned: <code>&amp;num=50</code>
	</li>
	<li>
		To return TV shows only aired in the last x days (default is all): <code>&amp;airdate=20</code>
	</li>
</ul>

<p>
	Most NZB clients which support NZB RSS feeds will appreciate the full URL, with download link and your user token.
</p>

<p>
	The feeds include additional attributes to help provide better filtering in your NZB client, such as size, group and categorisation. If you want to chain multiple categories together or do more advanced searching, use the <a href="{$smarty.const.WWW_TOP}/apihelp">api</a>, which returns its data in an RSS compatible format.
</p>

<h2>Available Feeds</h2>
<h3>General</h3>
<ul style="text-align: left;">
	<li>
		Full site :
		<code><a href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
	<li>
		<a href="{$smarty.const.WWW_TOP}/cart">My cart</a> :
		<code><a href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1</a></code>
	</li><br/>
	<li>
		<a href="{$smarty.const.WWW_TOP}/myshows">My shows</a> :
		<code><a href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1</a></code>
	</li><br/>
	<li>
		<a href="{$smarty.const.WWW_TOP}/mymovies">My movies</a> :
		<code><a href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1</a></code>
	</li><br/>

</ul>
<h3>Parent Category</h3>
<ul style="text-align: left;">
	{foreach from=$parentcategorylist item=category}
	<li>
		<a href="{$smarty.const.WWW_TOP}/browse?t={$category.id}">{$category.title}</a></code> feed:
		<code><a href="{$smarty.const.WWW_TOP}/rss?t={$category.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/rss?t={$category.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
	{/foreach}

</ul>

<h3>Sub Category</h3>
<ul style="text-align: left;">

	{foreach from=$categorylist item=category}
	<li>
		<a href="{$smarty.const.WWW_TOP}/browse?t={$category.id}">{$category.title}</a> feed:
		<code><a href="{$smarty.const.WWW_TOP}/rss?t={$category.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/rss?t={$category.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
	{/foreach}
</ul>

<h3>Multi Category</h3>
<ul style="text-align: left;">
	<li>
		Multiple categories separated by comma.<br/>
		<code><a href="{$smarty.const.WWW_TOP}/rss?t=1000,2000,3010&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/rss?t=1000,2000,3010&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
</ul>

<h2>Additional Feeds</h2>
<ul style="text-align: left;">
	<li>
		Tv Series (Use the TVRage ID)<br/>
		<code><a href="{$smarty.const.WWW_TOP}/rss?rage=1234&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/RSS/?rage=1234&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
	<li>
		Tv Series aired in last seven days (Using the TVRage ID and airdate)<br/>
		<code><a href="{$smarty.const.WWW_TOP}/rss?rage=1234&amp;airdate=7&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/RSS/?rage=1234&amp;airdate=7&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
	<li>
		Anime Feed (Use the AniDB ID)<br/>
		<code><a href="{$smarty.const.WWW_TOP}/rss?anidb=1234&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{$smarty.const.WWW_TOP}/RSS/?anidb=1234&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}</a></code>
	</li><br/>
</ul>
