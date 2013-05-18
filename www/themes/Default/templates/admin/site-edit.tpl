 
<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="post">

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<fieldset>
<legend>Main Site Settings, Html Layout, Tags</legend>
<table class="input">

<tr>
	<td><label for="title">Title</label>:</td>
	<td>
		<input id="title" class="long" name="title" type="text" value="{$fsite->title}" />
		<div class="hint">Displayed around the site and contact form as the name for the site.</div>
	</td>
</tr>

<tr>
	<td><label for="strapline">Strapline</label>:</td>
	<td>
		<input id="strapline" class="long" name="strapline" type="text" value="{$fsite->strapline}" />
		<div class="hint">Displayed in the header on every public page.</div>
	</td>
</tr>

<tr>
	<td><label for="metatitle">Meta Title</label>:</td>
	<td>
		<input id="metatitle" class="long" name="metatitle" type="text" value="{$fsite->metatitle}" />
		<div class="hint">Stem meta-tag appended to all page title tags.</div>
	</td>
</tr>


<tr>
	<td><label for="metadescription">Meta Description</label>:</td>
	<td>
		<textarea id="metadescription" name="metadescription">{$fsite->metadescription}</textarea>
		<div class="hint">Stem meta-description appended to all page meta description tags.</div>
	</td>
</tr>

<tr>
	<td><label for="metakeywords">Meta Keywords</label>:</td>
	<td>
		<textarea id="metakeywords" name="metakeywords">{$fsite->metakeywords}</textarea>
		<div class="hint">Stem meta-keywords appended to all page meta keyword tags.</div>
	</td>
</tr>

<tr>
	<td><label for="footer">Footer</label>:</td>
	<td>
		<textarea id="footer" name="footer">{$fsite->footer}</textarea>
		<div class="hint">Displayed in the footer section of every public page.</div>
	</td>
</tr>

<tr>
	<td><label for="style">Default Home Page</label>:</td>
	<td>
		<input id="home_link" class="long" name="home_link" type="text" value="{$fsite->home_link}" />
		<div class="hint">The relative path to a the landing page shown when a user logs in, or clicks the home link.</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="codename">Code Name</label>:</td>
	<td>
		<input id="codename" name="code" type="text" value="{$fsite->code}" />
		<input type="hidden" name="id" value="{$fsite->id}" />
		<div class="hint">A just for fun value shown in debug and not on public pages.</div>
	</td>
</tr>

<tr>
	<td><label for="style">Categorize Language</label>:</td>
	<td>
		{html_options class="catlanguage" id="catlanguage" name='catlanguage' values=$langlist_ids output=$langlist_names selected=$fsite->catlanguage}
		<div class="hint">Which category.php file to use. (This is WIP, looking for people to help with this. So right now I suggest sticking to english.)</div>
	</td>
</tr>

<tr>
	<td><label for="categorizeforeign">Categorize Foreign</label>:</td>
	<td>
		{html_radios id="categorizeforeign" name='categorizeforeign' values=$yesno_ids output=$yesno_names selected=$fsite->categorizeforeign separator='<br />'}
		<div class="hint">This only works if the above is set to english. Whether to send foreign movies/tv to foreign sections or not. If set to true they will go in foreign categories.</div>
	</td>
</tr>

<tr>
	<td><label for="style">Theme</label>:</td>
	<td>
		{html_options class="siteeditstyle" id="style" name='style' values=$themelist output=$themelist selected=$fsite->style}
		<div class="hint">The theme folder which will be loaded for css and images. (Use / for default)</div>
	</td>
</tr>

<tr>
	<td><label for="style">User Menu Position</label>:</td>
	<td>
		{html_options class="siteeditmenuposition" id="menuposition" name='menuposition' values=$menupos_ids output=$menupos_names selected=$fsite->menuposition}
		<div class="hint">Where the menu should appear. Moving the menu to the top will require using a theme which widens the content panel. (not currently functional)</div>
	</td>
</tr>

<tr>
	<td><label for="style">Dereferrer Link</label>:</td>
	<td>
		<input id="dereferrer_link" class="long" name="dereferrer_link" type="text" value="{$fsite->dereferrer_link}" />
		<div class="hint">Optional URL to prepend to external links</div>
	</td>
</tr>

<tr>
	<td><label for="email">Email</label>:</td>
	<td>
		<input id="email" class="long" name="email" type="text" value="{$fsite->email}" />
		<div class="hint">Shown in the contact us page, and where the contact html form is sent to.</div>
	</td>
</tr>

<tr>
	<td><label for="tandc">Terms and Conditions</label>:</td>
	<td>
		<textarea id="tandc" name="tandc">{$fsite->tandc}</textarea>
		<div class="hint">Text displayed in the terms and conditions page.</div>
	</td>
</tr>

</table>
</fieldset>

<fieldset>
<legend>Google Adsense, Analytics and 3rd Party Banners</legend>
<table class="input">
<tr>
	<td style="width:160px;"><label for="google_analytics_acc">Google Analytics</label>:</td>
	<td>
		<input id="google_analytics_acc" name="google_analytics_acc" type="text" value="{$fsite->google_analytics_acc}" />
		<div class="hint">e.g. UA-xxxxxx-x</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="google_adsense_acc">Google Adsense</label>:</td>
	<td>
		<input id="google_adsense_acc" name="google_adsense_acc" type="text" value="{$fsite->google_adsense_acc}" />
		<div class="hint">e.g. pub-123123123123123</div>
	</td>
</tr>

<tr>
	<td><label for="google_adsense_search">Google Adsense Search</label>:</td>
	<td>
		<input id="google_adsense_search" name="google_adsense_search" type="text" value="{$fsite->google_adsense_search}" />
		<div class="hint">The ID of the google search ad panel displayed at the bottom of the left menu.</div>
	</td>
</tr>

<tr>
	<td><label for="adheader">Advert Space in Banner</label>:</td>
	<td>
		<textarea id="adheader" name="adheader">{$fsite->adheader}</textarea>
		<div class="hint">The banner slot in the header.</div>
	</td>
</tr>

<tr>
	<td><label for="adbrowse">Advert Space in Browse List</label>:</td>
	<td>
		<textarea id="adbrowse" name="adbrowse">{$fsite->adbrowse}</textarea>
		<div class="hint">The banner slot in the header.</div>
	</td>
</tr>

<tr>
	<td><label for="addetail">Advert Space in Detail View</label>:</td>
	<td>
		<textarea id="addetail" name="addetail">{$fsite->addetail}</textarea>
		<div class="hint">The banner slot in the release details view.</div>
	</td>
</tr>

</table>
</fieldset>


<fieldset>
<legend>3<sup>rd</sup> Party API Keys</legend>
<table class="input">
<tr>
	<td style="width:160px;"><label for="tmdbkey">TMDB Key</label>:</td>
	<td>
		<input id="tmdbkey" class="long" name="tmdbkey" type="text" value="{$fsite->tmdbkey}" />
		<div class="hint">The api key used for access to tmdb.</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="rottentomatokey">Rotten Tomatoes Key</label>:</td>
	<td>
		<input id="rottentomatokey" class="long" name="rottentomatokey" type="text" value="{$fsite->rottentomatokey}" />
		<div class="hint">The api key used for access to rotten tomatoes.</div>
	</td>
</tr>

<tr>
	<td><label for="amazonpubkey">Amazon Public Key</label>:</td>
	<td>
		<input id="amazonpubkey" class="long" name="amazonpubkey" type="text" value="{$fsite->amazonpubkey}" />
		<div class="hint">The amazon public api key. Used for music/book lookups.</div>
	</td>
</tr>

<tr>
	<td><label for="amazonprivkey">Amazon Private Key</label>:</td>
	<td>
		<input id="amazonprivkey" class="long" name="amazonprivkey" type="text" value="{$fsite->amazonprivkey}" />
		<div class="hint">The amazon private api key. Used for music/book lookups.</div>
	</td>
</tr>

<tr>
	<td><label for="amazonassociatetag">Amazon Associate Tag</label>:</td>
	<td>
		<input id="amazonassociatetag" class="long" name="amazonassociatetag" type="text" value="{$fsite->amazonassociatetag}" />
		<div class="hint">The amazon associate tag. Used for music/book lookups.</div>
	</td>
</tr>

<tr>
	<td><label for="trakttvkey">Trakt.tv API key</label>:</td>
	<td>
		<input id="trakttvkey" class="long" name="trakttvkey" type="text" value="{$fsite->trakttvkey}" />
		<div class="hint">The trakt.tv api key. Used for movie and tv lookups.</div>
	</td>
</tr>

</table>
</fieldset>

<fieldset>
<legend>3<sup>rd</sup> Party Application Paths</legend>
<table class="input">
<tr>
	<td style="width:160px;"><label for="unrarpath">Unrar Path</label>:</td>
	<td>
		<input id="unrarpath" class="long" name="unrarpath" type="text" value="{$fsite->unrarpath}" />
		<div class="hint">The path to an unrar binary, used in deep password detection and media info grabbing.
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/unrar.exe</span></div>
	</td>
</tr>

<tr>
	<td><label for="tmpunrarpath">Temp Unrar File Path</label>:</td>
	<td>
		<input id="tmpunrarpath" class="long" name="tmpunrarpath" type="text" value="{$fsite->tmpunrarpath}" />
		<div class="hint">The path to where unrar puts files. WARNING: This directory will have its contents deleted.
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/temp/path/stuff/will/be/unpacked/to</span></div>
	</td>
</tr>

<tr>
	<td><label for="mediainfopath">Mediainfo Path</label>:</td>
	<td>
		<input id="mediainfopath" class="long" name="mediainfopath" type="text" value="{$fsite->mediainfopath}" />
		<div class="hint">The path to the <a href="http://mediainfo.sourceforge.net">mediainfo</a> binary. Used for deep file media analysis. Use empty path to disable mediainfo checks
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/mediainfo.exe</span></div>
	</td>
</tr>

<tr>
	<td><label for="ffmpegpath">Ffmpeg Path</label>:</td>
	<td>
		<input id="ffmpegpath" class="long" name="ffmpegpath" type="text" value="{$fsite->ffmpegpath}" />
		<div class="hint">The path to the <a href="http://www.ffmpeg.org/">ffmpeg</a> binary. Used for thumbnailing. Use empty path to disable thumbnailing.
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/ffmpeg.exe</span></div>
	</td>
</tr>

</table>
</fieldset>


<fieldset>
<legend>SABnzbd Integration Settings</legend>
<table class="input">
<tr>
	<td style="width:160px;"><label for="sabintegrationtype">Integration Type</label>:</td>
	<td>
		{html_radios id="sabintegrationtype" name='sabintegrationtype' values=$sabintegrationtype_ids output=$sabintegrationtype_names selected=$fsite->sabintegrationtype separator='<br />'}
		<div class="hint">Whether to allow integration with a SAB install and if so what type of integration<br/></div>
	</td>
</tr>

<tr>
	<td><label for="saburl">SABnzbd Url</label>:</td>
	<td>
		<input id="saburl" class="long" name="saburl" type="text" value="{$fsite->saburl}" />
		<div class="hint">The url of the SAB installation, for example: http://localhost:8080/sabnzbd/</div>
	</td>
</tr>

<tr>
	<td><label for="sabapikey">SABnzbd Api Key</label>:</td>
	<td>
		<input id="sabapikey" class="long" name="sabapikey" type="text" value="{$fsite->sabapikey}" />
		<div class="hint">The Api key of the SAB installation. Can be the full api key or the nzb api key (as of SAB 0.6)</div>
	</td>
</tr>

<tr>
	<td><label for="sabapikeytype">Api Key Type</label>:</td>
	<td>
		{html_radios id="sabapikeytype" name='sabapikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$fsite->sabapikeytype separator='<br />'}
		<div class="hint">Select the type of api key you entered in the above setting</div>
	</td>
</tr>

<tr>
	<td><label for="sabpriority">Priority Level</label>:</td>
	<td>
		{html_options id="sabpriority" name='sabpriority' values=$sabpriority_ids output=$sabpriority_names selected=$fsite->sabpriority}
		<div class="hint">Set the priority level for NZBs that are added to your queue</div>
	</td>
</tr>

</table>
</fieldset>


<fieldset>
<legend>Usenet Settings</legend>
<table class="input">

<tr>
	<td><label for="nzbpath">Nzb File Path</label>:</td>
	<td>
		<input id="nzbpath" class="long" name="nzbpath" type="text" value="{$fsite->nzbpath}" />
		<div class="hint">The directory where nzb files will be stored.</div>
	</td>
</tr>

<tr>
	<td><label for="minfilestoformrelease">Minimum Files to Make a Release</label>:</td>
	<td>
		<input class="tiny" id="minfilestoformrelease" name="minfilestoformrelease" type="text" value="{$fsite->minfilestoformrelease}" />
		<div class="hint">The minimum number of files to make a release. i.e. if set to two, then releases which only contain one file will not be created.</div>
	</td>
</tr>

<tr>
	<td><label for="minsizetoformrelease">Minimum File Size to Make a Release</label>:</td>
	<td>
		<input class="small" id="minsizetoformrelease" name="minsizetoformrelease" type="text" value="{$fsite->minsizetoformrelease}" />
		<div class="hint">The minimum total size in bytes to make a release. If set to 0, then ignored. Only deletes during release creation.</div>
	</td>
</tr>

<tr>
	<td><label for="maxsizetoformrelease">Maximum File Size to Make a Release</label>:</td>
	<td>
		<input class="small" id="maxsizetoformrelease" name="maxsizetoformrelease" type="text" value="{$fsite->maxsizetoformrelease}" />
		<div class="hint">The maximum total size in bytes to make a release. If set to 0, then ignored. Only deletes during release creation.</div>
	</td>
</tr>

<tr>
	<td><label for="checkpasswordedrar">Check For Passworded Releases</label>:</td>
	<td>
		{html_radios id="checkpasswordedrar" name='checkpasswordedrar' values=$passwd_ids output=$passwd_names selected=$fsite->checkpasswordedrar separator='<br />'}
		<div class="hint">Whether to attempt to peek into every release, to see if rar files are password protected.<br/></div>
	</td>
</tr>

<tr>
	<td><label for="deletepasswordedrelease">Delete Passworded Releases</label>:</td>
	<td>
		{html_radios id="deletepasswordedrelease" name='deletepasswordedrelease' values=$yesno_ids output=$yesno_names selected=$fsite->deletepasswordedrelease separator='<br />'}
		<div class="hint">Whether to delete releases which are passworded or potentially passworded.<br/></div>
	</td>
</tr>

<tr>
	<td><label for="showpasswordedrelease">Show Passworded Releases</label>:</td>
	<td>
		{html_options id="showpasswordedrelease" name='showpasswordedrelease' values=$passworded_ids output=$passworded_names selected=$fsite->showpasswordedrelease}
		<div class="hint">Whether to show passworded or potentially passworded releases in browse, search, api and rss feeds. Potentially passworded means releases which contain .cab or .ace files which are typically password protected.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupnfo">Lookup NFO</label>:</td>
	<td>
		{html_radios id="lookupnfo" name='lookupnfo' values=$yesno_ids output=$yesno_names selected=$fsite->lookupnfo separator='<br />'}
		<div class="hint">Whether to attempt to retrieve the an nfo file from usenet when processing binaries.<br/><strong>NOTE: disabling nfo lookups will disable movie lookups.</strong></div>
	</td>
</tr>

<tr>
	<td><label for="lookuptvrage">Lookup TV Rage</label>:</td>
	<td>
		{html_radios id="lookuptvrage" name='lookuptvrage' values=$yesno_ids output=$yesno_names selected=$fsite->lookuptvrage separator='<br />'}
		<div class="hint">Whether to attempt to lookup tv rage ids on the web when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupimdb">Lookup Movies</label>:</td>
	<td>
		{html_radios id="lookupimdb" name='lookupimdb' values=$yesno_ids output=$yesno_names selected=$fsite->lookupimdb separator='<br />'}
		<div class="hint">Whether to attempt to lookup film information from IMDB or TheMovieDB when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupanidb">Lookup AniDB</label>:</td>
	<td>
		{html_radios id="lookupanidb" name='lookupanidb' values=$yesno_ids output=$yesno_names selected=$fsite->lookupanidb separator='<br />'}
		<div class="hint">Whether to attempt to lookup anime information from AniDB when processing binaries. Currently it is not recommend to enable this.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupmusic">Lookup Music</label>:</td>
	<td>
		{html_radios id="lookupmusic" name='lookupmusic' values=$yesno_ids output=$yesno_names selected=$fsite->lookupmusic separator='<br />'}
		<div class="hint">Whether to attempt to lookup music information from Amazon when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupgames">Lookup Games</label>:</td>
	<td>
		{html_radios id="lookupgames" name='lookupgames' values=$yesno_ids output=$yesno_names selected=$fsite->lookupgames separator='<br />'}
		<div class="hint">Whether to attempt to lookup game information from Amazon when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupbooks">Lookup Books</label>:</td>
	<td>
		{html_radios id="lookupbooks" name='lookupbooks' values=$yesno_ids output=$yesno_names selected=$fsite->lookupbooks separator='<br />'}
		<div class="hint">Whether to attempt to lookup book information from Amazon when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="compressedheaders">Use Compressed Headers</label>:</td>
	<td>
		{html_radios id="compressedheaders" name='compressedheaders' values=$yesno_ids output=$yesno_names selected=$fsite->compressedheaders separator='<br />'}
		<div class="hint">Some servers allow headers to be sent over in a compressed format.  If enabled this will use much less bandwidth, but processing times may increase.</div>
	</td>
</tr>

<tr>
	<td><label for="newgroupscanmethod">Where to start new groups</label>:</td>
	<td>
		{html_radios id="newgroupscanmethod" name='newgroupscanmethod' values=$yesno_ids output=$newgroupscan_names selected=$fsite->newgroupscanmethod separator='<br />'}
		<input class="tiny" id="newgroupdaystoscan" name="newgroupdaystoscan" type="text" value="{$fsite->newgroupdaystoscan}" /> Days  or 
		<input class="small" id="newgroupmsgstoscan" name="newgroupmsgstoscan" type="text" value="{$fsite->newgroupmsgstoscan}" /> Posts<br />
		<div class="hint">Scan back X (posts/days) for each new group?  Can backfill to scan further.</div>
	</td>
</tr>
</table>
</fieldset>

<fieldset>
<legend>Advanced Settings - For advanced users</legend>
<table class="input">

<tr>
	<td><label for="maxaddprocessed">Maximum add PP per run</label>:</td>
	<td>
		<input class="tiny" id="maxaddprocessed" name="maxaddprocessed" type="text" value="{$fsite->maxaddprocessed}" />
		<div class="hint">The maximum amount of releases to process for passwords/previews/mediainfo per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxpartsprocessed">Maximum add PP parts downloaded</label>:</td>
	<td>
		<input class="tiny" id="maxpartsprocessed" name="maxpartsprocessed" type="text" value="{$fsite->maxpartsprocessed}" />
		<div class="hint">If a part fails to download while post processing, this will retry up to the amount you set, then give up.</div>
	</td>
</tr>

<tr>
	<td><label for="passchkattempts">Maximum add PP parts checked</label>:</td>
	<td>
		<input class="tiny" id="passchkattempts" name="passchkattempts" type="text" value="{$fsite->passchkattempts}" />
		<div class="hint">This overrides the above setting if set above 1. How many parts to check for a password before giving up. This slows down post processing massively, better to leave it 1.</div>
	</td>
</tr>

<tr>
	<td><label for="maxnfoprocessed">Maximum NFO files per run</label>:</td>
	<td>
		<input class="tiny" id="maxnfoprocessed" name="maxnfoprocessed" type="text" value="{$fsite->maxnfoprocessed}" />
		<div class="hint">The maximum amount of NFO files to process per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxrageprocessed">Maximum TVRage per run</label>:</td>
	<td>
		<input class="tiny" id="maxrageprocessed" name="maxrageprocessed" type="text" value="{$fsite->maxrageprocessed}" />
		<div class="hint">The maximum amount of TV shows to process with TVRage per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maximdbprocessed">Maximum movies per run</label>:</td>
	<td>
		<input class="tiny" id="maximdbprocessed" name="maximdbprocessed" type="text" value="{$fsite->maximdbprocessed}" />
		<div class="hint">The maximum amount of movies to process with IMDB per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxanidbprocessed">Maximum anidb per run</label>:</td>
	<td>
		<input class="tiny" id="maxanidbprocessed" name="maxanidbprocessed" type="text" value="{$fsite->maxanidbprocessed}" />
		<div class="hint">The maximum amount of anime to process with anidb per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxmusicprocessed">Maximum music per run</label>:</td>
	<td>
		<input class="tiny" id="maxmusicprocessed" name="maxmusicprocessed" type="text" value="{$fsite->maxmusicprocessed}" />
		<div class="hint">The maximum amount of music to process with amazon per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxgamesprocessed">Maximum games per run</label>:</td>
	<td>
		<input class="tiny" id="maxgamesprocessed" name="maxgamesprocessed" type="text" value="{$fsite->maxgamesprocessed}" />
		<div class="hint">The maximum amount of games to process with amazon per run.</div>
	</td>
</tr>

<tr>
	<td><label for="maxbooksprocessed">Maximum books per run</label>:</td>
	<td>
		<input class="tiny" id="maxbooksprocessed" name="maxbooksprocessed" type="text" value="{$fsite->maxbooksprocessed}" />
		<div class="hint">The maximum amount of books to process with amazon per run.</div>
	</td>
</tr>

<tr>
	<td><label for="amazonsleep">Amazon sleep time</label>:</td>
	<td>
		<input class="tiny" id="amazonsleep" name="amazonsleep" type="text" value="{$fsite->amazonsleep}" />
		<div class="hint">Sleep time in milliseconds to wait in between amazon requests. If you thread post-proc, multiply by 3. https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html</div>
	</td>
</tr>

<tr>
    <td><label for="postthreads">Postprocessing Threads</label>:</td>
    <td>
        <input class="tiny" id="postthreads" name="postthreads" type="text" value="{$fsite->postthreads}" />
        <div class="hint">The number of threads for postprocessing.</div>
    </td>
</tr>

<tr>
    <td><label for="binarythreads">Update Binaries Threads</label>:</td>
    <td>
        <input class="tiny" id="binarythreads" name="binarythreads" type="text" value="{$fsite->binarythreads}" />
        <div class="hint">The number of threads for update_binaries.</div>
    </td>
</tr>

<tr>
    <td><label for="backfillthreads">Backfill Threads</label>:</td>
    <td>
        <input class="tiny" id="backfillthreads" name="backfillthreads" type="text" value="{$fsite->backfillthreads}" />
        <div class="hint">The number of threads for backfill.</div>
    </td>
</tr>

<tr>
    <td><label for="nzbthreads">Import-nzb Threads</label>:</td>
    <td>
        <input class="tiny" id="nzbthreads" name="nzbthreads" type="text" value="{$fsite->nzbthreads}" />
        <div class="hint">The number of threads for import-nzb-bulk. This will thread each subfolder.</div>
    </td>
</tr>

<tr>
	<td><label for="nzbsplitlevel">Nzb File Path Level Deep</label>:</td>
	<td>
		<input id="nzbsplitlevel" class="tiny" name="nzbsplitlevel" type="text" value="{$fsite->nzbsplitlevel}" />
		<div class="hint">Levels deep to store the nzb Files.</div>
	</td>
</tr>

<tr>
	<td><label for="releaseretentiondays">Release Retention</label>:</td>
	<td>
		<input class="tiny" id="releasedays" name="releaseretentiondays" type="text" value="{$fsite->releaseretentiondays}" />
		<div class="hint">!!THIS IS NOT HEADER RETENTION!! The number of days releases will be retained for use throughout site. Set to 0 to disable.</div>
	</td>
</tr>

<tr>
	<td><label for="releasecompletion">Release Completion</label>:</td>
	<td>
		<input class="tiny" id="releasecompletion" name="releasecompletion" type="text" value="{$fsite->releasecompletion}" />
		<div class="hint">The minimum completion % to keep a release. Set to 0 to disable.</div>
	</td>
</tr>

<tr>
	<td><label for="crossposttime">Crossposted Time Check</label>:</td>
	<td>
		<input class="tiny" id="crossposttime" name="crossposttime" type="text" value="{$fsite->crossposttime}" />
		<div class="hint">The time in hours to check for crossposted releases.</div>
	</td>
</tr>

<tr>
	<td><label for="maxmssgs">Max Messages</label>:</td>
	<td>
		<input class="small" id="maxmssgs" name="maxmssgs" type="text" value="{$fsite->maxmssgs}" />
		<div class="hint">The maximum number of messages to fetch at a time from the server. Only raise this if you have php set right and lots of RAM.</div>
	</td>
</tr>

<tr>
	<td><label for="maxnzbsprocessed">Maximum NZBs stage5</label>:</td>
	<td>
		<input class="small" id="maxnzbsprocessed" name="maxnzbsprocessed" type="text" value="{$fsite->maxnzbsprocessed}" />
		<div class="hint">The maximum amount of NZB files to create on stage 5 in update_releases.</div>
	</td>
</tr>

<tr>
	<td><label for="maxpartrepair">Maximum repair per run</label>:</td>
	<td>
		<input class="small" id="maxpartrepair" name="maxpartrepair" type="text" value="{$fsite->maxpartrepair}" />
		<div class="hint">The maximum amount of articles to attempt to repair at a time.</div>
	</td>
</tr>

<tr>
	<td><label for="partrepair">Part Repair</label>:</td>
	<td>
		{html_radios id="partrepair" name='partrepair' values=$yesno_ids output=$yesno_names selected=$fsite->partrepair separator='<br />'}
		<div class="hint">Whether to attempt to repair parts or not, increases backfill/binaries updating time.</div>
	</td>
</tr>

<tr>
	<td><label for="grabstatus">Update grabs</label>:</td>
	<td>
		{html_radios id="grabstatus" name='grabstatus' values=$yesno_ids output=$yesno_names selected=$fsite->grabstatus separator='<br />'}
		<div class="hint">Whether to update download counts when someone downloads a release.</div>
	</td>
</tr>

</table>
</fieldset>

<fieldset>
<legend>User Settings</legend>
<table class="input">

<tr>
	<td style="width:160px;"><label for="registerstatus">Registration Status</label>:</td>
	<td>
		{html_radios id="registerstatus" name='registerstatus' values=$registerstatus_ids output=$registerstatus_names selected=$fsite->registerstatus separator='<br />'}
		<div class="hint">The status of registrations to the site.</div>
	</td>
</tr>

<tr>
	<td><label for="storeuserips">Store User Ip</label>:</td>
	<td>
		{html_radios id="storeuserips" name='storeuserips' values=$yesno_ids output=$yesno_names selected=$fsite->storeuserips separator='<br />'}
		<div class="hint">Whether to store the users ip address when they signup or login.</div>
	</td>
</tr>

</table>
</fieldset>

<input type="submit" value="Save Site Settings" />

</form>
