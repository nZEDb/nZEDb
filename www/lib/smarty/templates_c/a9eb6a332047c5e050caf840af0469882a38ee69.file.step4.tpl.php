<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:56:41
         compiled from "/var/www/newznab/www/views/templates/install/step4.tpl" */ ?>
<?php /*%%SmartyHeaderCode:16275833515166cf29ee56c6-50556241%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'a9eb6a332047c5e050caf840af0469882a38ee69' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/step4.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '16275833515166cf29ee56c6-50556241',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<div align="center">
<?php if (!$_smarty_tpl->getVariable('cfg')->value->error){?>
	<p>The configuration file has been saved, you may continue to the next step.</p>
	<form action="step5.php"><input type="submit" value="Step five: Setup admin user" /></form>
<?php }else{ ?>
	<?php if (!$_smarty_tpl->getVariable('cfg')->value->saveConfigCheck){?>
		<h3><span class="error">Error saving <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/config.php.</span></h3>
		<p>Please save the config.php youself by creating:<br /><b><?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/config.php</b><br />and setting its contents to the following:</p>
		<p><textarea cols="100" rows="60"><?php echo $_smarty_tpl->getVariable('cfg')->value->COMPILED_CONFIG;?>
</textarea></p>
	<?php }?>
	<?php if (!$_smarty_tpl->getVariable('cfg')->value->saveLockCheck){?>
		<br />
		<h3><span class="error">Error saving <?php echo $_smarty_tpl->getVariable('cfg')->value->INSTALL_DIR;?>
/install.lock</span></h3>
		<p>Please save the install.lock youself by creating:<br /><b><?php echo $_smarty_tpl->getVariable('cfg')->value->INSTALL_DIR;?>
/install.lock</b></p>
	<?php }?>
<?php }?>
</div>