<div class="category" style="padding-bottom:20px;">
	<h2 class="main-title">
		<a class="see-more" href="{$smarty.const.WWW_TOP}/{$goto}">see more &raquo;</a>
		The <strong>newest releases</strong> for
		<strong>
			<tr>
				<td>
					<select name="MySelect" id="MySelect" onchange="window.location='{$smarty.const.WWW_TOP}/newposterwall?t=' + this.value;">
						{foreach from=$types item=newtype}
							<option {if $type == $newtype}selected="selected"{/if} value="{$newtype}">
								{$newtype}
							</option>
						{/foreach}
					</select>
				</td>
			</tr>
		</strong>
	</h2>
	<div class="main-wrapper">
		<div class="main-content">
			<!-- library -->
			<div class="library-wrapper">
				{foreach from=$newest item=result}
					<div
						class=
							{if $type == 'Movies'}
								"library-show"
							{elseif $type == 'Music'}
								"library-music"
							{/if}
					>
						<div class="poster">
							{if $type == 'Movies'}
								<a class="titleinfo" title="{$result.guid}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
									<img alt="" src="{$smarty.const.WWW_TOP}/covers/movies/{$result.imdbid}-cover.jpg" />
								</a>
							{elseif $type = 'Music'}
								<a class="titleinfo" title="{$result.guid}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
									<img alt="" src="{$smarty.const.WWW_TOP}/covers/music/{$result.musicinfoid}.jpg" />
								</a>
							{/if}
						</div>
						<div class="rating-pod" id="guid{$result.guid}">
							<div class="icons">
								{if $type == 'Movies'}
									<div class="icon icon_imdb">
										<a title="View on IMDB" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbid}/" target="_blank" ></a>
									</div>
									{if $cpapi != '' && $cpurl != ''}
										<div class="icon icon_cp" title="Send to CouchPotato"></div>
									{/if}
								{/if}
								<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"url"}"></a></div>
								<div class="icon icon_cart" title="Add to Cart"></div>
								{if $sabintegrated}
									<div class="icon icon_sab" title="Send to my Sabnzbd"></div>
								{/if}
							</div>
						</div>
						<a class="plays" href="#"></a>
					</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>