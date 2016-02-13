<ul class="nav" role="navigation">
	{foreach $parentcatlist as $parentcat}
	{if $parentcat.id == {$category::GAME_ROOT} && $userdata.consoleview == "1"}
		<li class="dropdown">
		<a id="drop1" role="button" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop1">
			<li><a href="{$smarty.const.WWW_TOP}/console">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach $parentcat.subcatlist as $subcat}
				<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a></li>
			{/foreach}
		</ul>
		</li>
	{/if}
	{if $parentcat.id == {$category::MOVIE_ROOT} && $userdata.movieview == "1"}
	<li class="dropdown">
		<a id="drop2" role="button" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop2">
			<li><a href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach $parentcat.subcatlist as $subcat}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
			{/foreach}
		</ul>
	</li>
	{/if}
	{if ($parentcat.id == {$category::MUSIC_ROOT} && $userdata.musicview == "1")}
	<li class="dropdown">
		<a id="drop3" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop3">
			<li><a href="{$smarty.const.WWW_TOP}/music">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach $parentcat.subcatlist as $subcat}
			{if $subcat.id == {$category::MUSIC_AUDIOBOOK}}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
			{else}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
			{/if}
			{/foreach}
		</ul>
	</li>
	{/if}
	{if ($parentcat.id == {$category::PC_ROOT} && $userdata.gameview == "1")}
		<li class="dropdown">
			<a id="drop4" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
			<ul class="dropdown-menu" role="menu" aria-labelledby="drop4">
				<li><a href="{$smarty.const.WWW_TOP}/games">{$parentcat.title}</a></li>
				<li class="divider"></li>
				{foreach $parentcat.subcatlist as $subcat}
					{if $subcat.id !== {$category::PC_GAMES}}
						<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
					{else}
						<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a></li>
					{/if}
				{/foreach}
			</ul>
		</li>
	{/if}
	{if ($parentcat.id == {$category::TV_ROOT})}
	<li class="dropdown">
		<a id="drop{$parentcat.id}" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
			<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach $parentcat.subcatlist as $subcat}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
			{/foreach}
		</ul>
	</li>
	{/if}
	{if $parentcat.id == {$category::XXX_ROOT}}
		<li class="dropdown">
			<a id="cat3"
			   class="dropdown-toggle"
			   data-toggle="dropdown"
			   data-hover="dropdown"
			   href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
				<b class="caret"></b></a>
			<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
				{if $userdata.xxxview == "1"}
					<li><a href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}</a></li>
				{else}
					<li><a href="{$smarty.const.WWW_TOP}/browse?t=6000">{$parentcat.title}</a></li>
				{/if}
				<hr>
				{if $userdata.xxxview == "1"}
					{foreach $parentcat.subcatlist as $subcat}
						{if $subcat.id == {$category::XXX_DVD} OR $subcat.id == {$category::XXX_WMV} OR $subcat.id == {$category::XXX_XVID} OR $subcat.id == {$category::XXX_X264}}
							<li><a href="{$smarty.const.WWW_TOP}/xxx?t={$subcat.id}">{$subcat.title}</a>
							</li>
						{else}
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
							</li>
						{/if}
					{/foreach}
				{else}
					{foreach $parentcat.subcatlist as $subcat}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
					{/foreach}
				{/if}
			</ul>
		</li>
	{/if}
	{if $parentcat.id == {$category::BOOKS_ROOT}}
		<li class="dropdown">
			<a id="drop{$parentcat.id}"
			   class="dropdown-toggle"
			   data-toggle="dropdown"
			   data-hover="dropdown"
			   href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
				<b class="caret"></b></a>
			<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
				{if $userdata.bookview == "1"}
					<li><a href="{$smarty.const.WWW_TOP}/books">{$parentcat.title}</a></li>
				{else}
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$category::BOOKS_ROOT}">{$parentcat.title}</a></li>
				{/if}
				<hr>
				{foreach $parentcat.subcatlist as $subcat}
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
				{/foreach}
			</ul>
		</li>
	{/if}
	{/foreach}
	<li class="dropdown">
		<a id="dropOther" class="dropdown-toggle" data-toggle="dropdown" href="#">Other <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropOther">
			<hr>
			<li><a href="/browse?t={$category::OTHER_MISC}">Misc</a></li>
			<li><a href="/browse?t={$category::OTHER_HASHED}">Hashed</a></li>
		</ul>
	</li>
</ul>
<ul class="nav pull-left">
	<li class="">
		<form class="navbar-form" id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
				<select class="input-small" id="headcat" name="t">
					<option class="grouping" value="-1">All</option>
					{foreach $parentcatlist as $parentcat}
					<option {if $header_menu_cat==$parentcat.id}selected="selected"{/if} class="grouping" value="{$parentcat.id}">{$parentcat.title}</option>
					{foreach $parentcat.subcatlist as $subcat}
					<option {if $header_menu_cat==$subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;{$subcat.title}</option>
					{/foreach}
					{/foreach}
				</select>
				<input class="span3" id="headsearch" name="search" value="{if $header_menu_search == ""}{else}{$header_menu_search|escape:"htmlall"}{/if}" placeholder="Search" type="text" />
				<input class="btn" id="headsearch_go" type="submit" value="Search"/>
		</form>
	</li>
</ul>
