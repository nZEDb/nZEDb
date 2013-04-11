<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:05:32
         compiled from "/var/www/newznab/www/views/templates/frontend/usefullinksmenu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:18805399895166d13cca1933-00543833%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '7bcbba019d4c9929498380923c1cedf317a389eb' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/usefullinksmenu.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '18805399895166d13cca1933-00543833',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<li class="menu_useful"> 
	<h2>Useful Links</h2> 
	<ul>
	<li class="mmenu"><a title="Contact Us" href="<?php echo @WWW_TOP;?>
/contact-us">Contact Us</a></li>
	<li class="mmenu"><a title="Site Map" href="<?php echo @WWW_TOP;?>
/sitemap">Site Map</a></li>
	<?php if ($_smarty_tpl->getVariable('loggedin')->value=="true"){?>
	<li class="mmenu"><a title="Search Raw Headers" href="<?php echo @WWW_TOP;?>
/searchraw">Raw Search</a></li>
	<li class="mmenu"><a title="<?php echo $_smarty_tpl->getVariable('site')->value->title;?>
 Rss Feeds" href="<?php echo @WWW_TOP;?>
/rss">Rss Feeds</a></li>
	<li class="mmenu"><a title="<?php echo $_smarty_tpl->getVariable('site')->value->title;?>
 Api" href="<?php echo @WWW_TOP;?>
/apihelp">Api</a></li>
	<?php }?>
	<?php  $_smarty_tpl->tpl_vars['content'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('usefulcontentlist')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['content']->key => $_smarty_tpl->tpl_vars['content']->value){
?>
		<li class="mmenu<?php if ($_smarty_tpl->getVariable('menu')->value['newwindow']=="1"){?>_new<?php }?>"><a <?php if ($_smarty_tpl->getVariable('menu')->value['newwindow']=="1"){?>class="external" target="null"<?php }?> title="<?php echo $_smarty_tpl->getVariable('content')->value->title;?>
" href="<?php echo @WWW_TOP;?>
/content/<?php echo $_smarty_tpl->getVariable('content')->value->id;?>
<?php echo $_smarty_tpl->getVariable('content')->value->url;?>
"><?php echo $_smarty_tpl->getVariable('content')->value->title;?>
</a></li>
	<?php }} ?>
	</ul>
</li>
