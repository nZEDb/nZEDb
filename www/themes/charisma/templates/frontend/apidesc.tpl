<h1>{$page->title}</h1>
<p>Here lives the documentation for the api for accessing nzb and index data. Api functions can be called by either logged in users, or by providing an apikey.</p>
{if $loggedin=="true"}
	<h2>API Credentials</h2>
	<p>Your credentials should be provided as <span style="font-family:courier;">?apikey={$userdata.rsstoken}</span></p>
{/if}
<h2>Available Functions</h2>
<p>Use the parameter <span style="font-family:courier;">?t=</span> to specify the function being called.</p>
<ul>
	<li>
		<b>Capabilities</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=caps">?t=caps</a></span>
		<br/>
		Reports the capabilities if the server. Includes information about the server name, available search categories and version number of the newznab protocol being used.
		<br/>
		Capabilities does not require any credentials in order to be ran.
	</li>
	<li>
		<b>Register</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=register&amp;email=user@example.com">?t=register&amp;email=user@example.com</a></span>
		<br/>
		Registers a new user account. Does not require any credentials in order to be ran.
		<br/>
		Returns either the registered username and password if successful or an error code.
	</li>
	<li>
		<b>Search</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=search&amp;q=linux">?t=search&amp;q=linux</a></span>
		<br/>
		Returns a list of nzbs matching a query. You can also  filter by site category by including a comma separated list of categories as follows <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=search&amp;cat=1000,2000">?t=search&amp;cat=1000,2000</a></span>. Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.
	</li>
	<li>
		<b>TV</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;q=law%20and%20order&amp;season=7&amp;ep=12">?t=tvsearch&amp;q=law and order&amp;season=7&amp;ep=12</a></span>
		<br/>
		Returns a list of nzbs matching a query, category, tvrageid, season or episode. You can also filter by site category by including a comma separated list of categories as follows <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=tvsearch&amp;rid=2204&amp;cat=1000,2000">?t=tvsearch&amp;rid=2204&amp;cat=1000,2000</a></span>.  Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.
	</li>
	<li>
		<b>Movies</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=1418646">?t=movie&amp;imdbid=1418646</a></span>
		<br/>
		Returns a list of nzbs matching a query, an imdbid and optionally a category. Filter by site category by including a comma separated list of categories as follows <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=movie&amp;imdbid=1418646&amp;cat=2030,2040">?t=movie&amp;imdbid=1418646&amp;cat=2030,2040</a></span>.  Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the search results.
	</li>
	<li>
		<b>Details</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		<br/>
		Returns detailed information about an nzb.
	</li>
	<li>
		<b>Info</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		<br/>
		Returns the NFO contents of an NZB.  Specify &o=file to retrieve the NFO as a file download.
	</li>
	<li>
		<b>Get</b> <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda">?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		<br/>
		Downloads the nzb file associated with an Id.
	</li>
</ul>
<h2>Output Format</h2>
<p>Obviously not appropriate to functions which return an nzb file or an NFO.</p>
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
