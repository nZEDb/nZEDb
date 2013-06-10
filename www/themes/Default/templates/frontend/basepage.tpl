<!DOCTYPE HTML>, <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
{*<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> *}
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="{$page->meta_keywords}{if $site->metakeywords != ""},{$site->metakeywords}{/if}" />
	<meta name="description" content="{$page->meta_description}{if $site->metadescription != ""} - {$site->metadescription}{/if}" />	
	<meta name="nZEDb_version" content="{$site->version}" />
	<title>{$page->meta_title}{if $site->metatitle != ""} - {$site->metatitle}{/if}</title>
{if $loggedin=="true"}	<link rel="alternate" type="application/rss+xml" title="{$site->title} Full Rss Feed" href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}" />{/if}

	<link href="{$smarty.const.WWW_TOP}/themes/Default/styles/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Default/styles/jquery.qtip.css" rel="stylesheet" type="text/css" media="screen" />
{if $site->google_adsense_acc != ''}	<link href="http://www.google.com/cse/api/branding.css" rel="stylesheet" type="text/css" media="screen" />
{/if}

	<link rel="shortcut icon" type="image/ico" href="{$smarty.const.WWW_TOP}/themes/Default/images/favicon.ico"/>
{*	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Default/scripts/jquery-1.9.1.js"></script>*}
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Default/scripts/jquery.colorbox-min.js"></script>
    <script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Default/scripts/jquery.qtip.js"></script>
    <script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Default/scripts/utils.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Default/scripts/sorttable.js"></script>

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
			<a href="{$smarty.const.WWW_TOP}/profile">Profile</a> | <a href="{$smarty.const.WWW_TOP}/logout">Logout</a>
		{else}
			<a href="{$smarty.const.WWW_TOP}/login">Login</a> or <a href="{$smarty.const.WWW_TOP}/register">Register</a>
		{/if}
	</div>
	{/strip}

	<div id="logo">
		<a class="logolink" title="{$site->title} Logo" href="{$smarty.const.WWW_TOP}{$site->home_link}"><img class="logoimg" alt="{$site->title} Logo" src="{$smarty.const.WWW_TOP}/themes/Default/images/clearlogo.png" /></a>

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
