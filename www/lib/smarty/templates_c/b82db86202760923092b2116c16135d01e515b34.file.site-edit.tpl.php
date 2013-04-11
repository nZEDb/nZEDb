<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:57:28
         compiled from "/var/www/newznab/www/views/templates/admin/site-edit.tpl" */ ?>
<?php /*%%SmartyHeaderCode:16043587105166cf588360f7-02347876%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b82db86202760923092b2116c16135d01e515b34' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/site-edit.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '16043587105166cf588360f7-02347876',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_html_options')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_options.php';
if (!is_callable('smarty_function_html_radios')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_radios.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<form action="<?php echo $_smarty_tpl->getVariable('SCRIPT_NAME')->value;?>
?action=submit" method="post">

<?php if ($_smarty_tpl->getVariable('error')->value!=''){?>
	<div class="error"><?php echo $_smarty_tpl->getVariable('error')->value;?>
</div>
<?php }?>

<fieldset>
<legend>Main Site Settings, Html Layout, Tags</legend>
<table class="input">

<tr>
	<td><label for="title">Title</label>:</td>
	<td>
		<input id="title" class="long" name="title" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->title;?>
" />
		<div class="hint">Displayed around the site and contact form as the name for the site.</div>
	</td>
</tr>

<tr>
	<td><label for="strapline">Strapline</label>:</td>
	<td>
		<input id="strapline" class="long" name="strapline" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->strapline;?>
" />
		<div class="hint">Displayed in the header on every public page.</div>
	</td>
</tr>

<tr>
	<td><label for="metatitle">Meta Title</label>:</td>
	<td>
		<input id="metatitle" class="long" name="metatitle" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->metatitle;?>
" />
		<div class="hint">Stem meta-tag appended to all page title tags.</div>
	</td>
</tr>


<tr>
	<td><label for="metadescription">Meta Description</label>:</td>
	<td>
		<textarea id="metadescription" name="metadescription"><?php echo $_smarty_tpl->getVariable('fsite')->value->metadescription;?>
</textarea>
		<div class="hint">Stem meta-description appended to all page meta description tags.</div>
	</td>
</tr>

<tr>
	<td><label for="metakeywords">Meta Keywords</label>:</td>
	<td>
		<textarea id="metakeywords" name="metakeywords"><?php echo $_smarty_tpl->getVariable('fsite')->value->metakeywords;?>
</textarea>
		<div class="hint">Stem meta-keywords appended to all page meta keyword tags.</div>
	</td>
</tr>

<tr>
	<td><label for="footer">Footer</label>:</td>
	<td>
		<textarea id="footer" name="footer"><?php echo $_smarty_tpl->getVariable('fsite')->value->footer;?>
</textarea>
		<div class="hint">Displayed in the footer section of every public page.</div>
	</td>
</tr>

<tr>
	<td><label for="style">Default Home Page</label>:</td>
	<td>
		<input id="home_link" class="long" name="home_link" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->home_link;?>
" />
		<div class="hint">The relative path to a the landing page shown when a user logs in, or clicks the home link.</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="codename">Code Name</label>:</td>
	<td>
		<input id="codename" name="code" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->code;?>
" />
		<input type="hidden" name="id" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->id;?>
" />
		<div class="hint">A just for fun value shown in debug and not on public pages.</div>
	</td>
</tr>

<tr>
	<td><label for="style">Theme</label>:</td>
	<td>
		<?php echo smarty_function_html_options(array('class'=>"siteeditstyle",'id'=>"style",'name'=>'style','values'=>$_smarty_tpl->getVariable('themelist')->value,'output'=>$_smarty_tpl->getVariable('themelist')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->style),$_smarty_tpl);?>

		<div class="hint">The theme folder which will be loaded for css and images. (Use / for default)</div>
	</td>
</tr>

<tr>
	<td><label for="style">User Menu Position</label>:</td>
	<td>
		<?php echo smarty_function_html_options(array('class'=>"siteeditmenuposition",'id'=>"menuposition",'name'=>'menuposition','values'=>$_smarty_tpl->getVariable('menupos_ids')->value,'output'=>$_smarty_tpl->getVariable('menupos_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->menuposition),$_smarty_tpl);?>

		<div class="hint">Where the menu should appear. Moving the menu to the top will require using a theme which widens the content panel. (e.g. nzbsu theme)</div>
	</td>
</tr>

<tr>
	<td><label for="style">Dereferrer Link</label>:</td>
	<td>
		<input id="dereferrer_link" class="long" name="dereferrer_link" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->dereferrer_link;?>
" />
		<div class="hint">Optional URL to prepend to external links</div>
	</td>
</tr>

<tr>
	<td><label for="email">Email</label>:</td>
	<td>
		<input id="email" class="long" name="email" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->email;?>
" />
		<div class="hint">Shown in the contact us page, and where the contact html form is sent to.</div>
	</td>
</tr>

<tr>
	<td><label for="tandc">Terms and Conditions</label>:</td>
	<td>
		<textarea id="tandc" name="tandc"><?php echo $_smarty_tpl->getVariable('fsite')->value->tandc;?>
</textarea>
		<div class="hint">Text displayed in the terms and conditions page.</div>
	</td>
</tr>

<tr>
	<td><label for="newznabID">newznab ID</label>:</td>
	<td>
		<input id="newznabID" class="long" name="newznabID" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->newznabID;?>
" />
		<div class="hint">Your registered newznab ID. Used for access to additional features.</div>
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
		<input id="google_analytics_acc" name="google_analytics_acc" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->google_analytics_acc;?>
" />
		<div class="hint">e.g. UA-xxxxxx-x</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="google_adsense_acc">Google Adsense</label>:</td>
	<td>
		<input id="google_adsense_acc" name="google_adsense_acc" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->google_adsense_acc;?>
" />
		<div class="hint">e.g. pub-123123123123123</div>
	</td>
</tr>

<tr>
	<td><label for="google_adsense_search">Google Adsense Search</label>:</td>
	<td>
		<input id="google_adsense_search" name="google_adsense_search" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->google_adsense_search;?>
" />
		<div class="hint">The ID of the google search ad panel displayed at the bottom of the left menu.</div>
	</td>
</tr>

<tr>
	<td><label for="adheader">Advert Space in Banner</label>:</td>
	<td>
		<textarea id="adheader" name="adheader"><?php echo $_smarty_tpl->getVariable('fsite')->value->adheader;?>
</textarea>
		<div class="hint">The banner slot in the header.</div>
	</td>
</tr>

<tr>
	<td><label for="adbrowse">Advert Space in Browse List</label>:</td>
	<td>
		<textarea id="adbrowse" name="adbrowse"><?php echo $_smarty_tpl->getVariable('fsite')->value->adbrowse;?>
</textarea>
		<div class="hint">The banner slot in the header.</div>
	</td>
</tr>

<tr>
	<td><label for="addetail">Advert Space in Detail View</label>:</td>
	<td>
		<textarea id="addetail" name="addetail"><?php echo $_smarty_tpl->getVariable('fsite')->value->addetail;?>
</textarea>
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
		<input id="tmdbkey" class="long" name="tmdbkey" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->tmdbkey;?>
" />
		<div class="hint">The api key used for access to tmdb</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="rottentomatokey">Rotten Tomatoes Key</label>:</td>
	<td>
		<input id="rottentomatokey" class="long" name="rottentomatokey" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->rottentomatokey;?>
" />
		<div class="hint">The api key used for access to rotten tomatoes</div>
	</td>
</tr>

<tr>
	<td><label for="amazonpubkey">Amazon Public Key</label>:</td>
	<td>
		<input id="amazonpubkey" class="long" name="amazonpubkey" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->amazonpubkey;?>
" />
		<div class="hint">The amazon public api key. Used for music lookups.</div>
	</td>
</tr>

<tr>
	<td><label for="amazonprivkey">Amazon Private Key</label>:</td>
	<td>
		<input id="amazonprivkey" class="long" name="amazonprivkey" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->amazonprivkey;?>
" />
		<div class="hint">The amazon private api key. Used for music lookups.</div>
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
		<input id="unrarpath" class="long" name="unrarpath" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->unrarpath;?>
" />
		<div class="hint">The path to an unrar binary, used in deep password detection and media info grabbing.
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/unrar.exe</span></div>
	</td>
</tr>

<tr>
	<td><label for="tmpunrarpath">Temp Unrar File Path</label>:</td>
	<td>
		<input id="tmpunrarpath" class="long" name="tmpunrarpath" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->tmpunrarpath;?>
" />
		<div class="hint">The path to where unrar puts files. WARNING: This directory will have its contents deleted.
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/temp/path/stuff/will/be/unpacked/to</span></div>
	</td>
</tr>

<tr>
	<td><label for="mediainfopath">Mediainfo Path</label>:</td>
	<td>
		<input id="mediainfopath" class="long" name="mediainfopath" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->mediainfopath;?>
" />
		<div class="hint">The path to the <a href="http://mediainfo.sourceforge.net">mediainfo</a> binary. Used for deep file media analysis. Use empty path to disable mediainfo checks
		<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/mediainfo.exe</span></div>
	</td>
</tr>

<tr>
	<td><label for="ffmpegpath">Ffmpeg Path</label>:</td>
	<td>
		<input id="ffmpegpath" class="long" name="ffmpegpath" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->ffmpegpath;?>
" />
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
		<?php echo smarty_function_html_radios(array('id'=>"sabintegrationtype",'name'=>'sabintegrationtype','values'=>$_smarty_tpl->getVariable('sabintegrationtype_ids')->value,'output'=>$_smarty_tpl->getVariable('sabintegrationtype_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->sabintegrationtype,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to allow integration with a SAB install and if so what type of integration<br/></div>
	</td>
</tr>

<tr>
	<td><label for="saburl">SABnzbd Url</label>:</td>
	<td>
		<input id="saburl" class="long" name="saburl" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->saburl;?>
" />
		<div class="hint">The url of the SAB installation, for example: http://localhost:8080/sabnzbd/</div>
	</td>
</tr>

<tr>
	<td><label for="sabapikey">SABnzbd Api Key</label>:</td>
	<td>
		<input id="sabapikey" class="long" name="sabapikey" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->sabapikey;?>
" />
		<div class="hint">The Api key of the SAB installation. Can be the full api key or the nzb api key (as of SAB 0.6)</div>
	</td>
</tr>

<tr>
	<td><label for="sabapikeytype">Api Key Type</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"sabapikeytype",'name'=>'sabapikeytype','values'=>$_smarty_tpl->getVariable('sabapikeytype_ids')->value,'output'=>$_smarty_tpl->getVariable('sabapikeytype_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->sabapikeytype,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Select the type of api key you entered in the above setting</div>
	</td>
</tr>

<tr>
	<td><label for="sabpriority">Priority Level</label>:</td>
	<td>
		<?php echo smarty_function_html_options(array('id'=>"sabpriority",'name'=>'sabpriority','values'=>$_smarty_tpl->getVariable('sabpriority_ids')->value,'output'=>$_smarty_tpl->getVariable('sabpriority_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->sabpriority),$_smarty_tpl);?>

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
		<input id="nzbpath" class="long" name="nzbpath" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->nzbpath;?>
" />
		<div class="hint">The directory where nzb files will be stored.</div>
	</td>
</tr>

<tr>
	<td><label for="rawretentiondays">Header Retention</label>:</td>
	<td>
		<input class="tiny" id="rawretentiondays" name="rawretentiondays" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->rawretentiondays;?>
" />
		<div class="hint">The number of days binary and part data will be retained for use in raw search and release formation. <strong>Set to 0 during import to remove headers immediately.</strong></div>
	</td>
</tr>

<tr>
	<td><label for="attemptgroupbindays">Days to Attempt To Group</label>:</td>
	<td>
		<input class="tiny" id="attemptgroupbindays" name="attemptgroupbindays" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->attemptgroupbindays;?>
" />
		<div class="hint">The number of days an attempt will be made to group binaries into releases after being added.</div>
	</td>
</tr>

<tr>
	<td><label for="releaseretentiondays">Release Retention</label>:</td>
	<td>
		<input class="tiny" id="releasedays" name="releaseretentiondays" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->releaseretentiondays;?>
" />
		<div class="hint">The number of days releases will be retained for use throughout site. Set to 0 to disable.</div>
	</td>
</tr>

<tr>
	<td><label for="minfilestoformrelease">Minimum Files to Make a Release</label>:</td>
	<td>
		<input class="tiny" id="minfilestoformrelease" name="minfilestoformrelease" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->minfilestoformrelease;?>
" />
		<div class="hint">The minimum number of files to make a release. i.e. if set to two, then releases which only contain one file will not be created.</div>
	</td>
</tr>

<tr>
	<td><label for="minsizetoformrelease">Minimum File Size to Make a Release</label>:</td>
	<td>
		<input class="small" id="minsizetoformrelease" name="minsizetoformrelease" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->minsizetoformrelease;?>
" />
		<div class="hint">The minimum total size in bytes to make a release. If set to 0, then ignored.</div>
	</td>
</tr>

<tr>
	<td><label for="checkpasswordedrar">Check For Passworded Releases</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"checkpasswordedrar",'name'=>'checkpasswordedrar','values'=>$_smarty_tpl->getVariable('passwd_ids')->value,'output'=>$_smarty_tpl->getVariable('passwd_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->checkpasswordedrar,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to peek into every release, to see if rar files are password protected.<br/></div>
	</td>
</tr>

<tr>
	<td><label for="deletepasswordedrelease">Delete Passworded Releases</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"deletepasswordedrelease",'name'=>'deletepasswordedrelease','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->deletepasswordedrelease,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to delete releases which are passworded or potentially passworded.<br/></div>
	</td>
</tr>

<tr>
	<td><label for="showpasswordedrelease">Show Passworded Releases</label>:</td>
	<td>
		<?php echo smarty_function_html_options(array('id'=>"showpasswordedrelease",'name'=>'showpasswordedrelease','values'=>$_smarty_tpl->getVariable('passworded_ids')->value,'output'=>$_smarty_tpl->getVariable('passworded_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->showpasswordedrelease),$_smarty_tpl);?>

		<div class="hint">Whether to show passworded or potentially passworded releases in browse, search, api and rss feeds. Potentially passworded means releases which contain .cab or .ace files which are typically password protected.</div>
	</td>
</tr>

<tr>
	<td><label for="reqidurl">Allfilled Request Id Lookup URL</label>:</td>
	<td>
		<input class="long" id="reqidurl" name="reqidurl" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->reqidurl;?>
" />
		<div class="hint">The url to use to translate allfilled style reqid usenet posts into real release titles. Leave blank to not perform lookup.</div>
	</td>
</tr>

<tr>
	<td><label for="reqidurl">Latest Regex Lookup URL</label>:</td>
	<td>
		<input class="long" id="latestregexurl" name="latestregexurl" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->latestregexurl;?>
" />
		<div class="hint">The url to use to get the latest regexs. Leave blank to not perform lookup. This will retrieve all user contributed regexes.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupnfo">Lookup Nfo</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookupnfo",'name'=>'lookupnfo','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookupnfo,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to retrieve the an nfo file from usenet when processing binaries.<br/><strong>NOTE: disabling nfo lookups will disable movie lookups.</strong></div>
	</td>
</tr>


<tr>
	<td><label for="lookuptvrage">Lookup TV Rage</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookuptvrage",'name'=>'lookuptvrage','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookuptvrage,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to lookup tv rage ids on the web when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupimdb">Lookup Movies</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookupimdb",'name'=>'lookupimdb','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookupimdb,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to lookup film information from IMDB or TheMovieDB when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupanidb">Lookup AniDB</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookupanidb",'name'=>'lookupanidb','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookupanidb,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to lookup anime information from AniDB when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupmusic">Lookup Music</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookupmusic",'name'=>'lookupmusic','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookupmusic,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to lookup music information from Amazon when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="lookupgames">Lookup Games</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"lookupgames",'name'=>'lookupgames','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->lookupgames,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to attempt to lookup game information from Amazon when processing binaries.</div>
	</td>
</tr>

<tr>
	<td><label for="compressedheaders">Use Compressed Headers</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('class'=>($_smarty_tpl->getVariable('compress_headers_warning')->value),'id'=>"compressedheaders",'name'=>'compressedheaders','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->compressedheaders,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Some servers allow headers to be sent over in a compressed format.  If enabled this will use much less bandwidth, but processing times may increase.</div>
	</td>
</tr>


<tr>
	<td><label for="maxmssgs">Max Messages</label>:</td>
	<td>
		<input class="small" id="maxmssgs" name="maxmssgs" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->maxmssgs;?>
" />
		<div class="hint">The maximum number of messages to fetch at a time from the server.</div>
	</td>
</tr>
<tr>
	<td><label for="newgroupscanmethod">Where to start new groups</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"newgroupscanmethod",'name'=>'newgroupscanmethod','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('newgroupscan_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->newgroupscanmethod,'separator'=>'<br />'),$_smarty_tpl);?>

		<input class="tiny" id="newgroupdaystoscan" name="newgroupdaystoscan" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->newgroupdaystoscan;?>
" /> Days  or 
		<input class="small" id="newgroupmsgstoscan" name="newgroupmsgstoscan" type="text" value="<?php echo $_smarty_tpl->getVariable('fsite')->value->newgroupmsgstoscan;?>
" /> Posts<br />
		<div class="hint">Scan back X (posts/days) for each new group?  Can backfill to scan further.</div>
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
		<?php echo smarty_function_html_radios(array('id'=>"registerstatus",'name'=>'registerstatus','values'=>$_smarty_tpl->getVariable('registerstatus_ids')->value,'output'=>$_smarty_tpl->getVariable('registerstatus_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->registerstatus,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">The status of registrations to the site.</div>
	</td>
</tr>

<tr>
	<td><label for="storeuserips">Store User Ip</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"storeuserips",'name'=>'storeuserips','values'=>$_smarty_tpl->getVariable('yesno_ids')->value,'output'=>$_smarty_tpl->getVariable('yesno_names')->value,'selected'=>$_smarty_tpl->getVariable('fsite')->value->storeuserips,'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Whether to store the users ip address when they signup or login.</div>
	</td>
</tr>

</table>
</fieldset>

<input type="submit" value="Save Site Settings" />

</form>
