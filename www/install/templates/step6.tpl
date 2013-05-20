{if $page->isSuccess()}
	<div align="center">
		<h1>Install Complete!</h1>
		<br/>
		<p>Continue to the <a href="../admin/">admin home page</a> to give your site a name and learn how to start indexing usenet.</p>
		<br/><br/>
		<p><b >Note:</b> It is a good idea to remove the www/install directory after setup</p>
	</div>   
{else}

<p>You must set the NZB file path. This is the location where the NZB files are stored:</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="alt">
			<td><label for="nzbpath">Location:</label></td>
			<td><input type="text" name="nzbpath" value="{$cfg->NZB_PATH}" size="70" /></td>
		</tr>
	</table>

	<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
			<div>
				The following error was encountered:<br />
				{if !$cfg->nzbPathCheck}<br /><span class="error">The installer cannot write to {$cfg->NZB_PATH}. A quick solution is to run:<br />chmod -R 777 {$cfg->NZB_PATH}</span><br />{/if}
				<br />
			</div>
			{/if}
			<input type="submit" value="Set NZB File Path" />
	</div>

</form>

{/if}