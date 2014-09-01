{if $page->isSuccess()}
	<div style="text-align:center">
		<p>The openssl setup looks correct, you may continue to the next step.</p>
		<form action="step4.php"><input type="submit" value="Step four: Setup news server connection" /></form>
	</div>
{else}
	<form action="?" method="post">
		<p>An openssl CA bundle file is recommended to verify the authenticity of remote certificates when connecting to various servers using TLS/SSL.<br />
			If you do not have one, you can download one <a href="http://curl.haxx.se/docs/caextract.html">here</a>.<br />
			You can place the file in the /etc/ssl/certs/ folder if you are on linux.<br />
			The file must be readable by both your web user (www-data by default) and your CLI user running scripts.<br />
		</p>
		<table border="0" style="width:100%;margin-top:10px;" class="data highlight">
			<tr class="">
				<td><label for="cafile">(Optional) Full path to the ca bundle cert file:</label></td>
				<td>
					<input type="text" name="cafile" id="cafile" value="{$cfg->nZEDb_SSL_CAFILE}" />
					<div class="hint">Location of Certificate Authority file on local filesystem which will be used if the Verify Peer option is enabled to authenticate the identity of the remote peer.<br />
						ex: /etc/ssl/certs/cacert.pem</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="capath">(Optional) Folder path where cert files are stored:</label></td>
				<td>
					<input type="text" name="capath" id="capath" value="{$cfg->nZEDb_SSL_CAPATH}" />
					<div class="hint">If the ca bundle cert file is not specified or if the certificate is not found there, you can specify a directory here which will be searched for a suitable certificate.<br />
						ex: /etc/ssl/certs/</div>
				</td>
			</tr>
			<tr class="">
				<td><label for="verifypeer">Verify peer:</label></td>
				<td>
					<input type="checkbox" name="verifypeer" id="verifypeer" value="1" {if $cfg->nZEDb_SSL_VERIFY_PEER=="true"}checked="checked"{/if} />
					<div class="hint">Disabling this will disable TLS/SSL remote certificate verification which is not recommended.<br />
						Enabling this requires the ca bundle cert file path to be set.</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="verifyhost">Verify host:</label></td>
				<td>
					<input type="checkbox" name="verifyhost" id="verifyhost" value="1" {if $cfg->nZEDb_SSL_VERIFY_HOST=="true"}checked="checked"{/if} />
					<div class="hint">This makes sure the host is who they say they are.<br />
						See <a href="http://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html">this</a> link for detailed info.<br />
						You might need to disable this as some providers are using the wrong CN in their certs (probably because they are cheap and re-using a cert and rely on people disabling host verification or lack knowledge).<br />
						Note that disabling this is a security risk and you should consider getting a usenet provider that has a proper certificate.</div>
				</td>
			</tr>
			<tr class="">
				<td><label for="allowselfsigned">Allow self signed certificates:</label></td>
				<td>
					<input type="checkbox" name="allowselfsigned" id="allowselfsigned" value="1" {if $cfg->nZEDb_SSL_ALLOW_SELF_SIGNED=="true"}checked="checked"{/if} />
					<div class="hint">Enabling this will not verify self-signed openssl certificates.<br />
						Note that you will require non self-signed certificates for sabnzbd/nzbget/etc if you disable this.</div>
				</td>
			</tr>
		</table>
		<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
				<div>
					The following error was encountered:<br />
					<span class="error">&bull; {$cfg->error}</span><br /><br /><br />
				</div>
			{/if}
			<input type="submit" value="Verify openssl settings" />
		</div>
	</form>
{/if}