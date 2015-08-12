{if $site->adbrowse != ''}
	{$site->adbrowse}
{/if}
<h1>{$page->title}</h1>
<form name="predbsearch" method="get" action="{$smarty.const.WWW_TOP}/predb" id="custom-search-form"
	  class="form-search form-horizontal col-4 col-lg-4 pull-right">
	<div id="search" class="input-group col-12 col-lg-12">
		<input type="text" class="form-control" placeholder="Search predb" id="predbsearch" name="predbsearch"
			   value="{$lastSearch|escape:'html'}">
		<span class="input-group-btn">
			<button type="submit" value="Go" class="btn btn-default">
				<i class="fa fa-search"></i>
			</button>
		</span>
	</div>
</form>
{$pager}
<div class="box-body">
	<div class="box-inner">
		<div class="row">
			<div class="col-xlg-12 portlets">
				<div class="panel panel-default">
					<div class="panel-body pagination2">
						<div class="row">
							<div class="col-md-8">
								<table class="data table table-condensed table-striped table-responsive table-hover">
									<tr>
										<th style="width: auto"> Date</th>
										<th style="width: auto"> Title</th>
										<th style="width: auto"> Category</th>
										<th style="width: auto"> Source</th>
										<th style="width: auto"> Reqid</th>
										<th style="width: auto"> Size</th>
										<th style="width: auto"> Files</th>
										<th></th>
										<th></th>
									</tr>
									{foreach from=$results item=result}
										<tr class="{cycle values=",alt"}">
											<td class="predb" style="text-align:center;">
												{$result.predate|date_format:"%Y-%m-%d %H:%M:%S"}
											</td>
											<td class="predb" style="text-align:center;">
												{if isset($result.guid)}
													<a style="font-style:italic;text-decoration:underline;color:#{if $result.nuked == 1}009933{elseif $result.nuked > 1}990000{/if};"
													   class="title" title="View details"
													   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
														<span title="{if $result.nuked == 1}UNNUKED: {$result.nukereason|escape:"htmlall"}{elseif $result.nuked > 1}NUKED: {$result.nukereason|escape:"htmlall"}{else}{$result.title|escape:"htmlall"}{/if}">{$result.title|escape:"htmlall"}</span>
													</a>
												{else}
													<span style="color:#{if $result.nuked == 1}009933{elseif $result.nuked > 1}990000{/if};"
														  title="{if $result.nuked == 1}UNNUKED: {$result.nukereason|escape:"htmlall"}{elseif $result.nuked > 1}NUKED: {$result.nukereason|escape:"htmlall"}{else}{$result.title|escape:"htmlall"}{/if}">{$result.title|escape:"htmlall"}</span>
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												{* Console *}
												{* Xbox 360 *}
												{if {$result.category} == 'XBOX360'}
													<a class="title" title="View category XBOX 360"
													   href="{$smarty.const.WWW_TOP}/browse?t=1050">Console
														Xbox
														360</a>
													{* Movies *}
												{elseif in_array({$result.category}, array('Movies'))}
													<a class="title" title="View category Movies"
													   href="{$smarty.const.WWW_TOP}/browse?t=2000">Movies</a>
													{* SD *}
												{elseif in_array({$result.category}, array('movies-sd', 'Movies: STD', 'XVid'))}
													<a class="title" title="View category Movies SD"
													   href="{$smarty.const.WWW_TOP}/browse?t=2030">Movies
														SD</a>
													{* HD *}
												{elseif in_array({$result.category}, array('Movies: HD', 'X264'))}
													<a class="title" title="View category Movies HD"
													   href="{$smarty.const.WWW_TOP}/browse?t=2040">Movies
														HD</a>
													{* BluRay *}
												{elseif in_array({$result.category}, array('BLURAY'))}
													<a class="title" title="View category BluRay"
													   href="{$smarty.const.WWW_TOP}/browse?t=2060">Movies
														BluRay</a>
													{* DVD *}
												{elseif in_array({$result.category}, array('DVDR', 'Movies: DVD'))}
													<a class="title" title="View category DVDR"
													   href="{$smarty.const.WWW_TOP}/browse?t=2070">DVD</a>
													{* Audio *}
												{elseif in_array({$result.category}, array('music-audio'))}
													<a class="title" title="View category Music"
													   href="{$smarty.const.WWW_TOP}/music">Audio</a>
													{* MP3 *}
												{elseif in_array({$result.category}, array('MP3', 'Music: MP3'))}
													<a class="title" title="View category MP3"
													   href="{$smarty.const.WWW_TOP}/browse?t=3010">MP3</a>
													{* Video *}
												{elseif in_array({$result.category}, array('MVid', 'Music: MVid'))}
													<a class="title" title="View category Audio Video"
													   href="{$smarty.const.WWW_TOP}/browse?t=3020">Music
														Videos</a>
													{* Audiobook *}
												{elseif in_array({$result.category}, array('audiobook', 'Audiobook'))}
													<a class="title" title="View category Audiobook"
													   href="{$smarty.const.WWW_TOP}/browse?t=3030">Audiobook</a>
													{* Lossless *}
												{elseif in_array({$result.category}, array('FLAC', 'Music: FLAC'))}
													<a class="title" title="View category Music Lossless"
													   href="{$smarty.const.WWW_TOP}/browse?t=3040">Lossless
														Music</a>
													{* PC *}
													{* 0day *}
												{elseif in_array({$result.category}, array('0DAY', 'APPS', 'Apps: PC', 'Apps: Linux', 'DOX'))}
													<a class="title" title="View category PC 0day"
													   href="{$smarty.const.WWW_TOP}/browse?t=4010">PC
														0DAY</a>
													{* Mac *}
												{elseif in_array({$result.category}, array('Apps: MAC', 'Games: MAC'))}
													<a class="title" title="View category PC Mac"
													   href="{$smarty.const.WWW_TOP}/browse?t=4030">PC
														Mac</a>
													{* Phone-Other *}
												{elseif in_array({$result.category}, array('Apps: Phone', 'PDA'))}
													<a class="title" title="View category Phone Other"
													   href="{$smarty.const.WWW_TOP}/browse?t=4040">Phone
														Other</a>
													{* Games *}
												{elseif in_array({$result.category}, array('GAMES', 'Games: PC', 'Games: Other'))}
													<a class="title" title="View category PC Games"
													   href="{$smarty.const.WWW_TOP}/browse?t=4050">PC
														Games</a>
													{* TV *}
												{elseif in_array({$result.category}, array('TV'))}
													<a class="title" title="View category TV"
													   href="{$smarty.const.WWW_TOP}/browse?t=5000">TV</a>
													{* SD *}
												{elseif in_array({$result.category}, array('TV-DVDRIP', 'tv-sd', 'TV: STD', 'TV-XVid'))}
													<a class="title" title="View category TV SD"
													   href="{$smarty.const.WWW_TOP}/browse?t=5030">SDTV</a>
													{* HD *}
												{elseif in_array({$result.category}, array('tv-hd', 'TV: HD', 'TV-x264', 'TV-X264'))}
													<a class="title" title="View category TV HD"
													   href="{$smarty.const.WWW_TOP}/browse?t=5040">HDTV</a>
													{* XXX *}
												{elseif in_array({$result.category}, array('XXX'))}
													<a class="title" title="View category XXX"
													   href="{$smarty.const.WWW_TOP}/browse?t=6000">XXX</a>
													{* DVD *}
												{elseif in_array({$result.category}, array('XXX: DVD'))}
													<a class="title" title="View category XXX DVD"
													   href="{$smarty.const.WWW_TOP}/browse?t=6010">XXX
														DVD</a>
													{* XviD *}
												{elseif in_array({$result.category}, array('XXX: SD-CLIPS', 'XXX: MOVIES-SD'))}
													<a class="title" title="View category XXX XviD"
													   href="{$smarty.const.WWW_TOP}/browse?t=6030">XXX
														SD</a>
													{* x264 *}
												{elseif in_array({$result.category}, array('XXX: HD-CLIPS', 'XXX: MOVIES-HD'))}
													<a class="title" title="View category XXX x264"
													   href="{$smarty.const.WWW_TOP}/browse?t=6040">XXX
														HD</a>
													{* Other *}
												{elseif in_array({$result.category}, array('xxx-videos'))}
													<a class="title" title="View category XXX Other"
													   href="{$smarty.const.WWW_TOP}/browse?t=6050">XXX
														Other</a>
													{* Imageset *}
												{elseif in_array({$result.category}, array('XXX-IMGSET'))}
													<a class="title" title="View category XXX Imageset"
													   href="{$smarty.const.WWW_TOP}/browse?t=6060">XXX
														Imagesets</a>
													{* Books *}
												{elseif in_array({$result.category}, array('EBOOK'))}
													<a class="title" title="View category Books"
													   href="{$smarty.const.WWW_TOP}/browse?t=8000">Ebooks</a>
													{* Other *}
												{elseif in_array({$result.category}, array('Other: E-Books'))}
													<a class="title" title="View category Books Other"
													   href="{$smarty.const.WWW_TOP}/browse?t=8050">Ebooks
														Other</a>
												{elseif in_array({$result.category}, array('', 'PRE'))}
													N/A
												{else}
													{$result.category}
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												{if {$result.source} == abgx}
													<a title="Visit abgx"
													   href="{$site->dereferrer_link}http://www.abgx.net/rss/x360/posted.rss"
													   target="_blank">
														abgx.net
													</a>
												{elseif in_array({$result.source}, array('abErotica', 'abMooVee', 'abTeeVee', 'abForeign'))}
													<a title="Visit allfilled {$result.source}"
													   href="{$site->dereferrer_link}http://{$result.source}.allfilled.com/search.php?q={$result.title}&Search=Search"
													   target="_blank">
														{$result.source}
													</a>
												{elseif $result.source|strpos:'#a.b.' !== false}
													<a title="Visit {$result.source} on IRC"
													   href="irc://irc.Prison.NET:6667/{str_replace('#a.b.', 'alt.binaries.', {$result.source})}"
													   target="_blank">
														{$result.source}
													</a>
												{elseif {$result.source} == omgwtfnzbs}
													<a title="Visit omgwtfnzbs"
													   href="{$site->dereferrer_link}http://rss.omgwtfnzbs.org/rss-info.php"
													   target="_blank">
														omgwtfnzbs
													</a>
												{elseif {$result.source} == orlydb}
													<a title="Visit ORLYDB"
													   href="{$site->dereferrer_link}http://orlydb.com/?q={$result.title}"
													   target="_blank">
														ORLYDB
													</a>
												{elseif {$result.source} == predbme}
													<a title="Visit PreDB.me"
													   href="{$site->dereferrer_link}http://predb.me/?search={$result.title}"
													   target="_blank">
														PreDB
													</a>
												{elseif {$result.source} == prelist}
													<a title="Visit Prelist"
													   href="{$site->dereferrer_link}http://www.prelist.ws/?search={$result.title}"
													   target="_blank">
														Prelist
													</a>
												{elseif {$result.source} == "#Pre@zenet"}
													<a title="Visit zenet on IRC" href="irc://irc.zenet.org:6667/Pre"
													   target="_blank">
														Zenet IRC
													</a>
												{elseif {$result.source} == "#pre@corrupt"}
													<a title="Visit corrupt on IRC"
													   href="irc://irc.corrupt-net.org:6667/pre"
													   target="_blank">
														Corrupt-Net
													</a>
												{elseif {$result.source} == srrdb}
													<a title="Visit srrDB"
													   href="{$site->dereferrer_link}http://www.srrdb.com/browse/{$result.title}"
													   target="_blank">
														srrDB
													</a>
												{elseif {$result.source} == "#scnzb"}
													<a title="Visit srrDB" href="irc://irc.Prison.NET:6667/scnzb"
													   target="_blank">
														srrDB
													</a>
												{elseif {$result.source} == "#tvnzb"}
													<a title="Visit srrDB" href="irc://irc.Prison.NET:6667/tvnzb"
													   target="_blank">
														srrDB
													</a>
												{elseif {$result.source} == "usenet-crawler"}
													<a title="Visit Usenet-Crawler"
													   href="{$site->dereferrer_link}http://www.usenet-crawler.com/predb?q={$result.title}"
													   target="_blank">
														Usenet-Crawler
													</a>
												{elseif {$result.source} == womble}
													<a title="Visit Womble"
													   href="{$site->dereferrer_link}http://www.newshost.co.za/?s={$result.title}"
													   target="_blank">
														Womble
													</a>
												{elseif {$result.source} == zenet}
													<a title="Visit ZEnet"
													   href="{$site->dereferrer_link}http://pre.zenet.org/?search={$result.title}"
													   target="_blank">
														ZEnet
													</a>
												{else}
													{$result.source}
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												{if is_numeric({$result.requestid}) && {$result.requestid} != 0}
													<a
															class="requestid"
															title="{$result.requestid}"
															href="{$smarty.const.WWW_TOP}/search?searchadvr=&searchadvsubject={$result.requestid}
						&searchadvposter=&searchadvdaysnew=&searchadvdaysold=&searchadvgroups=-1&searchadvcat=-1
						&searchadvsizefrom=-1&searchadvsizeto=-1&searchadvhasnfo=0&searchadvhascomments=0&search_type=adv"
															>
														{$result.requestid}
													</a>
												{else}
													N/A
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												{if not in_array({$result.size}, array('NULL', '', '0MB'))}
													{if strpos($result.size, 'MB') != false && {$result.size|regex_replace:"/(\.\d|,|MB)+/":''|count_characters} > 3}
														{math equation=($result.size|regex_replace:'/(\.\d|,|MB)+/':'' / 1024)|round}GB
													{else}
														{$result.size|regex_replace:"/(\.\d|,)+/":''}
													{/if}
												{else}
													N/A
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												{if isset($result.files)}
													{$result.files}
												{else}
													N/A
												{/if}
											</td>
											<td class="predb" style="text-align:center;">
												<a
														style="float: right;"
														title="NzbIndex"
														href="{$site->dereferrer_link}http://nzbindex.com/search/?q={$result.title}"
														target="_blank"
														>
													<img src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/nzbindex.png"/>
													&nbsp;
												</a>
											</td>
											<td class="predb" style="text-align:center;">
												<a
														style="float: right;"
														title="BinSearch"
														href="{$site->dereferrer_link}http://binsearch.info/?q={$result.title}"
														target="_blank"
														>
													<img src="{$smarty.const.WWW_TOP}/themes/charisma/images/icons/binsearch.png"/>
													&nbsp;
												</a>
											</td>
										</tr>
									{/foreach}
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
<div style="padding-bottom:10px;">
	{$pager}
</div>