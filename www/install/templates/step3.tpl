{if $page->isSuccess()}
	<div style="align=center">
		<p style="text-align:center">The openssl setup looks correct, you may continue to the next step.</p>
		<form action="step4.php">
			<div style="padding-top:20px; text-align:center;">
				<input type="submit" value="Step four: Setup news server connection" />
			</div>
		</form>
	</div>
{else}
	<form action="?" method="post">
		<p>An openssl CA bundle file is recommended to verify the authenticity of remote certificates when connecting to various servers using TLS/SSL.</p>
		<ul>
			<li>If you do not have a CA bundle, you can download one <a href="http://curl.haxx.se/docs/caextract.html">here</a>.</li>
			<li>You can place the CA bundle in the /etc/ssl/certs/ folder if you are on linux.</li>
			<li>The file must be readable by both your web user and your CLI user running scripts.</li>
		</ul>

		<table border="0" style="width:100%;margin-top:10px;" class="data highlight">
			<tr class="">
				<td><label for="cafile">CA Bundle Path(Optional):</label></td>
				<td>
					<input type="text" name="cafile" id="cafile" value="{$cfg->nZEDb_SSL_CAFILE}" />
					<div>
						Location of Certificate Authority file on local filesystem which will be used if the Verify Peer option is enabled to authenticate the identity of the remote peer.
						<br />Example: /etc/ssl/certs/cacert.pem
					</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="capath">Certificate Folder(Optional):</label></td>
				<td>
					<input type="text" name="capath" id="capath" value="{$cfg->nZEDb_SSL_CAPATH}" />
					<div>
						If the ca bundle cert file is not specified or if the certificate is not found there, you can specify a directory here which will be searched for a suitable certificate.
						<br />Example: /etc/ssl/certs/</div>
				</td>
			</tr>
			<tr class="">
				<td><label for="verifypeer">Verify peer:</label></td>
				<td>
					<input type="checkbox" name="verifypeer" id="verifypeer" value="1" {if $cfg->nZEDb_SSL_VERIFY_PEER=="true"}checked="checked"{/if} />
					<div>
						Enabling this requires the ca bundle cert file path to be set.<br />
						Disabling this will disable TLS/SSL remote certificate verification which is not recommended.
					</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="verifyhost">Verify host:</label></td>
				<td>
					<input type="checkbox" name="verifyhost" id="verifyhost" value="1" {if $cfg->nZEDb_SSL_VERIFY_HOST=="true"}checked="checked"{/if} />
					<div>
						This makes sure the host is who they say they are.<br />
						See <a href="http://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html">this</a> link for detailed info.<br />
						You might need to disable this as some providers are using the wrong CN in their certs (probably because they are cheap and re-using a cert and rely on people disabling host verification or lack knowledge).<br />
						Note that disabling this is a security risk and you should consider getting a usenet provider that has a proper certificate.
					</div>
				</td>
			</tr>
			<tr class="">
				<td><label for="allowselfsigned">Allow self signed certificates:</label></td>
				<td>
					<input type="checkbox" name="allowselfsigned" id="allowselfsigned" value="1" {if $cfg->nZEDb_SSL_ALLOW_SELF_SIGNED=="true"}checked="checked"{/if} />
					<div>
						Enabling this will not verify self-signed openssl certificates.<br />
						Note that you will require non self-signed certificates for sabnzbd/nzbget/etc if you disable this.
					</div>
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