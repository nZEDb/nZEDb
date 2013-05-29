 
<h1>{$page->title}</h1>

{$pager}

<table style="width:100%;margin-bottom:10px; margin-top:5px;" class="data Sortable highlight">

	<tr>
		<th>title</th>
		<th>added</th>
		<th>pre-date</th>
		<th>source</th>
		<th>category</th>
		<th>size</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="predb">{$result.title}</td>
			<td class="predb">{$result.adddate}</td>
			<td class="predb">{$result.predate}</td>
			<td class="predb">
				{if {$result.source} == orlydb}
					<a title="Visit ORLYDB" href="{$site->dereferrer_link}http://www.orlydb.com/">
						ORLYDB.com
					</a>
				{/if}
				{if {$result.source} == predbme}
					<a title="Visit PreDB.me" href="{$site->dereferrer_link}http://predb.me/">
						PreDB.me
					</a>
				{/if}
				{if {$result.source} == prelist}
					<a title="Visit Prelist" href="{$site->dereferrer_link}http://pre.zenet.org/">
						Prelist.ws
					</a>
				{/if}
				{if {$result.source} == srrdb}
					<a title="Visit srrDB" href="{$site->dereferrer_link}http://www.srrdb.com/">
						srrDB.com
					</a>
				{/if}
				{if {$result.source} == womble}
					<a title="Visit Womble" href="{$site->dereferrer_link}http://nzb.isasecret.com/">
						Womble's NZB Index
					</a>
				{/if}
				{if {$result.source} == zenet}
					<a title="Visit ZEnet" href="{$site->dereferrer_link}http://pre.zenet.org/">
						ZEnet.org
					</a>
				{/if}
			</td>
			<td class="predb">{$result.category}</td>
			<td class="predb">{$result.size}</td>
		</tr>
	{/foreach}


</table>

<pager style="padding-bottom:10px;"> {$pager} </pager>
