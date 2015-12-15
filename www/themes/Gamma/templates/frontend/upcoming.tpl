<h1>{$page->title}</h1>
{$site->adbrowse}
{if $data|@count > 0}
<table style="width:100%;" class="data highlight icons" id="coverstable">
		<tr>
			<th></th>
			<th>&nbsp;</th>
		</tr>
		{foreach $data as $result}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
				<div class="movcover">
					<center>
					<img class="shadow img img-polaroid" src="{replace_url($result->posters->original)}" width="120" border="0"
						 alt="{$result->title|escape:"htmlall"}"/>
					</center>
					<div class="movextra">
						<center>
						<a class="rndbtn badge badge-success" target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten</a>
						<a class="rndbtn badge badge-imdb" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result->alternate_ids->imdb}" title="View Imdb Details">Imdb</a>
						</center>
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h4><a href="{$smarty.const.WWW_TOP}/movies?imdb={$result->alternate_ids->imdb}">{$result->title|escape:"htmlall"}</a> (<a class="title" title="{$result->year}" href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>) {if $result->ratings->critics_score > 0}{$result->ratings->critics_score}/100{/if}</h4>
				{if $result->synopsis == ""}No synopsis. Check <a target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a> for more information.{else}{$result->synopsis}{/if}
				{if $result->abridged_cast|@count > 0}
					<br /><br />
					<b>Starring:</b>
					{foreach from=$result->abridged_cast item=cast name=cast}
						<a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}" title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>
						{if $smarty.foreach.cast.last}<br/><br/>{else},{/if}
					{/foreach}
				{else}
					<br/><br/>
				{/if}
				<br/>
			</td>
		</tr>
		{/foreach}
</table>
{else}
<h2>No results</h2>
{/if}
