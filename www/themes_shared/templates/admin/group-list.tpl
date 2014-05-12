<div id="group_list">
	<h1>{$page->title}</h1>
		<p>
			Below is a list of all usenet groups available to be indexed. Click 'Activate' to start indexing a group. Backfill works independently of active.
		</p>
	{if $grouplist}
		<div style="position:relative;margin-bottom:5px;">
			<form name="groupsearch" action="" style="margin-bottom:5px;">
				<label for="groupname">Group</label>
				<input id="groupname" type="text" name="groupname" value="{$groupname}" size="15" />
				&nbsp;&nbsp;
				<input type="submit" value="Go" />
			</form>
			<div style="position:absolute;">
				{$pager}
			</div>
			<div style="text-align:center;">
				<a title="Reset all groups" href="javascript:ajax_all_reset()" class="all_reset" onclick="return confirm('Are you sure? This will reset all groups, deleting all collections/binaries/parts (does not delete releases).');" >Reset all</a> |
				<a title="Delete all releases, collections/binaries/parts from all groups" href="javascript:ajax_all_purge()" class="all_purge" onclick="return confirm('Are you sure? This will delete all releases, collections/binaries/parts.');">Purge all</a><br />
				<a title="List all groups Activated for Update_Binaries" href="{$smarty.const.WWW_TOP}/group-list-active.php">Active Groups</a> |
				<a title="List all groups NOT Activated for Update_Binaries" href="{$smarty.const.WWW_TOP}/group-list-inactive.php">Inactive Groups</a> |
				<a title="List all groups" href="{$smarty.const.WWW_TOP}/group-list.php">All Groups</a>
			</div>
		</div>
		<div id="message">msg</div>
		<table style="width:100%;" class="data highlight Sortable">
			<tr>
				<th>group</th>
				<th>First Post</th>
				<th>Last Post</th>
				<th>last updated</th>
				<th>active</th>
				<th>backfill</th>
				<th>releases</th>
				<th>Min Files</th>
				<th>Min Size</th>
				<th>Backfill Days</th>
				<th>options</th>
			</tr>
			{foreach from=$grouplist item=group}
				<tr id="grouprow-{$group.id}" class="{cycle values=",alt"}">
					<td>
						<a href="{$smarty.const.WWW_TOP}/group-edit.php?id={$group.id}">{$group.name|replace:"alt.binaries":"a.b"}</a>
						<div class="hint">{$group.description}</div>
					</td>
					<td class="less">{$group.first_record_postdate}<br />{$group.first_record_postdate|timeago}</td>
					<td class="less">{$group.last_record_postdate}<br />{$group.last_record_postdate|timeago}</td>
					<td class="less">{$group.last_updated|timeago} ago</td>
					<td class="less" id="group-{$group.id}">{if $group.active=="1"}<a href="javascript:ajax_group_status({$group.id}, 0)" class="group_active">Deactivate</a>{else}<a href="javascript:ajax_group_status({$group.id}, 1)" class="group_deactive">Activate</a>{/if}</td>
					<td class="less" id="backfill-{$group.id}">{if $group.backfill=="1"}<a href="javascript:ajax_backfill_status({$group.id}, 0)" class="backfill_active">Deactivate</a>{else}<a href="javascript:ajax_backfill_status({$group.id}, 1)" class="backfill_deactive">Activate</a>{/if}</td>
					<td class="less">{$group.num_releases}</td>
					<td class="less">{if $group.minfilestoformrelease==""}n/a{else}{$group.minfilestoformrelease}{/if}</td>
					<td class="less">{if $group.minsizetoformrelease==""}n/a{else}{$group.minsizetoformrelease|fsize_format:"MB"}{/if}</td>
					<td class="less">{$group.backfill_target}</td>
					<td class="less" id="groupdel-{$group.id}">
						<a title="Reset this group" href="javascript:ajax_group_reset({$group.id})" class="group_reset">Reset</a> |
						<a title="Delete this group and all of its releases" href="javascript:ajax_group_delete({$group.id})" class="group_delete" onclick="return confirm('Are you sure? This will delete the group from this list.');" >Delete</a> |
						<a title="Remove all releases from this group" href="javascript:ajax_group_purge({$group.id})" class="group_purge" onclick="return confirm('Are you sure? This will delete all releases, binaries/parts in the selected group');" >Purge</a>
					</td>
				</tr>
			{/foreach}
		</table>
		<div style="position:relative;margin-top:5px;">
			<div style="position:absolute;">
				{$pager}
			</div>
			<div style="text-align:center;">
				<form name="groupsearch" action="" style="margin-bottom:5px;">
					<label for="groupname">Group</label>
					<input id="groupname" type="text" name="groupname" value="{$groupname}" size="15" />
					&nbsp;&nbsp;
					<input type="submit" value="Go" />
				</form>
				<a title="Reset all groups" href="javascript:ajax_all_reset()" class="all_reset" onclick="return confirm('Are you sure? This will reset all groups, deleting all collections/binaries/parts (does not delete releases).');" >Reset all</a> |
				<a title="Delete all releases, collections/binaries/parts from all groups" href="javascript:ajax_all_purge()" class="all_purge" onclick="return confirm('Are you sure? This will delete all releases, collections/binaries/parts.');">Purge all</a><br />
				<a title="List all groups Activated for Update_Binaries" href="{$smarty.const.WWW_TOP}/group-list-active.php">Active Groups</a> |
				<a title="List all groups NOT Activated for Update_Binaries" href="{$smarty.const.WWW_TOP}/group-list-inactive.php">Inactive Groups</a> |
				<a title="List all groups" href="{$smarty.const.WWW_TOP}/group-list.php">All Groups</a>
			</div>
		</div>
	{else}
		<p>No groups available (eg. none have been added).</p>
	{/if}
</div>