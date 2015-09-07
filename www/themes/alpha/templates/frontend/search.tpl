{if {$site->adbrowse} != ''}
	<div class="container" style="width:500px;">
		<fieldset class="adbanner div-center">
			<legend class="adbanner">Advertisement</legend>
			{$site->adbrowse}
		</fieldset>
	</div>
	<br>
{/if}
<h3 class="text-center">
	<a href="#" onclick="if (jQuery(this).text() == 'Advanced Search')
		jQuery(this).text('Basic Search');
		else
		jQuery(this).text('Advanced Search');
		jQuery('#sbasic,#sadvanced').toggle();
		return false;">{if $sadvanced}Basic{else}Click For Advanced{/if} Search
	</a>
</h3>
<center>
	{$search_description}
</center>
<br>
<form style="float:none; margin:0 auto;" method="get" action="{$smarty.const.WWW_TOP}/search" class="form-search form-horizontal col-lg-4">
	<div id="sbasic" class="input-group col-lg-12" style="{if $sadvanced}display:none;{/if}">

		<input id="search" name="search" value="{$search|escape:'html'}" type="text" class="form-control" placeholder="Name"/>
		<span class="input-group-btn">
			<button id="search_search_button" type="submit" value="Name" class="btn btn-default">
				<i class="icon-search"></i>
			</button>
		</span>
		<input type="hidden" name="t" value="{if $category[0]!=""}{$category[0]}{else}-1{/if}" id="search_cat" />
		<input type="hidden" name="search_type" value="basic" id="search_type" />
	</div>
</form>
<form method="get" action="{$smarty.const.WWW_TOP}/search">
	<div id="sadvanced" {if not $sadvanced}style="display:none"{/if}>
		<center>
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
		</center>
	</div>
</form>
<br>
<br>
{if $results|@count == 0 && ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) != ""}
	<center><div class="nosearchresults">
			Your search did not match any releases.
			<br><br>
			Suggestions:
			<br><br>
			<ul>
				<center><li>Make sure all words are spelled correctly.</li></center>
				<center><li>Try different keywords.</li></center>
				<center><li>Try more general keywords.</li></center>
				<center><li>Try fewer keywords.</li></center>
			</ul>
		</div></center>
{elseif ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) == ""}
{else}
	<form id="nzb_multi_operations_form" method="get" action="{$smarty.const.WWW_TOP}/search">
		<div class="container nzb_multi_operations" style="text-align:right;margin-bottom:5px;">
			{if $covgroup != ''}View:
				<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">
					<i class="icon-th-list"></i>
				</a>
				&nbsp;&nbsp;
				<span>
					<i class="icon-align-justify"></i>
				</span>
			{/if}
			{if $isadmin || $ismod}
				&nbsp;&nbsp;
				Admin:
				<button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
				<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete</button>
			{/if}
		</div>
		{include file='multi-operations.tpl'}

		<table class="table table-collapsed table-striped table-bordered table-hover data" id="browsetable">
			<thead>
			<tr>
				<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"></th>
				<th style="vertical-align:top;">
					name
					<a title="Sort Descending" href="{$orderbyname_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyname_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					category<br>
					<a title="Sort Descending" href="{$orderbycat_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbycat_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					posted<br>
					<a title="Sort Descending" href="{$orderbyposted_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyposted_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					size<br>
					<a title="Sort Descending" href="{$orderbysize_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbysize_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">files<br>
					<a title="Sort Descending" href="{$orderbyfiles_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyfiles_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">
					stats<br>
					<a title="Sort Descending" href="{$orderbystats_desc}">
						<i class="icon-chevron-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbystats_asc}">
						<i class="icon-chevron-up"></i>
					</a>
				</th>
				<th style="vertical-align:top;">action</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$results item=result}
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
							>{$result.searchname|escape:"htmlall"|wordwrap:70:"\n":true}</a>
						</label>
						<div class="resextra">
							{if $result.passwordstatus == 1}
								<span class="label label-default" title="Probably Passworded"><i class="icon-unlock-alt"></i></span>
							{elseif $result.passwordstatus == 2}
								<span class="label label-default" title="Broken Post"><i class="icon-unlink"></i></span>
							{elseif $result.passwordstatus == 10}
								<span class="label label-default" title="Passworded Archive"><i class="icon-lock"></i></span>
							{/if}
							{if $result.videostatus > 0}
								<a
									class="model_prev label label-default"
									href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
									title="This release has a video preview."
									rel="preview"
								><i class="icon-youtube-play"></i>
								</a>
							{/if}
							{if $result.nfoid > 0}
								<a
									href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
									title="View Nfo"
									class="modal_nfo label label-default" rel="nfo"
								><i class="icon-info-sign"></i></a>
							{/if}
							{if $result.imdbid > 0}
								<a
									href="#"
									name="name{$result.imdbid}"
									title="View movie info"
									class="modal_imdb label label-default"
									rel="movie"
								><i class="icon-film"></i></a>
							{/if}
							{if $result.musicinfoid > 0}
								<a
									href="#"
									name="name{$result.musicinfoid}"
									title="View music info"
									class="modal_music label label-default"
									rel="music"
								><i class="icon-music"></i></a>
							{/if}
							{if $result.consoleinfoid > 0}
								<a
									href="#"
									name="name{$result.consoleinfoid}"
									title="View console info"
									class="modal_console label label-default"
									rel="console"
								><i class="icon-off"></i></a>
							{/if}
							{if $result.haspreview == 1 && $userdata.canpreview == 1}
								<a
									class="modal_prev label label-default"
									href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Screenshot of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="icon-camera"></i></a>
							{/if}
							{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
								<a
									class="modal_prev label label-default"
									href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Sample of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="icon-picture"></i></a>
							{/if}
							{if $result.rageid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/series/{$result.rageid}"
									title="View all episodes"
								><i class="icon-bookmark"></i></a>
							{/if}
							{if $result.anidbid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}"
									title="View all episodes"
								><i class="icon-font"></i></a>
							{/if}
							{if $result.tvairdate != ""}
								<span
									class="seriesinfo label label-default"
									title="{$result.guid}"
								>Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>
							{/if}
							{if $result.reid > 0}
								<span
									class="mediainfo label label-default"
									title="{$result.guid}"
								><i class="icon-list-alt"></i></span>
							{/if}
							{if $result.preid > 0}
								<span
									class="label label-default preinfo rndbtn"
									title="{$result.preid}"
								><i class="icon-eye-open"></i></span>
							{/if}
							{if $result.group_name != ""}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}"
									title="Browse {$result.group_name}"
								><i class="icon-share-alt"></i></a>
							{/if}
							{release_flag($result.searchname, browse)}
						</div>
					</td>
					<td class="category" style="width:auto;text-align:center;white-space:nowrap;">
						<a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryid}">{$result.category_name}</a>
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
								<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/magnifier.png" alt="{$result.guid}">
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