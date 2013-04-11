<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 16:54:25
         compiled from "/var/www/newznab/www/views/templates/admin/adminmenu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1612332250516723013847e6-38468850%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '89381d2525b69656adf0cf764b5b8dc688cc7761' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/adminmenu.tpl',
      1 => 1365713661,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1612332250516723013847e6-38468850',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
 		<h2>Admin Functions</h2> 
		<ul>
		<li><a title="Home" href="<?php echo @WWW_TOP;?>
/..<?php echo $_smarty_tpl->getVariable('site')->value->home_link;?>
">Home</a></li>
		<li><a title="Admin Home" href="<?php echo @WWW_TOP;?>
/">Admin Home</a></li>
		<li><a title="Edit Site" href="<?php echo @WWW_TOP;?>
/site-edit.php">Edit Site</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/content-add.php?action=add">Add</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/content-list.php">Edit</a> Content Page</li>
		<li><a href="<?php echo @WWW_TOP;?>
/menu-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/menu-edit.php?action=add">Add</a> Menu Items</li>
		<li><a href="<?php echo @WWW_TOP;?>
/category-list.php?action=add">Edit</a> Categories</li>
		<li><a href="<?php echo @WWW_TOP;?>
/group-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/group-edit.php">Add</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/group-bulk.php">BulkAdd</a> Groups</li>
		<li><a href="<?php echo @WWW_TOP;?>
/binaryblacklist-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/binaryblacklist-edit.php?action=add">Add</a> Blacklist</li>
		<li><a href="<?php echo @WWW_TOP;?>
/release-list.php">View Releases</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/rage-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/rage-edit.php?action=add">Add</a> TVRage List</li>
		<li><a href="<?php echo @WWW_TOP;?>
/movie-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/movie-add.php">Add</a> Movie List</li>
		<li><a href="<?php echo @WWW_TOP;?>
/anidb-list.php">View AniDB List</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/music-list.php">View Music List</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/console-list.php">View Console List</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/nzb-import.php">Import</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/nzb-export.php">Export</a> Nzb's</li>
		<li><a href="<?php echo @WWW_TOP;?>
/db-optimise.php">Optimise</a> Tables</li>
		<li><a href="<?php echo @WWW_TOP;?>
/comments-list.php">View Comments</a></li>
		<li><a href="<?php echo @WWW_TOP;?>
/user-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/user-edit.php?action=add">Add</a> Users</li>
		<li><a href="<?php echo @WWW_TOP;?>
/role-list.php">View</a> <a style="padding:0;" href="<?php echo @WWW_TOP;?>
/role-edit.php?action=add">Add</a> Roles</li>
		<li><a href="<?php echo @WWW_TOP;?>
/site-stats.php">Site Stats</a></li>
		</ul>
