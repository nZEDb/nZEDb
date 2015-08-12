<div class="header">
	<h2>Profile > <strong>{$user.username|escape:"htmlall"}</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Profile / {$user.username|escape:"htmlall"}
		</ol>
	</div>
</div>
<div class="row">
	<div class="box col-md-12">
		<div class="box-content">
			<div class="row">
				<div class="col-lg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							<div class="panel-body">
								<ul class="nav nav-tabs nav-primary">
									<li class="active"><a href="#tab2_1" data-toggle="tab"><i class="fa fa-user"></i>
											Main</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade active in" id="tab2_1">
										<div id="tab-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
											<table cellpadding="0" cellspacing="0" width="100%">
												<tbody>
												<tr valign="top">
													<td>
														<table class="table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-primary">
																<td colspan="2" style="padding-left: 8px;"><strong>General</strong>
																</td>
															</tr>
															<tr>
																<th width="200">Username</th>
																<td>{$user.username|escape:"htmlall"}</td>
															</tr>
															{if $isadmin || !$publicview}
															<tr>
																<th width="200" title="Not public">E-mail</th>
																<td>{$user.email}</td>
															</tr>
															{/if}
															<tr>
																<th width="200">Registered</th>
																<td>{$user.createddate|date_format}
																	({$user.createddate|timeago} ago)
																</td>
															</tr>
															<tr>
																<th width="200">Last Login</th>
																<td>{$user.lastlogin|date_format}
																	({$user.lastlogin|timeago} ago)
																</td>
															</tr>
															<tr>
																<th width="200">Role</th>
																<td>{$user.rolename}</td>
															</tr>
															{if $userinvitedby && $userinvitedby.username != ""}
															<tr>
																<th width="200">Invited By</th>
																{if $privileged || !$privateprofiles}
																<td><a title="View {$userinvitedby.username}'s profile"
																	   href="{$smarty.const.WWW_TOP}/profile?name={$userinvitedby.username}">{$userinvitedby.username}</a>
																</td>
																{else}
																	<td>
																	{$userinvitedby.username}
																	</td>
																	{/if}
																{/if}
															</tr>
															</tbody>
														</table>
														<table class="data table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-primary">
																<td colspan="2" style="padding-left: 8px;"><strong>API &
																		Downloads</strong></td>
															</tr>
															<tr>
																<th>API Hits Today</th>
																<td>
																	<span id="uatd">{$apihits.num}</span>  {if $userdata.role==2 && $apihits.num > 0}
																		<a
																		onclick="resetapireq({$user.id}, 'api'); document.getElementById('uatd').innerHTML='0'; return false;"
																		href="#" class="label label-danger">
																			Reset</a>{/if}</td>
															</tr>
															<tr>
																<th>Downloads Today</th>
																<td><span id="ugrtd">{$grabstoday.num}</span> /
																	Unlimited {if $grabstoday.num >= $user.downloadrequests}&nbsp;&nbsp;
																		<small>(Next DL
																		in {($grabstoday.nextdl/3600)|intval}
																		h {($grabstoday.nextdl/60) % 60}
																		m)</small>{/if}{if $userdata.role==2 && $grabstoday.num > 0}
																		<a
																		onclick="resetapireq({$user.id}, 'grabs'); document.getElementById('ugrtd').innerHTML='0'; return false;"
																		href="#" class="label label-danger">
																			Reset</a>{/if}</td>
															</tr>
															<tr>
																<th>Downloads Total</th>
																<td>{$user.grabs}</td>
															</tr>
															{if $isadmin || !$publicview}
															<tr>
																<th title="Not public">API/RSS Key</th>
																<td>
																	<a href="{$smarty.const.WWW_TOP}rss?t=0&amp;dl=1&amp;i={$user.id}&amp;r={$user.rsstoken}">{$user.rsstoken}</a>
																	<a href="{$smarty.const.WWW_TOP}profileedit?action=newapikey"
																	   class="label label-danger">GENERATE NEW KEY</a>
																</td>
															</tr>
															{/if}
															</tbody>
														</table>
													</td>
												</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								<a class="btn btn-primary" href="{$smarty.const.WWW_TOP}profileedit">Edit Profile</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>