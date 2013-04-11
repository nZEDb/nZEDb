<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:47:55
         compiled from "/var/www/newznab/www/views/templates/install/installpage.tpl" */ ?>
<?php /*%%SmartyHeaderCode:4636186135166cd1bd62184-60921193%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '236f68a8ddff44bc2012aac6bbd66053a4b75c77' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/installpage.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '4636186135166cd1bd62184-60921193',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_date_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.date_format.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</title>
	<link href="../views/styles/install.css" rel="stylesheet" type="text/css" media="screen" />
	<link rel="shortcut icon" type="image/ico" href="../views/images/favicon.ico"/>
	<?php echo $_smarty_tpl->getVariable('page')->value->head;?>

</head>
<body>
	<h1 id="logo"><img alt="Newznab" src="../views/images/banner.jpg" /></h1>
	<div class="content">	
		<h2><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h2>
		<?php echo $_smarty_tpl->getVariable('page')->value->content;?>

	
		<div class="footer">
			<p><br /><a href="http://www.newznab.com/">newznab</a> is released under GPL. All rights reserved <?php echo smarty_modifier_date_format(time(),"%Y");?>
.</p>
		</div>
	</div>
</body>
</html>
