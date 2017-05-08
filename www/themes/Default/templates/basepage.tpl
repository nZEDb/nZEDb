<!DOCTYPE html>
<html lang="en">
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>{$page->meta_title}{if $site->metatitle != ""} - {$site->metatitle}{/if}</title>
	<meta name="keywords" content="{$page->meta_keywords}{if $site->metakeywords != ""},{$site->metakeywords}{/if}">
	<meta name="description" content="{$page->meta_description}{if $site->metadescription != ""} - {$site->metadescription}{/if}">
	<meta name="application-name" content="nZEDb-v{$site->version}">
	<meta name="viewport" content="width=device-width">

	{if $loggedin=="true"}<link rel="alternate" type="application/rss+xml" title="{$site->title} Full Rss Feed" href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">{/if}

	<!-- Included CSS files - Bootstrap 3 - Font Awesome 3.2.0 - plugins master style.css -->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-2.3.x/docs/assets/css/bootstrap.css" rel="stylesheet"
		  type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/font-awesome-4.5.x/css/font-awesome.min.css" rel="stylesheet"
		  type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/css/jquery.qtip.css" rel="stylesheet" media="screen">
	<!-- <link href="{$smarty.const.WWW_THEMES}/shared/css/subnav.css" rel="stylesheet" media="screen"> -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/posterwall.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_THEMES}/{$theme}/styles/style.css" rel="stylesheet" media="screen">

	<!-- Manual Adjustment for Search input fields on browse pages. -->
	<style>
	select { min-width: 120px ; width: auto; }
	input { width: 180px; }
	</style>

	<!-- Site Icon files -->
	<link rel="shortcut icon" href="{$smarty.const.WWW_THEMES}/shared/img/favicon.ico">

	<!-- Additional site files -->
	{if $site->google_adsense_acc != ''}<link href="http://www.google.com/cse/api/branding.css" rel="stylesheet" media="screen">{/if}
	<!--[if lt IE 9]>
	<script src="{$smarty.const.WWW_THEMES}/shared/libs/html5shiv-3.7.x/dist/html5shiv.min.js"></script>
	<![endif]-->

	<script>
	/* <![CDATA[ */
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "{$serverroot}";
		var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
	/* ]]> */
	</script>
	{$page->head}
</head>

<body {$page->body}>

		<!-- Status bar along the very top showing login / logout links -->
		{strip}
		<div id="statusbar">
			{if $loggedin=="true"}
			<a href="{$smarty.const.WWW_TOP}/profile">Profile</a> | <a href="{$smarty.const.WWW_TOP}/logout">Logout</a>
			{else}
			<a href="{$smarty.const.WWW_TOP}/login">Login</a> or <a href="{$smarty.const.WWW_TOP}/register">Register</a>
			{/if}
		</div>
		{/strip}

		<!-- Header Logo Area Including Main Menu -->
		<div id="logo">
			<a class="logolink" title="{$site->title} Logo" href="{$smarty.const.WWW_TOP}{$site->home_link}"><img class="logoimg" alt="{$site->title} Logo" src="{$smarty.const.WWW_THEMES}/shared/img/clearlogo.png" /></a>

			<!-- Main Menu at top of page -->
			<ul>{$main_menu}</ul>

			<h1><a href="{$smarty.const.WWW_TOP}{$site->home_link}">{$site->title}</a></h1>

			<p><em>{$site->strapline}</em></p>



		</div>

<hr />

	<!-- Header Menu and Search Bar -->
<div id="header">
	<div id="menu">
		{if $loggedin=="true"}{$header_menu}{/if}
	</div>
</div>

	<!-- Main Site Page Content - Tables, Detailed Views -->
		<!--[if lt IE 7]>
			<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

<div id="page">
	<div id="content">
	   {$page->content}
	</div>
</div>

	<!-- Moved The Script Files to end of site for faster page loading -->
	<!-- jQuery 1.9.1 -->
		<script src="{$smarty.const.WWW_THEMES}/shared/libs/jquery-1.9.x/jquery.min.js"></script>
	<script src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-2.3.x/docs/assets/js/bootstrap.min.js"></script>
	<!-- <script src="{$smarty.const.WWW_THEMES}/shared/js/subnav.js"></script> -->
	<script src="{$smarty.const.WWW_THEMES}/shared/libs/colorbox-1.6.x/jquery.colorbox-min.js" type="text/javascript" ></script>
	<!-- tinymce editor -->
	<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/tinymce-builded/js/tinymce/tinymce.min.js"></script>
	<script src="{$smarty.const.WWW_THEMES}/shared/js/jquery.qtip.min.js" type="text/javascript" ></script>
	<script src="{$smarty.const.WWW_THEMES}/{$theme}/scripts/utils.js"></script>
	<script src="{$smarty.const.WWW_THEMES}/shared/js/sorttable.js"></script>

	<!-- Google Analytics Tracking Code -->
	{if $site->google_analytics_acc != ''}
	{literal}
	<script>
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
	{/literal}{/if}

	{if $loggedin=="true"}
		<input type="hidden" name="UID" value="{$userdata.id}">
		<input type="hidden" name="RSSTOKEN" value="{$userdata.rsstoken}">{/if}

		<script type="text/javascript">
			tinyMCE.init({
				selector: 'textarea#addMessage',
				theme : "modern",
				plugins: [
					'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
					'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
					'save table contextmenu directionality emoticons template paste textcolor code'
				],
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				toolbar: 'insertfile undo redo | styleselect | fontselect |sizeselect | fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons | code',
				fontsize_formats: "8pt 9pt 10pt 11pt 12pt 13pt 14pt 15pt 16pt 17pt 18pt 24pt 36pt",
				mode : "exact",
				relative_urls : false,
				remove_script_host : false,
				convert_urls : true
			});
		</script>

	</body>
</html>
