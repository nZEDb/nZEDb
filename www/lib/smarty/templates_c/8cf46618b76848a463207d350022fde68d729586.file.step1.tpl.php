<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 10:55:50
         compiled from "/var/www/newznab/www/views/templates/install/step1.tpl" */ ?>
<?php /*%%SmartyHeaderCode:5796584805166cef6c38c20-10086836%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8cf46618b76848a463207d350022fde68d729586' => 
    array (
      0 => '/var/www/newznab/www/views/templates/install/step1.tpl',
      1 => 1365692148,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '5796584805166cef6c38c20-10086836',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.truncate.php';
?><table width="100%" border="0" style="margin-top:10px;" class="data highlight">
	<tr>
		<th>check</th>
		<th style="width:75px;">status</th>
	</tr>
	<tr class="alt">
		<td>Checking for Curl support:<?php if (!$_smarty_tpl->getVariable('cfg')->value->curlCheck){?><br /><span class="warn">The PHP installation lacks support for curl.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->curlCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking for sha1():<?php if (!$_smarty_tpl->getVariable('cfg')->value->sha1Check){?><br /><span class="error">The PHP installation lacks support for sha1.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->sha1Check){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking for mysql_connect():<?php if (!$_smarty_tpl->getVariable('cfg')->value->mysqlCheck){?><br /><span class="error">The PHP installation lacks support for MySQL(mysql_connect).</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->mysqlCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking for GD support:<?php if (!$_smarty_tpl->getVariable('cfg')->value->gdCheck){?><br /><span class="warn">The PHP installation lacks support for GD.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->gdCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking for Pear:<?php if (!$_smarty_tpl->getVariable('cfg')->value->pearCheck){?><br /><span class="error">Cannot find PEAR. To install PEAR follow the instructions at <a href="http://pear.php.net/manual/en/installation.php" target="_blank">http://pear.php.net/manual/en/installation.php</a></span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->pearCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking that Smarty cache is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->cacheCheck){?><br /><span class="error">The template cache folder must be writable. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->SMARTY_DIR;?>
/templates_c</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->cacheCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking that movie covers dir is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->movieCoversCheck){?><br /><span class="error">The covers/movies dir must be writable. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/covers/movies</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->movieCoversCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking that anime covers dir is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->animeCoversCheck){?><br /><span class="error">The covers/anime dir must be writable. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/covers/anime</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->animeCoversCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking that music covers dir is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->musicCoversCheck){?><br /><span class="error">The covers/music dir must be writable. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/covers/music</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->musicCoversCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking that config.php is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->configCheck){?><br /><span class="error">The installer cannot write to <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
/config.php. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->WWW_DIR;?>
</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->configCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking that install.lock is writeable:<?php if (!$_smarty_tpl->getVariable('cfg')->value->lockCheck){?><br /><span class="error">The installer cannot write to <?php echo $_smarty_tpl->getVariable('cfg')->value->INSTALL_DIR;?>
/install.lock. A quick solution is to run:<br />chmod 777 <?php echo $_smarty_tpl->getVariable('cfg')->value->INSTALL_DIR;?>
</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->lockCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking for schema.sql file:<?php if (!$_smarty_tpl->getVariable('cfg')->value->schemaCheck){?><br /><span class="error">The schema.sql file is missing, please make sure it is placed in: <?php echo $_smarty_tpl->getVariable('cfg')->value->DB_DIR;?>
/schema.sql</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->schemaCheck){?><span class="success">OK</span><?php }else{ ?><span class="error">Error</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking PHP's version:<?php if (!$_smarty_tpl->getVariable('cfg')->value->phpCheck){?><br /><span class="warn">Your PHP verion is lower than recommened (5.4.0). You may encounter errors if you proceed.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->phpCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking date.timezone:<?php if (!$_smarty_tpl->getVariable('cfg')->value->timezoneCheck){?><br /><span class="warn">You have no default timezone set in php.ini. e.g date.timezone = America/New_York</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->timezoneCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking max_execution_time:<?php if (!$_smarty_tpl->getVariable('cfg')->value->timelimitCheck){?><br /><span class="warn">Your PHP installation's max_execution_time setting is low, please consider increasing it >= 60</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->timelimitCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="">
		<td>Checking PHP's memory_limit:<?php if (!$_smarty_tpl->getVariable('cfg')->value->memlimitCheck){?><br /><span class="warn">Your PHP installation's memory_limit setting is low, please consider increasing it >= 256MB. If it is set to -1, ignore this warning.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->memlimitCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="alt">
		<td>Checking PHP OpenSSL Extension:<?php if (!$_smarty_tpl->getVariable('cfg')->value->opensslCheck){?><br /><span class="warn">Your PHP installation does not have the openssl extension loaded. SSL Usenet connections will fail.</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->opensslCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
	</tr>
	<tr class="">
<?php if (smarty_modifier_truncate($_SERVER['SERVER_SOFTWARE'],8,'')=='lighttpd'){?>
		<td>Instructions for Lighttpd's mod_rewrite:<br /><span class="warn">It is not possible for me to check you have enabled this properly! YOU will need to ensure that "mod_rewrite" is included in server.modules, check lighttpd for this, also ensure the rewrite rules are installed for your host. See misc/urlrewriting/lighttpd.txt for examples.</span></td>
		<td><span class="warn">Warning</span></td>
<?php }else{ ?>
		<td>Checking for Apache's mod_rewrite:<?php if (!$_smarty_tpl->getVariable('cfg')->value->rewriteCheck){?><br /><span class="warn">The Apache module mod_rewrite is not loaded. This module is required, please enable it if you are running Apache</span><?php }?></td>
		<td><?php if ($_smarty_tpl->getVariable('cfg')->value->rewriteCheck){?><span class="success">OK</span><?php }else{ ?><span class="warn">Warning</span><?php }?></td>
<?php }?>		
	</tr>
	
</table>

<div align="center">
<?php if (!$_smarty_tpl->getVariable('cfg')->value->error){?>
	<p>No problems were found and you are ready to install.</p>
	<form action="step2.php"><input type="submit" value="Go to step two: Set up the database" /></form>              
<?php }else{ ?>
	<div class="error">Errors encountered - Newznab will not function correctly unless these problems are solved.</div> 
<?php }?>
</div>
