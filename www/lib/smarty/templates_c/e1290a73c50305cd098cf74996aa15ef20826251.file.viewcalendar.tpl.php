<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:05:37
         compiled from "/var/www/newznab/www/views/templates/frontend/viewcalendar.tpl" */ ?>
<?php /*%%SmartyHeaderCode:8729360695166d141113656-96748007%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'e1290a73c50305cd098cf74996aa15ef20826251' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/viewcalendar.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '8729360695166d141113656-96748007',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
?><h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<div style="float:right;">
<?php  $_smarty_tpl->tpl_vars['c'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('cal')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['c']->key => $_smarty_tpl->tpl_vars['c']->value){
?>
<a href="<?php echo @WWW_TOP;?>
/calendar?date=<?php echo $_smarty_tpl->tpl_vars['c']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['c']->value;?>
</a>&nbsp;&nbsp;&nbsp;             
<?php }} ?>
</div>
<table><tr valign="top"><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	<?php if (count($_smarty_tpl->getVariable('predata')->value)>0){?>
		<td style="padding-top:15px;" colspan="10"><h2><?php echo $_smarty_tpl->getVariable('predate')->value;?>
</h2></td>
	</tr>
	<?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('predata')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td><a class="title" title="View series" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
"><?php echo $_smarty_tpl->tpl_vars['s']->value['showtitle'];?>
</a><br/><?php echo $_smarty_tpl->tpl_vars['s']->value['fullep'];?>
 - <?php echo $_smarty_tpl->tpl_vars['s']->value['eptitle'];?>
</td>
		</tr>
	<?php }} ?>
<?php }else{ ?>
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
<?php }?>
</table>
</td><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	<?php if (count($_smarty_tpl->getVariable('daydata')->value)>0){?>
		<td style="padding-top:15px;" colspan="10"><h2><?php echo $_smarty_tpl->getVariable('date')->value;?>
</h2></td>
	</tr>
	<?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('daydata')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td><a class="title" title="View series" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
"><?php echo $_smarty_tpl->tpl_vars['s']->value['showtitle'];?>
</a><br/><?php echo $_smarty_tpl->tpl_vars['s']->value['fullep'];?>
 - <?php echo $_smarty_tpl->tpl_vars['s']->value['eptitle'];?>
</td>
		</tr>
	<?php }} ?>
<?php }else{ ?>
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
<?php }?>
</table>
</td><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	<?php if (count($_smarty_tpl->getVariable('nxtdata')->value)>0){?>
		<td style="padding-top:15px;" colspan="10"><h2><?php echo $_smarty_tpl->getVariable('nxtdate')->value;?>
</h2></td>
	</tr>
	<?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('nxtdata')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td><a class="title" title="View series" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
"><?php echo $_smarty_tpl->tpl_vars['s']->value['showtitle'];?>
</a><br/><?php echo $_smarty_tpl->tpl_vars['s']->value['fullep'];?>
 - <?php echo $_smarty_tpl->tpl_vars['s']->value['eptitle'];?>
</td>
		</tr>
	<?php }} ?>
<?php }else{ ?>
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
<?php }?>
</table>
</td></tr></table>
