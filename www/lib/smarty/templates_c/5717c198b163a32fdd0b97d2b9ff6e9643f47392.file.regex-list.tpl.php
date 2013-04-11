<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 14:44:56
         compiled from "/var/www/newznab/www/views/templates/admin/regex-list.tpl" */ ?>
<?php /*%%SmartyHeaderCode:281640570516704a823ce66-77277785%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '5717c198b163a32fdd0b97d2b9ff6e9643f47392' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/regex-list.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '281640570516704a823ce66-77277785',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_html_options')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_options.php';
if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<p>
	Regexs are applied to group message subjects into releases. The capture groups are named to hold the release name and number of parts.
	They are applied to messages from that group in order, then any general regexs are applied in order afterwards.
</p>
<p>
	If you want to apply a regex to a group and all its children then append an asterix a.b.blah* to the end. 
</p>

<div id="message">msg</div>

<?php echo smarty_function_html_options(array('id'=>"regexGroupSelect",'name'=>'category','options'=>$_smarty_tpl->getVariable('reggrouplist')->value,'selected'=>$_smarty_tpl->getVariable('selectedgroup')->value),$_smarty_tpl);?>


<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th style="width:20px;">id</th>
		<th>group</th>
		<th>regex</th>
		<th>category</th>
		<th>status</th>
		<th>releases</th>
		<th>last match</th>
		<th>ordinal</th>
		<th style="display:none;width:60px;">Order</th>
		<th style="width:75px;">Options</th>
	</tr>
	
	<?php  $_smarty_tpl->tpl_vars['regex'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('regexlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['regex']->key => $_smarty_tpl->tpl_vars['regex']->value){
?>
	<tr id="row-<?php echo $_smarty_tpl->tpl_vars['regex']->value['ID'];?>
" class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
		<td><a id="<?php echo $_smarty_tpl->tpl_vars['regex']->value['ID'];?>
"></a><?php echo $_smarty_tpl->tpl_vars['regex']->value['ID'];?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['regex']->value['description'];?>
"><?php if ($_smarty_tpl->tpl_vars['regex']->value['groupname']==''){?>all<?php }else{ ?><?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['regex']->value['groupname'],"alt.binaries","a.b");?>
<?php }?></td>
		<td title="Edit regex"><a href="<?php echo @WWW_TOP;?>
/regex-edit.php?id=<?php echo $_smarty_tpl->tpl_vars['regex']->value['ID'];?>
"><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['regex']->value['regex'],'html');?>
</a><br>
			<?php echo $_smarty_tpl->tpl_vars['regex']->value['description'];?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['regex']->value['categoryID'];?>
"><?php if ($_smarty_tpl->tpl_vars['regex']->value['categoryID']!=''){?><?php echo $_smarty_tpl->tpl_vars['regex']->value['categoryTitle'];?>
<?php }?></td>
		<td><?php if ($_smarty_tpl->tpl_vars['regex']->value['status']==1){?>active<?php }else{ ?>disabled<?php }?></td>
		<td><?php echo $_smarty_tpl->tpl_vars['regex']->value['num_releases'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['regex']->value['max_releasedate'];?>
</td>
		<td style="text-align:center;"><?php echo $_smarty_tpl->tpl_vars['regex']->value['ordinal'];?>
</td>
		<td style="display:none;"><a title="Move up" href="#">up</a> | <a title="Move down" href="#">down</a></td>
		<td><a href="javascript:ajax_releaseregex_delete(<?php echo $_smarty_tpl->tpl_vars['regex']->value['ID'];?>
)">delete</a><?php if ($_smarty_tpl->tpl_vars['regex']->value['groupname']!=''){?> | <a href="<?php echo @WWW_TOP;?>
/regex-test.php?action=submit&groupname=<?php echo $_smarty_tpl->tpl_vars['regex']->value['groupID'];?>
&regex=<?php echo urlencode($_smarty_tpl->tpl_vars['regex']->value['regex']);?>
">test</a><?php }?></td>
	</tr>
	<?php }} ?>


</table>
