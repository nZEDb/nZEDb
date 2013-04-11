<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:05:32
         compiled from "/var/www/newznab/www/views/templates/frontend/mainmenu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:13512369955166d13cc2b911-75166939%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9b1b0ab82aaddcb6b95cb06d48fd6067febe6904' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/mainmenu.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '13512369955166d13cc2b911-75166939',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
?><?php if (count($_smarty_tpl->getVariable('menulist')->value)>0){?> 
<li class="menu_main">
	<h2>Menu</h2> 
	<ul>
	<?php  $_smarty_tpl->tpl_vars['menu'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('menulist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['menu']->key => $_smarty_tpl->tpl_vars['menu']->value){
?>
	<?php $_smarty_tpl->tpl_vars["var"] = new Smarty_variable($_smarty_tpl->tpl_vars['menu']->value['menueval'], null, null);?>	
	<?php $_template = new Smarty_Internal_Template('eval:'.($_smarty_tpl->getVariable('var')->value).",", $_smarty_tpl->smarty, $_smarty_tpl);$_smarty_tpl->assign('menuevalresult',$_template->getRenderedTemplate()); ?>
	<?php if (smarty_modifier_replace($_smarty_tpl->getVariable('menuevalresult')->value,",","1")=="1"){?>
	<li class="mmenu<?php if ($_smarty_tpl->tpl_vars['menu']->value['newwindow']=="1"){?>_new<?php }?>"><a <?php if ($_smarty_tpl->tpl_vars['menu']->value['newwindow']=="1"){?>class="external" target="null"<?php }?> title="<?php echo $_smarty_tpl->tpl_vars['menu']->value['tooltip'];?>
" href="<?php echo $_smarty_tpl->tpl_vars['menu']->value['href'];?>
"><?php echo $_smarty_tpl->tpl_vars['menu']->value['title'];?>
</a></li>
	<?php }?>
	<?php }} ?>
	</ul>
</li>
<?php }?>