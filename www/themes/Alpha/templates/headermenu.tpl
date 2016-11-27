<ul class="nav navbar-nav" id="nav-menu" role="navigation">
	{if isset($userdata)}
		{foreach $parentcatlist as $parentcat}
			{if $parentcat.id == {$catClass::GAME_ROOT}}
				<li class="dropdown">
					<a id="cat1" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/console">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
						{if $userdata.consoleview == "1"}
							<li><a href="{$smarty.const.WWW_TOP}/console">All {$parentcat.title}</a></li>
						{else}
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
						{/if}
						{foreach $consolecatlist as $systemtype=>$system}
							<li class="dropdown-submenu" >
							<a tabindex="-1" href="#">{$systemtype}</a>
							<ul class="dropdown-menu" style="overflow:auto">
							{foreach $system as $subcat}
								{if $userdata.consoleview == "1"}
									<li><a tabindex="-1" title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a></li>
								{else}
									<li><a tabindex="-1" title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/if}
							{/foreach}
							</ul>
							</li>
						{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::MOVIE_ROOT}}
				<li class="dropdown">
					<a id="cat2" role="button" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat2">
					{if $userdata.movieview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/movies"> All {$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}"> All {$parentcat.title}</a></li>
					{/if}
					{foreach $parentcat.subcatlist as $subcat}
						{if $userdata.movieview == "1"}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
						{else}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/if}
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::MUSIC_ROOT}}
				<li class="dropdown">
					<a id="cat3" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/music">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
					{if $userdata.musicview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/music">All {$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}"> All {$parentcat.title}</a></li>
					{/if}
					{foreach $parentcat.subcatlist as $subcat}
						{if $userdata.musicview == "1" && $subcat.id != {$catClass::MUSIC_AUDIOBOOK}}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
						{else}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/if}
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::PC_ROOT}}
				<li class="dropdown">
				{if $userdata.gameview == "1"}
					<a id="cat4" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/games">{$parentcat.title} <b class="caret"></b></a>
				{else}
					<a id="cat4" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title} <b class="caret"></b></a>
				{/if}
				<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
					{if $userdata.gameview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/games">All {$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
					{/if}
					{foreach $parentcat.subcatlist as $subcat}
						{if $subcat.id == {$catClass::PC_GAMES} && $userdata.gameview == "1"}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a></li>
						{else}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/if}
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::TV_ROOT}}
				<li class="dropdown">
					<a id="cat5" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat{$parentcat.id}">
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::XXX_ROOT}}
				<li class="dropdown">
					<a id="cat6"
						class="dropdown-toggle"
						data-toggle="dropdown"
						data-hover="dropdown"
						href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
						<b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
					{if $userdata.xxxview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/xxx">All {$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
					{/if}
					{foreach $parentcat.subcatlist as $subcat}
						{if ($subcat.id == {$catClass::XXX_DVD} OR {$catClass::XXX_WMV} OR {$catClass::XXX_XVID} OR {$catClass::XXX_X264}) && $userdata.xxxview == "1"}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/xxx?t={$subcat.id}">{$subcat.title}</a></li>
						{else}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/if}
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id == {$catClass::BOOKS_ROOT}}
				<li class="dropdown">
					<a id="cat7" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/books">{$parentcat.title} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
					{if $userdata.bookview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/books">All {$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
					{/if}
					{foreach $parentcat.subcatlist as $subcat}
						{if $subcat.id == {$catClass::BOOKS_UNKNOWN} && $userdata.bookview == "1"}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books?t={$subcat.id}">{$subcat.title}</a></li>
						{else}
							<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/if}
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $parentcat.id === "0"}
				<li class="dropdown">
					<a id="cat8" class="dropdown-toggle" data-toggle="dropdown" href="#">Other <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_ROOT}">Other</a></li>
						<hr>
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_MISC}">Misc</a></li>
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_HASHED}">Hashed</a></li>
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
			{foreach $parentcatlist as $parentcat}
				<option {if $header_menu_cat==$parentcat.id}selected="selected"{/if} class="grouping" value="{$parentcat.id}">{$parentcat.title}</option>
				{foreach $parentcat.subcatlist as $subcat}
					<option {if $header_menu_cat==$subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;{$subcat.title}</option>
				{/foreach}
			{/foreach}
		</select>
	</div>
	<button type="submit" id="headsearch_go" class="btn btn-default">Search</button>
</form>
