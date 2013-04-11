<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 14:32:35
         compiled from "/var/www/newznab/www/views/templates/frontend/content.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1544955907516701c3939fb0-39209012%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'dc84be375fc3c1ff1455432e5bcd29b10d038354' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/content.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1544955907516701c3939fb0-39209012',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
 
			<h1><?php echo $_smarty_tpl->getVariable('content')->value->title;?>
</h1>

			<?php echo $_smarty_tpl->getVariable('content')->value->body;?>

