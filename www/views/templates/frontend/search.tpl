  
<h1>Search</h1>

	<div><center>
		<a href="#" onclick="if(jQuery(this).text()=='Advanced Search')jQuery(this).text('Basic Search');else jQuery(this).text('Advanced Search');jQuery('#sbasic,#sadvanced').toggle();return false;">{if $sadvanced}Basic{else}Click For Advanced{/if} Search</a>
	</center></div><br/>
	
	<center> <b>Include ^ to indicate search must start with term, -- to exclude words.</b></center>

<form method="get" action="{$smarty.const.WWW_TOP}/search">
	<div id="sbasic" style="text-align:center;{if $sadvanced} display:none;"{/if}">
		<label for="search" style="display:none;">Search</label>
		<input id="search" name="search" value="{$search|escape:'html'}" type="text"/>
		<input id="search_search_button" type="submit" value="Name" />&nbsp;&nbsp;&nbsp;
		<label for="subject" style="display:none;">Subject</label>
		<input id="subject" name="subject" value="{$subject|escape:'html'}" type="text"/>
		<input id="subject_search_button" type="submit" value="Subject" /><br/>
		<input type="hidden" name="t" value="{if $category[0]!=""}{$category[0]}{else}-1{/if}" id="search_cat" />
		<input type="hidden" name="search_type" value="basic" id="search_type" />
	</div>
</form>

<form method="get" action="{$smarty.const.WWW_TOP}/search">
	<div id="sadvanced" {if not $sadvanced}style="display:none"{/if}>
		<center>
		<table class="data">
			<tr>
				<th><label for="searchadvr">Release Name</label>:</th>
				<td><input id="searchadvr" name="searchadvr" value="{$searchadvr|escape:'html'}" type="text"/></td>
			</tr>
			<tr>
				<th><label for="searchadvsubject">Usenet Name</label>:</th>
				<td><input id="searchadvsubject" name="searchadvsubject" value="{$searchadvsubject|escape:'html'}" type="text"/></td>
			</tr>			
			<tr>
				<th><label for="searchadvposter">Poster</label>:</th>
				<td><input id="searchadvposter" name="searchadvposter" value="{$searchadvposter|escape:'html'}" type="text"/></td>
			</tr>	
			<tr>
				<th><label for="searchadvdaysold">Max Age</label>:</th>
				<td><input id="searchadvdaysold" name="searchadvdaysold" value="{$searchadvdaysold|escape:'html'}" type="text"/></td>
			</tr>		
			<tr>
				<th><label for="searchadvgroups">Group</label>:</th>
				<td>{html_options id="searchadvgroups" name="searchadvgroups" options=$grouplist selected=$selectedgroup}</td>
			</tr>
			<tr>
				<th><label for="searchadvcat">Category</label>:</th>
				<td>{html_options id="searchadvcat" name="searchadvcat" options=$catlist selected=$selectedcat}</td>
			</tr>
			<tr>
				<th><label for="searchadvsizefrom">Size Between</label>:</th>
				<td>
					{html_options id="searchadvsizefrom" name="searchadvsizefrom" options=$sizelist selected=$selectedsizefrom}
					and {html_options id="searchadvsizeto" name="searchadvsizeto" options=$sizelist selected=$selectedsizeto}
				</td>
			</tr>
			<tr>
				<th><label for="searchadvhasnfo">NFO/Comments</label>:</th>
				<td><input type="hidden" name="searchadvhasnfo" value="0" /> <input type="checkbox" name="searchadvhasnfo" value="1" />
				<input type="hidden" name="searchadvhascomments" value="0" /><input type="checkbox" name="searchadvhascomments" value="1"/></td>
			</tr>
			<tr>
				<th></th>
				<td>
					<input type="hidden" name="search_type" value="adv" id="search_type" />
					<input id="search_adv_button" type="submit" value="search" />
				</td>
			</tr>
		</table>
		</center>
	</div>
</form>

{if $results|@count == 0 && ($search || $subject|| $searchadvr|| $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) != ""}
	<center><div class="nosearchresults">
		Your search did not match any releases.
		<br/><br/>
		Suggestions:
		<br/><br/>
		<ul>
		<center><li>Make sure all words are spelled correctly.</li></center>
		<center><li>Try different keywords.</li></center>
		<center><li>Try more general keywords.</li></center>
		<center><li>Try fewer keywords.</li></center>
		</ul>
	</div></center>
{elseif ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) == ""}
{else}

{$site->adbrowse}	

<form style="padding-top:10px;" id="nzb_multi_operations_form" method="get" action="{$smarty.const.WWW_TOP}/search">

{$pager}

<div class="nzb_multi_operations">
	<small>With selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab" value="Send to SAB" />{/if}
	{if $isadmin}
	&nbsp;&nbsp;
	<input type="button" class="nzb_multi_operations_edit" value="Edit" />
	<input type="button" class="nzb_multi_operations_delete" value="Del" />
	{/if}
</div>

<table style="width:100%;" class="data highlight icons" id="browsetable">
	<tr>
		<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all" /><label for="chkSelectAll" style="display:none;">Select All</label></th>
		<th>name<br/><a title="Sort Descending" href="{$orderbyname_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyname_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>category<br/><a title="Sort Descending" href="{$orderbycat_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbycat_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>posted<br/><a title="Sort Descending" href="{$orderbyposted_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyposted_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>size<br/><a title="Sort Descending" href="{$orderbysize_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbysize_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>files<br/><a title="Sort Descending" href="{$orderbyfiles_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyfiles_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>stats<br/><a title="Sort Descending" href="{$orderbystats_desc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbystats_asc}"><img src="{$smarty.const.WWW_TOP}/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th></th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}{if $lastvisit|strtotime<$result.adddate|strtotime} new{/if}" id="guid{$result.guid}">
			<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="id[]" value="{$result.guid}" /></td>
			<td class="item">
				<label for="chk{$result.guid|substr:0:7}"><a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a></label>

				{if $result.passwordstatus == 1}
					<img title="Passworded Rar Archive" src="{$smarty.const.WWW_TOP}/views/images/icons/lock.gif" alt="Passworded Rar Archive" />
				{elseif $result.passwordstatus == 2}
					<img title="Contains .cab/ace Archive" src="{$smarty.const.WWW_TOP}/views/images/icons/lock.gif" alt="Contains .cab/ace Archive" />
				{/if}
				
				<div class="resextra">
					<div class="btns">
						{if $result.nfoID > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="rndbtn modal_nfo" rel="nfo">Nfo</a>{/if}
						{if $result.imdbID > 0}<a href="#" name="name{$result.imdbID}" title="View movie info" class="rndbtn modal_imdb" rel="movie" >Cover</a>{/if}
						{if $result.haspreview == 1 && $userdata.canpreview == 1}<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}" class="modal_prev rndbtn" rel="preview">Preview</a>{/if}
						{if $result.rageID > 0}<a class="rndbtn" href="{$smarty.const.WWW_TOP}/series/{$result.rageID}" title="View all episodes">View Series</a>{/if}
						{if $result.anidbID > 0}<a class="rndbtn" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbID}" title="View all episodes">View Anime</a>{/if}
						{if $result.consoleinfoID > 0}<a href="#" name="name{$result.consoleinfoID}" title="View console info" class="modal_console rndbtn" rel="console" >Cover</a>{/if}
						{if $result.musicinfoID > 0}<a href="#" name="name{$result.musicinfoID}" title="View music info" class="modal_music rndbtn" rel="music" >Cover</a>{/if}
						{if $result.tvairdate != ""}<span class="rndbtn" title="{$result.tvtitle} Aired on {$result.tvairdate|date_format}">Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>{/if}
						{if $result.reID > 0}<span class="mediainfo rndbtn" title="{$result.guid}">Media</span>{/if}
					</div>
				</div>
			</td>
			<td class="less"><a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryID}">{$result.category_name}</a></td>
			<td class="less mid" title="{$result.postdate}">{$result.postdate|timeago}</td>
			<td class="less right" width="55">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br />{if $result.completion < 100}<span class="warning">{$result.completion}%</span>{else}{$result.completion}%{/if}{/if}</td>
			<td class="less mid">
				<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
				{if $result.rarinnerfilecount > 0}
					<div class="rarfilelist">
						<img src="{$smarty.const.WWW_TOP}/views/images/icons/magnifier.png" alt="{$result.guid}" class="tooltip" />				
					</div>
				{/if}			
			</td>
			<td class="less" nowrap="nowrap"><a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}</td>
			<td class="icons">
				<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}">&nbsp;</a></div>
				<div class="icon icon_cart" title="Add to Cart"></div>
				{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
			</td>
		</tr>
	{/foreach}
	
</table>
<br/>

{$pager}

<div class="nzb_multi_operations">
	<small>With selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab" value="Send to SAB" />{/if}
	{if $isadmin}
	&nbsp;&nbsp;
	<input type="button" class="nzb_multi_operations_edit" value="Edit" />
	<input type="button" class="nzb_multi_operations_delete" value="Del" />
	{/if}
</div>

<br/><br/><br/>

</form>

{/if}

