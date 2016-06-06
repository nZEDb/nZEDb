{include file='elements/ads.tpl' ad=$site->adbrowse}
<h3 class="text-center">
	<a href="#" onclick="if (jQuery(this).text() == 'Advanced Search')
		jQuery(this).text('Basic Search');
		else
		jQuery(this).text('Advanced Search');
		jQuery('#sbasic,#sadvanced').toggle();
		return false;">{if $sadvanced}Basic{else}Click For Advanced{/if} Search
	</a>
</h3>
<div style="text-align: center;">
	{$search_description}
</div>
<br>
<form style="float:none; margin:0 auto;" method="get" action="{$smarty.const.WWW_TOP}/search" class="form-inline form-horizontal col-lg-4">
	<div id="sbasic" class="input-group col-lg-12" style="{if $sadvanced}display:none;{/if}">

		<input id="search" name="search" value="{$search|escape:'html'}" type="text" class="form-control" placeholder="Name"/>
		<span class="input-group-btn">
			<button id="search_search_button" type="submit" value="Name" class="btn btn-default">
				<i class="fa fa-search"></i>
			</button>
		</span>
		<input type="hidden" name="t" value="{if $category[0]!=""}{$category[0]}{else}-1{/if}" id="search_cat" />
		<input type="hidden" name="search_type" value="basic" id="search_type" />
	</div>
</form>
<form method="get" action="{$smarty.const.WWW_TOP}/search">
	<div id="sadvanced" {if not $sadvanced}style="display:none"{/if}>
		<div style="text-align: center;">
			<table class="data">
				<tr>
					<th><label for="searchadvr">Release Name:</label></th>
					<td><input class="searchadv" id="searchadvr" name="searchadvr" value="{$searchadvr|escape:'html'}" type="text"/></td>
				</tr>
				<tr>
					<th><label for="searchadvsubject">Usenet Name:</label></th>
					<td><input class="searchadv" id="searchadvsubject" name="searchadvsubject" value="{$searchadvsubject|escape:'html'}" type="text"/></td>
				</tr>
				<tr>
					<th><label for="searchadvposter">Poster:</label></th>
					<td><input class="searchadv" id="searchadvposter" name="searchadvposter" value="{$searchadvposter|escape:'html'}" type="text"/></td>
				</tr>
				<tr>
					<th><label for="searchadvfilename">Filename:</label></th>
					<td><input class="searchadv" id="searchadvfilename" name="searchadvfilename" value="{$searchadvfilename|escape:'html'}" type="text"/></td>
				</tr>
				<tr>
					<th><label for="searchadvdaysnew">Min age(days):</label></th>
					<td>
						<input class="searchdaysinput" id="searchadvdaysnew" name="searchadvdaysnew" value="{$searchadvdaysnew|escape:'html'}" type="text"/>
					</td>
				</tr>
				<tr>
					<th><label for="searchadvdaysold">Max age(days):</label></th>
					<td>
						<input class="searchdaysinput" id="searchadvdaysold" name="searchadvdaysold" value="{$searchadvdaysold|escape:'html'}" type="text"/>
					</td>
				</tr>
				<tr>
					<th><label for="searchadvgroups">Group:</label></th>
					<td>{html_options class="searchadvbtns" id="searchadvgroups" name="searchadvgroups" options=$grouplist selected=$selectedgroup}</td>
				</tr>
				<tr>
					<th><label for="searchadvcat">Category:</label></th>
					<td>{html_options class="searchadvbtns" id="searchadvcat" name="searchadvcat" options=$catlist selected=$selectedcat}</td>
				</tr>
				<tr>
					<th><label for="searchadvsizefrom">Min/Max Size:</label></th>
					<td>
						{html_options id="searchadvsizefrom" name="searchadvsizefrom" options=$sizelist selected=$selectedsizefrom}
						{html_options id="searchadvsizeto" name="searchadvsizeto" options=$sizelist selected=$selectedsizeto}
					</td>
				</tr>
				<tr>
					<th><label for="searchadvhasnfo">NFO/Comments:</label></th>
					<td><input type="hidden" name="searchadvhasnfo" value="0" /> <input type="checkbox" name="searchadvhasnfo" value="1" />
						<input type="hidden" name="searchadvhascomments" value="0" /><input type="checkbox" name="searchadvhascomments" value="1"/> <div style="float:right;"><input type="hidden" name="search_type" value="adv" id="search_type" /> <input id="search_adv_button" type="submit" value="search" /></div> </td>
				</tr>
			</table>
		</div>
	</div>
</form>
<br>
<br>
{if $results|@count == 0 && ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) != ""}
	<div style="text-align: center;"><div class="nosearchresults">
			Your search did not match any releases.
			<br><br>
			Suggestions:
			<br><br>
			<ul>
				<div style="text-align: center;"><li>Make sure all words are spelled correctly.</li></div>
				<div style="text-align: center;"><li>Try different keywords.</li></div>
				<div style="text-align: center;"><li>Try more general keywords.</li></div>
				<div style="text-align: center;"><li>Try fewer keywords.</li></div>
			</ul>
		</div></div>
{elseif ($search || $subject || $searchadvr || $searchadvsubject || $searchadvfilename || $selectedgroup || $selectedsizefrom || $searchadvdaysold) == ""}
{else}
	<form id="nzb_multi_operations_form" method="get" action="{$smarty.const.WWW_TOP}/search">
		{include file='elements/admin-buttons-covgroup.tpl'}
		{include file='multi-operations.tpl'}

		<table class="table table-collapsed table-striped table-bordered table-hover data" id="browsetable">
			<thead>
			<tr>
				<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"></th>
				<th style="vertical-align:top;">
					name
					<a title="Sort Descending" href="{$orderbyname_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyname_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					category<br>
					<a title="Sort Descending" href="{$orderbycat_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbycat_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					posted<br>
					<a title="Sort Descending" href="{$orderbyposted_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyposted_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					size<br>
					<a title="Sort Descending" href="{$orderbysize_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbysize_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">files<br>
					<a title="Sort Descending" href="{$orderbyfiles_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyfiles_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					stats<br>
					<a title="Sort Descending" href="{$orderbystats_desc}">
						<i class="fa fa-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbystats_asc}">
						<i class="fa fa-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">action</th>
			</tr>
			</thead>
			<tbody>
			{foreach $results as $result}
				<tr class="{if $lastvisit|strtotime<$result.adddate|strtotime}success{/if}" id="guid{$result.guid}">
					<td class="check" style="width:26px;text-align:center;white-space:nowrap;">
						<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.guid}">
					</td>
					<td class="item" style="width:100%;text-align:left;">
						<label for="chk{$result.guid|substr:0:7}">
							<a
								class="title"
								title="View details"
								href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
							>{$result.searchname|escape:"htmlall"|wordwrap:70:"\n":true}</a>{if $result.failed > 0} <i class="fa fa-exclamation-circle" style="color: red" title="This release has failed to download for some users"></i>{/if}
						</label>
						<div class="resextra">
							{if $result.passwordstatus == 1}
								<span class="label label-default" title="Probably Passworded"><i class="fa fa-unlock-alt"></i></span>
							{elseif $result.passwordstatus == 2}
								<span class="label label-default" title="Broken Post"><i class="fa fa-chain-broken"></i></span>
							{elseif $result.passwordstatus == 10}
								<span class="label label-default" title="Passworded Archive"><i class="fa fa-lock"></i></span>
							{/if}
							{if $result.videostatus > 0}
								<a
									class="model_prev label label-default"
									href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
									title="This release has a video preview."
									rel="preview"
								><i class="fa fa-youtube-play"></i>
								</a>
							{/if}
							{if $result.nfoid > 0}
								<a
									href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
									title="View Nfo"
									class="modal_nfo label label-default" rel="nfo"
								><i class="fa fa-info"></i></a>
							{/if}
							{if $result.imdbid > 0}
								<a
									href="#"
									name="name{$result.imdbid}"
									title="View movie info"
									class="modal_imdb label label-default"
									rel="movie"
								><i class="fa fa-film"></i></a>
							{/if}
							{if $result.musicinfo_id > 0}
								<a
									href="#"
									name="name{$result.musicinfo_id}"
									title="View music info"
									class="modal_music label label-default"
									rel="music"
								><i class="fa fa-music"></i></a>
							{/if}
							{if $result.consoleinfo_id > 0}
								<a
									href="#"
									name="name{$result.consoleinfo_id}"
									title="View console info"
									class="modal_console label label-default"
									rel="console"
								><i class="fa fa-power-off"></i></a>
							{/if}
							{if $result.haspreview == 1 && $userdata.canpreview == 1}
								<a
									class="modal_prev label label-default"
									href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Screenshot of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="fa fa-camera"></i></a>
							{/if}
							{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
								<a
									class="modal_prev label label-default"
									href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Sample of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="fa fa-picture-o"></i></a>
							{/if}
							{if $result.videos_id > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}"
									title="View all episodes"
								><i class="fa fa-bookmark"></i></a>
							{/if}
							{if $result.anidbid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}"
									title="View all episodes"
								><i class="fa fa-font"></i></a>
							{/if}
							{if $result.firstaired != ""}
								<span
									class="seriesinfo label label-default"
									title="{$result.guid}"
								>Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>
							{/if}
							{if $result.reid > 0}
								<span
									class="mediainfo label label-default"
									title="{$result.guid}"
								><i class="fa fa-list-alt"></i></span>
							{/if}
							{if $result.predb_id > 0}
								<span
									class="label label-default preinfo rndbtn"
									title="{$result.predb_id}"
								><i class="fa fa-eye"></i></span>
							{/if}
							{if $result.group_name != ""}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}"
									title="Browse {$result.group_name}"
								><i class="fa fa-share-alt"></i></a>
							{/if}
							{release_flag($result.searchname, browse)}
							{if $result.failed > 0}<span class="label label-default">
								<i class ="fa fa-thumbs-o-up"></i> {$result.grabs} Grab{if $result.grabs != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$result.failed} Failed Download{if $result.failed != 1}s{/if}</span>
							{/if}
						</div>
					</td>
					<td class="category" style="width:auto;text-align:center;white-space:nowrap;">
						<a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categories_id}">{$result.category_name}</a>
					</td>
					<td class="posted" title="{$result.postdate}" style="white-space:nowrap;text-align:center;">{$result.postdate|timeago}</td>
					<td class="size" style="width:auto;text-align:center;white-space:nowrap;">
						{$result.size|fsize_format:"MB"}
						{if $result.completion > 0}
							<br>
							{if $result.completion < 100}
								<span class="label label-warning">{$result.completion}%</span>
							{else}
								<span class="label label-success">{$result.completion}%</span>
							{/if}
						{/if}
					</td>
					<td class="files" style="width:auto;text-align:center;white-space:nowrap;">
						<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">
							{$result.totalpart}
						</a>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/magnifier.png" alt="{$result.guid}">
							</div>
						{/if}
					</td>
					<td class="stats" style="width:auto;text-align:center;white-space:nowrap;">
						<a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a>
						<br/>
						{$result.grabs} grab{if $result.grabs != 1}s{/if}
					</td>
					<td class="icons" style="width:60px;text-align:center;white-space:nowrap;">
						<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}">&nbsp;</a></div>
						{if $sabintegrated}
							<div class="icon icon_sab" title="Send to my Queue"></div>
						{/if}
						<div class="icon icon_cart" title="Add to Cart"></div>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		{if $results|@count > 10}
			<div class="nzb_multi_operations">
				{include file='multi-operations.tpl'}
			</div>
		{/if}
	</form>
{/if}
