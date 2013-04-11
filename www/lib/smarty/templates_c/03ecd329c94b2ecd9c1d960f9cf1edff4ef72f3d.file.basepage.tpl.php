<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 17:46:24
         compiled from "/var/www/newznab/www/views/templates/frontend/basepage.tpl" */ ?>
<?php /*%%SmartyHeaderCode:50466145551672f301a5244-39316607%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '03ecd329c94b2ecd9c1d960f9cf1edff4ef72f3d' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/basepage.tpl',
      1 => 1365716781,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '50466145551672f301a5244-39316607',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="<?php echo $_smarty_tpl->getVariable('page')->value->meta_keywords;?>
<?php if ($_smarty_tpl->getVariable('site')->value->metakeywords!=''){?>,<?php echo $_smarty_tpl->getVariable('site')->value->metakeywords;?>
<?php }?>" />
	<meta name="description" content="<?php echo $_smarty_tpl->getVariable('page')->value->meta_description;?>
<?php if ($_smarty_tpl->getVariable('site')->value->metadescription!=''){?> - <?php echo $_smarty_tpl->getVariable('site')->value->metadescription;?>
<?php }?>" />	
	<meta name="newznab_version" content="<?php echo $_smarty_tpl->getVariable('site')->value->version;?>
" />
	<title><?php echo $_smarty_tpl->getVariable('page')->value->meta_title;?>
<?php if ($_smarty_tpl->getVariable('site')->value->metatitle!=''){?> - <?php echo $_smarty_tpl->getVariable('site')->value->metatitle;?>
<?php }?></title>
<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?>	<link rel="alternate" type="application/rss+xml" title="<?php echo $_smarty_tpl->getVariable('site')->value->title;?>
 Full Rss Feed" href="<?php echo @WWW_TOP;?>
/rss?t=0&amp;dl=1&amp;i=<?php echo $_smarty_tpl->getVariable('userdata')->value['ID'];?>
&amp;r=<?php echo $_smarty_tpl->getVariable('userdata')->value['rsstoken'];?>
" /><?php }?>

	<link href="<?php echo @WWW_TOP;?>
/views/styles/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="<?php echo @WWW_TOP;?>
/views/styles/jquery.qtip.css" rel="stylesheet" type="text/css" media="screen" />
<?php if ($_smarty_tpl->getVariable('site')->value->google_adsense_acc!=''){?>	<link href="http://www.google.com/cse/api/branding.css" rel="stylesheet" type="text/css" media="screen" />
<?php }?>
<?php if ($_smarty_tpl->getVariable('site')->value->style!=''&&$_smarty_tpl->getVariable('site')->value->style!="/"){?>	<link href="<?php echo @WWW_TOP;?>
/views/themes/<?php echo $_smarty_tpl->getVariable('site')->value->style;?>
/style.css" rel="stylesheet" type="text/css" media="screen" />
<?php }?>
	<link rel="shortcut icon" type="image/ico" href="<?php echo @WWW_TOP;?>
/views/images/favicon.ico"/>
	<script type="text/javascript" src="<?php echo @WWW_TOP;?>
/views/scripts/jquery.js"></script>
	<script type="text/javascript" src="<?php echo @WWW_TOP;?>
/views/scripts/utils.js"></script>
	<script type="text/javascript" src="<?php echo @WWW_TOP;?>
/views/scripts/sorttable.js"></script>

	<script type="text/javascript">
	/* <![CDATA[ */	
		var WWW_TOP = "<?php echo @WWW_TOP;?>
";
		var SERVERROOT = "<?php echo $_smarty_tpl->getVariable('serverroot')->value;?>
";
		var UID = "<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?><?php echo $_smarty_tpl->getVariable('userdata')->value['ID'];?>
<?php }else{ ?><?php }?>";
		var RSSTOKEN = "<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?><?php echo $_smarty_tpl->getVariable('userdata')->value['rsstoken'];?>
<?php }else{ ?><?php }?>";
	/* ]]> */		
	</script>
	<?php echo $_smarty_tpl->getVariable('page')->value->head;?>

</head>
<body <?php echo $_smarty_tpl->getVariable('page')->value->body;?>
>

	<div id="statusbar"><?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?>Welcome back <a href="<?php echo @WWW_TOP;?>
/profile"><?php echo $_smarty_tpl->getVariable('userdata')->value['username'];?>
</a>. <a href="<?php echo @WWW_TOP;?>
/logout">Logout</a><?php }else{ ?><a href="<?php echo @WWW_TOP;?>
/login">Login</a> or <a href="<?php echo @WWW_TOP;?>
/register">Register</a><?php }?></div>

	<div id="logo">
		<a class="logolink" title="<?php echo $_smarty_tpl->getVariable('site')->value->title;?>
 Logo" href="<?php echo @WWW_TOP;?>
<?php echo $_smarty_tpl->getVariable('site')->value->home_link;?>
"><img class="logoimg" alt="<?php echo $_smarty_tpl->getVariable('site')->value->title;?>
 Logo" src="<?php echo @WWW_TOP;?>
/views/images/clearlogo.png" /></a>

		<ul><?php echo $_smarty_tpl->getVariable('main_menu')->value;?>
</ul>

		<h1><a href="<?php echo @WWW_TOP;?>
<?php echo $_smarty_tpl->getVariable('site')->value->home_link;?>
"><?php echo $_smarty_tpl->getVariable('site')->value->title;?>
</a></h1>
		<p><em><?php echo $_smarty_tpl->getVariable('site')->value->strapline;?>
</em></p>

		<?php echo $_smarty_tpl->getVariable('site')->value->adheader;?>
		
		
	</div>
	<hr />
	
	<div id="header">
		<div id="menu"> 

			<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?>
				<?php echo $_smarty_tpl->getVariable('header_menu')->value;?>

			<?php }?>
						
		</div> 
	</div>
	
	<div id="page">

		<div id="content">
			<?php echo $_smarty_tpl->getVariable('page')->value->content;?>

		</div>
	
		<div style="clear: both;text-align:right;">
			<a class="w3validator" href="http://validator.w3.org/check?uri=referer">
			<img src="<?php echo @WWW_TOP;?>
/views/images/valid-xhtml10.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" />
			</a> 
		</div>
		
	</div>
	
	<?php if ($_smarty_tpl->getVariable('site')->value->google_analytics_acc!=''){?>
	
	<script type="text/javascript">
	/* <![CDATA[ */	
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {	
	var pageTracker = _gat._getTracker("<?php echo $_smarty_tpl->getVariable('site')->value->google_analytics_acc;?>
");	
	pageTracker._trackPageview();
	} catch(err) {}
	/* ]]> */		
	</script>
	
	<?php }?>

<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?>
<input type="hidden" name="UID" value="<?php echo $_smarty_tpl->getVariable('userdata')->value['ID'];?>
" />
<input type="hidden" name="RSSTOKEN" value="<?php echo $_smarty_tpl->getVariable('userdata')->value['rsstoken'];?>
" />
<?php }?>
	
</body>
</html>
