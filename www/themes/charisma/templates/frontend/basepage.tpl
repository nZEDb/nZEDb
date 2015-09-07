<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
	<!--
		===
		This comment should NOT be removed.
		Charisma v2.0.0
		Copyright 2012-2014 Muhammad Usman
		Licensed under the Apache License v2.0
		http://www.apache.org/licenses/LICENSE-2.0
		http://usman.it
		http://twitter.com/halalit_usman
		===
	-->
	<script type="text/javascript">
		/* <![CDATA[ */
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "{$serverroot}";
		var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
		/* ]]> */
	</script>
	<meta charset="utf-8">
	<title>{$site->title}</title>
	<!-- Newposterwall -->
	<link href="{$smarty.const.WWW_TOP}/themes/charisma/styles/posterwall.css" rel="stylesheet" type="text/css" media="screen" />
	<!-- The styles -->
	<link id="bs-css" href="{$smarty.const.WWW_TOP}/themes/charisma/css/bootstrap-spacelab.min.css" rel="stylesheet">
	<link href="{$smarty.const.WWW_TOP}/themes/charisma/css/charisma-app.css" rel="stylesheet">
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/chosen/chosen.min.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/colorbox/example3/colorbox.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/responsive-tables/responsive-tables.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap-tour/build/css/bootstrap-tour.min.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/elfinder.min.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/elfinder.theme.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/jquery.iphone.toggle.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/animate.min.css' rel='stylesheet'>
	<!-- Normalize.css -->
	<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/normalize.css" rel="stylesheet" type="text/css">
	<!-- The fav icon -->
	<link rel="shortcut icon" href="{$smarty.const.WWW_TOP}/themes/charisma/img/favicon.ico">
</head>
{if ($loggedin)=="true"}
	<body>
	<!-- topbar starts -->
	<div class="navbar navbar-default" role="navigation">
		<div class="navbar-inner">
			<button type="button" class="navbar-toggle pull-left animated flip">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="{$site->home_link}"> <img alt="nZEDb logo"
															   src="{$smarty.const.WWW_TOP}/themes/charisma/img/logo-nzedb.png"
						/></a>
			{$header_menu}
			<!-- user dropdown starts -->
			<div class="btn-group pull-right">
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					<i class="fa fa-user"></i><span class="hidden-sm hidden-xs"><span
								class="username"> Hi, {$userdata.username}</span></span>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="{$serverroot}profile"><i class="fa fa-user"></i><span> My Profile</span></a></li>
					<li><a href="{$serverroot}cart"><i class="fa fa-shopping-cart"></i><span> My Cart</span></a></li>
					<li><a href="{$serverroot}queue"><i class="fa fa-cloud-download"></i><span> My Queue</span></a></li>
					<li><a href="{$serverroot}mymovies"><i class="fa fa-film"></i><span> My movies</span></a></li>
					<li><a href="{$serverroot}profileedit"><i class="fa fa-cog fa-spin"></i><span> Account Settings</span></a>
					</li>
					{if isset($isadmin)}
						<li><a href="{$serverroot}admin"><i class="fa fa-cogs fa-spin"></i><span> Admin</span></a></li>
					{/if}
					<li><a href="{$serverroot}logout"><i class="fa fa-unlock-alt"></i><span> Logout</span></a></li>
				</ul>
			</div>
			<!-- user dropdown ends -->
		</div>
	</div>
	<!-- topbar ends -->
	<div class="ch-container">
		<div class="row">
			<!-- left menu starts -->
			<div class="col-sm-2 col-lg-2">
				<div class="sidebar-nav">
					<div class="nav-canvas">
						<div class="nav-sm nav nav-stacked">
						</div>
						<ul class="nav nav-pills nav-stacked main-menu">
							<!-- search form -->
							<form id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
								<input id="headsearch" name="search" value="{if $header_menu_search == ""}Search...{else}{$header_menu_search|escape:"htmlall"}{/if}" class="form-control" type="text" tabindex="1$" />
								<div class="row no-gutter" style="padding-top:3px;">
									<div class="col-md-8">
										<select id="headcat" name="t" class="form-control" data-search="true">
											<option class="grouping" value="-1">All</option>
											{foreach from=$parentcatlist item=parentcat}
												<option {if $header_menu_cat==$parentcat.id}selected="selected"{/if} value="{$parentcat.id}"> [{$parentcat.title}]</option>
												{foreach from=$parentcat.subcatlist item=subcat}
													<option {if $header_menu_cat==$subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;&nbsp; > {$subcat.title}</option>
												{/foreach}
											{/foreach}
										</select>
									</div>
									<div class="col-md-3 no-gutter">
										<input id="headsearch_go" type="submit" class="btn btn-dark" style="margin-top:0px; margin-left:4px;" value="Go"/>
									</div>
								</div>
							</form>
							<!-- /.search form -->
							<li class="nav-header">Main</li>
							{if ($loggedin)=="true"}
							<li><a href="{$site->home_link}"><i class="fa fa-home"></i><span> Home</span> <span
											class="fa arrow"></span></a></li>
							<li class="accordion">
								<a href="#"><i class="fa fa-list-ol"></i><span> Browse</span></a>
								<ul class="nav nav-pills nav-stacked">
									<li><a href="{$serverroot}newposterwall"><i
													class="fa fa-file-image-o"></i><span> New Releases</span></a></li>
									<li><a href="{$serverroot}console"><i
													class="fa fa-gamepad"></i><span> Console</span></a></li>
									<li><a href="{$serverroot}movies"><i
													class="fa fa-film"></i><span> Movies</span></a></li>
									<li><a href="{$serverroot}music"><i
													class="fa fa-music"></i><span> Music</span></a></li>
									<li><a href="{$serverroot}games"><i
													class="fa fa-gamepad"></i><span> Games</span></a></li>
									<li><a href="{$serverroot}xxx"><i class="fa fa-ban"></i><span> XXX</span></a></li>
									<li><a href="{$serverroot}books"><i class="fa fa-book"></i><span> Books</span></a>
									</li>
									<li><a href="{$serverroot}browse"><i class="fa fa-list-ul"></i><span> Browse All Releases</span></a>
									<li><a href="{$serverroot}predb"><i
													class="fa fa-list-ol"></i><span> PreDb</span></a>
								</ul>
							</li>
							<li class="accordion">
								<a href="#"><i class="fa fa-list-ol"></i><span> Articles & Links</span></a>
								<ul class="nav nav-pills nav-stacked">
									<li><a href="{$serverroot}contact-us"><i
													class="fa fa-envelope-o"></i><span> Contact</span> <span
													class="fa arrow"></span></a></li>
									<li><a href="{$serverroot}search"><i class="fa fa-search"></i> Search</a></li>
									<li><a href="{$serverroot}rss"><i class="fa fa-rss"></i> RSS Feeds</a></li>
									<li><a href="{$serverroot}apihelp"><i class="fa fa-cloud"></i> API</a></li>
								</ul>
							</li>
							<li><a href="{$serverroot}logout"><i class="fa fa-unlock"></i><span> Logout</span></a>
								{else}
							<li><a href="{$serverroot}login"><i class="fa fa-lock"></i><span> Login</span></a>
								{/if}
							</li>
						</ul>
					</div>
				</div>
			</div>
			<!--/span-->
			<!-- left menu ends -->
			<noscript>
				<div class="alert alert-block col-md-12">
					<h4 class="alert-heading">Warning!</h4>
					<p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript" target="_blank">JavaScript</a>
						enabled to use this site.</p>
				</div>
			</noscript>
			<div id="content" class="col-lg-10 col-sm-10">
				<!-- content starts -->
				<div class="row">
					<div class="box col-md-12">
						<div class="box-inner">
							<div class="box-content">
								<!-- put your content here -->
								{$page->content}
							</div>
						</div>
					</div>
				</div>
				<!--/row-->
				<!-- content ends -->
			</div>
			<!--/#content.col-md-0-->
		</div>
		<!--/fluid-row-->
		<hr>
		<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
			 aria-hidden="true">
		</div>
		<footer class="row">
			<div class="box col-md-12">
				<p class="col-md-9 col-sm-9 col-xs-12">Copyright &copy; </i><a
							href="{$smarty.const.WWW_TOP}{$site->home_link}">{if $site->title == ''}nZEDb{else}{$site->title}{/if}</a> all rights reserved {$smarty.now|date_format:"%Y"}</p>
		</footer>
	</div>
	<!--/.fluid-container-->
	<!-- Scripts-->
	<!-- jQuery -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/jquery/jquery.min.js"></script>
	<!-- jQuery migrate script -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery-migrate-1.2.1.min.js"></script>
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
	<!-- Bootstrap hover on mouseover script -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/js/bootstrap-hover-dropdown.min.js"></script>
	<!-- library for cookie management -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.cookie.js"></script>
	<!-- data table plugin -->
	<script type="text/javascript"
			src='{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.dataTables.min.js'></script>
	<!-- select or dropdown enhancer -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/chosen/chosen.jquery.min.js"></script>
	<!-- plugin for gallery image view -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/colorbox/jquery.colorbox-min.js"></script>
	<!-- notification plugin -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<!-- library for making tables responsive -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/responsive-tables/responsive-tables.js"></script>
	<!-- tour plugin -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap-tour/build/js/bootstrap-tour.min.js"></script>
	<!-- star rating plugin -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.raty.min.js"></script>
	<!-- for iOS style toggle switch -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.iphone.toggle.js"></script>
	<!-- autogrowing textarea plugin -->
	<script type="text/javascript"
			src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.autogrow-textarea.js"></script>
	<!-- history.js for cross-browser state change on ajax -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.history.js"></script>
	<!-- Charisma functions -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/charisma.js"></script>
	<!-- Functions with noty -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/js/functions.js"></script>
	<!-- nZEDb default scripts, needed for stuff to work -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.colorbox-min.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.autosize-min.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes_shared/scripts/jquery.qtip2.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes_shared/scripts/sorttable.js"></script>
	<!-- Charisma utils.js -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/charisma/scripts/utils.js"></script>
	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	<script type="text/javascript" src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	</body>
	{* Start Login/Register Page *}
{elseif $smarty.server.REQUEST_URI == "/register" || $smarty.server.REQUEST_URI eq '/register?action=submit'}
	{include file="register.tpl"}
{elseif $smarty.server.REQUEST_URI == "/forgottenpassword" || $smarty.server.REQUEST_URI eq '/forgottenpassword?action=submit'}
	{include file="forgottenpassword.tpl"}
{elseif $smarty.server.REQUEST_URI == "/contact-us"}
	{include file="contact.tpl"}
{elseif $smarty.server.REQUEST_URI == "/terms-and-conditions"}
	{include file="terms.tpl"}
{else}
	{include file="login.tpl"}
{/if}
</html>