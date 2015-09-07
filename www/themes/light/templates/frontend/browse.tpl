{if $site->adbrowse != ''}
	{$site->adbrowse}
{/if}
<h1>Browse {$catname|escape:"htmlall"}</h1>
{if $shows}
	<p><b>Jump to</b>:
		&nbsp;&nbsp;[ <a href="{$smarty.const.WWW_TOP}/series" title="View available TV series">Series List</a> ]
		&nbsp;&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows" title="List my watched shows">My Shows</a> ]
		<br />Your shows can also be downloaded as an <a href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss Feed</a>.
	</p>
{/if}
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="nzb_multi_operations">
			{if $covgroup != ''}View: <a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">Covers</a> | <b>List</b><br />{/if}
			<small>With Selected:</small>
			<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
			<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
			{if $sabintegrated}
				<input type="button" class="nzb_multi_operations_sab" value="Send to my Queue" />
			{/if}
			{if $isadmin || $ismod}
				&nbsp;&nbsp;
				<input type="button" class="nzb_multi_operations_edit" value="Edit" />
				<input type="button" class="nzb_multi_operations_delete" value="Del" />
			{/if}
		</div>
		{$pager}
		<table style="width:100%;" class="data highlight icons" id="browsetable">
			<tr>
				<th></th>
				<th>
					name<br/>
					<a title="Sort Descending" href="{$orderbyname_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbyname_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th style="text-align:center;">
					category<br/>
					<a title="Sort Descending" href="{$orderbycat_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbycat_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th style="text-align:center;">
					posted<br/>
					<a title="Sort Descending" href="{$orderbyposted_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbyposted_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th style="text-align:center;">
					size<br/>
					<a title="Sort Descending" href="{$orderbysize_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbysize_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th style="text-align:center;">
					files<br/>
					<a title="Sort Descending" href="{$orderbyfiles_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbyfiles_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th style="text-align:center;">
					stats<br/>
					<a title="Sort Descending" href="{$orderbystats_desc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif" alt="Sort Descending" />
					</a>
					<a title="Sort Ascending" href="{$orderbystats_asc}">
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt="Sort Ascending" />
					</a>
				</th>
				<th>
					<input id="chkSelectAll" type="checkbox" class="nzb_check_all" />
					<label for="chkSelectAll" style="display:none;">Select All</label>
				</th>
			</tr>
			{foreach from=$results item=result}
				<tr class="{cycle values=",alt"}{if $lastvisit|strtotime<$result.adddate|strtotime} new{/if}" id="guid{$result.guid}">
					<td class="icons">
						<div class="icon icon_nzb">
							<a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}">&nbsp;</a>
						</div>
						<div class="icon icon_cart" title="Add to Cart"></div>
						{if $sabintegrated}
							<div class="icon icon_sab" title="Send to my Queue"></div>
						{/if}
					</td>
					<td class="item">
						<label for="chk{$result.guid|substr:0:7}">
							<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|truncate:150:"...":true}</a>
						</label value="Searchname">
						<div class="resextra">
							<a class="browsename" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.name|escape:"htmlall"|truncate:150:"...":true}</a>
							<div class="btns" style="float:right">
								{release_flag($result.searchname, browse)}
								{if $result.passwordstatus == 1}
									<img title="RAR/ZIP Possibly Passworded." src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/lock2.png" alt="RAR/ZIP Possibly Passworded." />
								{elseif $result.passwordstatus == 2}
									<img title="RAR/ZIP Possibly Damaged." src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/broken.png" alt="RAR/ZIP Possibly Damaged." />
								{elseif $result.passwordstatus == 10}
									<img title="RAR/ZIP is Passworded." src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/lock.gif" alt="RAR/ZIP is Passworded." />
								{/if}
								{if $result.videostatus == 1}
									<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}" title="This release has a video preview." class="model_prev rndbtnsml" rel="preview">
										<img src="{$smarty.const.WWW_TOP}/themes_shared/images/multimedia/video.png" />
									</a>
								{/if}
								{if $result.nfoid > 0}
									<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo rndbtnsml" rel="nfo">Nfo</a>
								{/if}
								{if $result.imdbid > 0}
									<a href="#" name="name{$result.imdbid}" title="View movie info" class="modal_imdb rndbtnsml" rel="movie" >Cover</a>
								{/if}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}
									<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}" class="modal_prev rndbtnsml" rel="preview">Preview</a>
								{/if}
								{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
									<a href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Sample of {$result.searchname|escape:"htmlall"}" class="modal_prev rndbtnsml" rel="preview">Sample</a>
								{/if}
								{if $result.musicinfoid > 0}
									<a href="#" name="name{$result.musicinfoid}" title="View music info" class="modal_music rndbtnsml" rel="music" >Cover</a>
								{/if}
								{if $result.consoleinfoid > 0}
									<a href="#" name="name{$result.consoleinfoid}" title="View console info" class="modal_console rndbtnsml" rel="console" >Cover</a>
								{/if}
								{if $result.rageid > 0}
									<a class="rndbtnsml" href="{$smarty.const.WWW_TOP}/series/{$result.rageid}" title="View all episodes">View Series</a>
								{/if}
								{if $result.anidbid > 0}
									<a class="rndbtnsml" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}" title="View all episodes">View Anime</a>
								{/if}
								{if $result.tvairdate != ""}
									<span class="seriesinfo rndbtnsml" title="{$result.guid}">Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>
								{/if}
								{if $result.reid > 0}
									<span class="mediainfo rndbtnsml" title="{$result.guid}">Media</span>
								{/if}
								{if $result.preid > 0}
									<span class="preinfo rndbtnsml" title="{$result.preid}">PreDB</span>
								{/if}
								{if $result.group_name != ""}
									<a class="rndbtnsml" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}" title="Browse {$result.group_name}">{$result.group_name|escape:"htmlall"|replace:"alt.binaries.":"a.b."}</a>
								{/if}
							</div>
						</div>
					</td>
					<td class="category">
						<a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryid}">{$result.category_name}</a>
					</td>
					<td class="posted" title="{$result.postdate}">
						{$result.postdate|timeago}
					</td>
					<td class="size">
						{$result.size|fsize_format:"MB"}
						{if $result.completion > 0}
							<br />
							{if $result.completion < 100}
								<span class="warning">
									{$result.completion}%
								</span>
							{else}
								{$result.completion}%
							{/if}
						{/if}
					</td>
					<td class="files">
						<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/magnifier.png" alt="{$result.guid}" />
							</div>
						{/if}
					</td>
					<td class="stats" nowrap="nowrap">
						<a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a>
						<br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}
					</td>
					<td class="check">
						<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.guid}" />
					</td>
				</tr>
			{/foreach}
		</table>
		<br/>
		{$pager}
		<div class="nzb_multi_operations">
			<small>With Selected:</small>
			<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
			<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
			{if $sabintegrated}
				<input type="button" class="nzb_multi_operations_sab" value="Send to my Queue" />
			{/if}
			{if $isadmin || $ismod}
				&nbsp;&nbsp;
				<input type="button" class="nzb_multi_operations_edit" value="Edit" />
				<input type="button" class="nzb_multi_operations_delete" value="Del" />
			{/if}
		</div>
	</form>
{/if}
<br/><br/><br/>