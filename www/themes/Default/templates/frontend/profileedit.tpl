<h1>Edit your profile</h1>
{if $error != ''}
	<div class="error"><strong style="color:#B22222">ERROR: {$error}</strong></div>
{/if}
<br/><br/>
<form action="profileedit?action=submit" method="post">
	<fieldset>
	<nZEDblegend>User Details</nZEDblegend>
	<table class="input">
		<tr><th width="100">Username:</th><td>{$user.username|escape:"htmlall"}</td></tr>
		<tr><th>First Name:</th><td><input id="firstname" class="form-control" name="firstname" type="text" value="{$user.firstname}"></td></tr>
		<tr><th>Last Name:</th><td><input id="lastname" class="form-control" name="lastname" type="text" value="{$user.lastname}"></td></tr>
		<tr><th>Email:</th><td><input id="email" class="long" name="email" type="text" value="{$user.email|escape:"htmlall"}"></input></td></tr>
		<tr><th>Password:</th>
			<td>
				<input autocomplete="off" id="password" name="password" type="password" value=""></input>
				<div class="hint">Only enter a password if you want to change it.</div>
			</td>
		</tr>
		<tr><th>Confirm Password:</th><td><input autocomplete="off" id="confirmpassword" name="confirmpassword" type="password" value=""></input>
		</td></tr>
		<tr><th>Site Api/Rss Key:</th><td>{$user.rsstoken}<br/><a class="confirm_action" href="?action=newapikey">Generate</a></td></tr>
	</table>
	</fieldset>
	<fieldset>
	<nZEDblegend>Site Preferences</nZEDblegend>
	<table class="input">
		<tr>
			<th>Site theme:</th>
			<td>
				{html_options id="style" name='style' values=$themelist output=$themelist selected=$user.style}
				<span class="help-block">Change the site theme, None will use the theme the administrator set.</span>
			</td>
		</tr>
		<tr>
			<th>View Movie Page:</th>
			<td>
				<input id="movieview" name="movieview" value="1" type="checkbox" {if $user.movieview=="1"}checked="checked"{/if}></input>
				<div class="hint">Browse movie covers. Only shows movies with known IMDB info.</div>
			</td>
		</tr>
				<tr>
			<th>View XXX Page:</th>
			<td>
				<input id="xxxview"	name="xxxview" value="1" type="checkbox" {if $user.xxxview=="1"}checked="checked"{/if}>
				<span class="help-block">Browse XXX covers. Only shows xxx releases with known lookup info.</span>
			</td>
		</tr>
		<tr><th>View Music Page:</th>
			<td>
				<input id="musicview" name="musicview" value="1" type="checkbox" {if $user.musicview=="1"}checked="checked"{/if}></input>
				<div class="hint">Browse music covers. Only shows music with known lookup info.</div>
			</td>
		</tr>
		<tr><th>View Console Page:</th>
			<td>
				<input id="consoleview" name="consoleview" value="1" type="checkbox" {if $user.consoleview=="1"}checked="checked"{/if}></input>
				<div class="hint">Browse console covers. Only shows games with known lookup info.</div>
			</td>
		</tr>
		<tr>
			<th>View Game Page:</th>
			<td>
				<input id="gameview" name="gameview" value="1" type="checkbox" {if $user.gameview=="1"}checked="checked"{/if}>
				<span class="help-block">Browse game covers. Only shows games with known lookup info.</span>
			</td>
		</tr>
		<tr><th>View Book Page:</th>
			<td>
				<input id="bookview" name="bookview" value="1" type="checkbox" {if $user.bookview=="1"}checked="checked"{/if}></input>
				<div class="hint">Browse book covers. Only shows books with known lookup info.</div>
			</td>
		</tr>
		<tr><th>Excluded Categories:</th>
			<td>
				{html_options style="height:105px;" multiple=multiple name="exccat[]" options=$catlist selected=$userexccat}
				<div class="hint">Use Ctrl and click to exclude multiple categories.</div>
			</td>
		</tr>
	</table>
	</fieldset>
	{if $page->settings->getSetting('sabintegrationtype') != 1}
		<fieldset>
			<nZEDblegend>Queue Type</nZEDblegend>
			<table class="table table-condensed input">
				<colgroup>
					<col style="width: 150px;">
				</colgroup>
				<tr>
					<th>Queue type:</th>
					<td>
						{html_options id="queuetypeids" name='queuetypeids' values=$queuetypeids output=$queuetypes selected=$user.queuetype}
						<span class="help-block">Pick the type of queue you wish to use, once you save your profile, the page will reload, the box will appear and you can fill out the details.</span>
					</td>
				</tr>
			</table>
		</fieldset>
	{/if}
	{if $user.queuetype == 2 && ($page->settings->getSetting('sabintegrationtype') == 0 || $page->settings->getSetting('sabintegrationtype') == 2)}
		<fieldset>
			<nZEDblegend>NZBGet Integration</nZEDblegend>
			<table class="table table-condensed input">
				<colgroup>
					<col style="width: 150px;">
				</colgroup>
				<tr>
					<th><label for="nzbgeturl">NZBGet Url:</label></th>
					<td>
						<input id="nzbgeturl" class="form-control" name="nzbgeturl" type="text" value="{$user.nzbgeturl}" />
						<span class="help-block">The url of the NZBGet installation, for example: http://127.0.0.1:6789/</span>
					</td>
				</tr>

				<tr>
					<th><label for="nzbgetusername">NZBGet Username:</label></th>
					<td>
						<input id="nzbgetusername" class="form-control" name="nzbgetusername" type="text" value="{$user.nzbgetusername}" />
						<span class="help-block">The user name for the NZBGet installation.</span>
					</td>
				</tr>

				<tr>
					<th><label for="nzbgetpassword">NZBGet Password:</label></th>
					<td>
						<input id="nzbgetpassword" class="form-control" name="nzbgetpassword" type="text" value="{$user.nzbgetpassword}" />
						<span class="help-block">The password for the NZBGet installation.</span>
					</td>
				</tr>

			</table>
		</fieldset>
	{/if}
	{if $user.queuetype == 1 && $page->settings->getSetting('sabintegrationtype') == 2}
	<fieldset>
	<nZEDblegend>SABnzbd Integration</nZEDblegend>
	<table class="input">
		<tr>
			<th width="100"><label for="saburl">SABnzbd Url:</label></th>
			<td>
				<input id="saburl" class="long" name="saburl" type="text" value="{$saburl_selected}" />
				<div class="hint">The url of the SAB installation, for example: http://localhost:8080/sabnzbd/</div>
			</td>
		</tr>

		<tr>
			<th><label for="sabapikey">SABnzbd Api Key:</label></th>
			<td>
				<input id="sabapikey" class="long" name="sabapikey" type="text" value="{$sabapikey_selected}" />
				<div class="hint">The api key of the SAB installation. Can be the full api key or the nzb api key (as of SAB 0.6)</div>
			</td>
		</tr>

		<tr>
			<th><label for="sabapikeytype">Api Key Type:</label></th>
			<td>
				{html_radios id="sabapikeytype" name='sabapikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$sabapikeytype_selected separator='<br />'}
				<div class="hint">Select the type of api key you entered in the above setting. Using your full SAB api key will allow you access to the SAB queue from within this site.</div>
			</td>
		</tr>

		<tr>
			<th><label for="sabpriority">Priority Level:</label></th>
			<td>
				{html_options id="sabpriority" name='sabpriority' values=$sabpriority_ids output=$sabpriority_names selected=$sabpriority_selected}
				<div class="hint">Set the priority level for NZBs that are added to your queue</div>
			</td>
		</tr>
		<tr>
			<th><label for="sabsetting">Setting Storage:</label></th>
			<td>
				{html_radios id="sabsetting" name='sabsetting' values=$sabsetting_ids output=$sabsetting_names selected=$sabsetting_selected separator='&nbsp;&nbsp;'}{if $sabsetting_selected == 2}&nbsp;&nbsp;[<a class="confirm_action" href="?action=clearcookies">Clear Cookies</a>]{/if}
				<div class="hint">Where to store the SAB setting.<br />&bull; <b>Cookie</b> will store the setting in your browsers coookies and will only work when using your current browser.<br/>&bull; <b>Site</b> will store the setting in your user account enabling it to work no matter where you are logged in from.<br /><span class="warning"><b>Please Note:</b></span> You should only store your full SAB api key with sites you trust.</div>
			</td>
		</tr>
	</table>
	</fieldset>
	{/if}
	<fieldset class="notop">
	<nZEDblegend>CouchPotato Integration</nZEDblegend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="cp_api">CouchPotato API key:</label></td>
			<td>
				<input id="cp_api" class="long" name="cp_api" type="text" value="{$cp_api_selected}" />
				<div class="hint">The CouchPotato api key. Used for 'Add To CouchPotato'.</div>
			</td>
		</tr>

		<tr>
			<td style="width:180px;"><label for="cp_url">CouchPotato Url:</label></td>
			<td>
				<input id="cp_url" class="long" name="cp_url" type="text" value="{$cp_url_selected}" />
				<div class="hint">The CouchPotato url. Used for 'Add To CouchPotato', for example: http://192.168.10.10:5050</div>
			</td>
		</tr>
	</table>
	</fieldset>
	<input type="submit" value="Save Profile" />
</form>
