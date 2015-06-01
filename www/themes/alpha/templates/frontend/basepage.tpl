<!DOCTYPE html>
<html lang="en">
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<!-- Meta, title, CSS, favicons, etc. -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="keywords" content="{$page->meta_keywords}{if $site->metakeywords != ""},{$site->metakeywords}{/if}">
		<meta name="description" content="{$page->meta_description}{if $site->metadescription != ""} - {$site->metadescription}{/if}">
		<meta name="application-name" content="nZEDb-v{$site->version}">
		<!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
		<title>{$page->meta_title}{if $site->metatitle != ""} - {$site->metatitle}{/if}</title>
		{if $loggedin=="true"}
			<link rel="alternate" type="application/rss+xml" title="{$site->title} Full Rss Feed" href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}" />
		{/if}

		<!-- nZEDb core CSS -->
		<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/bootstrap.min.css" rel="stylesheet" media="screen">
		<link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/posterwall.css" rel="stylesheet" type="text/css" media="screen" />
		<link href="{$smarty.const.WWW_TOP}/themes/alpha/styles/style.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/themes/alpha/styles/wip.css" rel="stylesheet" media="screen">
		<!-- nZEDb extras -->
		{if $site->google_adsense_acc != ''}
			<link href="//www.google.com/cse/api/branding.css" rel="stylesheet" media="screen">
		{/if}
		<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/jquery.pnotify.default.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/jquery.qtip.css" rel="stylesheet" media="screen">

		<style type="text/css">
			/* Sticky footer styles
			-------------------------------------------------- */
			html, body { height: 100%; }    /* The html and body elements cannot have any padding or margin. */
			/* Wrapper for page content to push down footer */
			#wrap { min-height: 100%; height: auto !important; height: 100%; margin: 0 auto -100px; padding: 0 0 100px; } /* Negative indent footer by its height */ /* Pad bottom by footer height */
			/* Set the fixed height of the footer here */
			footer { height: 100px; }
			/* Lastly, apply responsive CSS fixes as necessary */
			@media (max-width: 767px) { footer { margin-left: -20px; margin-right: -20px; padding-left: 20px; padding-right: 20px; }}

			/* Custom styles */
			legend.adbanner { font-size: 11px !important; font-weight: bold !important; text-align: left !important; width:auto; padding: 0 px; margin: 0 15px; border: 1px groove #ddd !important; }
			.footer-links { margin: 10px 0; padding-left: 0; }
			.footer-links li { display: inline; padding: 0 2px; }
			.footer-links li:first-child { padding-left: 0; }
			.dropdown-menu { border: 0; }
			.dropdown-menu .divider { height:2px;margin:0; }
			nav>.container,nav>.container>.navbar-header,nav>.container>.navbar-nav { height:30px;min-height: 30px; }
			nav>.container>.navbar-nav>li>a,nav>.container>.navbar-header>a { padding-top: 5px;height:30px;min-height: 30px; }
			nav.navbar.navbar-inverse { z-index:99999; }
		</style>

		<!-- Favicons WWWIIIPPP Larger Icons-->
		<link rel="shortcut icon" href="{$smarty.const.WWW_TOP}/themes_shared/images/favicon.ico">

		<!-- Additional nZEDb -->
		<!--[if lt IE 9]>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/html5shiv.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/respond.min.js"></script>
		<![endif]-->
		{literal}
		<script>
			/* <![CDATA[ */
			var WWW_TOP = "{/literal}{$smarty.const.WWW_TOP}{literal}";
			var SERVERROOT = "{/literal}{$serverroot}{literal}";
			var UID = "{/literal}{if $loggedin=="true"}{$userdata.id}{else}{/if}{literal}";
			var RSSTOKEN = "{/literal}{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}{literal}";
			/* ]]> */
		</script>
		{/literal}

		{$page->head}

	</head>
	<body {$page->body}>
		<div id="wrap">
			<!-- Status and Top Menu Area
			================================================== -->
			<nav class="navbar navbar-inverse navbar-static-top" role="navigation" style="min-height:30px;height:30px;min-width:1120px;background:none;margin-bottom:0;">
				<div class="container">
					<div class="navbar-header">
						<a class="navbar-brand" href="/">{$site->title|default:'ಠ_ಠ'}</a>
					</div>
					{if $site->menuposition == 2}
						{include file='topmenu.tpl'}
					{/if}
					<ul class="nav navbar-nav navbar-right">
						{if $loggedin=="true"}
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-user"></i> Profile <b class="caret"></b></a>
								<ul class="dropdown-menu pull-right">
									<li><a href="{$smarty.const.WWW_TOP}/profile"><i class="icon-home"></i> My Profile</a></li>
									{if $isadmin
										}<li><a href="{$smarty.const.WWW_TOP}/admin"><i class="icon-gears"></i> Admin Panel</a></li>
									{/if}
									<li class="divider"></li>
									<li><a href="{$smarty.const.WWW_TOP}/mymovies"><i class="icon-ticket"></i> My Movies</a></li>
									<li><a href="{$smarty.const.WWW_TOP}/myshows"><i class="icon-desktop"></i> My Shows</a></li>
									<li class="divider"></li>
									<li><a href="{$smarty.const.WWW_TOP}/cart"><i class="icon-shopping-cart"></i> My Cart</a></li>
									{if $sabintegrated}
										<li><a href="{$smarty.const.WWW_TOP}/queue"><i class="icon-tasks"></i> My Queue</a></li>
									{/if}
								</ul>
							</li>
						{else}
							<li><a href="{$smarty.const.WWW_TOP}/login"><i class="icon-signin"></i> Login</a></li>
						{/if}
						<li>
							{if $loggedin=="true"}
								<a href="{$smarty.const.WWW_TOP}/logout"><i class="icon-signout"></i> Logout</a>
							{else}
								<a href="{$smarty.const.WWW_TOP}/register"><i class="icon-sign-edit"></i> Register</a>
							{/if}
						</li>
					</ul>
				</div><!-- /.container -->
			</nav><!-- /.navbar -->

			<!-- Header area containing top menu, status menu, logo, ad header
			================================================== -->
			<header class="masthead">
				<div class="container" style="min-width:1120px;">
					<div class="col-xs-7">
						<div class="media">
							<a class="pull-left logo" style="padding: 2px 10px;" title="{$site->title}" href="{$smarty.const.WWW_TOP}{$site->home_link}">
								<img class="media-object" alt="{$site->title} Logo" src="{$smarty.const.WWW_TOP}/themes_shared/images/clearlogo.png"><!-- SITE LOGO -->
							</a>
							<div class="media-body" style="margin:0">
								<h1 class="media-heading" style="margin:0"><a title="{$site->title}" href="{$smarty.const.WWW_TOP}{$site->home_link}"> {$site->title} </a></h1><!-- SITE TITLE -->
								<div class="media" style="margin:0"><h4 style="margin:0">{$site->strapline|default:''}</h4></div><!-- SITE STRAPLINE -->
							</div>
						</div>
					</div><!--/.col-lg- -->
					<div class="col-xs-4">
						{$site->adheader}<!-- SITE AD BANNER -->
					</div><!--/.col-xs- -->
				</div><!-- end header-wrapper -->
			</header>

			<!-- Navigation Menu containing HeaderMenu and HeaderSearch
			================================================== -->
			<div class="navbar navbar-inverse navbar-static-top">
				<div class="container" style="min-width:1120px;">
					{if $loggedin=="true"}{$header_menu}{/if}<!-- SITE NAVIGATION -->
				</div><!--/.navbar -->
			</div><!-- end Navigation -->

			<!-- Content Area containing Side Menu and Main Content Panel
			================================================== -->
			<div class="container">
				{if $site->menuposition == 1}<!-- Side Menu Framework -->
					<div class="col-xs-2">
						{$main_menu}<!-- SIDE MENU -->
						{$article_menu}<!-- SIDE ARTICLES -->
						{$useful_menu}<!-- SIDE USEFUL -->
					</div><!--/.col-xs-2 -->
				{/if}
				<!--Start Main Content - Tables, Detailed Views-->
				<div class="{if $site->menuposition == 1 or $site->menuposition == 0}col-xs-10{else}col-xs-12{/if}">
					<div class="panel nzedb-panel">
						<div class="panel-heading nzedb-panel-heading">
							<h3 class="panel-title"><strong>{if isset($catname)}{$page->meta_title|regex_replace:'/Nzbs/i':$catname|escape:"htmlall"}{else}{$page->meta_title|escape:"htmlall"}{/if}</strong></h3>
						</div><!--/.panel-heading -->
						<div class="panel-body grey-frame">
							<div class="grey-box">

								<!--[if lt IE 7]>
								<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
								<![endif]-->

								{$page->content}

							</div><!--/.grey-box -->
						</div><!--/.grey-frame -->
					</div><!--/.panel- -->
				</div><!--/.col-xs-10 -->
				{if $site->menuposition == 0}<!-- Side Menu Framework -->
					<div class="col-xs-2">
						{$main_menu}<!-- SIDE MENU -->
						{$article_menu}<!-- SIDE ARTICLES -->
						{$useful_menu}<!-- SIDE USEFUL -->
					</div><!--/.col-xs-2 -->
				{/if}
			</div><!--/.container -->
		</div>

		<!-- Footer Area containing Footer contents
		================================================== -->
		<footer>
			<div class="container text-center">
				<p><i class="icon-certificate icon-2x" style="color:yellow;"></i>  <i class="icon-quote-left qoute"></i> {$site->footer} <i class="icon-quote-right qoute"></i></p>
				<p>Copyright &copy; <a href="{$smarty.const.WWW_TOP}{$site->home_link}">{if $site->title == ''}nZEDb{else}{$site->title}{/if}</a> all rights reserved {$smarty.now|date_format:"%Y"}</p>

				<ul class="footer-links"><li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
					<li class="muted"> | </li>
					<li><a href="{$smarty.const.WWW_TOP}/contact-us">Contact Us</a></li>
					<li class="muted"> | </li>
					<li><a href="{$smarty.const.WWW_TOP}/sitemap">Site Map</a></li>
					<li class="muted"> | </li>
					<li><a href="{$smarty.const.WWW_TOP}/apihelp">API</a></li>
					{if $loggedin != "true"}
						<li class="muted"> | </li>
						<li><a href="{$smarty.const.WWW_TOP}/login">Login</a></li>
					{/if}
				</ul>
			</div>
		</footer>

		<!-- JS and analytics only. -->
		<!-- Bootstrap core JavaScript
		================================================== -->
		<script src="//code.jquery.com/jquery-1.9.1.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes/alpha/scripts/bootstrap.min.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/holder.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.pnotify.min.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.qtip.min.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.autosize-min.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.colorbox-min.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes_shared/scripts/sorttable.js"></script>
		<script src="{$smarty.const.WWW_TOP}/themes/alpha/scripts/utils.js"></script>

		<!-- Additional nZEDb JS -->
		<script> Holder.add_theme("dark", { background: "black", foreground: "gray", size: 16 } )</script>
		<!-- <script>
			jQuery(function(){
				jQuery('.nzb_check, .nzb_check_all').click(function(){
					btb();
				});

				var btb = function() {
					var count = jQuery('.nzb_check:checked').size();
					if(count == 0) {
						jQuery('.nzb_multi_operations .btn-info').removeClass('btn-info').addClass('btn-default').addClass('disabled');
						jQuery('.nzb_multi_operations .btn-success').addClass('disabled');
						jQuery('.nzb_multi_operations .btn-warning').addClass('disabled');
						jQuery('.nzb_multi_operations .btn-danger').addClass('disabled');
					} else {
						jQuery('.nzb_multi_operations .btn-success').removeClass('disabled');
						jQuery('.nzb_multi_operations .btn-warning').removeClass('disabled');
						jQuery('.nzb_multi_operations .btn-danger').removeClass('disabled');
						jQuery('.nzb_multi_operations .btn-default').removeClass('btn-default').addClass('btn-info').removeClass('disabled');
					}
				}
				btb();
			});
		</script> -->

		{if $site->google_analytics_acc != ''}
			<!-- Analytics
			================================================== -->
			{literal}<script>
				/* <![CDATA[ */
				var _gaq = _gaq || [];
				_gaq.push(['_setAccount', '{/literal}{$site->google_analytics_acc}{literal}']);
				_gaq.push(['_trackPageview']);
				_gaq.push(['_trackPageLoadTime']);

				(function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
				/* ]]> */
			</script>{/literal}
		{/if}

		{if $loggedin=="true"}
			<input type="hidden" name="UID" value="{$userdata.id}">
			<input type="hidden" name="RSSTOKEN" value="{$userdata.rsstoken}">
		{/if}

	</body>
</html>