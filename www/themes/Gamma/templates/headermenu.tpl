{if isset($userdata)}
<ul class="nav navbar-nav">
	{foreach $parentcatlist as $parentcat}
		{if $parentcat.id == {$catClass::GAME_ROOT}}
			<li class="dropdown">
				<a id="drop1" role="button" class="dropdown-toggle" data-hover="dropdown" href="#">{$parentcat.title}
					<b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop1">
					{if $userdata.consoleview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/console">{$parentcat.title}</a></li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::GAME_ROOT}">{$parentcat.title}</a>
						</li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{/if}
				</ul>
			</li>
		{/if}
		{if $parentcat.id == {$catClass::MOVIE_ROOT}}
			<li class="dropdown">
				<a id="drop2" role="button" class="dropdown-toggle" data-hover="dropdown" href="#">{$parentcat.title}
					<b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop2">
					{if $userdata.movieview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title}</a></li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::MOVIE_ROOT}">{$parentcat.title}</a>
						</li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{/if}
				</ul>
			</li>
		{/if}
		{if $parentcat.id == {$catClass::MUSIC_ROOT}}
			<li class="dropdown">
				<a id="drop3" class="dropdown-toggle" data-hover="dropdown" href="#">{$parentcat.title} <b
							class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop3">
					{if $userdata.musicview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/music">{$parentcat.title}</a></li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							{if $subcat.id == {$catClass::MUSIC_AUDIOBOOK}}
								<li><a title="Browse {$subcat.title}"
									   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{else}
								<li><a title="Browse {$subcat.title}"
									   href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
							{/if}
						{/foreach}
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::MUSIC_ROOT}">{$parentcat.title}</a>
						</li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{/if}
				</ul>
			</li>
		{/if}
		{if $parentcat.id == {$catClass::PC_ROOT}}
			<li class="dropdown">
				<a id="drop4" class="dropdown-toggle" data-hover="dropdown" href="#">{$parentcat.title} <b
							class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop4">
					{if $userdata.gameview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/games">{$parentcat.title}</a></li>
						<li class="divider"></li>
						{foreach $parentcat.subcatlist as $subcat}
							{if $subcat.id != {$catClass::PC_GAMES}}
								<li><a title="Browse {$subcat.title}"
									   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{else}
								<li><a title="Browse {$subcat.title}"
									   href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a></li>
							{/if}
						{/foreach}
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::PC_ROOT}">{$parentcat.title}</a></li>
						{foreach $parentcat.subcatlist as $subcat}
							<li><a title="Browse {$subcat.title}"
								   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
						{/foreach}
					{/if}
				</ul>
			</li>
		{/if}
		{if $parentcat.id == {$catClass::TV_ROOT}}
			<li class="dropdown">
				<a id="drop{$parentcat.id}" class="dropdown-toggle" data-hover="dropdown" href="#">{$parentcat.title}
					<b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title}</a></li>
					<li class="divider"></li>
					{foreach $parentcat.subcatlist as $subcat}
						<li><a title="Browse {$subcat.title}"
							   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
					{/foreach}
				</ul>
			</li>
		{/if}
		{if $parentcat.id == {$catClass::XXX_ROOT}}
			<li class="dropdown">
				<a id="cat3"
				   class="dropdown-toggle"
				   data-hover="dropdown"
				   data-hover="dropdown"
				   href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
					<b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
					{if $userdata.xxxview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::XXX_ROOT}">{$parentcat.title}</a></li>
					{/if}
					<hr>
					{if $userdata.xxxview == "1"}
						{foreach $parentcat.subcatlist as $subcat}
							{if $subcat.id == {$catClass::XXX_DVD} OR $subcat.id == {$catClass::XXX_WMV} OR $subcat.id == {$catClass::XXX_XVID} OR $subcat.id == {$catClass::XXX_X264}}
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
		{if $parentcat.id == {$catClass::BOOKS_ROOT}}
			<li class="dropdown">
				<a id="drop{$parentcat.id}"
				   class="dropdown-toggle"
				   data-hover="dropdown"
				   data-hover="dropdown"
				   href="{$smarty.const.WWW_TOP}/books">{$parentcat.title}
					<b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
					{if $userdata.bookview == "1"}
						<li><a href="{$smarty.const.WWW_TOP}/books">{$parentcat.title}</a></li>
					{else}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::BOOKS_ROOT}">{$parentcat.title}</a>
						</li>
					{/if}
					<hr>
					{foreach $parentcat.subcatlist as $subcat}
						<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
					{/foreach}
				</ul>
			</li>
		{/if}
		{if $parentcat.id === "0"}
			<li class="dropdown">
				<a id="dropOther" class="dropdown-toggle" data-hover="dropdown" href="#">Other <b class="caret"></b></a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="dropOther">
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_ROOT}">Other</a></li>
					<hr>
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_MISC}">Misc</a></li>
					<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_HASHED}">Hashed</a></li>
				</ul>
			</li>
		{/if}
	{/foreach}
</ul>
<ul class="nav pull-left">
	<li>
		<form class="navbar-form" id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
			<select class="input-small" id="headcat" name="t">
				<option class="grouping" value="-1">All</option>
				{foreach $parentcatlist as $parentcat}
					<option {if $header_menu_cat == $parentcat.id}selected="selected"{/if} class="grouping"
							value="{$parentcat.id}">{$parentcat.title}</option>
					{foreach $parentcat.subcatlist as $subcat}
						<option {if $header_menu_cat == $subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;{$subcat.title}</option>
					{/foreach}
				{/foreach}
			</select>
			<input class="span3" id="headsearch" name="search"
				   value="{if $header_menu_search == ""}{else}{$header_menu_search|escape:"htmlall"}{/if}"
				   placeholder="Search" type="text"/>
			<input class="btn" id="headsearch_go" type="submit" value="Search"/>
		</form>
	</li>
</ul>
{/if}
