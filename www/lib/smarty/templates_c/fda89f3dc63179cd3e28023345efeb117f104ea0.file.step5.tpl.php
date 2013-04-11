<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:56:42
         compiled from "/var/www/newznab/www/views/templates/install/step5.tpl" */ ?>
<?php /*%%SmartyHeaderCode:8976864955166cf2ae5e3e6-20749042%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'fda89f3dc63179cd3e28023345efeb117f104ea0' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/step5.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '8976864955166cf2ae5e3e6-20749042',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_smarty_tpl->getVariable('page')->value->isSuccess()){?>
	<div align="center">
		<p>The admin user has been setup, you may continue to the next step.</p>
		<form action="step6.php"><input type="submit" value="Step Six: Set NZB File Path" /></form>
	</div>
<?php }else{ ?>

<p>You must setup an admin user. Please provide the following information:</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="alt">
			<td><label for="user">Username:</label></td>
			<td><input autocomplete="off" type="text" name="user" id="user" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->ADMIN_USER;?>
" /></td>
		</tr>
		<tr class="">
			<td><label for="pass">Password:</label></td>
			<td><input autocomplete="off" type="text" name="pass" id="pass" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->ADMIN_PASS;?>
" /></td>
		</tr>
		<tr class="alt">
			<td><label for="email">Email:</label> </td>
			<td><input autocomplete="off" type="text" name="email" id="email" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->ADMIN_EMAIL;?>
" /></td>
		</tr>
	</table>

	<div style="padding-top:20px; text-align:center;">
			<?php if ($_smarty_tpl->getVariable('cfg')->value->error){?>
			<div>
				The following error(s) were encountered:<br />
				<?php if ($_smarty_tpl->getVariable('cfg')->value->ADMIN_USER==''){?><span class="error">&bull; Invalid username</span><br /><?php }?>
				<?php if ($_smarty_tpl->getVariable('cfg')->value->ADMIN_PASS==''){?><span class="error">&bull; Invalid password</span><br /><?php }?>
				<?php if ($_smarty_tpl->getVariable('cfg')->value->ADMIN_EMAIL==''){?><span class="error">&bull; Invalid email</span><br /><?php }?>
				<br />
			</div>
			<?php }?>
			<input type="submit" value="Create Admin User" />
	</div>
</form>

<?php }?>