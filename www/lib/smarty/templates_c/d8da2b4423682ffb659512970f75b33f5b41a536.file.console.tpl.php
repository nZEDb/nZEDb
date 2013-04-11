<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:09:36
         compiled from "/var/www/newznab/www/views/templates/frontend/console.tpl" */ ?>
<?php /*%%SmartyHeaderCode:7781051495166d23022be22-76068572%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd8da2b4423682ffb659512970f75b33f5b41a536' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/console.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7781051495166d23022be22-76068572',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
if (!is_callable('smarty_modifier_date_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_timeago')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.timeago.php';
if (!is_callable('smarty_modifier_fsize_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.fsize_format.php';
?> 
<h1>Browse Console</h1>

<form name="browseby" action="console">
<table class="rndbtn" border="0" cellpadding="2" cellspacing="0">
	<tr>
		<th class="left"><label for="title">Title</label></th>
		<th class="left"><label for="platform">Platform</label></th>
		<th class="left"><label for="genre">Genre</label></th>
		<th class="left"><label for="category">Category</label></th>
		<th></th>
	</tr>
	<tr>
		<td><input id="title" type="text" name="title" value="<?php echo $_smarty_tpl->getVariable('title')->value;?>
" size="15" /></td>
		<td><input id="platform" type="text" name="platform" value="<?php echo $_smarty_tpl->getVariable('platform')->value;?>
" size="15" /></td>
		<td>
			<select id="genre" name="genre">
			<option class="grouping" value=""></option>
			<?php  $_smarty_tpl->tpl_vars['gen'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('genres')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['gen']->key => $_smarty_tpl->tpl_vars['gen']->value){
?>
				<option <?php if ($_smarty_tpl->tpl_vars['gen']->value['ID']==$_smarty_tpl->getVariable('genre')->value){?>selected="selected"<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['gen']->value['ID'];?>
"><?php echo $_smarty_tpl->tpl_vars['gen']->value['title'];?>
</option>
			<?php }} ?>
			</select>
		</td>
		<td>
			<select id="category" name="t">
			<option class="grouping" value="1000"></option>
			<?php  $_smarty_tpl->tpl_vars['ct'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('catlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['ct']->key => $_smarty_tpl->tpl_vars['ct']->value){
?>
				<option <?php if ($_smarty_tpl->tpl_vars['ct']->value['ID']==$_smarty_tpl->getVariable('category')->value){?>selected="selected"<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['ct']->value['ID'];?>
"><?php echo $_smarty_tpl->tpl_vars['ct']->value['title'];?>
</option>
			<?php }} ?>
			</select>
		</td>
		<td><input type="submit" value="Go" /></td>
	</tr>
</table>
</form>
<p></p>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	

<?php if (count($_smarty_tpl->getVariable('results')->value)>0){?>

<form id="nzb_multi_operations_form" action="get">

<div class="nzb_multi_operations">
	View: <b>Covers</b> | <a href="<?php echo @WWW_TOP;?>
/browse?t=<?php echo $_smarty_tpl->getVariable('category')->value;?>
">List</a><br />
	<small>With Selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><input type="button" class="nzb_multi_operations_sab" value="Send to SAB" /><?php }?>
</div>
<br/>

<?php echo $_smarty_tpl->getVariable('pager')->value;?>


<table style="width:100%;" class="data highlight icons" id="coverstable">
	<tr>
		<th width="130"><input type="checkbox" class="nzb_check_all" /></th>
		<th>title<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbytitle_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbytitle_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>platform<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyplatform_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyplatform_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>genre<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbygenre_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbygenre_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>release date<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyreleasedate_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyreleasedate_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>posted<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyposted_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyposted_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>size<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbysize_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbysize_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>files<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyfiles_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyfiles_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>stats<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbystats_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbystats_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
	</tr>

	<?php  $_smarty_tpl->tpl_vars['result'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('results')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['result']->key => $_smarty_tpl->tpl_vars['result']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td class="mid">
				<div class="movcover">
				<a class="title" title="View details" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
">
					<img class="shadow" src="<?php echo @WWW_TOP;?>
/covers/console/<?php if ($_smarty_tpl->tpl_vars['result']->value['cover']==1){?><?php echo $_smarty_tpl->tpl_vars['result']->value['consoleinfoID'];?>
.jpg<?php }else{ ?>no-cover.jpg<?php }?>" width="120" border="0" alt="<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['title'],"htmlall");?>
" />
				</a>
				<div class="movextra">
					<?php if ($_smarty_tpl->tpl_vars['result']->value['nfoID']>0){?><a href="<?php echo @WWW_TOP;?>
/nfo/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" title="View Nfo" class="rndbtn modal_nfo" rel="nfo">Nfo</a><?php }?>
					<a class="rndbtn" target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
<?php echo $_smarty_tpl->tpl_vars['result']->value['url'];?>
" name="amazon<?php echo $_smarty_tpl->tpl_vars['result']->value['consoleinfoID'];?>
" title="View amazon page">Amazon</a>
					<a class="rndbtn" href="<?php echo @WWW_TOP;?>
/browse?g=<?php echo $_smarty_tpl->tpl_vars['result']->value['group_name'];?>
" title="Browse releases in <?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['result']->value['group_name'],"alt.binaries","a.b");?>
">Grp</a>
				</div>
				</div>
			</td>
			<td colspan="8" class="left" id="guid<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
">
				<h2><a class="title" title="View details" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
"><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['title'],"htmlall");?>
 - <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['platform'],"htmlall");?>
</a></h2>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['genre']!=''){?><b>Genre:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['genre'];?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['esrb']!=''){?><b>Rating:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['esrb'];?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['publisher']!=''){?><b>Publisher:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['publisher'];?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['releasedate']!=''){?><b>Released:</b> <?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['result']->value['releasedate']);?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['review']!=''){?><b>Review:</b> <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['review'],'htmlall');?>
<br /><?php }?>
				<br />
				<div class="movextra">
					<b><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
</b> <a class="rndbtn" href="<?php echo @WWW_TOP;?>
/console?platform=<?php echo $_smarty_tpl->tpl_vars['result']->value['platform'];?>
" title="View similar nzbs">Similar</a>
					<?php if ($_smarty_tpl->getVariable('isadmin')->value){?>
						<a class="rndbtn" href="<?php echo @WWW_TOP;?>
/admin/release-edit.php?id=<?php echo $_smarty_tpl->tpl_vars['result']->value['releaseID'];?>
&amp;from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" title="Edit Release">Edit</a> <a class="rndbtn confirm_action" href="<?php echo @WWW_TOP;?>
/admin/release-delete.php?id=<?php echo $_smarty_tpl->tpl_vars['result']->value['releaseID'];?>
&amp;from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" title="Delete Release">Del</a> <a class="rndbtn confirm_action" href="<?php echo @WWW_TOP;?>
/admin/release-rebuild.php?id=<?php echo $_smarty_tpl->tpl_vars['result']->value['releaseID'];?>
&amp;from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" title="Rebuild Release - Delete and reset for reprocessing if binaries still exist.">Reb</a>
					<?php }?>
					<br />
					<b>Info:</b> <?php echo smarty_modifier_timeago($_smarty_tpl->tpl_vars['result']->value['postdate']);?>
,  <?php echo smarty_modifier_fsize_format($_smarty_tpl->tpl_vars['result']->value['size'],"MB");?>
,  <a title="View file list" href="<?php echo @WWW_TOP;?>
/filelist/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
"><?php echo $_smarty_tpl->tpl_vars['result']->value['totalpart'];?>
 files</a>,  <a title="View comments for <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
#comments"><?php echo $_smarty_tpl->tpl_vars['result']->value['comments'];?>
 cmt<?php if ($_smarty_tpl->tpl_vars['result']->value['comments']!=1){?>s<?php }?></a>, <?php echo $_smarty_tpl->tpl_vars['result']->value['grabs'];?>
 grab<?php if ($_smarty_tpl->tpl_vars['result']->value['grabs']!=1){?>s<?php }?>
					<br />
					<div class="icon"><input type="checkbox" class="nzb_check" value="<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" /></div>
					<div class="icon icon_nzb"><a title="Download Nzb" href="<?php echo @WWW_TOP;?>
/getnzb/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
">&nbsp;</a></div>
					<div class="icon icon_cart" title="Add to Cart"></div>
					<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><div class="icon icon_sab" title="Send to my Sabnzbd"></div><?php }?>
				</div>
			</td>
		</tr>
	<?php }} ?>
	
</table>

<br/>

<?php echo $_smarty_tpl->getVariable('pager')->value;?>


<div class="nzb_multi_operations">
	<small>With Selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><input type="button" class="nzb_multi_operations_sab" value="Send to SAB" /><?php }?>
</div>

</form>

<?php }?>

<br/><br/><br/>
