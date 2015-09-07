<h1>{$page->title}</h1>
{if $site->apienabled != "1"}
	<p>
		The api is currently disabled. <a href="{$smarty.const.WWW_TOP}/contact-us">Contact us</a> if you require api
		access.
	</p>
{else}
	<p>
		Here lives the documentation for the api for accessing nzb and index data. Api functions can be
		called by either logged in users, or by providing an apikey.
	</p>
	{if $loggedin=="true"}
		<p>
			Your credentials should be provided as <span
					style="font-family:courier;">?apikey={$userdata.rsstoken}</span>
		</p>
	{/if}
	<h2>Available Functions</h2>
	<p>Use the parameter <span style="font-family:courier;">?t=</span> to specify the function being called.</p>
	<h3>Server Functions</h3>
	<ul>
		<li>
			<b>Capabilities</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=caps">?t=caps</a></span>
			<br/>
			Reports the capabilities of the server. Includes information about the server name, available search
			categories and version number of the newznab protocol being used.
			<br/>
			Capabilities does not require any credentials in order to be ran.
		</li>
	</ul>
	<h3>User Functions</h3>
	<ul>
		<li>
			<b>Register</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=register">?t=register&amp;email=user@newznab.com</a></span>
			<br/>
			Registers a new user account. Does not require any credentials in order to be ran.
			<br/>
			Returns either the registered username and password if successful or an error code.
		</li>
	</ul>
	<h3>Search Functions</h3>
	<ul>
		<li>
			<b>Search</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=search&amp;q=linux&amp;sort=size_asc">?t=search&amp;q=linux&amp;sort=size_asc</a></span>
			<br/>
			Returns a list of nzbs matching a query. You can also filter by site category, minimum size, maximum size or
			group name by including a comma separated list as follows <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=search&amp;cat=1000,2000&amp;group=a.b.multimedia&amp;minsize=0&amp;maxsize=734003200">?t=search&amp;cat=1000,2000&amp;group=a.b.multimedia&amp;minsize=0&amp;maxsize=734003200</a></span>.
			Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the
			search results. Sort options include cat, name, size, files, stats, posted in the format "value_asc/desc",
			e.g. &amp;sort=size_desc
		</li>
		<li>
			<b>TV</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;q=beverly%20hillbillies&amp;season=1&amp;ep=1">?t=tvsearch&amp;q=beverly%20hillbillies&amp;season=1&amp;ep=1</a></span>
			<br/>
			Returns a list of nzbs matching a query, category, tvrageid, season or episode. You can also filter by site
			category by including a comma separated list of categories as follows <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;rid=2204&amp;cat=1000,2000">?t=tvsearch&amp;rid=2204&amp;cat=1000,2000</a></span>.
			Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the
			search results.
		</li>
		<li>
			<b>Movies</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=0023010">?t=movie&amp;imdbid=0023010</a></span>
			<br/>
			Returns a list of nzbs matching a query, an imdbid and optionally a category or genre. Filter by site
			category by including a comma separated list of categories as follows <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=0023010&amp;cat=2030,2040&amp;genre=Romance">?t=movie&amp;imdbid=0023010&amp;cat=2030,2040&amp;genre=Romance</a></span>.
			Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the
			search results.
		</li>
		<li>
			<b>Music</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=music&amp;artist=Jack">?t=music&amp;artist=Jack</a></span>
			<br/>
			Returns a list of nzbs matching an audio based query and optionally a category. Filter by site category by
			including a comma separated list of categories as follows <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=music&amp;artist=Jack&amp;cat=2030,2040">?t=music&amp;artist=Jack&amp;cat=2030,2040</a></span>.
			Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the
			search results. Other search parameters include artist, album, label, year, genre (supports comma separated
			list).
		</li>
		<li>
			<b>Book</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=book&amp;author=Daniel">?t=book&amp;author=Daniel</a></span>
			<br/>
			Returns a list of nzbs matching a book based query. Include <span style="font-family:courier;">&amp;extended=1</span>
			to return extended information in the search results. Other search parameters include title.
		</li>
		<li>
			<b>Details</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
			<br/>
			Returns detailed information about an nzb.
		</li>
		<li>
			<b>GetNfo</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=getnfo&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=getnfo&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
			<br/>
			Returns an nfo file for an nzb. Optional parameter <span style="font-family:courier;">&amp;raw=1</span>
			returns just the nfo file without the rss container.
		</li>
		<li>
			<b>Comments</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=comments&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=comments&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
			<br/>
			Returns comments for an nzb.
		</li>
	</ul>
	<h3>NZB Functions</h3>
	<ul>
		<li>
			<b>Get</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
			<br/>
			Downloads the nzb file associated with an Id.
		</li>
		<li>
			<b>CommentAdd</b> <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=commentadd&amp;id=9ca52909ba9b9e5e6758d815fef4ecda&amp;text=comment">?t=comments&amp;id=9ca52909ba9b9e5e6758d815fef4ecda&amp;text=comment</a></span>
			<br/>
			Adds a comment to an nzb.
		</li>
	</ul>
	<h2>Output Format</h2>
	<p>Obviously not appropriate to functions which return an nzb file.</p>
	<ul>
		<li>
			Xml (default) <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=xml</span>
			<br/>
			Returns the data in an xml document.
		</li>
		<li>
			Json <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=json</span>
			<br/>
			Returns the data in a json object.
		</li>
	</ul>
	<h2>Extended Attributes</h2>
	<p>Using the attrs tag and a comma separated list of supported values, extended information can be returned in the
		search results. <br/>For example <span style="font-family:courier;">?attrs=files,poster,group</span>. Note that
		not every attribute is available for every release type. Below is a list of some of the supported attributes. To
		return all known attributes per release use the parameter <span style="font-family:courier;">?extended=1</span>.
		See the API specification for a full list.</p>
	<ul>
		<li>files</li>
		<li>poster</li>
		<li>group</li>
		<li>team</li>
		<li>grabs</li>
		<li>password</li>
		<li>comments</li>
		<li>usenetdate</li>
		<li>info</li>
		<li>year</li>
		<li>season</li>
		<li>episode</li>
		<li>rageid</li>
		<li>tvtitle</li>
		<li>tvairdate</li>
		<li>video</li>
		<li>audio</li>
		<li>resolution</li>
		<li>framerate</li>
		<li>language</li>
		<li>subs</li>
		<li>imdb</li>
		<li>imdbscore</li>
		<li>imdbtitle</li>
		<li>imdbtagline</li>
		<li>imdbplot</li>
		<li>imdbyear</li>
		<li>imdbdirector</li>
		<li>imdbactors</li>
		<li>genre</li>
		<li>artist</li>
		<li>album</li>
		<li>publisher</li>
		<li>tracks</li>
		<li>coverurl</li>
		<li>backdropcoverurl</li>
		<li>review</li>
	</ul>
{/if}