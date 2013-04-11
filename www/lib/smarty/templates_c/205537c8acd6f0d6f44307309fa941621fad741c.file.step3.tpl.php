<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:56:04
         compiled from "/var/www/newznab/www/views/templates/install/step3.tpl" */ ?>
<?php /*%%SmartyHeaderCode:2074276015166cf0441bc04-50297194%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '205537c8acd6f0d6f44307309fa941621fad741c' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/step3.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '2074276015166cf0441bc04-50297194',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_smarty_tpl->getVariable('page')->value->isSuccess()){?>
	<div align="center">
		<p>The news server setup is correct, you may continue to the next step.</p>
		<form action="step4.php"><input type="submit" value="Step four: Save Settings" /></form> 
	</div>
<?php }else{ ?>

<p>newznab has a <a href="http://affiliate.astraweb.com/10457.html">special affilate deal</a> with <a href="http://affiliate.astraweb.com/10457.html">astraweb</a>, who in our opinion provide the best value usenet access. newznab is also designed to take advantage of astrawebs compression support, which other providers do not offer.
</p>

<p>If you already have a news server (NNTP), please provide the following information:</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="">
			<td><label for="server">Server:</label></td>
			<td>
				<input type="text" name="server" id="server" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->NNTP_SERVER;?>
" />
				<div class="hint">e.g. eu.news.astraweb.com</div>
			</td>
		</tr>
		<tr class="alt">
			<td><label for="user">Username:</label></td>
			<td><input type="text" name="user" id="user" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->NNTP_USERNAME;?>
" /></td>
		</tr>
		<tr class="">
			<td><label for="pass">Password:</label></td>
			<td>
				<input type="text" name="pass" id="pass" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->NNTP_PASSWORD;?>
" />
			</td>
		</tr>
		<tr class="alt">
			<td><label for="port">Port:</label></td>
			<td>
				<input type="text" name="port" id="port" value="<?php echo $_smarty_tpl->getVariable('cfg')->value->NNTP_PORT;?>
" />
				<div class="hint">e.g. 119 or 443,564 for SSL</div>
			</td>
		</tr>
		<tr>
			<td><label for="ssl">SSL?:</label></td>
			<td>
				<input type="checkbox" name="ssl" id="ssl" value="1" <?php if ($_smarty_tpl->getVariable('cfg')->value->NNTP_SSLENABLED=="true"){?>checked="checked"<?php }?> />
			</td>
		</tr>		
	</table>

	<div style="padding-top:20px; text-align:center;">
			<?php if ($_smarty_tpl->getVariable('cfg')->value->error){?>
			<div>
					The following error was encountered:<br />
					<span class="error">&bull; <?php echo $_smarty_tpl->getVariable('cfg')->value->nntpCheck->message;?>
</span><br /><br />
				<br />
			</div>
			<?php }?>
			<input type="submit" value="Test Connection" />
	</div>

</form>

<?php }?>