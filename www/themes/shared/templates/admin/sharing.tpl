<h1>{$page->title}</h1>
<div style="width:700px">
	<strong>
		Note: If you are running nntpproxy you will not be able to upload comments, turn off the post option if you are using it.<br />
		If you turn on or off the Alternate NNTP provider you will need to click the reset button to reset sharing settings.
	</strong>
	<br />
	<div id="message" style="width:717px;">msg</div>
	{if $local}
		<form action="{$SCRIPT_NAME}" method="post">
			<fieldset style="width:717px;">
				<legend>Local sharing settings.</legend>
				<table class="input">
					<tr>
						<td style="width: 100px;"><label for="sharing_name">Site Name:</label></td>
						<td>
							<input id="sharing_name" class="long" name="sharing_name" type="text" value="{$local.site_name}" />
							<div>This is your site name, changing this will update other sites running sharing also, it must not contain spaces (use underscores _).</div>
						</td>
					</tr>
					<tr>
						<td style="width: 100px;"><label for="sharing_maxpush">Max Uploads:</label></td>
						<td>
							<input id="sharing_maxpush" class="short" name="sharing_maxpush" type="text" value="{$local.max_push}" />
							<div>This is how many comments to upload per run (the more you increase this, the longer it takes).</div>
						</td>
					</tr>
					<tr>
						<td style="width: 100px;"><label for="sharing_maxdownload">Max Downloads:</label></td>
						<td>
							<input id="sharing_maxdownload" class="short" name="sharing_maxdownload" type="text" value="{$local.max_download}" />
							<div>This is how many comments to download per run.</div>
						</td>
					</tr>
					<tr>
						<td style="width: 100px;"><label for="sharing_maxpull">Max Headers:</label></td>
						<td>
							<input id="sharing_maxpull" class="short" name="sharing_maxpull" type="text" value="{$local.max_pull}" />
							<div>This is how many headers to download and look through for comments per run.</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_enabled">Enabled:</label></td>
						<td>
							<div>
								<strong id="enabled-1">
									{if $local.enabled == "1"}
										<a title="Click this to disable sharing." href="javascript:ajax_sharing_enabled(1, 0)" class="sharing_enabled_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable sharing." href="javascript:ajax_sharing_enabled(1, 1)" class="sharing_enabled_deactive">[ENABLE]</a>
									{/if}
								</strong>
								Is the sharing/retrieving enabled? This overrides posting/fetching.
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_posting">Posting:</label></td>
						<td>
							<div>
								<strong id="posting-1">
									{if $local.posting == "1"}
										<a title="Click this to disable posting." href="javascript:ajax_sharing_posting(1, 0)" class="sharing_posting_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable posting." href="javascript:ajax_sharing_posting(1, 1)" class="sharing_posting_deactive">[ENABLE]</a>
									{/if}
								</strong>
								If you turn this on, this will post your comments to usenet. <br />
								<strong>This requires posting rights to usenet!</strong>
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_fetching">Fetching:</label></td>
						<td>
							<div>
								<strong id="fetching-1">
									{if $local.fetching == "1"}
										<a title="Click this to disable fetching." href="javascript:ajax_sharing_fetching(1, 0)" class="sharing_fetching_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable fetching." href="javascript:ajax_sharing_fetching(1, 1)" class="sharing_fetching_deactive">[ENABLE]</a>
									{/if}
								</strong>
								If you turn this on, this will download comments from usenet.
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_auto">Auto-Enable:</label></td>
						<td>
							<div>
								<strong id="auto-1">
									{if $local.auto_enable == "1"}
										<a title="Click this to disable auto-enable." href="javascript:ajax_sharing_auto(1, 0)" class="sharing_auto_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable auto-enable." href="javascript:ajax_sharing_auto(1, 1)" class="sharing_auto_deactive">[ENABLE]</a>
									{/if}
								</strong>
								This will auto-enable new sites as we see them.
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_hide">Hide Users:</label></td>
						<td>
							<div>
								<strong id="hide-1">
									{if $local.hide_users == "1"}
										<a title="Click this to disable hiding users." href="javascript:ajax_sharing_hide(1, 0)" class="sharing_hide_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable hiding users." href="javascript:ajax_sharing_hide(1, 1)" class="sharing_hide_deactive">[ENABLE]</a>
									{/if}
								</strong>
								This will hide user names from being visible on remote sites.
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_startposition">Backfill:</label></td>
						<td>
							<div>
								<strong id="startposition-1">
									{if $local.start_position == "1"}
										<a title="Click this to disable backfill." href="javascript:ajax_sharing_startposition(1, 0)" class="sharing_startposition_active">[DISABLE]</a>
									{else}
										<a title="Click this to enable backfill." href="javascript:ajax_sharing_startposition(1, 1)" class="sharing_startposition_deactive">[ENABLE]</a>
									{/if}
								</strong>
								When pulling the first time, or after resetting, start from the beginning of the group (takes more time).<br />
								<strong>With the backfill setting it will take a few runs before you find anything the first time because the group has articles other than comments in it.</strong>
							</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_reset">Reset settings:</label></td>
						<td>
							<div>
								<strong id="reset-1">
									<a href="javascript:ajax_sharing_reset(1)" class="sharing_reset" onclick="return confirm('Are you sure? You will lose all your settings!');">[RESET]</a>
								</strong>
								<strong>This will reset your sharing settings (if you need to change Usenet Provider for example).</strong>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
			<input type="submit" value="Save Settings" />
		</form>
		<br />
	{else}
		<p>You have not run Sharing yet, until you run it this page will contain no settings.</p>
	{/if}

	{if $sites}
		<div style="margin-bottom:5px;">These are the remote websites we have seen so far:</div>
		{$pager}
		<div style="float:right;">
			<a href="javascript:ajax_sharing_toggle_all(1);" onclick="setTimeout('history.go(0);',700);" class="sharing_toggle_all">[Enable All]</a>
			<a href="javascript:ajax_sharing_toggle_all(0);" onclick="setTimeout('history.go(0);',700);" class="sharing_toggle_all">[Disable All]</a>
		</div>
		<table style="margin-top:5px;margin-bottom:5px;width:733px" class="data Sortable highlight">
			<tr>
				<th style="width:22px;text-align:center;">ID</th>
				<th style="width:300px;text-align:center;">Name</th>
				<th style="width:100px;text-align:center;">First seen</th>
				<th style="width:100px;text-align:center;">Last seen</th>
				<th style="width:50px;text-align:center;">Status</th>
				<th style="width:70px;text-align:center;">Comments</th>
				<th style="width:40px;text-align:center;"></th>
			</tr>
			{foreach from=$sites item=site}
				<tr id="row-{$site.id}" class="{cycle values=",alt"}">
					<td style="text-align:center;">{$site.id}</td>
					<td style="text-align:center;">{$site.site_name}</td>
					<td style="text-align:center;">{$site.first_time|timeago}</td>
					<td style="text-align:center;">{$site.last_time|timeago}</td>
					<td style="text-align:center;" id="site-{$site.id}">
						{if $site.enabled=="1"}
							<a href="javascript:ajax_sharing_site_status({$site.id}, 0)" class="sharing_site_active">Disable</a>
						{else}
							<a href="javascript:ajax_sharing_site_status({$site.id}, 1)" class="sharing_site_deactive">Enable</a>
						{/if}
					</td>
					<td style="text-align:center;">{$site.comments}</td>
					<td style="text-align:center;">
						<a href="javascript:ajax_sharing_site_purge({$site.id})" class="sharing_site_purge" onclick="return confirm('Are you sure? This will delete all comments from this site!');">Purge</a>
					</td>
				</tr>
			{/foreach}
		</table>
		{$pager}
	{else}
		<p>No remote sites found in your database.</p>
	{/if}
</div>