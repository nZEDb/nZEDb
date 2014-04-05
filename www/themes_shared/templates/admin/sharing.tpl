<h1>{$page->title}</h1>
<div style="width:700px">
	<strong>Sharing of comments does not work with NntpProxy, NntpProxy does not have all the required NNTP commands.</strong>
	<br />
	<div id="message" style="width:677px;">msg</div>
	{if $local}
		<form action="{$SCRIPT_NAME}?action=submit" method="post">
			<fieldset style="width:677px;">
				<legend>Local sharing settings.</legend>
				<table class="input">
					<tr>
						<td style="width: 100px;"><label for="sharing_name">Site Name:</label></td>
						<td>
							<input id="sharing_name" class="long" name="sharing_name" type="text" value="{$local.site_name}" />
							<div>This is your site name, changing this will update other sites running sharing also, it must not contain spaces (user underscores _).</div>
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
						<td style="width: 100px;"><label for="sharing_maxpull">Max Downloads:</label></td>
						<td>
							<input id="sharing_maxpull" class="short" name="sharing_maxpull" type="text" value="{$local.max_pull}" />
							<div>This is how many <strong>headers</strong> to download per run(we do not know how many comments we will download).</div>
						</td>
					</tr>
					<tr>
						<td style="width:100px;"><label for="sharing_enabled">Enabled:</label></td>
						<td>
							<div>
								<strong id="enabled-1">
									{if $local.enabled == "1"}
										<a href="javascript:ajax_sharing_enabled(1, 0)" class="sharing_enabled_active">[DISABLE]</a>
									{else}
										<a href="javascript:ajax_sharing_enabled(1, 1)" class="sharing_enabled_deactive">[ENABLE]</a>
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
										<a href="javascript:ajax_sharing_posting(1, 0)" class="sharing_posting_active">[DISABLE]</a>
									{else}
										<a href="javascript:ajax_sharing_posting(1, 1)" class="sharing_posting_deactive">[ENABLE]</a>
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
										<a href="javascript:ajax_sharing_fetching(1, 0)" class="sharing_fetching_active">[DISABLE]</a>
									{else}
										<a href="javascript:ajax_sharing_fetching(1, 1)" class="sharing_fetching_deactive">[ENABLE]</a>
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
										<a href="javascript:ajax_sharing_auto(1, 0)" class="sharing_auto_active">[DISABLE]</a>
									{else}
										<a href="javascript:ajax_sharing_auto(1, 1)" class="sharing_auto_deactive">[ENABLE]</a>
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
										<a href="javascript:ajax_sharing_hide(1, 0)" class="sharing_hide_active">[DISABLE]</a>
									{else}
										<a href="javascript:ajax_sharing_hide(1, 1)" class="sharing_hide_deactive">[ENABLE]</a>
									{/if}
								</strong>
								This will hide user names from being visible on remote sites.
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
		<table style="margin-top:5px;margin-bottom:5px;width:700px" class="data Sortable highlight">
			<tr>
				<th style="width:22px;text-align:center;">ID</th>
				<th style="width:300px;text-align:center;">Name</th>
				<th style="width:100px;text-align:center;">First seen</th>
				<th style="width:100px;text-align:center;">Last seen</th>
				<th style="width:50px;text-align:center;">Enabled</th>
				<th style="width:70px;text-align:center;">Comments</th>
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
				</tr>
			{/foreach}
		</table>
		{$pager}
	{else}
		<p>No remote sites found in your database.</p>
	{/if}
</div>