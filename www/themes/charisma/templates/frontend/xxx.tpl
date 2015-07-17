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
		<div class="col-xlg-12 portlets">
			<div class="panel">
				<div class="panel-content pagination2">
					<div class="row">
						<div class="col-md-8">
							<div class="nzb_multi_operations">
								View: <strong>Covers</strong> | <a
										href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
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
					{foreach $results as $result}
						<!-- Iteratie: {counter} -->
						{if isset($result.category_name)}
							{assign var="catnamesplit" value=">"|explode:$result.category_name}
						{/if}
						{if $result@iteration is odd by 1}
							<!-- Begin Row -->
							<div class="row">
								<!-- Left -->
								<div class="col-md-6 small-gutter-right movie-height">
									<div class="panel">
										<div class="panel-content">
											<div class="row no-gutter">
												<div class="col-md-3 no-gutter">
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
													{foreach from=$msplits item=m name=loop}
													{if $smarty.foreach.loop.first}

													<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"><img
																class="cover"
																src="{if $result.cover == 1}{$serverroot}covers/xxx/{$result.id}-cover.jpg{else}{$serverroot}themes/omicron/images/nocover.png{/if}"
																width="100" border="0"
																alt="{$result.title|escape:"htmlall"}"/></a>
													{if $result.classused == "ade"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewade{$result.title}"
																title="View AdultdvdEmpire page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/ade.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.adultdvdempire.com/dvd/search?q={$result.title}"
																name="viewade{$result.title}"
																title="Search AdultdvdEmpire page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/ade.png"></a>
													{/if}
													{if $result.classused == "hm"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewhm{$result.title}"
																title="View Hot Movies page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/hotmovies.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.hotmovies.com/search.php?words={$result.title}&complete=on&search_in=video_title"
																name="viewhm{$result.title}"
																title="Search Hot Movies page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/hotmovies.png"></a>
													{/if}
													{if $result.classused == "pop"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewpop{$result.id}"
																title="View Popporn page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/popporn.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.popporn.com/results/index.cfm?v=4&g=0&searchtext={$result.title}"
																name="viewpop{$result.id}"
																title="Search Popporn page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/popporn.png"></a>
													{/if}
													<a
															target="_blank"
															href="{$site->dereferrer_link}http://www.iafd.com/results.asp?searchtype=title&searchstring={$result.title}"
															name="viewiafd{$result.title}"
															title="Search Internet Adult Film Database"
															><img
																src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/iafd.png"></a>
													{if $mnfo[$m@index] > 0}<a
														href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"
														title="View NFO" class="label label-default"
														rel="nfo">NFO</a>{/if}
													<a class="label label-default"
													   href="{$smarty.const.WWW_TOP}/browse?g={$result.grp_release_grpname}"
													   title="Browse releases in {$result.grp_release_grpname|replace:"alt.binaries":"a.b"}">Group</a>
												</div>
												<div class="col-md-9 no-gutter">
																<span class="release-title"><a class="text-muted"
																							   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}">{$result.title|escape:"htmlall"}</a></span>

													<div class="release-subtitle">{if $result.genre != ''}{$result.genre}, {/if}</div>
													<div>
														<span class="label label-primary">{if isset($catsplit[0])} {$catsplit[0]}{/if}</span>
														<span class="label label-danger">{if isset($catsplit[1])} {$catsplit[1]}{/if}</span>
														<span class="label label-default">{if isset($result.year)} {$result.year}{/if}</span>

														<span class="label label-default">{$msize[$m@index]|fsize_format:"MB"}</span>
																	<span class="label label-default">Posted {$mpostdate[$m@index]|timeago}
																		ago</span>
														<br/><br/><br/>

														<div class="release-name text-muted"><a
																	href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}">{$mname[$m@index]|escape:"htmlall"}</a>
														</div>
														<div>
															<a role="button" class="btn btn-default btn-xs"
															   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"><i
																		class="fa fa-download"></i><span
																		class="badge"> {$mgrabs[$m@index]}
																	Grab{if $mgrabs[$m@index] != 1}s{/if}</span></a>
															<a role="button" class="btn btn-default btn-xs"
															   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments"><i
																		class="fa fa-comment-o"></i><span
																		class="badge"> {$mcomments[$m@index]}
																	Comment{if $mcomments[$m@index] != 1}s{/if}</span></a>
														</div>
														{/if}
														{/foreach}
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<!-- /Left -->
								{else}
								<!-- Right -->
								<div class="col-md-6 small-gutter-left movie-height">
									<div class="panel">
										<div class="panel-content">
											<div class="row no-gutter">
												<div class="col-md-3 no-gutter">
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
													{foreach from=$msplits item=m name=loop}
													{if $smarty.foreach.loop.first}
													<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"><img
																class="cover"
																src="{if $result.cover == 1}{$serverroot}covers/xxx/{$result.id}-cover.jpg{else}{$serverroot}themes/omicron/images/nocover.png{/if}"
																width="100" border="0"
																alt="{$result.title|escape:"htmlall"}"/></a>
													{if $result.classused == "ade"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewade{$result.title}"
																title="View AdultdvdEmpire page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/ade.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.adultdvdempire.com/dvd/search?q={$result.title}"
																name="viewade{$result.title}"
																title="Search AdultdvdEmpire page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/ade.png"></a>
													{/if}
													{if $result.classused == "hm"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewhm{$result.title}"
																title="View Hot Movies page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/hotmovies.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.hotmovies.com/search.php?words={$result.title}&complete=on&search_in=video_title"
																name="viewhm{$result.title}"
																title="Search Hot Movies page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/hotmovies.png"></a>
													{/if}
													{if $result.classused == "pop"}
														<a
																target="_blank"
																href="{$site->dereferrer_link}{$result.directurl}"
																name="viewpop{$result.id}"
																title="View Popporn page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/popporn.png"></a>
													{else}
														<a
																target="_blank"
																href="{$site->dereferrer_link}http://www.popporn.com/results/index.cfm?v=4&g=0&searchtext={$result.title}"
																name="viewpop{$result.id}"
																title="Search Popporn page"
																><img
																	src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/popporn.png"></a>
													{/if}
													<a
															target="_blank"
															href="{$site->dereferrer_link}http://www.iafd.com/results.asp?searchtype=title&searchstring={$result.title}"
															name="viewiafd{$result.title}"
															title="Search Internet Adult Film Database"
															><img
																src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/iafd.png"></a>
													{if $mnfo[$m@index] > 0}<a
														href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"
														title="View NFO" class="label label-default"
														rel="nfo">NFO</a>{/if}
													<a class="label label-default"
													   href="{$smarty.const.WWW_TOP}/browse?g={$result.grp_release_grpname}"
													   title="Browse releases in {$result.grp_release_grpname|replace:"alt.binaries":"a.b"}">Group</a>
												</div>
												<div class="col-md-9 no-gutter">
																<span class="release-title"><a class="text-muted"
																							   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}">{$result.title|escape:"htmlall"}</a></span>

													<div class="release-subtitle">{if $result.genre != ''}{$result.genre}, {/if}</div>
													<div>
														<span class="label label-primary">{if isset($catsplit[0])} {$catsplit[0]}{/if}</span>
														<span class="label label-danger">{if isset($catsplit[1])} {$catsplit[1]}{/if}</span>
														<span class="label label-default">{if isset($result.year)} {$result.year}{/if}</span>

														<span class="label label-default">{$msize[$m@index]|fsize_format:"MB"}</span>
																	<span class="label label-default">Posted {$mpostdate[$m@index]|timeago}
																		ago</span>
														<br/><br/><br/>

														<div class="release-name text-muted"><a
																	href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$mname[$m@index]|escape:"htmlall"}</a>
														</div>
														<div>
															<a role="button" class="btn btn-default btn-xs"
															   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"><i
																		class="fa fa-download"></i><span
																		class="badge"> {$mgrabs[$m@index]}
																	Grab{if $mgrabs[$m@index] != 1}s{/if}</span></a>
															<a role="button" class="btn btn-default btn-xs"
															   href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments"><i
																		class="fa fa-comment-o"></i><span
																		class="badge"> {$mcomments[$m@index]}
																	Comment{if $mcomments[$m@index] != 1}s{/if}</span></a>
														</div>
														{/if}
														{/foreach}
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<!-- /Right -->
							</div>
							<hr>
							<!-- End Row -->
						{/if}
					{/foreach}
					<div class="row">
						<div class="col-md-8">
							<div class="nzb_multi_operations">
								View: <strong>Covers</strong> | <a
										href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
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