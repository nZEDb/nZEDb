<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:05:38
         compiled from "/var/www/newznab/www/views/templates/frontend/viewserieslist.tpl" */ ?>
<?php /*%%SmartyHeaderCode:17209534745166d142acc196-74903268%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '42deb309122ecaa2ff9ddbb661d5a1caf853522a' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/viewserieslist.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '17209534745166d142acc196-74903268',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_modifier_date_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
?><h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<p><b>Jump to</b>:
&nbsp;&nbsp;[ <?php if ($_smarty_tpl->getVariable('seriesletter')->value=='0-9'){?><b><u><?php }?><a href="<?php echo @WWW_TOP;?>
/series/0-9">0-9</a><?php if ($_smarty_tpl->getVariable('seriesletter')->value=='0-9'){?></u></b><?php }?> 
<?php  $_smarty_tpl->tpl_vars['range'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('seriesrange')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['range']->key => $_smarty_tpl->tpl_vars['range']->value){
?>
<?php if ($_smarty_tpl->tpl_vars['range']->value==$_smarty_tpl->getVariable('seriesletter')->value){?><b><u><?php }?><a href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['range']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['range']->value;?>
</a><?php if ($_smarty_tpl->tpl_vars['range']->value==$_smarty_tpl->getVariable('seriesletter')->value){?></u></b><?php }?> 
<?php }} ?>]
&nbsp;&nbsp;[ <a href="<?php echo @WWW_TOP;?>
/myshows" title="List my watched shows">My Shows</a> ]
&nbsp;&nbsp;[ <a href="<?php echo @WWW_TOP;?>
/myshows/browse" title="browse your shows">Browse My Shows</a> ]
</p>

<div style="float:right;">
	<form name="ragesearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="title" type="text" name="title" value="<?php echo $_smarty_tpl->getVariable('ragename')->value;?>
" size="25" />
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	

<?php if (count($_smarty_tpl->getVariable('serieslist')->value)>0){?>

<table style="width:100%;" class="data highlight icons" id="browsetable">
	<?php  $_smarty_tpl->tpl_vars['series'] = new Smarty_Variable;
 $_smarty_tpl->tpl_vars['sletter'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('serieslist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['series']->key => $_smarty_tpl->tpl_vars['series']->value){
 $_smarty_tpl->tpl_vars['sletter']->value = $_smarty_tpl->tpl_vars['series']->key;
?>
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2><?php echo $_smarty_tpl->tpl_vars['sletter']->value;?>
...</h2></td>
		</tr>
		<tr>
			<th width="35%">Name</th>
			<th>Country</th>
			<th width="35%">Genre</th>
			<th class="mid">Option</th>
			<th class="mid">View</th>
		</tr>
		<?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['series']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
?>
			<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
				<td><a class="title" title="View series" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
"><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['s']->value['releasetitle'],"htmlall");?>
</a><?php if ($_smarty_tpl->tpl_vars['s']->value['prevdate']!=''){?><br />Last: <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['s']->value['previnfo'],"htmlall");?>
 aired <?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['s']->value['prevdate']);?>
<?php }?></td>
				<td><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['s']->value['country'],"htmlall");?>
</td>
				<td><?php echo smarty_modifier_replace(smarty_modifier_escape($_smarty_tpl->tpl_vars['s']->value['genre'],"htmlall"),'|',', ');?>
</td>
				<td class="mid">
					<?php if ($_smarty_tpl->tpl_vars['s']->value['userseriesID']!=''){?>
						<a href="<?php echo @WWW_TOP;?>
/myshows/edit/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
?from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" class="myshows" rel="edit" name="series<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
" title="Edit">Edit</a>&nbsp;&nbsp;<a href="<?php echo @WWW_TOP;?>
/myshows/delete/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
?from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" class="myshows" rel="remove" name="series<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
" title="Remove from My Shows">Remove</a>
					<?php }else{ ?>
						<a href="<?php echo @WWW_TOP;?>
/myshows/add/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
?from=<?php echo smarty_modifier_escape($_SERVER['REQUEST_URI'],"url");?>
" class="myshows" rel="add" name="series<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
" title="Add to My Shows">Add</a>
					<?php }?>
				</td>
				<td class="mid"><a title="View series" href="<?php echo @WWW_TOP;?>
/series/<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
">Series</a>&nbsp;&nbsp;<?php if ($_smarty_tpl->tpl_vars['s']->value['rageID']>0){?><a title="View at TVRage" target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
http://www.tvrage.com/shows/id-<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
">TVRage</a>&nbsp;&nbsp;<a title="RSS Feed for <?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['s']->value['releasetitle'],"htmlall");?>
" href="<?php echo @WWW_TOP;?>
/rss?rage=<?php echo $_smarty_tpl->tpl_vars['s']->value['rageID'];?>
&amp;dl=1&amp;i=<?php echo $_smarty_tpl->getVariable('userdata')->value['ID'];?>
&amp;r=<?php echo $_smarty_tpl->getVariable('userdata')->value['rsstoken'];?>
">Rss</a><?php }?></td>
			</tr>
		<?php }} ?>
	<?php }} ?>
</table>

<?php }else{ ?>
<h2>No results</h2>
<?php }?>
