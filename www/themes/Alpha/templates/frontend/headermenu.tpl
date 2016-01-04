<ul class="nav navbar-nav" id="nav-menu" role="navigation">
	{if isset($userdata)}
		{foreach from=$parentcatlist item=parentcat}
			{if $parentcat.id == 1000 && $userdata.consoleview=="1"}
				<li class="dropdown">
					<a id="cat1" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/console">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
						<li><a href="{$smarty.const.WWW_TOP}/console">All {$parentcat.title}</a></li>
						{foreach from=$consolecatlist key=systemtype item=system}
							<li class="dropdown-submenu" >
							<a tabindex="-1" href="#">{$systemtype}</a>
							<ul class="dropdown-menu" style="overflow:auto">
								{foreach from=$system item=subcat}
									<li>
										<a tabindex="-1" title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a>
									</li>
								{/foreach}
							</ul>
						{/foreach}
						</li>
					</ul>
				</li>
			{elseif $parentcat.id == 2000 && $userdata.movieview=="1"}
				<li class="dropdown">
					<a id="cat2" role="button" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat2">
						<li><a href="{$smarty.const.WWW_TOP}/movies"> All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					</ul>
				</li>
			{elseif ($parentcat.id == 3000 && $userdata.musicview=="1")}
				<li class="dropdown">
					<a id="cat3" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/music">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
						<li><a href="{$smarty.const.WWW_TOP}/music">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							{if $subcat.id == 3030}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{else}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
							{/if}
						{/foreach}
					</ul>
				</li>
			{elseif ($parentcat.id == 4000 && $userdata.gameview=="1")}
				<li class="dropdown">
					<a id="cat3" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/games">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
						<li><a href="{$smarty.const.WWW_TOP}/games">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							{if $subcat.id == 4050}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a></li>
							{else}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/if}
						{/foreach}
					</ul>
				</li>
			{elseif ($parentcat.id == 5000)}
				<li class="dropdown">
					<a id="cat5" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat{$parentcat.id}">
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					</ul>
				</li>
			{elseif ($parentcat.id == 6000 && $userdata.xxxview=="1" && $site->lookupxxx=="1")}
				<li class="dropdown">
					<a id="cat3"
						class="dropdown-toggle"
						data-toggle="dropdown"
						data-hover="dropdown"
						href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
						<b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
						<li><a href="{$smarty.const.WWW_TOP}/xxx">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							{if $subcat.id == 6010 OR 6020 OR 6030 OR 6040}
								<li><a title="Browse {$subcat.title}"
										href="{$smarty.const.WWW_TOP}/xxx?t={$subcat.id}">{$subcat.title}</a>
								</li>
							{else}
								<li><a title="Browse {$subcat.title}"
										href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
								</li>
							{/if}
						{/foreach}
					</ul>
				</li>
			{elseif ($parentcat.id == 8000 && $userdata.bookview=="1")}
				<li class="dropdown">
					<a id="cat3" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/books">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
						<li><a href="{$smarty.const.WWW_TOP}/books">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							{if $subcat.id == 8010}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books?t={$subcat.id}">{$subcat.title}</a></li>
							{else}
								<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/if}
						{/foreach}
					</ul>
				</li>
			{else}
				<li class="dropdown">
					<a id="drop{$parentcat.id}" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
						{foreach from=$parentcat.subcatlist item=subcat}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					</ul>
				</li>
			{/if}
		{/foreach}
	{/if}
	<li class="dropdown">
		<a id="dropAll" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse">Browse <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropAll">
			<li><a href="{$smarty.const.WWW_TOP}/browse">Browse All</a></li>
			<li><a title="Browse Groups" href="{$smarty.const.WWW_TOP}/browsegroup">Groups</a></li>
		</ul>
	</li>
</ul>

<form class="navbar-form navbar-right" id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
	<div class="form-group">
		<label class="sr-only" for="headsearch">Keyword</label>
		<input type="text" class="form-control" style="width: 160px;" id="headsearch" name="search" value="{if $header_menu_search == ""}{else}{$header_menu_search|escape:"htmlall"}{/if}" placeholder="Keyword">
	</div>
	<div class="form-group">
		<label class="sr-only" for="headcat">Category</label>
		<select class="form-control" style="width: auto;" id="headcat" name="t">
			<option class="grouping" value="-1">All</option>
			{foreach from=$parentcatlist item=parentcat}
				<option {if $header_menu_cat==$parentcat.id}selected="selected"{/if} class="grouping" value="{$parentcat.id}">{$parentcat.title}</option>
				{foreach from=$parentcat.subcatlist item=subcat}
					<option {if $header_menu_cat==$subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;{$subcat.title}</option>
				{/foreach}
			{/foreach}
		</select>
	</div>
	<button type="submit" id="headsearch_go" class="btn btn-default">Search</button>
</form>
