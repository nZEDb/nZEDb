<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 14:25:04
         compiled from "/var/www/newznab/www/views/templates/frontend/viewfilelist.tpl" */ ?>
<?php /*%%SmartyHeaderCode:409756560516700006b57b5-63115333%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'e3ae8cdf0019f7b96c2cc0152ab2ff83d175fe6a' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/viewfilelist.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '409756560516700006b57b5-63115333',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_fsize_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.fsize_format.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<h2>For <a href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->getVariable('rel')->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('rel')->value['searchname'],"htmlall");?>
"><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('rel')->value['searchname'],'htmlall');?>
</a></h2>

<table style="width:100%;margin-bottom:10px;" class="data Sortable highlight">

	<tr>
		<th>#</th>
		<th>filename</th>
		<th></th>
		<th style="text-align:center;">completion</th>
		<th style="text-align:center;">size</th>
	</tr>

	<?php  $_smarty_tpl->tpl_vars['file'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('files')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['smarty']->value['foreach']['iteration']['index']=-1;
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['file']->key => $_smarty_tpl->tpl_vars['file']->value){
 $_smarty_tpl->tpl_vars['smarty']->value['foreach']['iteration']['index']++;
?>
	<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
		<td width="20"><?php echo $_smarty_tpl->getVariable('smarty')->value['foreach']['iteration']['index']+1;?>
</td>
		<td><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['file']->value['title'],'htmlall');?>
</td>
		
		<?php $_smarty_tpl->tpl_vars["icon"] = new Smarty_variable((('views/images/fileicons/').($_smarty_tpl->tpl_vars['file']->value['ext'])).(".png"), null, null);?> 
		<?php if ($_smarty_tpl->tpl_vars['file']->value['ext']==''||!is_file(($_smarty_tpl->getVariable('icon')->value))){?>
			<?php $_smarty_tpl->tpl_vars["icon"] = new Smarty_variable('file', null, null);?>
		<?php }else{ ?>
			<?php $_smarty_tpl->tpl_vars["icon"] = new Smarty_variable($_smarty_tpl->tpl_vars['file']->value['ext'], null, null);?>
		<?php }?>
		
		<?php $_smarty_tpl->tpl_vars["completion"] = new Smarty_variable(number_format(($_smarty_tpl->tpl_vars['file']->value['partsactual']/$_smarty_tpl->tpl_vars['file']->value['partstotal']*100),1), null, null);?>
		
		<td><img title=".<?php echo $_smarty_tpl->tpl_vars['file']->value['ext'];?>
" alt="<?php echo $_smarty_tpl->tpl_vars['file']->value['ext'];?>
" src="<?php echo @WWW_TOP;?>
/views/images/fileicons/<?php echo $_smarty_tpl->getVariable('icon')->value;?>
.png" /></td>
		<td class="less right"><?php if ($_smarty_tpl->getVariable('completion')->value<100){?><span class="warning"><?php echo $_smarty_tpl->getVariable('completion')->value;?>
%</span><?php }else{ ?><?php echo $_smarty_tpl->getVariable('completion')->value;?>
%<?php }?></td>
		<td class="less right"><?php if ($_smarty_tpl->tpl_vars['file']->value['size']<100000){?><?php echo smarty_modifier_fsize_format($_smarty_tpl->tpl_vars['file']->value['size'],"KB");?>
<?php }else{ ?><?php echo smarty_modifier_fsize_format($_smarty_tpl->tpl_vars['file']->value['size'],"MB");?>
<?php }?></td>
	</tr>
	<?php }} ?>

</table>	

