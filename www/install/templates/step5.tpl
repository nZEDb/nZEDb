<div style="text-align:center">
{if !$cfg->error}
	<p>The configuration file has been saved, you may continue to the next step.</p>
	<form action="step6.php"><input type="submit" value="Step six: Setup admin user" /></form>
{else}
	{if !$cfg->saveConfigCheck}
		<h3><span class="error">Error saving {$cfg->nZEDb_WWW}/config.php.</span></h3>
		<p>Please save the config.php yourself by creating:<br /><b>{$cfg->nZEDb_WWW}/config.php</b><br />and setting its contents to the following:</p>
		<p><textarea cols="100" rows="60">{$cfg->COMPILED_CONFIG}</textarea></p>
	{/if}
	{if !$cfg->saveLockCheck}
		<br />
		<h3><span class="error">Error saving {$cfg->INSTALL_DIR}/install.lock</span></h3>
		<p>Please save the install.lock yourself by creating:<br /><b>{$cfg->INSTALL_DIR}/install.lock</b></p>
	{/if}
{/if}
</div>
