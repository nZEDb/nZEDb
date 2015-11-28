<div class="header">
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ {$catname|escape:"htmlall"}
		</ol>
	</div>
</div>
<div class="well well-sm">
	<form class="form-inline" role="form" name="browseby" action="console">
		<div class="form-group form-group-sm">
			<label class="sr-only" for="title">Title:</label>
			<input type="text" class="form-control" id="title" name="title" value="{$title}" placeholder="Title">
		</div>
		<div class="form-group form-group-sm">
			<label class="sr-only" for="platform">Platform:</label>
			<input type="text" class="form-control" id="platform" name="platform" value="{$platform}" placeholder="Platform">
		</div>
		<div class="form-group form-group-sm">
			<label class="sr-only" for="genre">Genre:</label>
			<select id="genre" name="genre" class="form-control">
				<option class="grouping" value="" selected>Genre</option>
				{foreach from=$genres item=gen}
					<option {if $gen.id == $genre}selected="selected"{/if} value="{$gen.id}">{$gen.title|escape:"htmlall"}</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group form-group-sm">
			<label class="sr-only" for="category">Category:</label>
			<select id="category" name="t" class="form-control">
				<option class="grouping" value="" selected>Category</option>
				{foreach from=$catlist item=ct}
					<option {if $ct.id==$category}selected="selected"{/if} value="{$ct.id}">{$ct.title}</option>
				{/foreach}
			</select>
		</div>
		<input type="submit" class="btn btn-primary" value="Search!"/>
	</form>
</div>
<form id="nzb_multi_operations_form" action="get">
	<div class="box-body"
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
						{assign var="mfailed" value=","|explode:$result.failed}
						{assign var="mpass" value=","|explode:$result.grp_release_password}
						{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
						{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
						{foreach from=$msplits item=m name=loop}
							{if $smarty.foreach.loop.first}
								<div class="panel panel-default">
									<div class="panel-body">
										<div class="row">
											<div class="col-md-2 small-gutter-left">
												<a title="View details"
												   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">
													<img src="{$smarty.const.WWW_TOP}/covers/console/{if $result.cover == 1}{$result.consoleinfoid}.jpg{else}no-cover.jpg{/if}"
														 width="140" border="0"
														 alt="{$result.title|escape:"htmlall"}"/>{if $mfailed[$m@index] > 0} <i class="fa fa-exclamation-circle" style="color: red" title="This release has failed to download for some users"></i>{/if}
												</a>
												{if $result.url != ""}<a class="label label-default"
																		 target="_blank"
																		 href="{$site->dereferrer_link}{$result.url}"
																		 name="amazon{$result.consoleinfoid}"
																		 title="View Amazon page">
														Amazon</a>{/if}
												{if $result.nfoid > 0}<a
													href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}|escape:"htmlall"}"
													title="View NFO" class="label label-default" rel="nfo">
														NFO</a>{/if}
												<a class="label label-default"
												   href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}"
												   title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Group</a>
												{if $mfailed[$m@index] > 0}
													<span class="btn btn-default btn-xs" title="This release has failed to download for some users">
														<i class ="fa fa-thumbs-o-up"></i> {$mgrabs[$m@index]} Grab{if {$mgrabs[$m@index]} != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$mfailed[$m@index]} Failed Download{if {$mfailed[$m@index]} != 1}s{/if}</span>												{/if}
											</div>
											<div class="col-md-10 small-gutter-left">
												<h4><a title="View details"
													   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$result.title|escape:"htmlall"}</a>
												</h4>
												<table>
													<tr>
														<td id="guid{$mguid[$m@index]}">
															<label>
																<input type="checkbox"
																	   class="nzb_check"
																	   value="{$mguid[$m@index]}" id="chksingle"/>
															</label>
															<span class="label label-default">{$msize[$m@index]|fsize_format:"MB"}</span>
																	<span class="label label-default">Posted {$mpostdate[$m@index]|timeago}
																		ago</span>
															{if isset($isadmin)}<a class="label label-warning"
																				   href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.grp_release_id}&amp;from={$smarty.server.REQUEST_URI}"
																				   title="Edit release">
																	Edit</a>{/if}
															<br/>
															{if isset($result.genre) && $result.genre != ""}
																<b>Genre:</b>
																{$result.genre}
																<br/>
															{/if}
															{if isset($result.esrb) && $result.esrb != ""}
																<b>Rating:</b>
																{$result.esrb}
																<br/>
															{/if}
															{if isset($result.publisger) && $result.publisher != ""}
																<b>Publisher:</b>
																{$result.publisher}
																<br/>
															{/if}
															{if isset($result.releasedate) && $result.releasedate != ""}
																<b>Released:</b>
																{$result.releasedate|date_format}
																<br/>
															{/if}
															{if isset($result.review) && $result.review != ""}
																<b>Review:</b>
																{$result.review|escape:'htmlall'}
																<br/>
															{/if}
															<div>
																<a role="button" class="btn btn-default btn-xs"
																   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}|escape:"htmlall"}"><i
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
							{/if}
						{/foreach}
					{/foreach}
					<hr>
					<div class="row">
						<div class="col-md-8">
							<div class="nzb_multi_operations">
								View: <strong>Covers</strong> | <a
										href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
								Check all: <input type="checkbox" class="nntmux_check_all"/> <br/>
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
				</div>
			</div>
		</div>
	</div>
</form>
