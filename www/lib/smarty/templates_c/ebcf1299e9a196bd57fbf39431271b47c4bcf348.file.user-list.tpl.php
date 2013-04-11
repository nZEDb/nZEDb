<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 14:47:26
         compiled from "/var/www/newznab/www/views/templates/admin/user-list.tpl" */ ?>
<?php /*%%SmartyHeaderCode:8761534415167053ed0ca16-77247868%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'ebcf1299e9a196bd57fbf39431271b47c4bcf348' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/user-list.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '8761534415167053ed0ca16-77247868',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_html_options')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_options.php';
if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_date_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.date_format.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<div style="float:right;">

	<form name="usersearch" action="">
		<label for="username">username</label>
		<input id="username" type="text" name="username" value="<?php echo $_smarty_tpl->getVariable('username')->value;?>
" size="10" />
		&nbsp;&nbsp;
		<label for="email">email</label>
		<input id="email" type="text" name="email" value="<?php echo $_smarty_tpl->getVariable('email')->value;?>
" size="10" />
		&nbsp;&nbsp;
		<label for="host">host</label>
		<input id="host" type="text" name="host" value="<?php echo $_smarty_tpl->getVariable('host')->value;?>
" size="10" />
		&nbsp;&nbsp;
		<label for="role">role</label>
		<select name="role">
			<option value="">-- any --</option>
			<?php echo smarty_function_html_options(array('values'=>$_smarty_tpl->getVariable('role_ids')->value,'output'=>$_smarty_tpl->getVariable('role_names')->value,'selected'=>$_smarty_tpl->getVariable('role')->value),$_smarty_tpl);?>

		</select>
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

<?php echo $_smarty_tpl->getVariable('pager')->value;?>


<br/><br/>

<table style="width:100%;margin-top:10px;" class="data highlight">

	<tr>
		<th>name<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyusername_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyusername_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>email<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyemail_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyemail_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>host<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyhost_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyhost_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>join date<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbycreateddate_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbycreateddate_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>last login<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbylastlogin_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbylastlogin_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>api access<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyapiaccess_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyapiaccess_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>grabs<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbygrabs_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbygrabs_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>invites</th>
		<th>role<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyrole_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyrole_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/../views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>options</th>
	</tr>

	
	<?php  $_smarty_tpl->tpl_vars['user'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('userlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['user']->key => $_smarty_tpl->tpl_vars['user']->value){
?>
	<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
		<td><a href="<?php echo @WWW_TOP;?>
/user-edit.php?id=<?php echo $_smarty_tpl->tpl_vars['user']->value['ID'];?>
"><?php echo $_smarty_tpl->tpl_vars['user']->value['username'];?>
</a></td>
		<td><?php echo $_smarty_tpl->tpl_vars['user']->value['email'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['user']->value['host'];?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['user']->value['createddate'];?>
"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['user']->value['createddate']);?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['user']->value['lastlogin'];?>
"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['user']->value['lastlogin']);?>
</td>
		<td title="<?php echo $_smarty_tpl->tpl_vars['user']->value['apiaccess'];?>
"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['user']->value['apiaccess']);?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['user']->value['grabs'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['user']->value['invites'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['user']->value['rolename'];?>
</td>
		<td><?php if ($_smarty_tpl->tpl_vars['user']->value['role']!="2"){?><a class="confirm_action" href="<?php echo @WWW_TOP;?>
/user-delete.php?id=<?php echo $_smarty_tpl->tpl_vars['user']->value['ID'];?>
">delete</a><?php }?></td>
	</tr>
	<?php }} ?>


</table>