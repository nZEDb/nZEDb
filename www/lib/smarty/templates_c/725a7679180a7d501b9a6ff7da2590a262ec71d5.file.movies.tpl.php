<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 11:09:37
         compiled from "/var/www/newznab/www/views/templates/frontend/movies.tpl" */ ?>
<?php /*%%SmartyHeaderCode:20009486475166d2315e2bd7-60274297%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '725a7679180a7d501b9a6ff7da2590a262ec71d5' => 
    array (
      0 => '/var/www/newznab/www/views/templates/frontend/movies.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '20009486475166d2315e2bd7-60274297',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_function_cycle')) include '/var/www/newznab/www/lib/smarty/plugins/function.cycle.php';
if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_modifier_timeago')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.timeago.php';
if (!is_callable('smarty_modifier_fsize_format')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.fsize_format.php';
if (!is_callable('smarty_modifier_replace')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.replace.php';
?> 
<h1>Browse <?php echo $_smarty_tpl->getVariable('catname')->value;?>
</h1>

<form name="browseby" action="movies">
<table class="rndbtn" border="0" cellpadding="2" cellspacing="0">
	<tr>
		<th class="left"><label for="movietitle">Title</label></th>
		<th class="left"><label for="movieactors">Actor</label></th>
		<th class="left"><label for="moviedirector">Director</label></th>
		<th class="left"><label for="rating">Rating</label></th>
		<th class="left"><label for="genre">Genre</label></th>
		<th class="left"><label for="year">Year</label></th>
		<th class="left"><label for="category">Category</label></th>
		<th></th>
	</tr>
	<tr>
		<td><input id="movietitle" type="text" name="title" value="<?php echo $_smarty_tpl->getVariable('title')->value;?>
" size="15" /></td>
		<td><input id="movieactors" type="text" name="actors" value="<?php echo $_smarty_tpl->getVariable('actors')->value;?>
" size="15" /></td>
		<td><input id="moviedirector" type="text" name="director" value="<?php echo $_smarty_tpl->getVariable('director')->value;?>
" size="15" /></td>
		<td>
			<select id="rating" name="rating">
			<option class="grouping" value=""></option>
			<?php  $_smarty_tpl->tpl_vars['rate'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('ratings')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['rate']->key => $_smarty_tpl->tpl_vars['rate']->value){
?>
				<option <?php if ($_smarty_tpl->getVariable('rating')->value==$_smarty_tpl->tpl_vars['rate']->value){?>selected="selected"<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['rate']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['rate']->value;?>
</option>
			<?php }} ?>
			</select>
		</td>
		<td>
			<select id="genre" name="genre">
			<option class="grouping" value=""></option>
			<?php  $_smarty_tpl->tpl_vars['gen'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('genres')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['gen']->key => $_smarty_tpl->tpl_vars['gen']->value){
?>
				<option <?php if ($_smarty_tpl->tpl_vars['gen']->value==$_smarty_tpl->getVariable('genre')->value){?>selected="selected"<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['gen']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['gen']->value;?>
</option>
			<?php }} ?>
			</select>
		</td>
		<td>
			<select id="year" name="year">
			<option class="grouping" value=""></option>
			<?php  $_smarty_tpl->tpl_vars['yr'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('years')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['yr']->key => $_smarty_tpl->tpl_vars['yr']->value){
?>
				<option <?php if ($_smarty_tpl->tpl_vars['yr']->value==$_smarty_tpl->getVariable('year')->value){?>selected="selected"<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['yr']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['yr']->value;?>
</option>
			<?php }} ?>
			</select>
		</td>
		<td>
			<select id="category" name="t">
			<option class="grouping" value="2000"></option>
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
		<th>year<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyyear_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyyear_asc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_up.gif" alt="" /></a></th>
		<th>rating<br/><a title="Sort Descending" href="<?php echo $_smarty_tpl->getVariable('orderbyrating_desc')->value;?>
"><img src="<?php echo @WWW_TOP;?>
/views/images/sorting/arrow_down.gif" alt="" /></a><a title="Sort Ascending" href="<?php echo $_smarty_tpl->getVariable('orderbyrating_asc')->value;?>
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
				<a target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
http://www.imdb.com/title/tt<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
/" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
" title="View movie info" class="modal_imdb" rel="movie" >
					<img class="shadow" src="<?php echo @WWW_TOP;?>
/covers/movies/<?php if ($_smarty_tpl->tpl_vars['result']->value['cover']==1){?><?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
-cover.jpg<?php }else{ ?>no-cover.jpg<?php }?>" width="120" border="0" alt="<?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['title'],"htmlall");?>
" />
				</a>
				<div class="movextra">
					<a target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
http://www.imdb.com/title/tt<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
/" name="name<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
" title="View movie info" class="rndbtn modal_imdb" rel="movie" >Cover</a>
					<a class="rndbtn" target="_blank" href="<?php echo $_smarty_tpl->getVariable('site')->value->dereferrer_link;?>
http://www.imdb.com/title/tt<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
/" name="imdb<?php echo $_smarty_tpl->tpl_vars['result']->value['imdbID'];?>
" title="View imdb page">Imdb</a>
				</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h2><?php echo smarty_modifier_escape($_smarty_tpl->tpl_vars['result']->value['title'],"htmlall");?>
 (<a class="title" title="<?php echo $_smarty_tpl->tpl_vars['result']->value['year'];?>
" href="<?php echo @WWW_TOP;?>
/movies?year=<?php echo $_smarty_tpl->tpl_vars['result']->value['year'];?>
"><?php echo $_smarty_tpl->tpl_vars['result']->value['year'];?>
</a>) <?php if ($_smarty_tpl->tpl_vars['result']->value['rating']!=''){?><?php echo $_smarty_tpl->tpl_vars['result']->value['rating'];?>
/10<?php }?></h2>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['tagline']!=''){?><b><?php echo $_smarty_tpl->tpl_vars['result']->value['tagline'];?>
</b><br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['plot']!=''){?><?php echo $_smarty_tpl->tpl_vars['result']->value['plot'];?>
<br /><br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['genre']!=''){?><b>Genre:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['genre'];?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['director']!=''){?><b>Director:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['director'];?>
<br /><?php }?>
				<?php if ($_smarty_tpl->tpl_vars['result']->value['actors']!=''){?><b>Starring:</b> <?php echo $_smarty_tpl->tpl_vars['result']->value['actors'];?>
<br /><br /><?php }?>
				<div class="movextra">
					<table>
						<?php $_smarty_tpl->tpl_vars["msplits"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_id']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mguid"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_guid']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mnfo"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_nfoID']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mgrp"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_grpname']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mname"] = new Smarty_variable(explode("#",$_smarty_tpl->tpl_vars['result']->value['grp_release_name']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mpostdate"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_postdate']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["msize"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_size']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mtotalparts"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_totalparts']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mcomments"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_comments']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mgrabs"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_grabs']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mpass"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_release_password']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["minnerfiles"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_rarinnerfilecount']), null, null);?>
						<?php $_smarty_tpl->tpl_vars["mhaspreview"] = new Smarty_variable(explode(",",$_smarty_tpl->tpl_vars['result']->value['grp_haspreview']), null, null);?>
						<?php  $_smarty_tpl->tpl_vars['m'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('msplits')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['m']->total= $_smarty_tpl->_count($_from);
 $_smarty_tpl->tpl_vars['m']->index=-1;
if ($_smarty_tpl->tpl_vars['m']->total > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['m']->key => $_smarty_tpl->tpl_vars['m']->value){
 $_smarty_tpl->tpl_vars['m']->index++;
?>
						<tr id="guid<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
" <?php if ($_smarty_tpl->tpl_vars['m']->index>1){?>class="mlextra"<?php }?>>
							<td>
								<div class="icon"><input type="checkbox" class="nzb_check" value="<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
" /></div>							
							</td>
							<td>
								<a href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
"><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
</a>
								<div>
								Posted <?php echo smarty_modifier_timeago($_smarty_tpl->getVariable('mpostdate')->value[$_smarty_tpl->tpl_vars['m']->index]);?>
,  <?php echo smarty_modifier_fsize_format($_smarty_tpl->getVariable('msize')->value[$_smarty_tpl->tpl_vars['m']->index],"MB");?>
,  <a title="View file list" href="<?php echo @WWW_TOP;?>
/filelist/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
"><?php echo $_smarty_tpl->getVariable('mtotalparts')->value[$_smarty_tpl->tpl_vars['m']->index];?>
 files</a>,  <a title="View comments for <?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
" href="<?php echo @WWW_TOP;?>
/details/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
#comments"><?php echo $_smarty_tpl->getVariable('mcomments')->value[$_smarty_tpl->tpl_vars['m']->index];?>
 cmt<?php if ($_smarty_tpl->getVariable('mcomments')->value[$_smarty_tpl->tpl_vars['m']->index]!=1){?>s<?php }?></a>, <?php echo $_smarty_tpl->getVariable('mgrabs')->value[$_smarty_tpl->tpl_vars['m']->index];?>
 grab<?php if ($_smarty_tpl->getVariable('mgrabs')->value[$_smarty_tpl->tpl_vars['m']->index]!=1){?>s<?php }?>,								
								<?php if ($_smarty_tpl->getVariable('mnfo')->value[$_smarty_tpl->tpl_vars['m']->index]>0){?><a href="<?php echo @WWW_TOP;?>
/nfo/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
" title="View Nfo" class="modal_nfo" rel="nfo">Nfo</a>, <?php }?>
								<?php if ($_smarty_tpl->getVariable('mpass')->value[$_smarty_tpl->tpl_vars['m']->index]==1){?>Passworded, <?php }elseif($_smarty_tpl->getVariable('mpass')->value[$_smarty_tpl->tpl_vars['m']->index]==2){?>Potential Password, <?php }?>
								<a href="<?php echo @WWW_TOP;?>
/browse?g=<?php echo $_smarty_tpl->getVariable('mgrp')->value[$_smarty_tpl->tpl_vars['m']->index];?>
" title="Browse releases in <?php echo smarty_modifier_replace($_smarty_tpl->getVariable('mgrp')->value[$_smarty_tpl->tpl_vars['m']->index],"alt.binaries","a.b");?>
">Grp</a>
								<?php if ($_smarty_tpl->getVariable('mhaspreview')->value[$_smarty_tpl->tpl_vars['m']->index]==1&&$_smarty_tpl->getVariable('userdata')->value['canpreview']==1){?>, <a href="<?php echo @WWW_TOP;?>
/covers/preview/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
_thumb.jpg" name="name<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
" title="Screenshot of <?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
" class="modal_prev" rel="preview">Preview</a><?php }?>
								<?php if ($_smarty_tpl->getVariable('minnerfiles')->value[$_smarty_tpl->tpl_vars['m']->index]>0){?>, <a href="#" onclick="return false;" class="mediainfo" title="<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
">Media</a><?php }?>
								</div>
							</td>
							<td class="icons">
								<div class="icon icon_nzb"><a title="Download Nzb" href="<?php echo @WWW_TOP;?>
/getnzb/<?php echo $_smarty_tpl->getVariable('mguid')->value[$_smarty_tpl->tpl_vars['m']->index];?>
/<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('mname')->value[$_smarty_tpl->tpl_vars['m']->index],"htmlall");?>
">&nbsp;</a></div>
								<div class="icon icon_cart" title="Add to Cart"></div>
								<?php if ($_smarty_tpl->getVariable('sabintegrated')->value){?><div class="icon icon_sab" title="Send to my Sabnzbd"></div><?php }?>
							</td>
						</tr>
						<?php if ($_smarty_tpl->tpl_vars['m']->index==1&&$_smarty_tpl->tpl_vars['m']->total>2){?>
							<tr><td colspan="5"><a class="mlmore" href="#"><?php echo $_smarty_tpl->tpl_vars['m']->total-2;?>
 more...</a></td></tr>
						<?php }?>
						<?php }} ?>		
					</table>
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
