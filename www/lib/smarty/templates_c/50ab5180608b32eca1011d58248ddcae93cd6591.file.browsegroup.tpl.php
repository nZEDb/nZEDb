<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:09:40
         compiled from "/var/www/newznab/www/views/templates/frontend/browsegroup.tpl" */ ?>
<?php /*%%SmartyHeaderCode:10743803635166d234c06a29-31455491%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '50ab5180608b32eca1011d58248ddcae93cd6591' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/browsegroup.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '10743803635166d234c06a29-31455491',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
if (!is_callable('smarty_modifier_timeago')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.timeago.php';
?> 
<h1>Browse Groups</h1>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	
	
<?php if (count($_smarty_tpl->getVariable('results')->value)>0){?>

<table style="width:100%;" class="data highlight Sortable" id="browsetable">
	<tr>
		<th>name</th>
		<th>description</th>
		<th>updated</th>
		<th>releases</th>
	</tr>

	<?php  $_smarty_tpl->tpl_vars['result'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('results')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['result']->key => $_smarty_tpl->tpl_vars['result']->value){
?>
		<?php if ($_smarty_tpl->tpl_vars['result']->value['num_releases']>0){?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td>
				<a title="Browse releases from <?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['result']->value['name'],"alt.binaries","a.b");?>
" href="<?php echo @WWW_TOP;?>
/browse?g=<?php echo $_smarty_tpl->tpl_vars['result']->value['name'];?>
"><?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['result']->value['name'],"alt.binaries","a.b");?>
</a>
			</td>
			<td>
					<?php echo $_smarty_tpl->tpl_vars['result']->value['description'];?>

			</td>
			<td class="less"><?php echo smarty_modifier_timeago($_smarty_tpl->tpl_vars['result']->value['last_updated']);?>
 ago</td>
			<td class="less"><?php echo $_smarty_tpl->tpl_vars['result']->value['num_releases'];?>
</td>
		</tr>
		<?php }?>
	<?php }} ?>
	
</table>

<?php }?>
