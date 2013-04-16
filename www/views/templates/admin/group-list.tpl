<div id="group_list"> 

    <h1>{$page->title}</h1>

		<p>
			Below is a list of all usenet groups available to be indexed. Click 'Activate' to start indexing a group.
		</p>
    
	{if $grouplist}
    <div style="position:relative;margin-bottom:5px;">
        <center><td class="less" id="alldel">
            <form name="groupsearch" action="" style="margin-bottom:5px;">
                <label for="groupname">Group</label>
                <input id="groupname" type="text" name="groupname" value="{$groupname}" size="15" />
                &nbsp;&nbsp;
                <input type="submit" value="Go" />
            </form>

        <div style="position:absolute;">
            {$pager}
        </div>
		<a title="Reset all groups" href="javascript:ajax_all_reset()" class="all_reset" onclick="return confirm('Are you sure? This will reset all groups, deleting all collections/binaries/parts (does not delete releases).');" >Reset all</a> | <a href="javascript:ajax_all_purge()" class="all_purge" onclick="return confirm('Are you sure? This will delete all releases, collections/binaries/parts.');">Purge all</a> \\// <a href="{$smarty.const.WWW_TOP}/group-list-active.php">Active Groups</a> | <a href="{$smarty.const.WWW_TOP}/group-list-inactive.php">Inactive Groups</a> | <a href="{$smarty.const.WWW_TOP}/group-list.php">All Groups</a>

        </td><center/>
    </div>

    <div id="message">msg</div>
    <table style="width:100%;" class="data highlight">

        <tr>
            <th>group</th>
            <th>First Post</th>
			<th>Last Post</th>
            <th>last updated</th>
            <th>active</th>
            <th>releases</th>
			<th>Min Files</th>
			<th>Min Size</th>
            <th>Backfill Days</th>
			<th>options</th>
        </tr>
        
        {foreach from=$grouplist item=group}
        <tr id="grouprow-{$group.ID}" class="{cycle values=",alt"}">
            <td>
				<a href="{$smarty.const.WWW_TOP}/group-edit.php?id={$group.ID}">{$group.name|replace:"alt.binaries":"a.b"}</a>
				<div class="hint">{$group.description}</div>
			</td>
            <td class="less">{$group.first_record_postdate|timeago}</td>
			<td class="less">{$group.last_record_postdate|timeago}</td>
            <td class="less">{$group.last_updated|timeago} ago</td>
            <td class="less" id="group-{$group.ID}">{if $group.active=="1"}<a href="javascript:ajax_group_status({$group.ID}, 0)" class="group_active">Deactivate</a>{else}<a href="javascript:ajax_group_status({$group.ID}, 1)" class="group_deactive">Activate</a>{/if}</td>
            <td class="less">{$group.num_releases}</td>
			<td class="less">{if $group.minfilestoformrelease==""}n/a{else}{$group.minfilestoformrelease}{/if}</td>
			<td class="less">{if $group.minsizetoformrelease==""}n/a{else}{$group.minsizetoformrelease|fsize_format:"MB"}{/if}</td>
            <td class="less">{$group.backfill_target}</td>
            <td class="less" id="groupdel-{$group.ID}"><a title="Reset this group" href="javascript:ajax_group_reset({$group.ID})" class="group_reset">Reset</a> | <a href="javascript:ajax_group_delete({$group.ID})" class="group_delete">Delete</a> | <a href="javascript:ajax_group_purge({$group.ID})" class="group_purge" onclick="return confirm('Are you sure? This will delete all releases, binaries/parts in the selected group');" >Purge</a></td>
        </tr>
        {/foreach}

    </table>
	<div style="position:relative;margin-top:5px;">
	    <div style="position:absolute;">
	  	    {$pager}
	    </div>

	    <center><td class="less" id="alldel">
    	    <form name="groupsearch" action="" style="margin-bottom:5px;">
        	    <label for="groupname">Group</label>
            	<input id="groupname" type="text" name="groupname" value="{$groupname}" size="15" />
	            &nbsp;&nbsp;
    	        <input type="submit" value="Go" />
        	</form>
			<a title="Reset all groups" href="javascript:ajax_all_reset()" class="all_reset" onclick="return confirm('Are you sure? This will reset all groups, deleting all collections/binaries/parts (does not delete releases).');" >Reset all</a> | <a href="javascript:ajax_all_purge()" class="all_purge" onclick="return confirm('Are you sure? This will delete all releases, collections/binaries/parts.');">Purge all</a> \\// <a href="javascript:ajax_all_active()" class="all_active" >Active Groups</a> | <a href="javascript:ajax_all_inactive()" class="all_inactive" >Inactive Groups</a> | <a href="javascript:ajax_all_groups()" class="all_groups" >All Groups</a>
		</td><center/>
	</div>

    {else}
    <p>No groups available (eg. none have been added).</p>
    {/if}

</div>
