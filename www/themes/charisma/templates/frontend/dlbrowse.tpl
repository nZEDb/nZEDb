<h1>Browse Downloads</h1>
<h2>/{$subpath|escape:"htmlall"}</h2>
<div class="nzb_multi_operations">
	View:
	{if $lm}<a href="{$smarty.server.REQUEST_URI|replace:"&lm=1":""}&lm=0">Covers</a> |
		<b>List</b>
	{else}
		<b>Covers</b>
		| <a href="{$smarty.server.REQUEST_URI|replace:"&lm=0":""}&lm=1">List</a>{/if}
</div>
<table style="width:100%;" class="data highlight" id="browsetable">
	<tr>
		<th width="20"></th>
		<th>name</th>
		<th class="mid" width="70">size</th>
		<th class="mid" width="80">category</th>
		<th class="mid" width="80">info</th>
		<th class="mid" width="40">date</th>
	</tr>
	{if $parentpath != ""}
		<tr>
			<td></td>
			<td colspan="5"><a
						href="{if $parentpath==-1}dlbrowse?lm={if $lm}1{else}0{/if}{else}?sp={$parentpath}&lm={if $lm}1{else}0{/if}{/if}"><strong>..</strong></a>
			</td>
		</tr>
	{/if}
	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			{assign var="icon" value='themes/charisma/images/fileicons/'|cat:$result.pathinfo.extension|cat:".png"}
			{if $result.isdir == "1"}
				{assign var="icon" value='folder'}
			{elseif $result.pathinfo.extension == "" || !is_file("$icon")}
				{assign var="icon" value='file'}
			{else}
				{assign var="icon" value=$result.pathinfo.extension}
			{/if}
			<td><img title=".{$result.pathinfo.extension}" alt="{$result.pathinfo.extension}"
					 src="{$smarty.const.WWW_TOP}/themes/charisma/images/fileicons/{$icon}.png"/></td>
			<td class="item">
				{if $result.isdir == 1}
					<a href="?sp={$subpath|escape:"url"}{$result.name|escape:"url"}&lm={if $lm}1{else}0{/if}">{$result.name|escape:"htmlall"}</a>
				{else}
					<a href="{$result.webpath}">{$result.name|escape:"htmlall"}</a>
				{/if}
				{if $result.release.movie_id != "" && $result.release.ep_id == ""}
					<div style="padding-top:10px;">
						{if $result.release.tagline != ''}<b>{$result.release.tagline}</b><br/>{/if}
						{if $result.release.plot != ''}{$result.release.plot}<br/><br/>{/if}
						{if $result.release.genre != ''}<b>Genre:</b>{$result.release.genre}<br/>{/if}
						{if $result.release.director != ''}<b>Director:</b>{$result.release.director}<br/>{/if}
						{if $result.release.actors != ''}<b>Starring:</b>{$result.release.actors}<br/><br/>{/if}
					</div>
				{/if}
				{if $result.release.ep_id != ""}
					<div style="padding-top:10px;">
						{if $result.release.ep_showtitle != ''}<b>{$result.release.ep_showtitle}</b><br/>{/if}
						{if $result.release.ep_overview != ''}{$result.release.ep_overview}<br/><br/>{/if}
						{if $result.release.ep_airdate != ''}
							<b>Aired:</b>
							{$result.release.ep_airdate|date_format}
							<br/>
						{/if}
						{if $result.release.ep_fullep != ''}<b>Episode:</b>{$result.release.ep_fullep}<br/>{/if}
					</div>
				{/if}
				{if $result.release.music_id != ""}
					<div style="padding-top:10px;">
						{if $result.release.mu_title != ''}<b>{$result.release.mu_title}</b><br/>{/if}
						{if $result.release.mu_artist != ''}{$result.release.mu_artist}<br/><br/>{/if}
						{if $result.release.mu_year != ''}<b>Year:</b>{$result.release.mu_year}<br/>{/if}
					</div>
				{/if}
				{if $result.release.music_id == "" && $result.release.ep_id == "" && $result.release.movie_id == ""}
					<br/>
				{/if}
				{if $result.release.id != ""}
					<br/>
					<a class="rndbtn" title="More info" href="{$smarty.const.WWW_TOP}/details/{$result.release.guid}">More
						Info</a>
					<br/>
					<br/>
				{/if}
			</td>
			<td class="less right">
				{if $result.isdir == 0}
					{if $result.size < 100000}{$result.size|fsize_format:"KB"}{else}{$result.size|fsize_format:"MB"}{/if}
				{/if}
			</td>
			<td class="less mid">
				{if $result.release.categoryid != ""}
					<a href="{$smarty.const.WWW_TOP}/browse?t={$result.release.categoryid}">{$result.release.category_name}</a>
				{/if}
			</td>
			<td>
				{if $result.release.movie_id != ""}
					<img class="shadow"
						 src="{$smarty.const.WWW_TOP}/covers/movies/{if $result.release.cover == 1}{$result.release.imdbid}-cover.jpg{else}no-cover.jpg{/if}"
						 width="120" border="0" alt="{$result.release.title|escape:"htmlall"}"/>
				{/if}
				{if $result.release.rage_imgdata != ""}
					<img width="120" class="shadow" alt="{$result.release.showtitle} Logo"
						 src="{$smarty.const.WWW_TOP}/getimage?type=tvrage&amp;id={$result.release.rg_id}"/>
				{/if}
				{if $result.release.mu_cover == "1"}
					<img class="shadow"
						 src="{$smarty.const.WWW_TOP}/covers/music/{if $result.release.mu_cover == 1}{$result.release.music_id}.jpg{else}no-cover.jpg{/if}"
						 width="120" border="0" alt="{$result.release.title|escape:"htmlall"}"/>
				{/if}
			</td>
			<td class="less mid" title="{$result.mtime|date_format:"%d/%m/%Y %H:%M:%S"}">{$result.mtime|timeago}</td>
		</tr>
	{/foreach}
</table>
<br/><br/><br/>