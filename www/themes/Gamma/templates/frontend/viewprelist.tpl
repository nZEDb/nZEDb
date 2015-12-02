<h2>{$page->title}</h2>

<div class="navbar">
	<div class="navbar-inner">
		<table width="100%" style="margin-top: 4px;">
		<tr>
			<td width="30%">
				<div class="pull-left">
    			<form class="form-inline" name="predbsearch" action="" method="get" style="margin:0;">
    				<div class="input-append">
            			<input class="input input-large" id="q" type="text" name="q" value="{$query}" placeholder="Search" />
            			<input class="btn btn-success" type="submit" value="Search" />
    				</div>
    			</form>
				</div>
			</td>
			<td width="70%">
				{$pager}
			</td>
		</tr>
	</table>
	</div>
</div>


{$site->adbrowse}	

{if $results|@count > 0}

<table style="width:100%;" class="data Sortable highlight table table-striped" id="browsetable">
	<tr>
		<th style="width:150px;" class="mid">Date</th>
		<th class="left">Directory</th>
		<th class="mid">Category</th>
		<th class="mid">FS/FC</th>
	</tr>

	{foreach $results as $pre}
		<tr class="{cycle values=",alt"}">
			<td class="left">{$pre.ctime|date_format:"%b %e, %Y %T"}</td>
			<td class="left">
				{if $pre.guid != ''}
				
					<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$pre.guid}/{$pre.dirname}">{$pre.dirname|wordwrap:80:"\n":true}</a>
				{else}
				
					{$pre.dirname|wordwrap:80:"\n":true}
					{if $pre.nuketype != '' && $pre.nukereason != ''}</br style="font-size: 12px"><sub>({$pre.nuketype}: {$pre.nukereason})</sub>{/if}
				
				{/if}
			</td>
			<td class="mid"><a href="{$smarty.const.WWW_TOP}/predb?c={$pre.category}">{$pre.category}</a></td>
			<td class="mid">{if $pre.filesize > 0}{$pre.filesize}MB{if $pre.filecount > 0}/{$pre.filecount}F{/if}{else}--{/if}</td>
		</tr>
	{/foreach}
</table>

<div class="well well-small pagination pagination-small pagination-centered"> {$pager} </div>

{else}
        <div class="alert">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Sorry!</strong> No releases available.
        </div>
{/if}
