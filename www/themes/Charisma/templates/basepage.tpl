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
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
	<title>{$site->title}</title>
	<!-- Newposterwall -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/posterwall.css"
		  rel="stylesheet"
		  type="text/css"
		  media="screen"/>
	<!-- The styles -->
	<link id="bs-css" href="{$smarty.const.WWW_THEMES}/shared/libs/bootswatch/slate/bootstrap.min.css" rel="stylesheet">
	<link href="{$smarty.const.WWW_THEMES}/{$theme}/css/charisma-app.css" rel="stylesheet">
	<link href='{$smarty.const.WWW_THEMES}/shared/libs/chosen-1.5.x/chosen.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_THEMES}/shared/libs/colorbox-1.6.x/example3/colorbox.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_THEMES}/shared/libs/responsive-tables-js-1.0.x/dist/responsivetables.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_THEMES}/{$theme}/css/elfinder.min.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_THEMES}/{$theme}/css/elfinder.theme.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_THEMES}/{$theme}/css/jquery.iphone.toggle.css' rel='stylesheet'>
	<link href="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.css" rel="stylesheet" type="text/css"/>
	<link href='{$smarty.const.WWW_THEMES}/shared/libs/animate.css/animate.min.css' rel='stylesheet'>
	<!-- Font Awesome Icons -->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/font-awesome-4.5.x/css/font-awesome.min.css" rel="stylesheet"
		  type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/css/jquery.qtip.css" type="text/css" media="screen"/>
	<!-- Normalize.css -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/normalize.css" rel="stylesheet" type="text/css">
	<!-- The fav icon -->
	<link rel="shortcut icon" href="{$smarty.const.WWW_THEMES}/shared/img/favicon.ico">
</head>
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
																src="{$smarty.const.WWW_THEMES}/shared/img/logo.png"
			/></a>
		{$header_menu}
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
						{if $loggedin == "true"}
							<li class="nav-header">Main</li>
							<li>
								<a href="{$site->home_link}"><i class="fa fa-home"></i><span> Home</span> <span
											class="fa arrow"></span></a></li>
							<li class="accordion">
								<a href="#"><i class="fa fa-list-ol"></i><span> Browse</span></a>
								<ul class="nav nav-pills nav-stacked">
									<li><a href="{$smarty.const.WWW_TOP}/newposterwall"><i
													class="fa fa-file-image-o"></i><span> New Releases</span></a>
									</li>
									<li><a href="{$smarty.const.WWW_TOP}/console"><i
													class="fa fa-gamepad"></i><span> Console</span></a>
									</li>
									<li><a href="{$smarty.const.WWW_TOP}/movies"><i
													class="fa fa-film"></i><span> Movies</span></a></li>
									<li><a href="{$smarty.const.WWW_TOP}/music"><i
													class="fa fa-music"></i><span> Music</span></a></li>
									<li><a href="{$smarty.const.WWW_TOP}/games"><i
													class="fa fa-gamepad"></i><span> Games</span></a>
									<li><a href="{$smarty.const.WWW_TOP}/series"><i
													class="fa fa-television"></i><span> TV</span></a>
									</li>
									<li>
										<a href="{$smarty.const.WWW_TOP}/xxx"><i class="fa fa-venus-mars"></i><span> Adult</span></a>
									</li>
									<li>
										<a href="{$smarty.const.WWW_TOP}/books"><i
													class="fa fa-book"></i><span> Books</span></a>
									</li>
									<li>
										<a href="{$smarty.const.WWW_TOP}/browse"><i class="fa fa-list-ul"></i><span> Browse All Releases</span></a>
									<li><a href="{$smarty.const.WWW_TOP}/browsegroup"><i class="fa fa-object-group"></i><span> Browse Groups</span></a>
									</li>
								</ul>
							</li>
						{/if}
						<li class="accordion">
							<a href="#"><i class="fa fa-list-ol"></i><span> Articles & Links</span></a>
							<ul class="nav nav-pills nav-stacked">
								<li><a href="{$smarty.const.WWW_TOP}/contact-us"><i
												class="fa fa-envelope-o"></i><span> Contact</span> <span
												class="fa arrow"></span></a></li>
								{if ($loggedin)=="true"}
								<li><a href="{$smarty.const.WWW_TOP}/forum"><i class="fa fa-forumbee"></i> Forum</a>
								</li>
								<li><a href="{$smarty.const.WWW_TOP}/search"><i class="fa fa-search"></i>
										Search</a></li>
								<li><a href="{$smarty.const.WWW_TOP}/rss"><i class="fa fa-rss"></i> RSS
										Feeds</a>
								</li>
								<li><a href="{$smarty.const.WWW_TOP}/apihelp"><i class="fa fa-cloud"></i>
										API</a></li>
							</ul>
						</li>
						<li><a href="{$smarty.const.WWW_TOP}/logout"><i class="fa fa-unlock"></i><span> Logout</span></a></li>
							{/if}
					</ul>
				</div>
			</div>
		</div>
		<!--/span-->
		<!-- left menu ends -->
		<noscript>
			<div class="alert alert-block col-md-12">
				<h4 class="alert-heading">Warning!</h4>

				<p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript"
									   target="_blank">JavaScript</a>
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
	<div class="modal fade"
		 id="myModal"
		 tabindex="-1"
		 role="dialog"
		 aria-labelledby="myModalLabel"
		 aria-hidden="true">
	</div>
	<footer class="row">
		<div class="box col-md-12">
			<p class="col-md-9 col-sm-9 col-xs-12">Copyright &copy; </i><a
						href="{$smarty.const.WWW_TOP}{$site->home_link}">{if $site->title == ''}nZEDb{else}{$site->title}{/if}</a>
				all rights
				reserved {$smarty.now|date_format:"%Y"}</p>
	</footer>
</div>
<!--/.fluid-container-->
<!-- Scripts-->
<!-- jQuery -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/jquery-2.2.x/dist/jquery.min.js"></script>
<!-- jQuery migrate script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/jquery-migrate-1.4.x/jquery-migrate.min.js"></script>
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-3.3.x/dist/js/bootstrap.min.js"></script>
<!-- Bootstrap hover on mouseover script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-hover-dropdown-2.2.x/bootstrap-hover-dropdown.min.js"></script>
<!-- library for cookie management -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/{$theme}/js/jquery.cookie.js"></script>
<!-- data table plugin -->
<script type="text/javascript"
		src='{$smarty.const.WWW_THEMES}/shared/libs/datatables-1.10.x/media/js/jquery.dataTables.min.js'></script>
<!-- select or dropdown enhancer -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/chosen-1.5.x/chosen.jquery.js"></script>
<!-- plugin for gallery image view -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/colorbox-1.6.x/jquery.colorbox-min.js"></script>
<!-- library for making tables responsive -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/responsive-tables-js-1.0.x/dist/responsivetables.js"></script>
<!-- tinymce editor -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/tinymce-builded/js/tinymce/tinymce.min.js"></script>
<!-- Charisma functions -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/{$theme}/js/charisma.js"></script>
<!-- nZEDb default scripts, needed for stuff to work -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/autosize-3.0.x/dist/autosize.min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/sorttable.js"></script>
<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="{$smarty.const.WWW_THEMES}/shared/libs/html5shiv-3.7.x/dist/html5shiv.min.js"></script>
<script src="{$smarty.const.WWW_THEMES}/shared/libs/respond-1.4.x/dest/respond.min.js"></script>
<![endif]-->

<!-- autogrowing textarea plugin -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/{$theme}/js/jquery.autogrow-textarea.js"></script>
<!-- history.js for cross-browser state change on ajax -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/{$theme}/js/jquery.history.js"></script>
<!-- Custom functions-->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/functions.js"></script>
<!-- nZEDb default scripts, needed for stuff to work -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/colorbox-1.6.x/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/jquery.qtip2.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/sorttable.js"></script>
<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script type="text/javascript" src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<!-- PNotify -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.animate.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.desktop.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.callbacks.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.buttons.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.confirm.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/assets/pnotify/dist/pnotify.nonblock.js"></script>

<script type="text/javascript">
    tinyMCE.init({
        selector: 'textarea#addMessage',
        theme: "modern",
        plugins: [
            'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
            'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
            'save table contextmenu directionality emoticons template paste textcolor code'
        ],
        theme_advanced_toolbar_location: "top",
        theme_advanced_toolbar_align: "left",
        toolbar: 'insertfile undo redo | styleselect | fontselect |sizeselect | fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons | code',
        fontsize_formats: "8pt 9pt 10pt 11pt 12pt 13pt 14pt 15pt 16pt 17pt 18pt 24pt 36pt",
        mode: "exact",
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true
    });
</script>
</body>
</html>
