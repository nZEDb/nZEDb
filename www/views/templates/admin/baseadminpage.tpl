<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="" />
	<meta name="description" content="" />	
	<title>{$site->title|default:'newznab'} - {$page->meta_title|default:$page->title}</title>
	<link href="{$smarty.const.WWW_TOP}/../views/styles/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="{$smarty.const.WWW_TOP}/../views/styles/admin.css" rel="stylesheet" type="text/css" media="screen" />
	{if $site->style != "" && $site->style != "/"}<link href="{$smarty.const.WWW_TOP}/../views/themes/{$site->style}/style.css" rel="stylesheet" type="text/css" media="screen" />
<link href="{$smarty.const.WWW_TOP}/../views/themes/{$site->style}/admin.css" rel="stylesheet" type="text/css" media="screen" />	
	{/if}
	<link rel="shortcut icon" type="image/ico" href="{$smarty.const.WWW_TOP}/../views/images/favicon.ico"/>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/../views/scripts/jquery.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/../views/scripts/sorttable.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/../views/scripts/utils-admin.js"></script>
	<script type="text/javascript" src="{$smarty.const.WWW_TOP}/../views/scripts/jquery.multifile.js"></script>
	<script type="text/javascript">var WWW_TOP = "{$smarty.const.WWW_TOP}/..";</script>
	
	{$page->head}
</head>
<body>
	<div id="logo" style="cursor: pointer;">
		<h1><a href="/"></a></h1>
		<p><em></em></p>
	</div>
	<hr />
	
	<div id="header">
		<div id="menu"> 
		</div> 
		<!-- end #menu --> 
	</div>
	
	<div id="page">

		<div id="adpanel">

		</div>

		<div id="content">
			{$page->content}
		</div>
		<!-- end #content -->

		<div id="sidebar">
		<ul>		
		<li>
		{$admin_menu}
		</li>

		</ul>
		</div>
		<!-- end #sidebar -->
	
		<div style="clear: both;">&nbsp;</div>
			
	</div>
	<!-- end #page -->
	
	<div id="searchfooter">
		<center>
		</center>
	</div>
	
	<div class="footer">
	<p>
		{$site->footer}
		<br /><br /><br />Copyright &copy; {$smarty.now|date_format:"%Y"} {$site->title}. All rights reserved.
	</p>
	</div>
	<!-- end #footer -->
	
	{if $google_analytics_acc != ''}
	{literal}
	<script type="text/javascript">
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {	
	var pageTracker = _gat._getTracker("{/literal}{$google_analytics_acc}{literal}");	
	pageTracker._trackPageview();
	} catch(err) {}</script>
	{/literal}
	{/if}
	
</body>
</html>
