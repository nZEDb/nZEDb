<ul class="nav" role="navigation">
	{foreach from=$parentcatlist item=parentcat}
	{if $parentcat.id == 1000 && $userdata.consoleview=="1"}
		<li class="dropdown">
		<a id="drop1" role="button" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop1">
			<li><a href="{$smarty.const.WWW_TOP}/console">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach from=$parentcat.subcatlist item=subcat}
				<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a></li>
			{/foreach}
		</ul>
		</li>
	{elseif $parentcat.id == 2000 && $userdata.movieview=="1"}
	<li class="dropdown">
		<a id="drop2" role="button" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop2">
			<li><a href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach from=$parentcat.subcatlist item=subcat}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
			{/foreach}
		</ul>
	</li>
	{elseif ($parentcat.id == 3000 && $userdata.musicview=="1")}
	<li class="dropdown">
		<a id="drop3" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop3">
			<li><a href="{$smarty.const.WWW_TOP}/music">{$parentcat.title}</a></li>
			<li class="divider"></li>
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
			<a id="drop4" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
			<ul class="dropdown-menu" role="menu" aria-labelledby="drop4">
				<li><a href="{$smarty.const.WWW_TOP}/games">{$parentcat.title}</a></li>
				<li class="divider"></li>
				{foreach from=$parentcat.subcatlist item=subcat}
					{if $subcat.id !== 4050}
						<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
					{else}
						<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a></li>
					{/if}
				{/foreach}
			</ul>
		</li>
	{elseif ($parentcat.id == 5000)}
	<li class="dropdown">
		<a id="drop{$parentcat.id}" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">
			<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">{$parentcat.title}</a></li>
			<li class="divider"></li>
			{foreach from=$parentcat.subcatlist item=subcat}
			{if ($subcat.id == 7020 && $userdata.bookview=="1")}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books">{$subcat.title}</a></li>
			{else}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
			{/if}
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
				{if $subcat.id == 6010 || $subcat.id == 6020 || $subcat.id == 6030 || $subcat.id == 6040}
					<li><a title="Browse {$subcat.title}"
						   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
					</li>
				{else}
					<li><a title="Browse {$subcat.title}"
						   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
					</li>
				{/if}
			{/foreach}
		</ul>
	</li>
	{elseif ($parentcat.id == 6000 && $userdata.xxxview=="0" && $site->lookupxxx=="1")}
		<li class="dropdown">
			<a id="cat3"
			   class="dropdown-toggle"
			   data-toggle="dropdown"
			   data-hover="dropdown"
			   href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}
				<b class="caret"></b></a>
			<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
				<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">All {$parentcat.title}</a></li>
				{foreach from=$parentcat.subcatlist item=subcat}
					{if $subcat.id == 6010 || $subcat.id == 6020 || $subcat.id == 6030 || $subcat.id == 6040}
						<li><a title="Browse {$subcat.title}"
							   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
						</li>
					{else}
						<li><a title="Browse {$subcat.title}"
							   href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
						</li>
					{/if}
				{/foreach}
			</ul>
		</li>
	{else}
	<li class="dropdown">
		<a id="drop{$parentcat.id}" class="dropdown-toggle" data-toggle="dropdown" href="#">{$parentcat.title} <b class="caret"></b></a>
		<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.id}">

			{if ($parentcat.id == 7000 && $userdata.bookview=="1")}
				<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books">Books</a></li>
				<li class="divider"></li>
			{/if}

			{foreach from=$parentcat.subcatlist item=subcat}
			{if ($subcat.id == 8010)}
			<li class="divider"></li>
			<li><a href="{$smarty.const.WWW_TOP}/browse">All</a></li>
			<li class="divider"></li>
			{/if}
			{if ($subcat.id == 7020 && $userdata.bookview=="1")}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books">{$subcat.title}</a></li>
			{else}
			<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
			{/if}
			{/foreach}
		</ul>
	</li>
	{/if}
	{/foreach}
</ul>
<ul class="nav pull-left">
	<li class="">
		<form class="navbar-form" id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
				<select class="input-small" id="headcat" name="t">
					<option class="grouping" value="-1">All</option>
					{foreach from=$parentcatlist item=parentcat}
					<option {if $header_menu_cat==$parentcat.id}selected="selected"{/if} class="grouping" value="{$parentcat.id}">{$parentcat.title}</option>
					{foreach from=$parentcat.subcatlist item=subcat}
					<option {if $header_menu_cat==$subcat.id}selected="selected"{/if} value="{$subcat.id}">&nbsp;&nbsp;{$subcat.title}</option>
					{/foreach}
					{/foreach}
				</select>
				<input class="span3" id="headsearch" name="search" value="{if $header_menu_search == ""}{else}{$header_menu_search|escape:"htmlall"}{/if}" placeholder="Search" type="text" />
				<input class="btn" id="headsearch_go" type="submit" value="Search"/>
		</form>
	</li>
</ul>


