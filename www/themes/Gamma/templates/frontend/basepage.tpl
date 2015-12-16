<!DOCTYPE html>
<html lang="en">

<!--[if IE 6]>
    <link href="ie6.min.css" rel="stylesheet">
<![endif]-->

<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<meta name="keywords" content="{$page->meta_keywords}{if $page->meta_keywords != "" && $site->metakeywords != ""},{/if}{$site->metakeywords}" />
	<meta name="description" content="{$page->meta_description}{if $page->meta_description != "" && $site->metadescription != ""} - {/if}{$site->metadescription}" />
	<meta name="robots" content="noindex,nofollow"/>
	<meta name="application-name" content="newznab-{$site->version}" />
	<title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle}</title>

{if $loggedin=="true"}
	<link rel="alternate" type="application/rss+xml" title="{$site->title} Full Rss Feed" href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}" />
{/if}

{if $site->google_adsense_acc != ''}
	<link href="http://www.google.com/cse/api/branding.css" rel="stylesheet" type="text/css" media="screen" />
{/if}
	<!-- Newposterwall -->
	<link href="{$smarty.const.WWW_TOP}/themes_shared/styles/posterwall.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/bootstrap.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/font-awesome.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/extra.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/jquery.pnotify.default.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/bootstrap.cyborg.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/themes/Gamma/styles/bootstrap-fixes.css" rel="stylesheet" type="text/css" media="screen" />

	<!-- FAVICON -->
	<link rel="search" type="application/opensearchdescription+xml" href="/opensearch" title="{$site->title|escape}" />
	<link rel="shortcut icon" type="image/ico" href="{$smarty.const.WWW_TOP}/themes/Gamma/images/favicon.ico"/>

	<!-- Javascripts -->
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/jquery.colorbox-min.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/jquery.qtip2.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/utils.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/jquery.autosize-min.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/sorttable.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/jquery.qtip2.js"></script>

	<!-- Added the Bootstrap JS -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/bootstrap.js"></script>

	<!-- Pines Notify -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/Gamma/scripts/jquery.pnotify.js"></script>

	<script type="text/javascript">
	/* <![CDATA[ */
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "/";
		var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
	/* ]]> */
	</script>

	{$page->head}
</head>
<body {$page->body}>

<!-- NAV
	================================================== -->

	<!-- If you want the navbar "white" remove Navbar-inverse -->
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner" style="padding-left:30px; padding-right:30px;">

			<div class="container">
						{if $loggedin=="true"}
							{$header_menu}
						{/if}
					{if $loggedin=="true"}
						    <div class="btn-group">
								<a class="btn" href="{$smarty.const.WWW_TOP}/profile"><i class="icon-user icon-white"></i> {$userdata.username} </a>
								<a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
								<ul class="dropdown-menu">
										<li><a href="{$smarty.const.WWW_TOP}/profile"><i class="icon-user icon-white"></i> Profile</a></li>
										<li class="divider"></li>
										<li><a href="{$smarty.const.WWW_TOP}/queue"><i class="icon-tasks icon-white"></i> Queue</a></li>
										<li><a href="{$smarty.const.WWW_TOP}/cart"><i class="icon-shopping-cart icon-white"></i> Download Basket</a></li>
										<li><a href="{$smarty.const.WWW_TOP}/mymoviesedit"><i class="icon-hdd icon-white"></i> Movies</a></li>
									{if $isadmin}
											<li class="divider"></li>
											<li>
													<li><a href="{$smarty.const.WWW_TOP}/admin"><i class="icon-cog icon-white"></i> Admin</a></li>
											</li>
									{/if}
										<li class="divider"></li>
										<li><a href="{$smarty.const.WWW_TOP}/logout"><i class="icon-off icon-white"></i> Logout</a></li>
								</ul>
							</div>
					{else}
							<ul class="nav pull-right">
							<li class="">
								<a href="{$smarty.const.WWW_TOP}/login">Login</a>
							</li>
							</ul>
					{/if}
			</div>
		</div>
	</div>
	</br>
	</br>
	</br>

	<!-- Container
		================================================== -->
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span2">
					<ul class="nav nav-list">
					{$main_menu}
					{$useful_menu}
					</ul>
				</div>

				<div class="span10">
					{$page->content}
				</div>
			</div>
		</div>

		        {if $site->google_analytics_acc != ''}
		        {literal}
		        <script type="text/javascript">
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
		        </script>

		        {/literal}
		        {/if}

			{if $loggedin=="true"}
				<input type="hidden" name="UID" value="{$userdata.id}" />
				<input type="hidden" name="RSSTOKEN" value="{$userdata.rsstoken}" />
			{/if}

</body>
</html>
