<div id="menucontainer">
	<div class="collapse navbar-collapse nav navbar-nav top-menu">
		{if $loggedin=="true"}
			{foreach from=$parentcatlist item=parentcat}
				{if ($parentcat.id == 5000)}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-television"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.id}">TV</a></li>
							<hr>
							<li><a href="{$smarty.const.WWW_TOP}/series">TV Series</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/calendar">TV Calendar</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/myshows">My Shows</a></li>
							<hr>
							{foreach from=$parentcat.subcatlist item=subcat}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/foreach}
						</ul>
					</li>
				{elseif $parentcat.id == 2000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-film"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.movieview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title}</a></li>
							{elseif $userdata.movieview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=2000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							<li><a href="{$smarty.const.WWW_TOP}/upcoming">In Theatre</a></li>
							<li><a href="{$smarty.const.WWW_TOP}/mymovies">My Movies</a></li>
							<hr>
							{if $userdata.movieview == "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/movies?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{elseif $userdata.movieview != "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{elseif $parentcat.id == 1000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-gamepad"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.consoleview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/console">{$parentcat.title}</a></li>
							{elseif $userdata.consoleview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=1000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.consoleview == "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/console?t={$subcat.id}">{$subcat.title}</a>
									</li>
								{/foreach}
							{elseif $userdata.consoleview != "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{elseif $parentcat.id == 4000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-gamepad"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.gameview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/games">{$parentcat.title}</a></li>
							{elseif $userdata.gameview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=4000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.gameview == "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									{if $subcat.id == 4050}
										<li><a href="{$smarty.const.WWW_TOP}/games?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{else}
										<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{/if}
								{/foreach}
							{elseif $userdata.gameview != "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{elseif $parentcat.id == 3000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-music"></i> {$parentcat.title}<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.musicview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/music">{$parentcat.title}</a></li>
							{elseif $userdata.musicview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=3000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.musicview == "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/music?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{elseif $userdata.musicview != "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{elseif $parentcat.id == 8000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-book"></i> Books<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.bookview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/books">{$parentcat.title}</a></li>
							{elseif $userdata.bookview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=8000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{foreach from=$parentcat.subcatlist item=subcat}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
							{/foreach}
						</ul>
					</li>
				{elseif $parentcat.id == 6000}
					<li class="nav-parent">
						<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true"
						   data-delay="30">
							<i class="fa fa-venus-mars"></i> Adult<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							{if $userdata.xxxview == "1"}
								<li><a href="{$smarty.const.WWW_TOP}/xxx">{$parentcat.title}</a></li>
							{elseif $userdata.xxxview != "1"}
								<li><a href="{$smarty.const.WWW_TOP}/browse?t=6000">{$parentcat.title}</a></li>
							{/if}
							<hr>
							{if $userdata.xxxview == "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									{if $subcat.id == 6010 OR $subcat.id == 6020 OR $subcat.id == 6030 OR $subcat.id == 6040}
										<li><a href="{$smarty.const.WWW_TOP}/xxx?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{else}
										<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a>
										</li>
									{/if}
								{/foreach}
							{elseif $userdata.xxxview != "1"}
								{foreach from=$parentcat.subcatlist item=subcat}
									<li><a href="{$smarty.const.WWW_TOP}/browse?t={$subcat.id}">{$subcat.title}</a></li>
								{/foreach}
							{/if}
						</ul>
					</li>
				{/if}
			{/foreach}
			<li class="nav-parent">
				<a href="#" data-toggle="dropdown" data-hover="dropdown" data-close-others="true" data-delay="30">
					<i class="fa fa-bolt"></i> Misc<i class="fa fa-angle-down"></i>
				</a>
				<ul class="dropdown-menu">
					<li><a href="/browse?t=7000">Misc</a></li>
					<li><a href="/browse?t=7020">Hashed</a></li>
					<li><a href="/browse?t=7010">Other</a></li>
					<hr>
					<li><a href="/browse">All</a></li>
					<li><a href="/browsegroup">Browse Groups</a></li>
				</ul>
			</li>
			<!-- End If logged in -->
		{/if}
	</div>
</div>