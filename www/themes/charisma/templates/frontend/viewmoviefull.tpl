<div class="header">
	{assign var="catsplit" value=">"|explode:$catname}
	<h2>View > <strong>Movie</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ View Movie
		</ol>
	</div>
</div>
{if $results|@count > 0}
	<div class="box-body">
		{foreach from=$results item=result}
			<div id="moviefull" style="min-height:340px;">
				{if $result.cover == 1}
					<img class="pull-right" style="margin-right:50px; max-height:278px;"
						 alt="{$result.title|escape:"htmlall"} Logo"
						 src="{$smarty.const.WWW_TOP}/covers/movies/{$result.imdbid}-cover.jpg"/>
				{else}
					<img class="pull-right" style="margin-right:50px; max-height:278px;"
						 alt="{$result.title|escape:"htmlall"} Logo"
						 src="{$serverroot}themes/charisma/images/nomoviecover.jpg"/>
				{/if}
				<span class="h1" style="display:inline;">{$result.title|escape:"htmlall"} ({$result.year})</span><a
						class="btn btn-transparent btn-primary" target="_blank"
						href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbid}/"
						name="imdb{$result.imdbid}" title="View IMDB page for this movie">View on IMDB</a>
				<h4>{if $result.genre != ''}{$result.genre|replace:"|":" / "}{/if}</h4>
				{if $result.tagline != ''}
					<p class="lead" style="margin-right:300px;">"{$result.tagline|escape:"htmlall"}"</p>
				{/if}
				<dl style="margin-right:300px;">
					{if $result.plot != ''}
						<dt>Plot</dt>
						<dd>{$result.plot|escape:"htmlall"}</dd>
					{/if}
					{if $result.rating != ''}
						<dt>Rating</dt>
						<dd>{$result.rating}
						/10 {if $result.ratingcount != ''}({$result.ratingcount|number_format} votes)</dd>{/if}
					{/if}
					{if $result.director != ''}
						<dt>Director</dt>
						<dd>{$result.director|replace:"|":", "}</dd>
					{/if}
					{if $result.actors != ''}
						<dt>Actors</dt>
						<dd>{$result.actors|replace:"|":", "}</dd>
					{/if}
				</dl>
			</div>
			<form id="nzb_multi_operations_form" action="get">
				<div class="well well-small">
					<div class="nzb_multi_operations">
						{if $section != ''}View:
							<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}">Covers</a>
							|
							<b>List</b>
							<br/>
						{/if}
						With Selected:
						<div class="btn-group">
							<input type="button" class="nntmux_multi_operations_download btn btn-sm btn-success"
								   value="Download NZBs"/>
							<input type="button" class="nntmux_multi_operations_cart btn btn-sm btn-info"
								   value="Add to Cart"/>
							{if isset($sabintegrated)}
								<input type="button" class="nzb_multi_operations_sab btn btn-sm btn-primary"
									   value="Send to Queue"/>
							{/if}
						</div>
						{if isset($isadmin)}
							<div class="pull-right">
								Admin:
								<div class="btn-group">
									<input type="button" class="nntmux_multi_operations_edit btn btn-sm btn-warning"
										   value="Edit"/>
									<input type="button" class="nntmux_multi_operations_delete btn btn-sm btn-danger"
										   value="Delete"/>
								</div>
							</div>
						{/if}
					</div>
				</div>
				<div class="row">
					<div class="col-xlg-12 portlets">
						<div class="panel panel-default">
							<div class="panel-body pagination2">
								<table style="width:100%;"
									   class="data table table-condensed table-striped table-responsive table-hover">
									<tr>
										<th>
											<input id="chkSelectAll" type="checkbox" class="nntmux_check_all"/>
											<label for="chkSelectAll" style="display:none;">Select All</label>
										</th>
										<th>Name<br/>
											<a title="Sort Descending" href="{$orderbyname_desc}">
												<i class="fa fa-icon-caret-down"></i>
											</a>
											<a title="Sort Ascending" href="{$orderbyname_asc}">
												<i class="fa fa-icon-caret-up"></i>
											</a>
										</th>
										<th>Category<br/>
											<a title="Sort Descending" href="{$orderbycat_desc}">
												<i class="fa fa-icon-caret-down"></i>
											</a>
											<a title="Sort Ascending" href="{$orderbycat_asc}">
												<i class="fa fa-icon-caret-up"></i>
											</a>
										</th>
										<th>Posted<br/>
											<a title="Sort Descending" href="{$orderbyposted_desc}">
												<i class="fa fa-icon-caret-down"></i>
											</a>
											<a title="Sort Ascending" href="{$orderbyposted_asc}">
												<i class="fa fa-icon-caret-up"></i>
											</a>
										</th>
										<th>Size<br/>
											<a title="Sort Descending" href="{$orderbysize_desc}">
												<i class="fa fa-icon-caret-down"></i>
											</a>
											<a title="Sort Ascending" href="{$orderbysize_asc}">
												<i class="fa fa-icon-caret-up"></i>
											</a>
										</th>
										<th>Action</th>
									</tr>
									{assign var="msplits" value=","|explode:$result.grp_release_id}
									{assign var="mguid" value=","|explode:$result.grp_release_guid}
									{assign var="mnfo" value=","|explode:$result.grp_release_nfoid}
									{assign var="mgrp" value=","|explode:$result.grp_release_grpname}
									{assign var="mname" value="#"|explode:$result.grp_release_name}
									{assign var="mpostdate" value=","|explode:$result.grp_release_postdate}
									{assign var="msize" value=","|explode:$result.grp_release_size}
									{assign var="mtotalparts" value=","|explode:$result.grp_release_totalparts}
									{assign var="mcomments" value=","|explode:$result.grp_release_comments}
									{assign var="mgrabs" value=","|explode:$result.grp_release_grabs}
									{assign var="mpass" value=","|explode:$result.grp_release_password}
									{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
									{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
									{assign var="mcat" value=","|explode:$result.grp_release_categoryid}
									{assign var="mcatname" value=","|explode:$result.grp_release_categoryName}
									{foreach from=$msplits item=m}
										<tr class="{cycle values=",alt"}" id="guid{$mguid[$m@index]}">
											<td class="check"><input id="chk{$mguid[$m@index]|substr:0:7}"
																	 type="checkbox"
																	 class="nzb_check"
																	 value="{$mguid[$m@index]}"/></td>
											<td class="item">
												<a title="View details"
												   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$mname[$m@index]|escape:"htmlall"|replace:".":" "}</a>
												<br/>
						<span class="label label-default">{$mgrabs[$m@index]}
							grab{if $mgrabs[$m@index] != 1}s{/if}</span>
												{if $mnfo[$m@index] > 0}<span class="label label-default"><a
															href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}"
															class="text-muted">NFO</a>
													</span>{/if}
												{if $mpass[$m@index] == 2}
													<i class="fa fa-icon-lock"></i>
												{elseif $mpass[$m@index] == 1}
													<i class="fa fa-icon-lock"></i>
												{/if}
											</td>
											<td class="less"><span
														class="label label-default">{$mcatname[$m@index]}</span>
											</td>
											<td class="less mid"
												title="{$mpostdate[$m@index]}">{$mpostdate[$m@index]|timeago}</td>
											<td class="less right">{$msize[$m@index]|fsize_format:"MB"}</td>
											<td class="icons">
												<a title="Download NZB"
												   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"><i
															class="icon icon_nzb fa fa-download text-muted"></i></a>
												<a href="#" class="icon_cart text-muted"><i class="fa fa-shopping-cart"
																							title="Add to Cart"></i></a>
												{if isset($sabintegrated)}<img class="icon_sab"
																			   src="{$smarty.const.WWW_TOP}/themes/baffi/images/icons/sabup.png"/>{/if}
											</td>
										</tr>
									{/foreach}
								</table>
								<hr>
								{if $results|@count > 10}
									<div class="row">
										<div class="col-md-8">
											<div class="nzb_multi_operations">
												{if isset($section) && $section != ''}View:
													<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}">Covers</a>
													|
													<b>List</b>
													<br/>
												{/if}
												With Selected:
												<div class="btn-group">
													<input type="button"
														   class="nzb_multi_operations_download btn btn-sm btn-success"
														   value="Download NZBs"/>
													<input type="button"
														   class="nzb_multi_operations_cart btn btn-sm btn-info"
														   value="Add to Cart"/>
													{if isset($sabintegrated)}
														<input type="button"
															   class="nzb_multi_operations_sab btn btn-sm btn-primary"
															   value="Send to Queue"/>
													{/if}
													{if isset($nzbgetintegrated)}
														<input type="button"
															   class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
															   value="Send to NZBGet"/>
													{/if}
													{if isset($isadmin)}
														<input type="button"
															   class="nzb_multi_operations_edit btn btn-sm btn-warning"
															   value="Edit"/>
														<input type="button"
															   class="nzb_multi_operations_delete btn btn-sm btn-danger"
															   value="Delete"/>
													{/if}
												</div>
											</div>
										</div>
										<div class="col-md-4">
											{$pager}
										</div>
									</div>
								{/if}
							</div>
						</div>
			</form>
		{/foreach}
	</div>
{/if}