<!DOCTYPE html>

<html>
<head>
	<script type="text/javascript">
		/* <![CDATA[ */
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "{$serverroot}";
		/* ]]> */
	</script>
	<meta charset="UTF-8">
	<title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle}</title>
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
	<!-- Bootstrap 3.3.6 -->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap/v3/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
	<!-- Font Awesome Icons -->
	<link href="{$smarty.const.WWW_THEMES}/shared/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
	<!-- Ionicons -->
	<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
	<!-- Theme style -->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/AdminLTE/v2/dist/css/AdminLTE.css"
			rel="stylesheet" type="text/css"/>
	<!-- AdminLTE Skins. We have chosen the skin-blue for this starter
		  page. However, you can choose any other skin. Make sure you
		  apply the skin class to the body tag so the changes take effect.
	-->
	<link href="{$smarty.const.WWW_THEMES}/shared/libs/AdminLTE/v2/dist/css/skins/skin-blue.min.css" rel="stylesheet" type="text/css"/>
	<!-- Newznab utils.js -->
	<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/utils.js"></script>

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>

<body class="skin-blue layout-boxed">
<div class="wrapper">
	<div class="header">
		<h2>View > <strong>{$page->title}</strong></h2>

		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ {$page->title}
			</ol>
		</div>
	</div>
	<div class="box-body">
		<div class="box-content"
		<div class="row">
			<div class="box col-md-12">
				<div class="box-content">
					<div class="row">
						<div class="col-xlg-12 portlets">
							<div class="panel">
								<div class="panel-content pagination2">
									<p>{$site->tandc}</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.1.4 -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/plugins/jQuery/jQuery-2.1.4.min.js"></script>
<!-- Bootstrap 3.3.2 JS -->
<script src="{$smarty.const.WWW_THEMES}/shared/libs/bootstrap/v3/js/bootstrap.min.js"
		type="text/javascript"></script>
<!-- Bootstrap hover on mouseover script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/Omicron/plugins/hover/bootstrap-hover-dropdown.min.js"></script>
<!-- AdminLTE App -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/dist/js/app.min.js" type="text/javascript"></script>
<!-- jQuery migrate script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_THEMES}/Omicron/plugins/migrate/jquery-migrate-1.2.1.min.js"></script>
<!-- SlimScroll script -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- Fastclick script -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/plugins/fastclick/fastclick.min.js"></script>
<!-- Notification script -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/plugins/noty/packaged/jquery.noty.packaged.min.js"></script>
<!-- Custom functions -->
<script src="{$smarty.const.WWW_THEMES}/Omicron/dist/js/functions.js" type="text/javascript"></script>
<!-- data table plugin -->
<script type="text/javascript"
		src='{$smarty.const.WWW_THEMES}/Omicron/dist/js/jquery.dataTables.min.js'></script>
<!-- default scripts, needed for stuff to work -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/jquery.autosize-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/jquery.qtip2.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/sorttable.js"></script>
<!-- Newznab utils.js -->
<script type="text/javascript" src="{$smarty.const.WWW_THEMES}/Omicron/scripts/utils.js"></script>

</html>
