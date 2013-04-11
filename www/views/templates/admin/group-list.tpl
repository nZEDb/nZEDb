<div id="group_list"> 

    <h1>{$page->title}</h1>

		<p>
			Below is a list of all usenet groups available to be indexed. Click 'Activate' to start indexing a group.
		</p>


    {if $grouplist}
	
	<div style="float:right;">
	
		<form name="groupsearch" action="">
			<label for="groupname">Group</label>
			<input id="groupname" type="text" name="groupname" value="{$groupname}" size="15" />
			&nbsp;&nbsp;
			<input type="submit" value="Go" />
		</form>
	</div>
	
	{$pager}
	<br/><br/>
	
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
    {else}
    <p>No groups available (eg. none have been added).</p>
    {/if}

</div>		

