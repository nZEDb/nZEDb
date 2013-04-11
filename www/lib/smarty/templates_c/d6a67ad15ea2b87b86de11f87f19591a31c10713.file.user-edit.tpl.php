<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 14:47:05
         compiled from "/var/www/newznab/www/views/templates/admin/user-edit.tpl" */ ?>
<?php /*%%SmartyHeaderCode:113883733651670529251e17-56206885%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd6a67ad15ea2b87b86de11f87f19591a31c10713' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/user-edit.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '113883733651670529251e17-56206885',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_html_radios')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_radios.php';
?> 
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<?php if ($_smarty_tpl->getVariable('error')->value!=''){?>
	<div class="error"><?php echo $_smarty_tpl->getVariable('error')->value;?>
</div>
<?php }?>

<form action="<?php echo $_smarty_tpl->getVariable('SCRIPT_NAME')->value;?>
?action=submit" method="POST">

<table class="input">

<tr>
	<td>Name:</td>
	<td>
		<input type="hidden" name="id" value="<?php echo $_smarty_tpl->getVariable('user')->value['ID'];?>
" />
		<input autocomplete="off" class="long" name="username" type="text" value="<?php echo $_smarty_tpl->getVariable('user')->value['username'];?>
" />
	</td>
</tr>

<tr>
	<td>Email:</td>
	<td>
		<input autocomplete="off" class="long" name="email" type="text" value="<?php echo $_smarty_tpl->getVariable('user')->value['email'];?>
" />
	</td>
</tr>

<tr>
	<td>Password:</td>
	<td>
		<input autocomplete="off" class="long" name="password" type="password" value="" />
		<?php if ($_smarty_tpl->getVariable('user')->value['ID']){?>
			<div class="hint">Only enter a password if you want to change it.</div>
		<?php }?>
	</td>	
</tr>
<?php if ($_smarty_tpl->getVariable('user')->value['ID']){?>
<tr>
	<td>Grabs:</td>
	<td>
		<input class="short" name="grabs" type="text" value="<?php echo $_smarty_tpl->getVariable('user')->value['grabs'];?>
" />
	</td>
</tr>


<tr>
	<td>Invites:</td>
	<td>
		<input class="short" name="invites" type="text" value="<?php echo $_smarty_tpl->getVariable('user')->value['invites'];?>
" />
	</td>
</tr>
<?php }?>
<tr>
	<td>Movie View:</td>
	<td>
		<input name="movieview" type="checkbox" value="1" <?php if ($_smarty_tpl->getVariable('user')->value['movieview']=="1"){?>checked="checked"<?php }?>" />
	</td>
</tr>

<tr>
	<td>Music View:</td>
	<td>
		<input name="musicview" type="checkbox" value="1" <?php if ($_smarty_tpl->getVariable('user')->value['musicview']=="1"){?>checked="checked"<?php }?>" />
	</td>
</tr>

<tr>
	<td>Console View:</td>
	<td>
		<input name="consoleview" type="checkbox" value="1" <?php if ($_smarty_tpl->getVariable('user')->value['consoleview']=="1"){?>checked="checked"<?php }?>" />
	</td>
</tr>

<tr>
	<td><label for="role">Role</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"role",'name'=>'role','values'=>$_smarty_tpl->getVariable('role_ids')->value,'output'=>$_smarty_tpl->getVariable('role_names')->value,'selected'=>$_smarty_tpl->getVariable('user')->value['role'],'separator'=>'<br />'),$_smarty_tpl);?>

	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Save" />
	</td>
</tr>

</table>

</form>