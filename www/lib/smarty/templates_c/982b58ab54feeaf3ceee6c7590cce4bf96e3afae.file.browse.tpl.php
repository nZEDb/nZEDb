<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 22:17:22
         compiled from "/var/www/newznab/www/views/templates/frontend/browse.tpl" */ ?>
<?php /*%%SmartyHeaderCode:136577440551676eb2c1d213-40199246%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '982b58ab54feeaf3ceee6c7590cce4bf96e3afae' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/browse.tpl',
      1 => 1365732932,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '136577440551676eb2c1d213-40199246',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_strtotime')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.strtotime.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
if (!is_callable('smarty_modifier_truncate')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.truncate.php';
if (!is_callable('smarty_modifier_daysago')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.daysago.php';
if (!is_callable('smarty_modifier_timeago')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.timeago.php';
if (!is_callable('smarty_modifier_fsize_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.fsize_format.php';
?>
<h1>Browse <?php echo smarty_modifier_escape($_smarty_tpl->getVariable('catname')->value,"htmlall");?>
</h1>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	

<?php if ($_smarty_tpl->getVariable('shows')->value){?>
<p><b>Jump to</b>:
&nbsp;&nbsp;[ <a href="<?php echo @WWW_TOP;?>
/series" title="View available TV series">Series List</a> ]
&nbsp;&nbsp;[ <a href="<?php echo @WWW_TOP;?>
/myshows" title="List my watched shows">My Shows</a> ]
<br />Your shows can also be downloaded as an <a href="<?php echo @WWW_TOP;?>
/rss?t=-3&amp;dl=1&amp;i=<?php echo $_smarty_tpl->getVariable('userdata')->value['ID'];?>
&amp;r=<?php echo $_smarty_tpl->getVariable('userdata')->value['rsstoken'];?>
">Rss Feed</a>.
</p>
<?php }?>
	
<?php if (count($_smarty_tpl->getVariable('results')->value)>0){?>

<form id="nzb_multi_operations_form" action="get">

<div class="nzb_multi_operations">
	<?php if ($_smarty_tpl->getVariable('section')->value!=''){?>View: <a href="<?php echo @WWW_TOP;?>
/<?php echo $_smarty_tpl->getVariable('section')->value;?>
?t=<?php echo $_smarty_tpl->getVariable('category')->value;?>
">Covers</a> | <b>List</b><br /><?php }?>
	<small>With Selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><input type="button" class="nzb_multi_operations_sab" value="Send to SAB" /><?php }?>
	<?php if ($_smarty_tpl->getVariable('isadmin')->value||$_smarty_tpl->getVariable('ismod')->value){?>
	&nbsp;&nbsp;
	<input type="button" class="nzb_multi_operations_edit" value="Edit" />
	<input type="button" class="nzb_multi_operations_delete" value="Del" />
	<?php }?>	
</div>

<?php echo $_smarty_tpl->getVariable('pager')->value;?>


<table style="width:100%;" class="data highlight icons" id="browsetable">
	<tr>
		<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all" /><label for="chkSelectAll" style="display:none;">Select All</label></th>
		<th>name<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyname_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyname_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>category<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbycat_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbycat_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>posted<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyposted_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyposted_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>size<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbysize_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbysize_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>files<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyfiles_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyfiles_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th>stats<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbystats_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbystats_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
		<th></th>
	</tr>

	<?php  $_smarty_tpl->tpl_vars['result'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('results')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['result']->key => $_smarty_tpl->tpl_vars['result']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
<?php if (smarty_modifier_strtotime($_smarty_tpl->getVariable('lastvisit')->value)<smarty_modifier_strtotime($_smarty_tpl->tpl_vars['result']->value['adddate'])){?> new<?php }?>" id="guid<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
">
			<td class="check"><input id="chk<?php echo substr($_smarty_tpl->tpl_vars['result']->value['guid'],0,7);?>
" type="checkbox" class="nzb_check" value="<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" /></td>
			<td class="item">
			<label for="chk<?php echo substr($_smarty_tpl->tpl_vars['result']->value['guid'],0,7);?>
"><a class="title" title="View details" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
"><?php echo smarty_modifier_truncate(smarty_modifier_replace(smarty_modifier_replace(smarty_modifier_replace(smarty_modifier_replace(smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall"),"."," "),"-"," "),"["," "),"]"," "),150,"...",true);?>
</a></label value="Searchname">
			
				
				<?php if ($_smarty_tpl->tpl_vars['result']->value['passwordstatus']==1){?>
					<img title="Passworded Rar Archive" src="<?php echo @WWW_TOP;?>
/views/images/icons/lock.gif" alt="Passworded Rar Archive" />
				<?php }elseif($_smarty_tpl->tpl_vars['result']->value['passwordstatus']==2){?>
					<img title="Contains .cab/ace/rar Archive" src="<?php echo @WWW_TOP;?>
/views/images/icons/lock.gif" alt="Contains .cab/ace/rar Archive" />
				<?php }?>

				<div class="resextra">
					<div class="btns">
						<?php if ($_smarty_tpl->tpl_vars['result']->value['nfoID']>0){?><a href="<?php echo @WWW_TOP;?>
/nfo/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" title="View Nfo" class="modal_nfo rndbtn" rel="nfo">Nfo</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['imdbID']>0){?><a href="#" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
" title="View movie info" class="modal_imdb rndbtn" rel="movie" >Cover</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['haspreview']==1&&$_smarty_tpl->getVariable('userdata')->value['canpreview']==1){?><a href="<?php echo @WWW_TOP;?>
/covers/preview/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
_thumb.jpg" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" title="Screenshot of <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
" class="modal_prev rndbtn" rel="preview">Preview</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['musicinfoID']>0){?><a href="#" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['musicinfoID'];?>
" title="View music info" class="modal_music rndbtn" rel="music" >Cover</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['consoleinfoID']>0){?><a href="#" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['consoleinfoID'];?>
" title="View console info" class="modal_console rndbtn" rel="console" >Cover</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['rageID']>0){?><a class="rndbtn" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['result']->value['rageID'];?>
" title="View all episodes">View Series</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['anidbID']>0){?><a class="rndbtn" href="<?php echo @WWW_TOP;?>
/anime/<?php echo $_smarty_tpl->tpl_vars['result']->value['anidbID'];?>
" title="View all episodes">View Anime</a><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['tvairdate']!=''){?><span class="seriesinfo rndbtn" title="<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
">Aired <?php if (smarty_modifier_strtotime($_smarty_tpl->tpl_vars['result']->value['tvairdate'])>time()){?>in future<?php }else{ ?><?php echo smarty_modifier_daysago($_smarty_tpl->tpl_vars['result']->value['tvairdate']);?>
<?php }?></span><?php }?>
						<?php if ($_smarty_tpl->tpl_vars['result']->value['reID']>0){?><span class="mediainfo rndbtn" title="<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
">Media</span><?php }?>
					</div>
				</div>
			</td>
			<td class="less"><a title="Browse <?php echo $_smarty_tpl->tpl_vars['result']->value['category_name'];?>
" href="<?php echo @WWW_TOP;?>
/browse?t=<?php echo $_smarty_tpl->tpl_vars['result']->value['categoryID'];?>
"><?php echo $_smarty_tpl->tpl_vars['result']->value['category_name'];?>
</a></td>
			<td class="less mid" title="<?php echo $_smarty_tpl->tpl_vars['result']->value['postdate'];?>
"><?php echo smarty_modifier_timeago($_smarty_tpl->tpl_vars['result']->value['postdate']);?>
</td>
			<td class="less right"><?php echo smarty_modifier_fsize_format($_smarty_tpl->tpl_vars['result']->value['size'],"MB");?>
<?php if ($_smarty_tpl->tpl_vars['result']->value['completion']>0){?><br /><?php if ($_smarty_tpl->tpl_vars['result']->value['completion']<100){?><span class="warning"><?php echo $_smarty_tpl->tpl_vars['result']->value['completion'];?>
%</span><?php }else{ ?><?php echo $_smarty_tpl->tpl_vars['result']->value['completion'];?>
%<?php }?><?php }?></td>
			<td class="less mid">
				<a title="View file list" href="<?php echo @WWW_TOP;?>
/filelist/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
"><?php echo $_smarty_tpl->tpl_vars['result']->value['totalpart'];?>
</a>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['rarinnerfilecount']>0){?>
					<div class="rarfilelist">
						<img src="<?php echo @WWW_TOP;?>
/views/images/icons/magnifier.png" alt="<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
" class="tooltip" />				
					</div>
				<?php }?>
			</td>
			<td class="less" nowrap="nowrap"><a title="View comments" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/#comments"><?php echo $_smarty_tpl->tpl_vars['result']->value['comments'];?>
 cmt<?php if ($_smarty_tpl->tpl_vars['result']->value['comments']!=1){?>s<?php }?></a><br/><?php echo $_smarty_tpl->tpl_vars['result']->value['grabs'];?>
 grab<?php if ($_smarty_tpl->tpl_vars['result']->value['grabs']!=1){?>s<?php }?></td>
			<td class="icons">
				<div class="icon icon_nzb"><a title="Download Nzb" href="<?php echo @WWW_TOP;?>
/getnzb/<?php echo $_smarty_tpl->tpl_vars['result']->value['guid'];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['searchname'],"htmlall");?>
">&nbsp;</a></div>
				<div class="icon icon_cart" title="Add to Cart"></div>
				<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><div class="icon icon_sab" title="Send to my Sabnzbd"></div><?php }?>
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
	<?php if ($_smarty_tpl->getVariable('isadmin')->value||$_smarty_tpl->getVariable('ismod')->value){?>
	&nbsp;&nbsp;
	<input type="button" class="nzb_multi_operations_edit" value="Edit" />
	<input type="button" class="nzb_multi_operations_delete" value="Del" />
	<?php }?>	
</div>

</form>

<?php }?>

<br/><br/><br/>
