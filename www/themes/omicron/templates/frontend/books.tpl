<div class="header" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html"
	 xmlns="http://www.w3.org/1999/html">
	{assign var="catsplit" value=">"|explode:$catname}
	<h2>{$catsplit[0]} > <strong>{if isset($catsplit[1])} {$catsplit[1]}{/if}</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ {$catname|escape:"htmlall"}
		</ol>
	</div>
</div>
<form id="nzb_multi_operations_form" action="get">
	<div class="box-body"
	<div class="row">
		<div class="box col-md-12">
			<div class="box-content">
				<div class="row">
					<div class="col-xlg-12 portlets">
						<div class="panel panel-default">
							<div class="panel-body pagination2">
								<div class="row">
									<div class="col-md-8">
										<div class="nzb_multi_operations">
											View: <strong>Covers</strong> | <a
													href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
											Check all: <input type="checkbox" class="nntmux_check_all"/> <br/>
											With Selected:
											<div class="btn-group">
												<input type="button"
													   class="nntmux_multi_operations_download btn btn-sm btn-success"
													   value="Download NZBs"/>
												<input type="button"
													   class="nntmux_multi_operations_cart btn btn-sm btn-info"
													   value="Add to Cart"/>
												{if isset($sabintegrated)}
													<input type="button"
														   class="nntmux_multi_operations_sab btn btn-sm btn-primary"
														   value="Send to Queue"/>
												{/if}
												{if isset($nzbgetintegrated)}
													<input type="button"
														   class="nntmux_multi_operations_nzbget btn btn-sm btn-primary"
														   value="Send to NZBGet"/>
												{/if}
												{if isset($isadmin)}
													<input type="button"
														   class="nntmux_multi_operations_delete btn btn-sm btn-danger"
														   value="Delete"/>
												{/if}
											</div>
										</div>
									</div>
									<div class="col-md-4">
										{$pager}
									</div>
								</div>
								<hr>
								{foreach from=$results item=result}
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
									{foreach from=$msplits item=m}
										<div class="panel panel-default">
											<div class="panel-body">
												<div class="row">
													<div class="col-md-2 no-gutter">
														<a title="View details"
														   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">
															<img src="{$smarty.const.WWW_TOP}/covers/book/{if $result.cover == 1}{$result.bookinfoid}.jpg{else}no-cover.jpg{/if}"
																 width="140" border="0"
																 alt="{$result.author|escape:"htmlall"} - {$result.title|escape:"htmlall"}"/>
														</a>
														{if isset($resulturl) && $result.url != ""}<a
															class="label label-default" target="_blank"
															href="{$site->dereferrer_link}{$result.url}"
															name="amazon{$result.bookinfoid}" title="View amazon page">
																Amazon</a>{/if}
														{if isset($result.nfoid) && $result.nfoid > 0}<a
															href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
															title="View Nfo" class="label label-default" rel="nfo">
																NFO</a>{/if}
														<a class="label label-default"
														   href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}"
														   title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}">Group</a>
													</div>
													<div class="col-md-10 no-gutter">
														<h4><a title="View details"
															   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$result.author|escape:"htmlall"}
																- {$result.title|escape:"htmlall"}</a></h4>
														<table>
															<tr>
																<td id="guid{$mguid[$m@index]}">
																	<label>
																		<input type="checkbox"
																			   class="nzb_check"
																			   value="{$mguid[$m@index]}"
																			   id="chksingle"/>
																	</label>
																	<span class="label label-default">{$msize[$m@index]|fsize_format:"MB"}</span>
																<span class="label label-default">Posted {$mpostdate[$m@index]|timeago}
																	ago</span>
																	<br/>
																	{if $result.review != ""}<span class="descinitial">{$result.review|escape:"htmlall"|nl2br|magicurl|truncate:350}</span>{if $result.review|strlen > 350}<a class="descmore" href="#">more...</a><span class="descfull">{$result.review|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}<br /><br />{/if}
																	{if $result.publisher != ""}
																		<b>Publisher:</b>
																		{$result.publisher|escape:"htmlall"}
																		<br/>
																	{/if}
																	{if $result.publishdate != ""}
																		<b>Published:</b>
																		{$result.publishdate|date_format}
																		<br/>
																	{/if}
																	{if $result.pages != ""}
																		<b>Pages:</b>
																		{$result.pages}
																		<br/>
																	{/if}
																	{if $result.isbn != ""}
																		<b>ISBN:</b>
																		{$result.isbn}
																		<br/>
																	{/if}
																	<div>
																		<a role="button" class="btn btn-default btn-xs"
																		   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"><i
																					class="fa fa-download"></i><span
																					class="badge">{$mgrabs[$m@index]}
																				Grab{if $mgrabs[$m@index] != 1}s{/if}</span></a>
																		<a role="button" class="btn btn-default btn-xs"
																		   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}#comments"><i
																					class="fa fa-comment-o"></i><span
																					class="badge">{$mcomments[$m@index]}
																				Comment{if $mcomments[$m@index] != 1}s{/if}</span></a>
																		<span class="btn btn-hover btn-default btn-xs icon icon_cart text-muted"
																			  title="Add to Cart"><i
																					class="fa fa-shopping-cart"></i></span>
																		{if isset($sabintegrated)}
																			<span class="btn btn-hover btn-default btn-xs icon icon_sab text-muted"
																				  title="Send to my Queue"><i
																						class="fa fa-send"></i></span>
																		{/if}
																	</div>
																</td>
															</tr>
														</table>
													</div>
												</div>
											</div>
										</div>
									{/foreach}
								{/foreach}
								<div class="row">
									<div class="col-md-8">
										<form id="nzb_multi_operations_form" action="get">
											<div class="nzb_multi_operations">
												View: <strong>Covers</strong> | <a
														href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
												Check all: <input type="checkbox" class="nntmux_check_all"/> <br/>
												With Selected:
												<div class="btn-group">
													<input type="button"
														   class="nntmux_multi_operations_download btn btn-sm btn-success"
														   value="Download NZBs"/>
													<input type="button"
														   class="nntmux_multi_operations_cart btn btn-sm btn-info"
														   value="Add to Cart"/>
													{if isset($sabintegrated)}
														<input type="button"
															   class="nntmux_multi_operations_sab btn btn-sm btn-primary"
															   value="Send to Queue"/>
													{/if}
													{if isset($nzbgetintegrated)}
														<input type="button"
															   class="nntmux_multi_operations_nzbget btn btn-sm btn-primary"
															   value="Send to NZBGet"/>
													{/if}
													{if isset($isadmin)}
														<input type="button"
															   class="nntmux_multi_operations_delete btn btn-sm btn-danger"
															   value="Delete"/>
													{/if}
												</div>
											</div>
									</div>
									<div class="col-md-4">
										{$pager}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>