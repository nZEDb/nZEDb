<div class="header">
	<h2>Edit Profile > <strong>{$user.username|escape:"htmlall"}</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}">Home</a></li>
			/ Profile / {$user.username|escape:"htmlall"}
		</ol>
	</div>
</div>
<div class="row">
	<div class="box col-md-12">
		<div class="box-content">
			<div class="row">
				<div class="col-xlg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							{if $error != ''}
								<div class="alert alert-danger">{$error}</div>
							{/if}
							<ul class="nav nav-tabs nav-primary">
								<li class="active"><a href="#tab2_1" data-toggle="tab"><i class="fa fa-cogs fa-spin"></i>
										Settings</a></li>
								<li><a href="#tab2_3" data-toggle="tab"><i class="fa fa-download"></i> Downloaders</a>
								</li>
							</ul>
							<form action="profileedit?action=submit" method="post">
								<div class="tab-content">
									<div class="tab-pane fade active in" id="tab2_1">
										<table cellpadding="0" cellspacing="0" width="100%">
											<tbody>
											<tr valign="top">
												<td>
													<table class="data table table-condensed table-striped table-responsive">
														<tbody>
														<tr class="bg-primary">
															<td colspan="2" style="padding-left: 8px;">
																<strong>Profile</strong></td>
														</tr>
														<tr>
															<th width="200">E-Mail</th>
															<td><input id="email" class="form-control" name="email"
																	   type="text"
																	   value="{$user.email|escape:"htmlall"}"></td>
														</tr>
														<tr>
															<th width="200">Password</th>
															<td>
																<input autocomplete="off" id="password" name="password"
																	   type="password" class="form-control" value="">
																<div class="hint">Only enter your password if you want
																	to change it.
																</div>
															</td>
														</tr>
														<tr>
															<th width="200">Confirm password</th>
															<td>
																<input autocomplete="off" id="confirmpassword"
																	   name="confirmpassword" type="password"
																	   class="form-control" value="">
															</td>
														</tr>
														<tr>
															<th width="200">API Key</th>
															<td>
																{$user.rsstoken}
															</td>
														</tr>
														</tbody>
													</table>
													<table class="data table table-condensed table-striped table-responsive">
														<tbody>
														<tr class="bg-primary">
															<td colspan="2" style="padding-left: 8px;"><strong>Excluded
																	Categories</strong></td>
														</tr>
														<tr>
															<th width="200">Excluded Categories</th>
															<td>
																{html_options style="height:105px;" class="form-control" data-placeholder="Choose categories to exclude" multiple=multiple name="exccat[]" options=$catlist selected=$userexccat}
															</td>
														</tr>
														</tbody>
													</table>
													<table class="data table table-condensed table-striped table-responsive">
														<tbody>
														<tr class="bg-primary">
															<td colspan="2" style="padding-left: 8px;"><strong>UI
																	Preferences</strong></td>
														</tr>
														<tr>
															<th width="200">Movie Page</th>
															<td><input type="checkbox" name="movieview"
																	   class="onoffswitch-checkbox" id="movieview"
																	   {if $user.movieview=="1"}checked{/if}> Browse
																movie covers. Only shows movies with known IMDB info.
															</td>
														</tr>
														<tr>
															<th width="200">Music Page</th>
															<td><input type="checkbox" name="musicview"
																	   class="onoffswitch-checkbox" id="musicview"
																	   {if $user.musicview=="1"}checked{/if}> Browse
																music covers. Only shows music with known lookup info.
															</td>
														</tr>
														<tr>
															<th width="200">Console Page</th>
															<td><input type="checkbox" name="consoleview"
																	   class="onoffswitch-checkbox" id="consoleview"
																	   {if $user.consoleview=="1"}checked{/if}> Browse
																console covers. Only shows games with known lookup info.
															</td>
														</tr>
														<tr>
															<th width="200">Games Page</th>
															<td><input type="checkbox" name="gameview"
																	   class="onoffswitch-checkbox" id="gameview"
																	   {if $user.gameview=="1"}checked{/if}> Browse game
																covers. Only shows games with known lookup info.
															</td>
														</tr>
														<tr>
															<th width="200">Book Page</th>
															<td><input type="checkbox" name="bookview"
																	   class="onoffswitch-checkbox" id="bookview"
																	   {if $user.bookview=="1"}checked{/if}> Browse book
																covers. Only shows books with known lookup info.
															</td>
														</tr>
														<tr>
															<th width="200">XXX Page</th>
															<td><input type="checkbox" name="xxxview"
																	   class="onoffswitch-checkbox" id="xxxview"
																	   {if $user.xxxview=="1"}checked{/if}> Browse XXX
																covers. Only shows XXX releases with known lookup info.
															</td>
														</tr>
														</tbody>
													</table>
												</td>
											</tr>
											</tbody>
										</table>
									</div>
									<div class="tab-pane fade" id="tab2_3">
										<table cellpadding="0" cellspacing="0" width="100%">
											<tbody>
											<tr valign="top">
												<td>
													These settings are only needed if you want to be able to push NZB's
													to your downloader straight from the website. You don't need this
													for automation software like Sonarr, Sickbeard and Couchpotato to
													function.
													<br/>
													{if $page->settings->getSetting('sabintegrationtype') != 1}
														<table class="data table table-condensed table-striped table-responsive">
															<tbody>
															<tr class="bg-primary">
																<td colspan="2" style="padding-left: 8px;"><strong>Queue
																		type
																		<small>(NZBGet or SABnzbd)</small>
																	</strong></td>
															</tr>
															<tr>
																<th width="200">Select type</th>
																<td>
																	{html_options id="queuetypeids" name='queuetypeids' values=$queuetypeids output=$queuetypes selected=$user.queuetype}
																	<span class="help-block">Pick the type of queue you wish to use, once you save your profile, the page will reload, the box will appear and you can fill out the details.</span>
																</td>
															</tr>
															</tbody>
														</table>
													{/if}
													{if $user.queuetype == 1 && $page->settings->getSetting('sabintegrationtype') == 2}
														<table class="data table table-condensed table-striped table-responsive">
															<tbody>
															<tr class="bg-primary">
																<td colspan="2" style="padding-left: 8px;"><strong>SABnzbd</strong>
																</td>
															</tr>
															<tr>
																<th width="200">URL</th>
																<td><input id="saburl" class="form-control"
																		   name="saburl" type="text"
																		   placeholder="SABNZBd URL"
																		   value="{$saburl_selected}"></td>
															</tr>
															<tr>
																<th width="200">API Key</th>
																<td><input id="sabapikey" class="form-control"
																		   name="sabapikey" type="text"
																		   placeholder="SABNZbd API Key"
																		   value="{$sabapikey_selected}"></td>
															</tr>
															<tr>
																<th width="200">API Key Type</th>
																<td>
																	{html_radios id="sabapikeytype" name='sabapikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$sabapikeytype_selected separator='<br />'}
																	<div class="hint">
																		Select the type of api key you entered in the
																		above setting. Using your full SAB api key will
																		allow you access to the SAB queue from within
																		this site.
																	</div>
																</td>
															</tr>
															<tr>
																<th width="200">Priority</th>
																<td>{html_options id="sabpriority" name='sabpriority' values=$sabpriority_ids output=$sabpriority_names selected=$sabpriority_selected}</td>
															</tr>
															<tr>
																<th width="200">Storage</th>
																<td>{html_radios id="sabsetting" name='sabsetting' values=$sabsetting_ids output=$sabsetting_names selected=$sabsetting_selected separator='&nbsp;&nbsp;'}{if $sabsetting_selected == 2}&nbsp;&nbsp;[
																		<a class="confirm_action"
																		   href="?action=clearcookies">Clear Cookies</a>
																		]{/if}
																	<div class="hint">Where to store the SAB
																		setting.<br/>&bull; <b>Cookie</b> will store the
																		setting in your browsers coookies and will only
																		work when using your current browser.<br/>&bull;
																		<b>Site</b> will store the setting in your user
																		account enabling it to work no matter where you
																		are logged in from.<br/><span
																				class="warning"><b>Please
																				Note:</b></span> You should only store
																		your full SAB api key with sites you trust.
																	</div>
																</td>
															</tr>
															</tbody>
														</table>
													{/if}
													{if $user.queuetype == 2 && ($page->settings->getSetting('sabintegrationtype') == 0 || $page->settings->getSetting('sabintegrationtype') == 2)}
														<table class="data table table-condensed table-striped table-responsive">
															<tbody>
															<tr class="bg-primary">
																<td colspan="2" style="padding-left: 8px;"><strong>NZBget</strong>
																</td>
															</tr>
															<tr>
																<th width="200">URL</th>
																<td><input id="nzbgeturl" placeholder="NZBGet URL"
																		   class="form-control" name="nzbgeturl"
																		   type="text" value="{$user.nzbgeturl}"/></td>
															</tr>
															<tr>
																<th width="200">Username / Password</th>
																<td>
																	<div class="form-inline">
																		<input id="nzbgetusername"
																			   placeholder="NZBGet Username"
																			   class="form-control"
																			   name="nzbgetusername" type="text"
																			   value="{$user.nzbgetusername}"/>
																		/
																		<input id="nzbgetpassword"
																			   placeholder="NZBGet Password"
																			   class="form-control"
																			   name="nzbgetpassword" type="text"
																			   value="{$user.nzbgetpassword}"/>
																	</div>
																</td>
															</tr>
															</tbody>
														</table>
													{/if}
													<br/>
												</td>
											</tr>
											</tbody>
										</table>
									</div>
								</div>
								<table class="data table table-condensed table-striped table-responsive">
									<tbody>
									<tr class="bg-primary">
										<td colspan="2" style="padding-left: 8px;"><strong>Couchpotato</strong>
										</td>
									</tr>
									<tr>
										<th width="200">API / URL</th>
										<td>
											<div class="form-inline">
												<input id="cp_api"
													   placeholder="Couchpotato API key"
													   class="form-control"
													   name="cp_api" type="text"
													   value="{$cp_api_selected}"/>
												/
												<input id="cp_url"
													   placeholder="Couchpotato URL"
													   class="form-control"
													   name="cp_url" type="text"
													   value="{$cp_url_selected}"/>
											</div>
										</td>
									</tr>
									</tbody>
								</table>
								<table class="data table table-condensed table-striped table-responsive">
									<tbody>
									<tr class="bg-primary">
										<td colspan="2" style="padding-left: 8px;"><strong>Site theme</strong>
											<div>
												{html_options id="style" name='style' values=$themelist output=$themelist selected=$user.style}
											</div>
										</td>
									</tr>
									</tbody>
								</table>
								<input type="submit" value="Save" class="btn btn-primary"/>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>