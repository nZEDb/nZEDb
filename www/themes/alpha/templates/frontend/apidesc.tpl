<h3>{$page->title}</h3>
<p>Here lives the documentation for the api for accessing nzb and index data. Api functions can be called by either logged in users, or by providing an apikey.</p>
<br>
{if $loggedin=="true"}
	<h3>API Credentials</h3>
	<p>Your credentials should be provided as <span style="font-family:courier;">?apikey={$userdata.rsstoken}</span></p>
{/if}
<br>
<h3>Available Functions</h3>
<p>Use the parameter <span style="font-family:courier;">?t=</span> to specify the function being called.</p>
<dl>
	<dt>Capabilities <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=caps">?t=caps</a></span></dt>
	<dd>Reports the capabilities if the server. Includes information about the server name, available search categories and version number of the newznab protocol being used.<br>Capabilities does not require any credentials in order to be ran.</dd>
	<br>
	<dt>Register <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=register&amp;email=user@example.com">?t=register&amp;email=user@example.com</a></span></dt>
	<dd>Registers a new user account. Does not require any credentials in order to be ran.<br>Returns either the registered username and password if successful or an error code.</dd>
	<br>
	<dt>Search <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=search&amp;q=linux">?t=search&amp;q=linux</a></span></dt>
	<dd>Returns a list of nzbs matching a query. You can also  filter by site category by including a comma separated list of categories as follows <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=search&amp;cat=1000,2000">?t=search&amp;cat=1000,2000</a></span>. Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.</dd>
	<br>
	<dt>TV <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;q=law%20and%20order&amp;season=7&amp;ep=12">?t=tvsearch&amp;q=law and order&amp;season=7&amp;ep=12</a></span></dt>
	<dd>Returns a list of nzbs matching a query, category, tvrageid, season or episode.
		You can also filter by site category by including a comma separated list of categories as follows:
		<span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;rid=2204&amp;cat=1000,2000">?t=tvsearch&amp;cat=1000,2000</a></span>.
		Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.
	</dd>
	<dd>
		You can also supply the following parameters to do site specfic ID searches:
		&amp;rid=25056 (TVRage) &amp;tvdbid=153021 (TVDB) &amp;traktid=1393 (Trakt) &amp;tvmazeid=73 (TVMaze) &amp;imdbid=1520211 (IMDB) &amp;tmdbid=1402 (TMDB).
	</dd>
	<br>
	<dt>Movies <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=1418646">?t=movie&amp;imdbid=1418646</a></span></dt>
	<dd>Returns a list of nzbs matching a query, an imdbid and optionally a category. Filter by site category by including a comma separated list of categories as follows <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=1418646&amp;cat=2030,2040">?t=movie&amp;imdbid=1418646&amp;cat=2030,2040</a></span>.  Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.</dd>
	<br>
	<dt>Details <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span></dt>
	<dd>Returns detailed information about an nzb.</dd>
	<br>
	<dt>Info <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span></dt>
	<dd>Returns NFO contents for an NZB.  Retrieve the NFO as file by specifying o=file in the request URI.</dd>
	<br>
	<dt>Get <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span></dt>
	<dd>Downloads the nzb file associated with an Id.</dd>
</dl>
<br>
<h3>Output Format</h3>
<p>Obviously not appropriate to functions which return an nzb/nfo file.</p>
<dl>
	<dt>Xml (default) <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=xml</span></dt>
	<dd>Returns the data in an xml document.</dd>
	<br>
	<dt>Json <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=json</span></dt>
	<dd>Returns the data in a json object.</dd>
</dl>