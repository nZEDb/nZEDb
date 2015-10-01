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
															<tr class="bg-aqua-active">
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
															</tbody>
														</table>
														<table class="data table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-aqua-active">
																<td colspan="2" style="padding-left: 8px;"><strong>API &
																		Downloads</strong></td>
															</tr>
															<tr>
																<th>API Hits last 24 hours</th>
																<td>
																	<span id="uatd">{$apirequests}</span> {if $isadmin && $apirequests > 0}
																	<a
																			onclick="resetapireq({$user.id}, 'api'); document.getElementById('uatd').innerHTML='0'; return false;"
																			href="#" class="label label-danger">
																			Reset</a>{/if}</td>
															</tr>
															<tr>
																<th>Downloads last 24 hours</th>
																<td><span id="ugrtd">{$grabstoday}</span> /
																	Unlimited {if $user.grabs >= $user.downloadrequests}&nbsp;&nbsp;
																		<small>(Next DL
																		in {($grabstoday.nextdl/3600)|intval}
																		h {($grabstoday.nextdl/60) % 60}
																		m)</small>{/if}{if $isadmin && $grabstoday > 0}
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
																		   class="label label-danger">GENERATE NEW
																			KEY</a>
																	</td>
																</tr>
															{/if}
															</tbody>
														</table>
														{if ($user.id==$userdata.id || $isadmin) && $site->registerstatus==1}
															<table class="data table table-condensed table-striped table-responsive table-hover">
																<tbody>
																<tr class="bg-aqua-active">
																	<td colspan="2" style="padding-left: 8px;"><strong>Invites</strong>
																	</td>
																</tr>
																<tr>
																<tr>
																	<th title="Not public">Send Invite:</th>
																	<td>{$user.invites}
																		{if $user.invites > 0}
																			[
																			<a id="lnkSendInvite"
																			   onclick="return false;" href="#">Send
																				Invite</a>
																			]
																			<span title="Your invites will be reduced when the invitation is claimed."
																				  class="invitesuccess"
																				  id="divInviteSuccess">Invite Sent</span>
																			<span class="invitefailed"
																				  id="divInviteError"></span>
																			<div style="display:none;" id="divInvite">
																				<form id="frmSendInvite" method="GET">
																					<label for="txtInvite">Email</label>:
																					<input type="text" id="txtInvite"/>
																					<input type="submit" value="Send"/>
																				</form>
																			</div>
																		{/if}
																	</td>
																</tr>
																{if $userinvitedby && $userinvitedby.username != ""}
																<tr>
																	<th width="200">Invited By</th>
																	{if $privileged || !$privateprofiles}
																		<td>
																			<a title="View {$userinvitedby.username}'s profile"
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
														{/if}
													</td>
												</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								{if $isadmin || !$publicview}
									<a class="btn btn-primary" href="{$smarty.const.WWW_TOP}profileedit">Edit
										Profile</a>
								{/if}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>