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
	<!-- Bootstrap 3.3.4 -->
	<link href="{$smarty.const.WWW_TOP}/themes/omicron/bootstrap/css/bootstrap.min.css" rel="stylesheet"
		  type="text/css"/>
	<!-- Font Awesome Icons -->
	<link href="{$smarty.const.WWW_TOP}/themes/omicron/bootstrap/css/font-awesome.min.css" rel="stylesheet"
		  type="text/css"/>
	<!-- Ionicons -->
	<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
	<!-- Theme style -->
	<link href="{$smarty.const.WWW_TOP}/themes/omicron/dist/css/AdminLTE.css" rel="stylesheet" type="text/css"/>
	<!-- AdminLTE Skins. We have chosen the skin-blue for this starter
		  page. However, you can choose any other skin. Make sure you
		  apply the skin class to the body tag so the changes take effect.
	-->
	<link href="{$smarty.const.WWW_TOP}/themes/omicron/dist/css/skins/skin-blue.min.css" rel="stylesheet"
		  type="text/css"/>
	<!-- Newznab utils.js -->
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/utils.js"></script>
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
		<h2>Contact Us</h2>
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ Contact
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
								<div class="panel panel-default">
									<div class="panel-body pagination2">
										<div class="box-body">
										<div class="row">
											<div class="col-sm-8">
												{$msg}
												{if $msg == ""}
												<h2>Have a question? <br> Don't hesitate to send us a message. Our team
													will be
													happy to help you.</h2>
											</div>
										</div>
										<div class="row m-b-30">
											<div class="col-sm-6">
												<form method="POST" action="{$serverroot}contact-us">
													<div class="row">
														<div class="col-sm-6">
															<label for="username" class="h6">Name</label>
															<input id="username" type="text" name="username" value=""
																   placeholder="Name" class="form-control form-white">
														</div>
														<div class="col-sm-6">
															<label for="useremail" class="h6">E-mail</label>
															<input type="text" id="useremail" name="useremail"
																   class="form-control form-white">
														</div>
													</div>
													<label for="comment" class="h6">Message</label>
										<textarea rows="7" name="comment" id="comment"
												  class="form-control form-white"></textarea>
													{$page->smarty->fetch('captcha.tpl')}
													<button type="submit" value="submit" class="btn btn-primary m-t-20">
														Send
														message
													</button>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						{/if}
							</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
<!-- REQUIRED JS SCRIPTS -->
<!-- jQuery 2.1.4 -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/jQuery/jQuery-2.1.4.min.js"></script>
<!-- Bootstrap 3.3.2 JS -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/bootstrap/js/bootstrap.min.js"
		type="text/javascript"></script>
<!-- Bootstrap hover on mouseover script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/hover/bootstrap-hover-dropdown.min.js"></script>
<!-- AdminLTE App -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/dist/js/app.min.js" type="text/javascript"></script>
<!-- jQuery migrate script -->
<script type="text/javascript"
		src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/migrate/jquery-migrate-1.2.1.min.js"></script>
<!-- SlimScroll script -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- Fastclick script -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/fastclick/fastclick.min.js"></script>
<!-- Notification script -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/noty/packaged/jquery.noty.packaged.min.js"></script>
<!-- Custom functions -->
<script src="{$smarty.const.WWW_TOP}/themes/omicron/dist/js/functions.js" type="text/javascript"></script>
<!-- data table plugin -->
<script type="text/javascript"
		src='{$smarty.const.WWW_TOP}/themes/omicron/dist/js/jquery.dataTables.min.js'></script>
<!-- newznab default scripts, needed for stuff to work -->
<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/jquery.autosize-min.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/jquery.qtip2.js"></script>
<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/sorttable.js"></script>
<!-- Newznab utils.js -->
<script type="text/javascript" src="{$smarty.const.WWW_TOP}/themes/omicron/scripts/utils.js"></script>
</html>