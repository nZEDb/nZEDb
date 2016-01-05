<div class="header" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html"
	 xmlns="http://www.w3.org/1999/html">
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ {$catname|escape:"htmlall"}
		</ol>
	</div>
</div>
<div class="well well-sm">
	<form class="form-inline" role="form" name="browseby" action="books">
		<div class="form-group form-group-sm">
			<label class="sr-only" for="title">Title:</label>
			<input type="text" class="form-control" id="title" name="title" value="{$title}" placeholder="Title">
		</div>
		<div class="form-group form-group-sm">
			<label class="sr-only" for="author">Author:</label>
			<input type="text" class="form-control" id="author" name="author" value="{$author}" placeholder="Author">
		</div>
		<input type="submit" class="btn btn-primary" value="Search!"/>
	</form>
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
													   value="Send to my Download Basket"/>
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
									{assign var="mfailed" value=","|explode:$result.grp_release_failed}
									{assign var="mpass" value=","|explode:$result.grp_release_password}
									{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
									{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
									{foreach from=$msplits item=m}
										<div class="panel panel-default">
											<div class="panel-body">
												<div class="row">
													<div class="col-md-2 small-gutter-left">
														<a title="View details"
														   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">
															<img src="{$smarty.const.WWW_TOP}/covers/book/{if $result.cover == 1}{$result.bookinfoid}.jpg{else}{$smarty.const.WWW_TOP}/themes_shared/images/no-cover.png{/if}"
																 width="140" border="0"
																 alt="{$result.author|escape:"htmlall"} - {$result.title|escape:"htmlall"}"/>{if $mfailed[$m@index] > 0} <i class="fa fa-exclamation-circle" style="color: red" title="This release has failed to download for some users"></i>{/if}
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
														{if $mfailed[$m@index] > 0}
														<span class="btn btn-hover btn-default btn-xs"><i class="fa fa-thumbs-o-down"></i><span
																	class="badge"> {$mfailed[$m@index]}
																Failed Download{if $mfailed[$m@index] > 1}s{/if}</span>
															{/if}
													</div>
													<div class="col-md-10 small-gutter-left">
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
																		   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}|escape:"htmlall"}"><i
																				class="fa fa-cloud-download"></i><span
																				class="badge">{$mgrabs[$m@index]}
																			Grab{if $mgrabs[$m@index] != 1}s{/if}</span></a>
																		<a role="button" class="btn btn-default btn-xs"
																		   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}#comments"><i
																					class="fa fa-comment-o"></i><span
																					class="badge">{$mcomments[$m@index]}
																				Comment{if $mcomments[$m@index] != 1}s{/if}</span></a>
																		<span class="btn btn-hover btn-default btn-xs icon icon_cart text-muted"
																			  title="Send to my Download Basket"><i
																					class="fa fa-shopping-basket"></i></span>
																		{if isset($sabintegrated)}
																			<span class="btn btn-hover btn-default btn-xs icon icon_sab text-muted"
																				  title="Send to my Queue"><i
																						class="fa fa-share"></i></span>
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
														   value="Send to my Download Basket"/>
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
