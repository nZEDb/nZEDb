<!DOCTYPE html>
<html>

<head>
	{literal}
	<script>
		/* <![CDATA[ */
		var WWW_TOP = "{/literal}{$smarty.const.WWW_TOP}{literal}";
		var SERVERROOT = "{/literal}{$serverroot}{literal}";
		var UID = "{/literal}{if $loggedin == "true"}{$userdata.id}{else}{/if}{literal}";
		var RSSTOKEN = "{/literal}{if $loggedin == "true"}{$userdata.rsstoken}{else}{/if}{literal}";
		/* ]]> */
	</script>
	{/literal}
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<!-- Meta, title, CSS, favicons, etc. -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

	<title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle}</title>

	<!-- Bootstrap core CSS -->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-3.3.x/dist/css/bootstrap.min.css" rel="stylesheet"
		  type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/font-awesome-4.5.x/css/font-awesome.min.css" rel="stylesheet"
		  type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.css" rel="stylesheet" type="text/css"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/animate.css/animate.min.css" rel="stylesheet">
	<!-- Normalize.css -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/normalize.css" rel="stylesheet" type="text/css">
	<!-- Custom styling plus plugins -->
	<!-- Newposterwall -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/posterwall.css" rel="stylesheet" type="text/css" media="screen"/>
	<link href="{$smarty.const.WWW_THEMES}/shared/css/custom.css" rel="stylesheet">
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/icheck-1.0.x/skins/flat/green.css" rel="stylesheet">
	<link href="{$smarty.const.WWW_THEMES}/shared/js/jquery.qtip2.css" type="text/css" media="screen"/>
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	<script src="{$smarty.const.WWW_THEMES}/shared/libs/html5shiv-3.7.x/dist/html5shiv.min.js"></script>
	<script src="{$smarty.const.WWW_THEMES}/shared/libs/respond-1.4.x/dest/respond.min.js"></script>
	<![endif]-->

</head>
<body class="nav-md">
<div class="container body">
	<div class="main_container">
		<div class="col-md-3 left_col">
			<div class="left_col scroll-view">
				<div class="navbar nav_title" style="border: 0;">
					<a href="{$site->home_link}" class="site_title"><i class="fa fa-mixcloud"></i>
						<span>{$site->title}</span></a>
				</div>
				<div class="clearfix"></div>
				<!-- menu profile quick info -->
				<div class="profile">
					<div class="profile_pic">
						<img src="{$smarty.const.WWW_THEMES}/shared/img/userimage.png" alt="User Image"
							 class="img-circle profile_img">
					</div>
					{if $loggedin == "true"}
						<div class="profile_info">
							<span>Welcome,</span>
							<h2>{$userdata.username}</h2>
						</div>
					{/if}
				</div>
				<!-- /menu profile quick info -->
				<br/>
				<!-- sidebar menu -->
				<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
					<div class="menu_section">
						<h3>Main</h3>
						<ul class="nav side-menu">
							{if $loggedin == "true"}
								<li><a><i class="fa fa-home"></i><span> Browse</span> <span
												class="fa fa-chevron-down"></span></a>
									<ul class="nav child_menu" style="display: none">
										<li><a href="{$smarty.const.WWW_TOP}/newposterwall"><i
														class="fa fa-fire"></i><span> New Releases</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/console"><i
														class="fa fa-gamepad"></i><span> Console</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/movies"><i
														class="fa fa-film"></i><span> Movies</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/music"><i
														class="fa fa-music"></i><span> Music</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/games"><i
														class="fa fa-gamepad"></i><span> Games</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/series"><i
														class="fa fa-television"></i><span> TV</span></a></li>
										<li><a href="{$smarty.const.WWW_TOP}/xxx"><i class="fa fa-venus-mars"></i><span> Adult</span></a>
										</li>
										<li><a href="{$smarty.const.WWW_TOP}/books"><i class="fa fa-book"></i><span> Books</span></a>
										</li>
										<li><a href="{$smarty.const.WWW_TOP}/browse"><i
														class="fa fa-list-ul"></i><span> Browse All Releases</span></a>
										</li>
										<li><a href="{$smarty.const.WWW_TOP}/browsegroup"><i
														class="fa fa-object-group"></i><span> Browse Groups</span></a>
										</li>
									</ul>
								</li>
							{/if}
							<li><a><i class="fa fa-edit"></i> Articles & Links <span class="fa fa-chevron-down"></span></a>
								<ul class="nav child_menu" style="display: none">
									<li><a href="{$smarty.const.WWW_TOP}/contact-us"><i
													class="fa fa-envelope-o"></i><span> Contact</span> <span
													class="fa arrow"></span></a></li>
									{if $loggedin == "true"}
										<li><a href="{$smarty.const.WWW_TOP}/forum"><i class="fa fa-forumbee"></i> Forum</a>
										</li>
										<li><a href="{$smarty.const.WWW_TOP}/search"><i class="fa fa-search"></i> Search</a>
										</li>
										<li><a href="{$smarty.const.WWW_TOP}/rss"><i class="fa fa-rss"></i> RSS
												Feeds</a></li>
										<li><a href="{$smarty.const.WWW_TOP}/apihelp"><i class="fa fa-cloud"></i>
												API</a></li>
									{/if}
								</ul>
								{if $loggedin == "true"}
							<li><a href="{$smarty.const.WWW_TOP}/logout"><i
											class="fa fa-unlock"></i><span> Sign Out</span></a></li>
							{else}
							<li><a href="{$smarty.const.WWW_TOP}/login"><i class="fa fa-lock"></i><span> Sign In</span></a>
							</li>
							{/if}
						</ul>
					</div>
				</div>
				<!-- /sidebar menu -->
			</div>
		</div>
		<!-- top navigation -->
		<div class="top_nav">
			<div class="nav_menu">
				<nav class="" role="navigation">
					<div class="nav toggle">
						<a id="menu_toggle"><i class="fa fa-bars"></i></a>
					</div>
					{$header_menu}
					<ul class="nav navbar-nav">
						<li class="">
							<a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown"
							   data-hover="dropdown" data-close-others="true" data-delay="30" aria-expanded="false">
								{if $loggedin == "true"}
								<img src="{$smarty.const.WWW_THEMES}/shared/img/userimage.png"
									 alt="User Image"> {$userdata.username}
								<span class=" fa fa-angle-down"></span>
							</a>
							<ul class="dropdown-menu dropdown-usermenu animated jello pull-right">
								<li><a href="{$smarty.const.WWW_TOP}/cart"><i class="fa fa-shopping-basket"></i> My
										Download Basket</a>
								</li>
								<li>
									<a href="{$smarty.const.WWW_TOP}/queue"><i class="fa fa-list-alt"></i> My Queue</a>
								</li>
								<li>
									<a href="{$smarty.const.WWW_TOP}/mymovies"><i class="fa fa-film"></i> My Movies</a>
								</li>
								<li><a href="{$smarty.const.WWW_TOP}/myshows"><i class="fa fa-television"></i> My Shows</a>
								</li>
								<li>
									<a href="{$smarty.const.WWW_TOP}/profileedit"><i class="fa fa-cog fa-spin"></i>
										Account Settings</a>
								</li>
								{if isset($isadmin)}
									<li>
										<a href="{$smarty.const.WWW_TOP}/admin"><i class="fa fa-cogs fa-spin"></i> Admin</a>
									</li>
								{/if}
								<li>
									<a href="{$smarty.const.WWW_TOP}/profile" class="btn btn-default btn-flat"><i
												class="fa fa-user"></i> Profile</a>
								</li>
								<li>
									<a href="{$smarty.const.WWW_TOP}/logout" class="btn btn-default btn-flat"><i
												class="fa fa-unlock-alt"></i> Sign out</a>
								</li>
								{else}
								<li><a href="{$smarty.const.WWW_TOP}/login"><i
												class="fa fa-lock"></i><span> Login</span></a></li>
								<li><a href="{$smarty.const.WWW_TOP}/register"><i class="fa fa-bookmark-o"></i><span> Register</span></a>
								</li>
								{/if}
							</ul>
						</li>
					</ul>
				</nav>
			</div>
		</div>
		<!-- /top navigation -->

		<!-- page content -->
		<div class="right_col" role="main">
			<div class="clearfix"></div>
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					{$page->content}
					<div class="clearfix"></div>
				</div>
			</div>
			<!-- footer content -->
			<footer>
				<div class="copyright-info">
					<strong>Copyright &copy; <a
								href="{$smarty.const.WWW_TOP}{$site->home_link}">{if $site->title == ''}nZEDb{else}{$site->title}{/if}</a> all rights reserved {$smarty.now|date_format:"%Y"}
				</div>
				<div class="clearfix"></div>
			</footer>
			<!-- /footer content -->

		</div>
		<!-- /page content -->
	</div>

</div>
<script src="{$smarty.const.WWW_THEMES}/shared/libs/jquery-2.2.x/dist/jquery.min.js" type="text/javascript"></script>
<script src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-3.3.x/dist/js/bootstrap.min.js"
		type="text/javascript"></script>
<!-- bootstrap progress js -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-progressbar-0.9.x/bootstrap-progressbar.min.js"></script>
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap-hover-dropdown-2.2.x/bootstrap-hover-dropdown.min.js"></script>
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/shared/libs/jquery.nicescroll-3.6.x/jquery.nicescroll.min.js"></script>
<!-- Custom functions -->
<script src="{$smarty.const.WWW_THEMES}/shared/js/functions.js" type="text/javascript"></script>
<!-- icheck -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/icheck-1.0.x/icheck.min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/custom.js"></script>
<!-- jQuery migrate script -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/jquery-migrate-1.4.x/jquery-migrate.min.js"></script>
<!-- newznab default scripts, needed for stuff to work -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/colorbox-1.6.x/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/autosize-3.0.x/dist/autosize.min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/jquery.qtip2.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/js/sorttable.js"></script>

<!-- PNotify -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.animate.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.desktop.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.callbacks.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.buttons.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.confirm.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/pnotify-3.0.x/dist/pnotify.nonblock.js"></script>

<!-- pace -->
<script src="{$smarty.const.WWW_THEMES}/shared/libs/pace-1.0.x/pace.min.js"></script>
<!-- tinymce editor -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/shared/libs/tinymce-builded/js/tinymce/tinymce.min.js"></script>

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
