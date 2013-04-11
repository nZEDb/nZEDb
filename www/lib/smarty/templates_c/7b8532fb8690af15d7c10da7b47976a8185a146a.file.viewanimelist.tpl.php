<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:10:48
         compiled from "/var/www/newznab/www/views/templates/frontend/viewanimelist.tpl" */ ?>
<?php /*%%SmartyHeaderCode:8140272955166d278e657a9-26448787%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '7b8532fb8690af15d7c10da7b47976a8185a146a' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/viewanimelist.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '8140272955166d278e657a9-26448787',
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

<div style="float:right;">

	<form name="anidbsearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="title" type="text" name="title" value="<?php echo $_smarty_tpl->getVariable('animetitle')->value;?>
" size="25" />
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

<p><b>Jump to</b>:
&nbsp;&nbsp;[ <?php if ($_smarty_tpl->getVariable('animeletter')->value=='0-9'){?><b><u><?php }?><a href="<?php echo @WWW_TOP;?>
/anime/0-9">0-9</a><?php if ($_smarty_tpl->getVariable('animeletter')->value=='0-9'){?></u></b><?php }?> 
<?php  $_smarty_tpl->tpl_vars['range'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('animerange')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['range']->key => $_smarty_tpl->tpl_vars['range']->value){
?>
<?php if ($_smarty_tpl->tpl_vars['range']->value==$_smarty_tpl->getVariable('animeletter')->value){?><b><u><?php }?><a href="<?php echo @WWW_TOP;?>
/anime/<?php echo $_smarty_tpl->tpl_vars['range']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['range']->value;?>
</a><?php if ($_smarty_tpl->tpl_vars['range']->value==$_smarty_tpl->getVariable('animeletter')->value){?></u></b><?php }?> 
<?php }} ?>]
</p>

<?php echo $_smarty_tpl->getVariable('site')->value->adbrowse;?>
	

<?php if (count($_smarty_tpl->getVariable('animelist')->value)>0){?>

<table style="width:100%;" class="data highlight icons" id="browsetable">
	<?php  $_smarty_tpl->tpl_vars['anime'] = new Smarty_Variable;
 $_smarty_tpl->tpl_vars['aletter'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('animelist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['anime']->key => $_smarty_tpl->tpl_vars['anime']->value){
 $_smarty_tpl->tpl_vars['aletter']->value = $_smarty_tpl->tpl_vars['anime']->key;
?>
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2><?php echo $_smarty_tpl->getVariable('animeletter')->value;?>
...</h2></td>
		</tr>
		<tr>
			<th width="40%">Name</th>
			<th width="10%">Type</th>
			<th width="35%">Categories</th>
			<th width="5%">Rating</th>
			<th>View</th>
		</tr>
		<?php  $_smarty_tpl->tpl_vars['a'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['anime']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['a']->key => $_smarty_tpl->tpl_vars['a']->value){
?>
			<tr class="<?php echo smarty_function_cycle(array('values'=>",alt"),$_smarty_tpl);?>
">
				<td><a class="title" title="View anime" href="<?php echo @WWW_TOP;?>
/anime/<?php echo $_smarty_tpl->tpl_vars['a']->value['anidbID'];?>
"><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['a']->value['title'],"htmlall");?>
</a><?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['a']->value['startdate'];?>
<?php $_tmp1=ob_get_clean();?><?php if ($_tmp1!=''){?><br />(<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['a']->value['startdate']);?>
 - <?php }?><?php if ($_smarty_tpl->tpl_vars['a']->value['enddate']!=''){?><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['a']->value['enddate']);?>
<?php }?>)</td>
				<td style="text-align: center;"><?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['a']->value['type'];?>
<?php $_tmp2=ob_get_clean();?><?php if ($_tmp2!=''){?><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['a']->value['type'],"htmlall");?>
<?php }?></td>
				<td><?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['a']->value['categories'];?>
<?php $_tmp3=ob_get_clean();?><?php if ($_tmp3!=''){?><?php echo smarty_modifier_replace(smarty_modifier_escape($_smarty_tpl->tpl_vars['a']->value['categories'],"htmlall"),'|',', ');?>
<?php }?></td>
				<td style="text-align: center;"><?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['a']->value['rating'];?>
<?php $_tmp4=ob_get_clean();?><?php if ($_tmp4!=''){?><?php echo $_smarty_tpl->tpl_vars['a']->value['rating'];?>
<?php }?></td>
				<td><a title="View anime" href="<?php echo @WWW_TOP;?>
/anime/<?php echo $_smarty_tpl->tpl_vars['a']->value['anidbID'];?>
">Anime</a>&nbsp;&nbsp;<?php if ($_smarty_tpl->tpl_vars['a']->value['anidbID']>0){?><a title="View at AniDB" target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
http://anidb.net/perl-bin/animedb.pl?show=anime&aid=<?php echo $_smarty_tpl->tpl_vars['a']->value['anidbID'];?>
">AniDB</a><?php }?></td>
			</tr>
		<?php }} ?>
	<?php }} ?>
</table>

<?php }else{ ?>
<h2>No results</h2>
<?php }?>
