<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 13:31:45
         compiled from "/var/www/newznab/www/views/templates/frontend/upcoming.tpl" */ ?>
<?php /*%%SmartyHeaderCode:20969383875166f381a03c49-37978548%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9f00ecef22fc53c97ef1affa5b166ccb4c4ead00' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/upcoming.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '20969383875166f381a03c49-37978548',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
?><h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<p>
<a href="<?php echo @WWW_TOP;?>
/upcoming/1">Box Office</a> | 
<a href="<?php echo @WWW_TOP;?>
/upcoming/2">In Theatre</a> | 
<a href="<?php echo @WWW_TOP;?>
/upcoming/3">Opening</a> | 
<a href="<?php echo @WWW_TOP;?>
/upcoming/4">Upcoming</a> | 
<a href="<?php echo @WWW_TOP;?>
/upcoming/5">DVD Releases</a>
</p>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	

<?php if (count($_smarty_tpl->getVariable('data')->value)>0){?>

<table style="width:100%;" class="data highlight icons" id="coverstable">
		<tr>
			<th></th>
			<th>Name</th>
		</tr>

		<?php  $_smarty_tpl->tpl_vars['result'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('data')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['result']->key => $_smarty_tpl->tpl_vars['result']->value){
?>
		<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
			<td class="mid">
				<div class="movcover">
					<img class="shadow" src="<?php echo $_smarty_tpl->getVariable('result')->value->posters->profile;?>
" width="120" border="0" alt="<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('result')->value->title,"htmlall");?>
" />
					<div class="movextra">
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h2><a href="<?php echo @WWW_TOP;?>
/movies?title=<?php echo $_smarty_tpl->getVariable('result')->value->title;?>
&year=<?php echo $_smarty_tpl->getVariable('result')->value->year;?>
"><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('result')->value->title,"htmlall");?>
</a> (<a class="title" title="<?php echo $_smarty_tpl->getVariable('result')->value->year;?>
" href="<?php echo @WWW_TOP;?>
/movies?year=<?php echo $_smarty_tpl->getVariable('result')->value->year;?>
"><?php echo $_smarty_tpl->getVariable('result')->value->year;?>
</a>) <?php if ($_smarty_tpl->getVariable('result')->value->ratings->critics_score>0){?><?php echo $_smarty_tpl->getVariable('result')->value->ratings->critics_score;?>
/100<?php }?></h2>
				<?php if ($_smarty_tpl->getVariable('result')->value->synopsis==''){?>No synopsis. Check <a target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
<?php echo $_smarty_tpl->getVariable('result')->value->links->alternate;?>
" title="View Rotten Tomatoes Details">Rotten Tomatoes</a> for more information.<?php }else{ ?><?php echo $_smarty_tpl->getVariable('result')->value->synopsis;?>
<?php }?>
				<?php if (count($_smarty_tpl->getVariable('result')->value->abridged_cast)>0){?>
					<br /><br />
					<b>Starring:</b> 
					<?php  $_smarty_tpl->tpl_vars['cast'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('result')->value->abridged_cast; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['cast']->total= $_smarty_tpl->_count($_from);
 $_smarty_tpl->tpl_vars['cast']->iteration=0;
if ($_smarty_tpl->tpl_vars['cast']->total > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['cast']->key => $_smarty_tpl->tpl_vars['cast']->value){
 $_smarty_tpl->tpl_vars['cast']->iteration++;
 $_smarty_tpl->tpl_vars['cast']->last = $_smarty_tpl->tpl_vars['cast']->iteration === $_smarty_tpl->tpl_vars['cast']->total;
 $_smarty_tpl->tpl_vars['smarty']->value['foreach']['cast']['last'] = $_smarty_tpl->tpl_vars['cast']->last;
?>
						<a href="<?php echo @WWW_TOP;?>
/movies?actors=<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('cast')->value->name,"htmlall");?>
" title="Search for movies starring <?php echo smarty_modifier_escape($_smarty_tpl->getVariable('cast')->value->name,"htmlall");?>
"><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('cast')->value->name,"htmlall");?>
</a>
						<?php if ($_smarty_tpl->getVariable('smarty')->value['foreach']['cast']['last']){?><br/><br/><?php }else{ ?>,<?php }?>						
					<?php }} ?>
				<?php }else{ ?>
					<br/><br/>
				<?php }?>
				<a class="rndbtn" target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
<?php echo $_smarty_tpl->getVariable('result')->value->links->alternate;?>
" title="View Rotten Tomatoes Details">Rotten Tomatoes</a>
			</td>
		</tr>
		<?php }} ?>
</table>

<?php }else{ ?>
<h2>No results</h2>
<?php }?>
