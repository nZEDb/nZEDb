<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:55:55
         compiled from "/var/www/newznab/www/views/templates/install/step2.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1125745355166cefbdc9061-33981191%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2a1bc7052c81a49ba2be2b5aa6d7c9e5ca7e7450' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/step2.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1125745355166cefbdc9061-33981191',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_smarty_tpl->getVariable('page')->value->isSuccess()){?>
	<div align="center">
		<p>The database setup is correct, you may continue to the next step.</p>
		<form action="step3.php"><input type="submit" value="Step three: Setup news server connection" /></form> 
	</div>
<?php }else{ ?>

<p>We need some information about your MySQL database, please provide the following information</p>
<p>Note: If your database already exists, <u>it will be overwritten</u> with this version. If not it will be created.</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="">
			<td><label for="host">Hostname:</label></td>
			<td><input type="text" name="host" id="host" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->DB_HOST;?>
" /></td>
		</tr>
		<tr class="alt">
			<td><label for="user">Username:</label></td>
			<td><input type="text" name="user" id="user" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->DB_USER;?>
" /></td>
		</tr>
		<tr class="">
			<td><label for="pass">Password:</label></td>
			<td><input type="text" name="pass" id="pass" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->DB_PASSWORD;?>
" /></td>
		</tr>
		<tr class="alt">
			<td><label for="db">Database:</label></td>
			<td><input type="text" name="db" id="db" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->DB_NAME;?>
" /></td>
		</tr>
	</table>

	<div style="padding-top:20px; text-align:center;">
			<?php if ($_smarty_tpl->getVariable('cfg')->value->error){?>
			<div>
				The following error(s) were encountered:<br />
				<?php if ($_smarty_tpl->getVariable('cfg')->value->dbConnCheck===false){?><span class="error">&bull; Unable to connect to database</span><br /><?php }?>
				<?php if ($_smarty_tpl->getVariable('cfg')->value->dbNameCheck===false){?><span class="error">&bull; Unable to select database</span><br /><?php }?>
				<?php if ($_smarty_tpl->getVariable('cfg')->value->dbCreateCheck===false){?><span class="error">&bull; Unable to create database and data. Check permissions of your mysql user.</span><br /><?php }?>
				<br />
			</div>
			<?php }?>
			<input type="submit" value="Setup Database" />
	</div>
</form>

<?php }?>