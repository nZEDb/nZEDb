<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:05:32
         compiled from "/var/www/newznab/www/views/templates/frontend/articlesmenu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:254331525166d13ccde7c7-58331752%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '4566f3b4a611f2c7a6657429da5d89ddbfeac0ae' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/articlesmenu.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '254331525166d13ccde7c7-58331752',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (count($_smarty_tpl->getVariable('articlecontentlist')->value)>0){?>
<li class="menu_articles"> 
	<h2>Articles</h2> 
	<ul>
	<?php  $_smarty_tpl->tpl_vars['content'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('articlecontentlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['content']->key => $_smarty_tpl->tpl_vars['content']->value){
?>
		<li class="mmenu<?php if ($_smarty_tpl->getVariable('menu')->value['newwindow']=="1"){?>_new<?php }?>"><a <?php if ($_smarty_tpl->getVariable('menu')->value['newwindow']=="1"){?>class="external" target="null"<?php }?> title="<?php echo $_smarty_tpl->getVariable('content')->value->title;?>
" href="<?php echo @WWW_TOP;?>
/content/<?php echo $_smarty_tpl->getVariable('content')->value->id;?>
<?php echo $_smarty_tpl->getVariable('content')->value->url;?>
"><?php echo $_smarty_tpl->getVariable('content')->value->title;?>
</a></li>
	<?php }} ?>
	</ul>
</li>
<?php }?>


	
