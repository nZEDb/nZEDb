<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="{$page->meta_keywords}{if $site->metakeywords != ""},{$site->metakeywords}{/if}" />
	<meta name="description" content="{$page->meta_description}{if $site->metadescription != ""} - {$site->metadescription}{/if}" />	
	<meta name="newznab_version" content="{$site->version}" />
	<title>{$page->meta_title}{if $site->metatitle != ""} - {$site->metatitle}{/if}</title>
{if $loggedin=="true"}	<link rel="alternate" type="application/rss+xml" title="{$site->title} Full Rss Feed" href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}" />{/if}

	<link href="{$smarty.const.WWW_TOP}/views/styles/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/views/styles/jquery.qtip.css" rel="stylesheet" type="text/css" media="screen" />
{if $site->google_adsense_acc != ''}	<link href="http://www.google.com/cse/api/branding.css" rel="stylesheet" type="text/css" media="screen" />
{/if}
{if $site->style != "" && $site->style != "/"}	<link href="{$smarty.const.WWW_TOP}/views/themes/{$site->style}/style.css" rel="stylesheet" type="text/css" media="screen" />
{/if}
	<link rel="shortcut icon" type="image/ico" href="{$smarty.const.WWW_TOP}/views/images/favicon.ico"/>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/views/scripts/jquery.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/views/scripts/utils.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/views/scripts/sorttable.js"></script>

	<script type="text/javascript">
	/* <![CDATA[ */	
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "{$serverroot}";
		var UID = "{if $loggedin=="true"}{$userdata.ID}{else}{/if}";
		var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
	/* ]]> */		
	</script>
	{$page->head}
</head>
<body {$page->body}>

	{strip}
	<div id="statusbar">
		{if $loggedin=="true"}
			Welcome back <a href="{$smarty.const.WWW_TOP}/profile">{$userdata.username}</a>. <a href="{$smarty.const.WWW_TOP}/logout">Logout</a>
		{else}
			<a href="{$smarty.const.WWW_TOP}/login">Login</a> or <a href="{$smarty.const.WWW_TOP}/register">Register</a>
		{/if}
	</div>
	{/strip}

	<div id="logo">
		<a class="logolink" title="{$site->title} Logo" href="{$smarty.const.WWW_TOP}{$site->home_link}"><img class="logoimg" alt="{$site->title} Logo" src="{$smarty.const.WWW_TOP}/views/images/clearlogo.png" /></a>

		<ul>{$main_menu}</ul>

		<h1><a href="{$smarty.const.WWW_TOP}{$site->home_link}">{$site->title}</a></h1>
		<p><em>{$site->strapline}</em></p>

		{$site->adheader}		
		
	</div>
	<hr />
	
	<div id="header">
		<div id="menu"> 

			{if $loggedin=="true"}
				{$header_menu}
			{/if}
						
		</div> 
	</div>
	
	<div id="page">

		<div id="content">
			{$page->content}
		</div>
	
		<div style="clear: both;text-align:right;">
			<a class="w3validator" href="http://validator.w3.org/check?uri=referer">
			<img src="{$smarty.const.WWW_TOP}/views/images/valid-xhtml10.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" />
			</a> 
		</div>
		
	</div>

	<div class="footer">
	<p>
		{$site->footer}
		<br /><br /><br /><a title="Newznab - A usenet indexing web application with community features." href="http://www.newznab.com/">Newznab</a> is released under GPL. All rights reserved {$smarty.now|date_format:"%Y"}. <br/> <a title="Chat about newznab" href="http://www.newznab.com/chat.html">Newznab Chat</a> <br/><a href="{$smarty.const.WWW_TOP}/terms-and-conditions">{$site->title} Terms and Conditions</a>
	</p>
	</div>
	
	{if $site->google_analytics_acc != ''}
	{literal}
	<script type="text/javascript">
	/* <![CDATA[ */	
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {	
	var pageTracker = _gat._getTracker("{/literal}{$site->google_analytics_acc}{literal}");	
	pageTracker._trackPageview();
	} catch(err) {}
	/* ]]> */		
	</script>
	{/literal}
	{/if}

{if $loggedin=="true"}
<input type="hidden" name="UID" value="{$userdata.ID}" />
<input type="hidden" name="RSSTOKEN" value="{$userdata.rsstoken}" />
{/if}
	
</body>
</html>
