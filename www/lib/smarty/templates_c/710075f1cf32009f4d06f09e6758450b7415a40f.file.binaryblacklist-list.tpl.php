<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 12:33:38
         compiled from "/var/www/newznab/www/views/templates/admin/binaryblacklist-list.tpl" */ ?>
<?php /*%%SmartyHeaderCode:19397330425166e5e2e1b642-62257666%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '710075f1cf32009f4d06f09e6758450b7415a40f' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/binaryblacklist-list.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '19397330425166e5e2e1b642-62257666',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<p>
	Binaries can be prevented from being added to the index at all if they match a regex provided in the blacklist. They can also be included only if they match a regex (whitelist).
</p>

<div id="message"></div>

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th style="width:20px;">id</th>
		<th>group</th>
		<th>regex</th>
		<th>type</th>
		<th>field</th>
		<th>status</th>
		<th style="width:75px;">Options</th>
	</tr>
	
	<?php  $_smarty_tpl->tpl_vars['bin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('binlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['bin']->key => $_smarty_tpl->tpl_vars['bin']->value){
?>
	<tr id="row-<?php echo $_smarty_tpl->tpl_vars['bin']->value['ID'];?>
" class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
		<td><?php echo $_smarty_tpl->tpl_vars['bin']->value['ID'];?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['bin']->value['description'];?>
"><?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['bin']->value['groupname'],"alt.binaries","a.b");?>
</td>
		<td title="Edit regex"><a href="<?php echo @WWW_TOP;?>
/binaryblacklist-edit.php?id=<?php echo $_smarty_tpl->tpl_vars['bin']->value['ID'];?>
"><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['bin']->value['regex'],'html');?>
</a><br>
		<?php echo $_smarty_tpl->tpl_vars['bin']->value['description'];?>
</td>
		<td><?php if ($_smarty_tpl->tpl_vars['bin']->value['optype']==1){?>black<?php }else{ ?>white<?php }?></td>
		<td><?php if ($_smarty_tpl->tpl_vars['bin']->value['msgcol']==1){?>subject<?php }elseif($_smarty_tpl->tpl_vars['bin']->value['msgcol']==2){?>poster<?php }else{ ?>messageid<?php }?></td>
		<td><?php if ($_smarty_tpl->tpl_vars['bin']->value['status']==1){?>active<?php }else{ ?>disabled<?php }?></td>
		<td><a href="javascript:ajax_binaryblacklist_delete(<?php echo $_smarty_tpl->tpl_vars['bin']->value['ID'];?>
)">delete</a></td>
	</tr>
	<?php }} ?>

</table>
