{if isset($nodata) && $nodata != ""}

<h2>View TV Series</h2>

<div class="alert">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<strong>Sorry!</strong>
	{$nodata}
</div>
{else}

<h2>
	{$seriestitles} ({$show.publisher})

	{if $catname != ''} in {$catname|escape:"htmlall"}{/if}
</h2>

<div>
	<b><a title="Manage your shows" href="{$smarty.const.WWW_TOP}/myshows">My Shows</a></b>:
	<div class="btn-group">

		{if $myshows.id != ''}
		<a class="btn btn-mini btn-warning" href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="edit" name="series{$show.id}" title="Edit Categories for this show">Edit</a> |
		<a class="btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="remove" name="series{$show.id}" title="Remove from My Shows">Remove</a>
		{else}
		<a class="btn btn-mini btn-success" href="{$smarty.const.WWW_TOP}/myshows/add/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="add" name="series{$show.id}" title="Add to My Shows">Add</a>
		{/if}
	</div>
</div>

<div class="tvseriesheading">
	{if $show.image != 0}
	<center>
		<img class="shadow img img-polaroid" style="max-height:300px;" alt="{$seriestitles} Logo" src="{$smarty.const.WWW_TOP}/covers/tvshows/{$show.id}.jpg" />
	</center>
	<br/>
	{/if}
	<p>
		<span class="descinitial">{$seriessummary|escape:"htmlall"|nl2br|magicurl}</span>
	</p>

</div>

<center>
	<div class="btn-group">
		{if $show.tvdb > 0}
			<a class="btn btn-small btn-primarybtn-info" target="_blank"
			   href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$show.tvdb}"
			   title="View at TheTVDB">TheTVDB</a>
		{/if}
		{if $show.tvmaze > 0}
			<a class="btn btn-small btn-primary btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://tvmaze.com/shows/{$show.tvmaze}"
			   title="View at TVMaze">TVMaze</a>
		{/if}
		{if $show.trakt > 0}
			<a class="btn btn-small btn-primary btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$show.trakt}"
			   title="View at TraktTv">Trakt</a>
		{/if}
		{if $show.tvrage > 0}
			<a class="btn btn-small btn-primary btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$show.tvrage}"
			   title="View at TV Rage">TV Rage</a>
		{/if}
		{if $show.tmdb > 0}
			<a class="btn btn-small btn-primary btn-info" target="_blank"
			   href="{$site->dereferrer_link}https://www.themoviedb.org/tv/{$show.tmdb}"
			   title="View at TheMovieDB">TMDB</a>
		{/if}
	</div>
</center>

<br/>

<form id="nzb_multi_operations_form" action="get">
	<div class="well well-small">
		<div class="nzb_multi_operations">
			With Selected:
			<div class="btn-group">
				<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
				<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
				{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
				{if isset($nzbgetintegrated)}<input type="button" class="nzb_multi_operations_nzbget btn btn-small btn-primary" value="Send to NZBGet" />{/if}
			</div>

		    <div class="btn-group pull-right">
		        <div class="input-append">
                    <input class="span2"  id="filter-text" type="text" placeholder="Filter">
                </div>
		    </div>

		    <div class="btn-group pull-right" data-toggle="buttons-radio" id="filter-quality">
		        <button data-quality="" class="btn active">Any</button>
		        <button data-quality="hdtv" class="btn">HDTV</button>
		        <button data-quality="720p" class="btn">720p</button>
		        <button data-quality="1080p" class="btn">1080p</button>
		    </div>

			{if $isadmin}
			<div class="pull-right">
				Admin:
				<div class="btn-group">
					<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
					<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
				</div>
			</div>
			{/if}
		</div>
	</div>

	<script type="text/javascript">
        $(document).ready(function(){
            var ul = $('div.tabbable ul.nav').prepend('<ul id="filters">');

            function filter(event){
                var elements = $('table.data:visible tr.filter');
                elements.hide();

                /* quality filter */
                x = event;
                //if(event.currentTarget.at)
                if(event.target.dataset.quality != undefined){
                    var quality = event.target.dataset.quality;
                }else{
                    var quality = $('#filter-quality button.active').data('quality');
                }
                if(quality){
                    elements = elements.filter('[data-name*="' + quality + '"]');
                }

                var values = $('#filter-text').val().split(/\s+/);
                var i = values.length;
                while(i--){
                    var value = values[i];
                    //console.log('value', value);
                    if(value)elements = elements.filter('[data-name*="' + values[i] + '"]');
                }
                elements.show();
            }
            $('#filter-text').click(filter).blur(filter).keyup(filter);
            $('#filter-quality button').mouseup(filter);
        });
	</script>

	<br clear="all" />

	<a id="latest"></a>



	<div class="tabbable">
		<ul class="nav nav-tabs">
			{foreach $seasons as $seasonnum => $season name="seas"}
			<li {if $smarty.foreach.seas.first}class="active"{/if}><a title="View Season {$seasonnum}" href="#{$seasonnum}" data-toggle="tab">{$seasonnum}</a></li>
			{/foreach}
		</ul>

		<div class="tab-content">
			{foreach $seasons as $seasonnum => $season name=tv}
			<div class="tab-pane{if $smarty.foreach.tv.first} active{/if}" id="{$seasonnum}">
				<table class="tb_{$seasonnum} data highlight icons table table-striped" id="browsetable">
					<tr class="dont-filter">
						<th>Ep</th>
						<th>Name</th>
						<th><input id="chkSelectAll{$seasonnum}" type="checkbox" name="{$seasonnum}" class="nzb_check_all_season" /><label for="chkSelectAll{$seasonnum}" style="display:none;">Select All</label></th>
						<th>Category</th>
						<th style="text-align:center;">Posted</th>
						<th width="80" >Size</th>
						<th>Files</th>
						<th></th>
					</tr>
					{foreach $season as $episodes}
					{foreach $episodes as $result}

					{if $result@index == 0}
					<tr class="{cycle values=",alt"} dont-filter">
						<td style="padding-top: 20px;" colspan="8" class="static"><h4 style="height: 0px; margin-top: 20px; margin-bottom: -50px;">{$episodes@key}</h4></td>
					</tr>
					{/if}

					<tr class="{cycle values=",alt"} filter" id="guid{$result.guid}" data-name="{$result.searchname|escape:"htmlall"|lower|replace:".":" "}">
						<td width="20" class="static"></td>
						<td>
							<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}"><h5>{$result.searchname|escape:"htmlall"|replace:".":" "}</h5></a>

							<div class="resextra">
								<div class="btns">
										{if $result.nfoid > 0}<span><a
													href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
													class="modal_nfo label label-default" rel="nfo">NFO</a></span>{/if}
										{if $result.reid > 0}<span
																	class="mediainfo label label-default"
																	title="{$result.guid}">Media</span>{/if}
										{if $result.jpgstatus == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-default" rel="preview">Sample</a></span>{/if}
										{if $result.haspreview == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-default" rel="preview">Preview</a></span>{/if}
										{if $result.firstaired != ""}<span class="rndbtn badge badge-success halffade" title="{$result.title} Aired on {$result.firstaired|date_format}"> Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>{/if}
								</div>
							</div>
						</td>
						<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="{$seasonnum}" value="{$result.guid}" /></td>
						<td class="less"><a title="This series in {$result.category_name}" href="{$smarty.const.WWW_TOP}/series/{$show.id}?t={$result.categoryid}">{$result.category_name}</a></td>
						<td class="less mid" width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>

						<td class="less right">
							{$result.size|fsize_format:"MB"}
							{if $result.completion > 0}<br />
							{if $result.completion < 100}
							<span class="label label-important">{$result.completion}%</span>
							{else}
							<span class="label label-success">{$result.completion}%</span>
							{/if}
							{/if}
						</td>

						<td class="less mid">
							<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>&nbsp;<i class="fa fa-file"></i>
						</td>
						<td class="icons" style='width:100px;'>
							<ul class="inline">
								<li>
									<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"url"}"></a>
								<li>
									<a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket">
									</a>
								</li>
								{if $sabintegrated}
								<li>
									<a class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  href="#" title="Send to queue">
									</a>
								</li>
								{/if}
								{if isset($nzbgetintegrated)}
								<li>
									<a class="icon icon_nzb fa fa-cloud-downloadget" href="#" title="Send to NZBGet">
									</a>
								</li>
								{/if}
							</ul>
						</td>
					</tr>
					{/foreach}
					{/foreach}
				</table>
			</div>
			{/foreach}
		</div>
	</div>
</form>
{/if}
