<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 12:06:29
         compiled from "/var/www/newznab/www/views/templates/frontend/login.tpl" */ ?>
<?php /*%%SmartyHeaderCode:15526411455166df8548bb14-80795536%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '0ab08846174311ad84ac7591fb90a0c2e4bbd240' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/login.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '15526411455166df8548bb14-80795536',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
?> 
<h1>Login</h1>

<?php if ($_smarty_tpl->getVariable('error')->value!=''){?>
	<div class="error"><?php echo $_smarty_tpl->getVariable('error')->value;?>
</div>
<?php }?>

<form action="login" method="post">
	<input type="hidden" name="redirect" value="<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('redirect')->value,"htmlall");?>
" />
	<table class="data">
		<tr><th><label for="username">Username<br/> or Email</label>:</th>
			<td>
				<input style="width:150px;" id="username" value="<?php echo $_smarty_tpl->getVariable('username')->value;?>
" name="username" type="text"/>
			</td></tr>
		<tr><th><label for="password">Password</label>:</th>
			<td>
				<input style="width:150px;" id="password" name="password" type="password"/>
			</td></tr>
		<tr><th><label for="rememberme">Remember Me</label>:</th><td><input id="rememberme" <?php if ($_smarty_tpl->getVariable('rememberme')->value==1){?>checked="checked"<?php }?> name="rememberme" type="checkbox"/></td>
		<tr><th></th><td><input type="submit" value="Login"/></td></tr>
	</table>
</form>
<br/>
<a href="<?php echo @WWW_TOP;?>
/forgottenpassword">Forgotten your password?</a>
