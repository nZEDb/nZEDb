<div id="menucontainer">
	<div class="collapse navbar-collapse nav navbar-nav top-menu">
		{if isset($userdata)}
		{if $loggedin == "true"}
			{foreach $parentcatlist as $parentcat}
				{if $parentcat.id == {$catClass::TV_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-television"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">TV</a></li>
							<hr>
							<li><a href="{$smarty.const.WWW_TOP}/series">TV Series</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/anime">Anime Series</a></li>
							<hr>
							{foreach $parentcat.subcatlist as $subcat}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/foreach}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::MOVIE_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-film"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.movieview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title}</a></li>
							{elseif $userdata.movieview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::MOVIE_ROOT}">{$parentcat.title}</a></li>
							{/if}
							<hr>
							<li><a href="{$smarty.const.WWW_TOP}/upcoming">In Theatre</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/mymovies">My Movies</a></li>
							<hr>
							{if $userdata.movieview == "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{elseif $userdata.movieview != "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::GAME_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-gamepad"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.consoleview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/console">{$parentcat.title}</a></li>
							{elseif $userdata.consoleview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::GAME_ROOT}">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.consoleview == "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a>
									</li>
								{/foreach}
							{elseif $userdata.consoleview != "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::PC_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-gamepad"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.gameview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/games">{$parentcat.title}</a></li>
							{elseif $userdata.gameview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::PC_ROOT}">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.gameview == "1"}
								{foreach $parentcat.subcatlist as $subcat}
									{if $subcat.id == {$catClass::PC_GAMES}}
										<li><a href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{else}
										<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{/if}
								{/foreach}
							{elseif $userdata.gameview != "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::MUSIC_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-music"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.musicview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/music">{$parentcat.title}</a></li>
							{elseif $userdata.musicview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::MUSIC_ROOT}">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.musicview == "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{elseif $userdata.musicview != "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::BOOKS_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-book"></i> Books<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.bookview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/books">{$parentcat.title}</a></li>
							{elseif $userdata.bookview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::BOOKS_ROOT}">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{foreach $parentcat.subcatlist as $subcat}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/foreach}
						</ul>
					</li>
				{/if}
				{if $parentcat.id == {$catClass::XXX_ROOT}}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-venus-mars"></i> Adult<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.xxxview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}</a></li>
							{elseif $userdata.xxxview != "1"}
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
							{elseif $userdata.xxxview != "1"}
								{foreach $parentcat.subcatlist as $subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
				{if $parentcat.id === "0"}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true" data-delay="30">
							<i class="fa fa-bolt"></i> Other<i class="fa fa-angle-down"></i></a>
						<ul class="dropdown-menu">
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_ROOT}">Other</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_MISC}">Misc</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$catClass::OTHER_HASHED}">Hashed</a></li>
						</ul>
					</li>
				{/if}
			{/foreach}
		{/if}
		{/if}
		<!-- End If logged in -->
	</div>
</div>
