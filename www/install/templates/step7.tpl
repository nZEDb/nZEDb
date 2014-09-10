{if $page->isSuccess()}
	<div style="text-align:center">
		<h1>Install Complete!</h1>
		<br/>
		<p>Continue to the <a href="../admin/">admin home page</a> to give your site a name and learn how to start indexing usenet.</p>
	</div>
{else}

	<p>You must accept or change these file paths. This is the location where your covers, NZB, and temporary files are stored:</p>
	<form action="?" method="post">
		<table border="0" style="width:100%;margin-top:10px;" class="data highlight">
			<tr class="alt">
			<td><label for="coverspath">Place to save Covers\Posters etc.:</label></td>
			<td><input type="text" name="coverspath" value="{$cfg->COVERS_PATH}" size="70" /></td>
		</tr>
			<tr class="alt">
				<td><label for="nzbpath">Place to create NZB files:</label></td>
				<td><input type="text" name="nzbpath" value="{$cfg->NZB_PATH}" size="70" /></td>
			</tr>
			<tr class="alt">
				<td><label for="tmpunrarpath">Place for unRARing/temp work:</label></td>
				<td><input type="text" name="tmpunrarpath" value="{$cfg->UNRAR_PATH}" size="70" /></td>
			</tr>
		</table>

		<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
				<div>
					The following error was encountered:<br />
					<hr>
					{if !$cfg->nzbPathCheck}
						<span class="error">
						The installer cannot write to {$cfg->NZB_PATH}.<br />
						A quick solution is to run:<br />
						chmod -R 777 {$cfg->NZB_PATH}
							{$fixString} {$cfg->NZB_PATH}
					</span><br />
						<hr>
					{/if}
					{if !$cfg->coverPathCheck}<br /><span class="error">The installer cannot write to {$cfg->COVERS_PATH}. A quick solution is to run:<br />chmod -R 777 {$cfg->COVERS_PATH}</span><br />{/if}
					{if !$cfg->unrarPathCheck}
						<span class="error">
						The installer cannot write to {$cfg->UNRAR_PATH}.<br />
						A quick solution is to run:<br />
						chmod -R 777 {$cfg->UNRAR_PATH}
							{$fixString} {$cfg->UNRAR_PATH}
					</span><br />
						<hr>
					{/if}
					<br />
				</div>
			{/if}
			<input type="submit" value="Set file paths" />
		</div>

	</form>

{/if}
