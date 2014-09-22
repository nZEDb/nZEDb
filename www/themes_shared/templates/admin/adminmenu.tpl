<h2>Admin Functions</h2>
<div id="accordian">
<ul>
	<li><a title="Home" href="{$smarty.const.WWW_TOP}/..{$site->home_link}"><span>Home</span></a></li>
	<li class="active"><a title="Admin Home" href="{$smarty.const.WWW_TOP}/">Admin Home</a></li>

	<li class="has-sub"><a href="#">Site Settings</a>
		<ul>
			<li class="has-sub"><a href="#">Blacklist</a>
				<ul>
					<li><a href="{$smarty.const.WWW_TOP}/binaryblacklist-edit.php?action=add">Add</a></li>
					<li class="last"><a href="{$smarty.const.WWW_TOP}/binaryblacklist-list.php">View</a></li>
				</ul>
			</li>
			<li class="has-sub"><a href="#">Content Page</a>
				<ul>
					<li><a href="{$smarty.const.WWW_TOP}/content-add.php?action=add">Add</a></li>
					<li class="last"><a href="{$smarty.const.WWW_TOP}/content-list.php">Edit</a></li>
				</ul>
			</li>
			<li><a href="{$smarty.const.WWW_TOP}/category-list.php?action=add">Edit Categories</a></li>
			<li><a title="Edit Site" href="{$smarty.const.WWW_TOP}/site-edit.php">Edit Site</a></li>
			<li class="has-sub"><a href="#">Groups</a>
				<ul>
					<li><a href="{$smarty.const.WWW_TOP}/group-edit.php">Add</a></li>
					<li><a href="{$smarty.const.WWW_TOP}/group-bulk.php">BulkAdd</a></li>
					<li class="last"><a href="{$smarty.const.WWW_TOP}/group-list.php">View</a></li>
				</ul>
			</li>
			<li class="has-sub"><a href="#">Menu Items</a>
			<ul>
				<li><a href="{$smarty.const.WWW_TOP}/menu-edit.php?action=add">Add</a></li>
				<li class="last"><a href="{$smarty.const.WWW_TOP}/menu-list.php">View</a></li>
			</ul>
			</li>
			<li class="last"><a href="{$smarty.const.WWW_TOP}/tmux-edit.php">Tmux Settings</a></li>
		</ul>
	</li>

	<li class="has-sub"><a href="#">Release Lists</a>
		<ul>
			<li class="has-sub"><a href="#">Movies</a>
				<ul>
					<li><a href="{$smarty.const.WWW_TOP}/movie-add.php">Add</a></li>
					<li class="last"><a href="{$smarty.const.WWW_TOP}/movie-list.php">View</a></li>
				</ul>
			</li>
			<li class="has-sub"><a href="#">TVRage</a>
				<ul>
					<li><a href="{$smarty.const.WWW_TOP}/rage-edit.php?action=add">Add</a></li>
					<li class="last"><a href="{$smarty.const.WWW_TOP}/rage-list.php">View</a></li>
				</ul>
			</li>
			<li><a href="{$smarty.const.WWW_TOP}/anidb-list.php">View AniDB</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/console-list.php">View Consoles</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/game-list.php">View Games</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/music-list.php">View Music</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/musicgenre-list.php">View Music Genres</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/release-list.php">View Releases</a></li>
			<li class="last"><a href="{$smarty.const.WWW_TOP}/xxx-list.php">View XXX</a></li>
		</ul>
	</li>

	<li class="has-sub"><a href="#">Users</a>
		<ul>
			<li><a href="{$smarty.const.WWW_TOP}/user-edit.php?action=add">Add</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/user-list.php">View</a></li>
	<li class="has-sub"><a href="#">User Roles</a>
		<ul>
			<li><a href="{$smarty.const.WWW_TOP}/role-edit.php?action=add">Add</a></li>
			<li class="last"><a href="{$smarty.const.WWW_TOP}/role-list.php">View</a></li>
		</ul>
	</li>
		</ul>
	</li>

	<li class="has-sub"><a href="#">Misc</a>
		<ul>
			<li><a href="{$smarty.const.WWW_TOP}/delete-releases.php">Delete Releases</a></li>
	<li class="has-sub"><a href="#">NZB's</a>
		<ul>
			<li><a href="{$smarty.const.WWW_TOP}/nzb-import.php">Import</a></li>
			<li class="last"><a href="{$smarty.const.WWW_TOP}/nzb-export.php">Export</a></li>
		</ul>
	</li>
			<li><a href="{$smarty.const.WWW_TOP}/opcachestats.php">Opcache Statistics</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/db-optimise.php" class="confirm_action">Optimise Tables</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/sharing.php">Sharing Settings</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/site-stats.php">Site Stats</a></li>
			<li><a href="{$smarty.const.WWW_TOP}/comments-list.php">View Comments</a></li>
			<li style="border-bottom: 1px solid #000000;"><a href="{$smarty.const.WWW_TOP}/view-logs.php">View Logs</a></li>
		</ul>
	</li>
</ul>
</div>
