<ul class="breadcrumb">
	<li><h5><a href="{$smarty.const.WWW_TOP}/profile">Profile</a> <span class="divider">/</span></li>
	<li class="active">Edit profile</h5></li>
</ul>

{if $error != ''}
<div class="alert alert-error">
	<strong>Error!</strong>
	{$error}
</div>
{/if}

<form class="form-horizontal" action="profileedit?action=submit" method="post">
	<fieldset>
		<div class="control-group">
			<label class="control-label">Username</label>
			<div class="controls">
				{$user.username|escape:"htmlall"}
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="email">Email</label>
			<div class="controls">
				<input class="input input-xxlarge" id="email" class="long" name="email" type="text" value="{$user.email|escape:"htmlall"}">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="password">Password</label>
			<div class="controls">
				<input class="input input-xxlarge" autocomplete="off" id="password" name="password" type="password" value="" />
				<span class="help-block">Only enter your password if you want to change it.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="confirmpassword">Confirm Password</label>
			<div class="controls">
				<input class="input input-xxlarge" autocomplete="off" id="confirmpassword" name="confirmpassword" type="password" value="" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Site Api/Rss Key</label>
			<div class="controls">
				{$user.rsstoken}<br/><a class="confirm_action" href="?action=newapikey">Generate</a>
			</div>
		</div>
	</fieldset>

	<fieldset>
		<legend>Site Preferences</legend>

		<div class="control-group">
			<label class="control-label">Change site theme</label>
			<div class="controls">
				{if $page->settings->getSetting('userselstyle') == 1}
					{html_options id="style" name='style' values=$themelist output=$themelist selected=$user.style}
				{/if}
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="movieview">View Movie Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="movieview" name="movieview" value="1" type="checkbox" {if $user.movieview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse movie covers. Only shows movies with known IMDB info.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="musicview">View Music Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="musicview" name="musicview" value="1" type="checkbox" {if $user.musicview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse music covers. Only shows music with known lookup info.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="consoleview">View Console Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="consoleview" name="consoleview" value="1" type="checkbox" {if $user.consoleview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse console covers. Only shows games with known lookup info.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="gameview">View Games Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="gameview" name="gameview" value="1" type="checkbox" {if $user.gameview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse games covers. Only shows games with known lookup info.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="bookview">View Book Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="bookview" name="bookview" value="1" type="checkbox" {if $user.bookview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse book covers. Only shows books with known lookup info.</span>
			</div>
		</div>


		<div class="control-group">
			<label class="control-label" for="xxxview">View XXX Page</label>
			<div class="controls">
				<input class="input input-xxlarge" id="xxxview" name="xxxview" value="1" type="checkbox" {if $user.xxxview=="1"}checked="checked"{/if} />
				<span class="help-block">Browse XXX covers. Only shows XXX releases with known lookup info.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="exclu">Excluded Categories</label>
			<div class="controls">
				{html_options id="exclu" class="input input-xxlarge" style="height:305px;" multiple=multiple name="exccat[]" options=$catlist selected=$userexccat}
				<span class="help-block">Use Ctrl and click to exclude multiple categories.</span>
			</div>
		</div>
	</fieldset>

	{if $page->settings->getSetting('sabintegrationtype') > 0}

	{if $page->settings->getSetting('sabintegrationtype') != 1}
		<legend>Queue type (NZBget / Sabnzbd)</legend>

		<div class="control-group">
			<label class="control-label">Queue type</label>
				<div class="controls">
					{html_options id="queuetypeids" name='queuetypeids' values=$queuetypeids output=$queuetypes selected=$user.queuetype}
				</div>
			</div>
	{/if}
	<fieldset>
		<legend>SABnzbd Integration</legend>

		<div class="control-group">
			<label class="control-label" for="saburl">SABnzbd Url</label>
			<div class="controls">
				<input class="input input-xxlarge" id="saburl" class="long" name="saburl" type="text" value="{$saburl_selected}" />
				<span class="help-block">The url of the SAB installation, for example: <code>http://localhost:8080/sabnzbd/</code></span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="sabapikey">SABnzbd Api Key</label>
			<div class="controls">
				<input class="input input-xxlarge" id="sabapikey" class="long" name="sabapikey" type="text" value="{$sabapikey_selected}" />
				<span class="help-block">The api key of the SAB installation. Can be the full api key or the nzb api key (as of SAB 0.6)</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="sabapikeytype">Api Key Type</label>
			<div class="controls">
				{html_radios id="sabapikeytype" name='sabapikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$sabapikeytype_selected separator=''}
				<span class="help-block">Select the type of api key you entered in the above setting. Using your full SAB api key will allow you access to the SAB queue from within this site.</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="sabpriority">Priority Level</label>
			<div class="controls">
				{html_options class="input input-xxlarge" id="sabpriority" name='sabpriority' values=$sabpriority_ids output=$sabpriority_names selected=$sabpriority_selected}
				<span class="help-block">Set the priority level for NZBs that are added to your queue</span>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="sabsetting">Setting Storage</label>
			<div class="controls">
				{html_radios id="sabsetting" name='sabsetting' values=$sabsetting_ids output=$sabsetting_names selected=$sabsetting_selected separator=''} {if $sabsetting_selected == 2} [<a class="confirm_action" href="?action=clearcookies">Clear Cookies</a>]{/if}
				<span class="help-block">Where to store the SAB setting.<br />&bull; <b>Cookie</b> will store the setting in your browsers coookies and will only work when using your current browser.<br/>&bull; <b>Site</b> will store the setting in your user account enabling it to work no matter where you are logged in from.<br /><span style="color:#B94A48;"><b>Please Note:</b></span> You should only store your full SAB api key with sites you trust.</span>
			</div>
		</div>

	</fieldset>

	<fieldset>
    <legend>NZBGet Integration</legend>

	<div class="control-group">
            <label class="control-label" for="nzbgeturl">NZBGet Url</label>
			<div class="controls">
                <input id="nzbgeturl" class="input input-xxlarge" name="nzbgeturl" type="text" value="{$user.nzbgeturl}" />
                <div class="hint">The url of the NZBGet installation, for example: http://localhost:6789/</div>
            </div>
	</div>

	<div class="control-group">
            <label class="control-label" for="nzbgetusername">NZBGet Username</label>
			<div class="controls">
                <input id="nzbgetusername" class="input input-xxlarge" name="nzbgetusername" type="text" value="{$user.nzbgetusername}" />
                <div class="hint">The NZBGet ControlUsername e.g. nzbget</div>
			</div>
	</div>

	<div class="control-group">
            <label class="control-label" for="nzbgetpassword">NABGet Password</label>
			<div class="controls">
                <input id="nzbgetpassword" class="input input-xxlarge" name="nzbgetpassword" type="text" value="{$user.nzbgetpassword}" />
                <div class="hint">The NZBGet ControlPassword e.g. tegbzn6789</div>
			</div>
	</div>
	</fieldset>
	{/if}
	<fieldset>
		<legend>CouchPotato Integration</legend>
		<div class="control-group">
		<label class="control-label" for="cp_api">URL</label>
		<div class="controls">
			<input id="cp_url"
			   placeholder="Couchpotato URL"
			   class="form-control"
			   name="cp_url" type="text"
			   value="{$cp_url_selected}"/>
		</div>
		<label class="control-label" for="cp_api">API key</label>
		<div class="controls">
			<input id="cp_api"
				   placeholder="Couchpotato API key"
				   class="form-control"
				   name="cp_api" type="text"
				   value="{$cp_api_selected}"/>
		</div>
		</div
	</fieldset>
	<div class="control-group">
		<label class="control-label" ></label>
		<div class="controls">
			<input class="btn btn-success" type="submit" value="Save Profile" />
		</div>
	</div>
	</br>
</form>