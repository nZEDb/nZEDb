<?php /* Smarty version Smarty3-SVN$Rev: 3286 $, created on 2013-04-11 12:33:40
         compiled from "/var/www/newznab/www/views/templates/admin/binaryblacklist-edit.tpl" */ ?>
<?php /*%%SmartyHeaderCode:7436252335166e5e43210a7-67227277%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'ab240651ac2ddd188c08ce089cb4253658e8631f' => 
    array (
      0 => '/var/www/newznab/www/views/templates/admin/binaryblacklist-edit.tpl',
      1 => 1365687713,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7436252335166e5e43210a7-67227277',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php if (!is_callable('smarty_modifier_escape')) include '/var/www/newznab/www/lib/smarty/plugins/modifier.escape.php';
if (!is_callable('smarty_function_html_radios')) include '/var/www/newznab/www/lib/smarty/plugins/function.html_radios.php';
?>
<h1><?php echo $_smarty_tpl->getVariable('page')->value->title;?>
</h1>

<?php if ($_smarty_tpl->getVariable('error')->value!=''){?>
	<div class="error"><?php echo $_smarty_tpl->getVariable('error')->value;?>
</div>
<?php }?>

<form action="<?php echo $_smarty_tpl->getVariable('SCRIPT_NAME')->value;?>
?action=submit" method="POST">

<table class="input">


<tr>
	<td>Group:</td>
	<td>
		<input type="hidden" name="id" value="<?php echo $_smarty_tpl->getVariable('regex')->value['ID'];?>
" />
		<input type="text" id="groupname" name="groupname" value="<?php echo smarty_modifier_escape($_smarty_tpl->getVariable('regex')->value['groupname'],'html');?>
" />
		<div class="hint">The full name of a valid newsgroup. (Wildcard in the format 'alt.binaries.*')</div>		
	</td>
</tr>

<tr>
	<td>Regex:</td>
	<td>
		<textarea id="regex" name="regex" ><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('regex')->value['regex'],'html');?>
</textarea>
		<div class="hint">The regex to be applied. (Note: Beginning and Ending / are already included)</div>		
	</td>
</tr>

<tr>
	<td>Description:</td>
	<td>
		<textarea id="description" name="description" ><?php echo smarty_modifier_escape($_smarty_tpl->getVariable('regex')->value['description'],'html');?>
</textarea>
		<div class="hint">A description for this regex</div>		
	</td>
</tr>

<tr>
	<td><label for="msgcol">Message Field</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"msgcol",'name'=>'msgcol','values'=>$_smarty_tpl->getVariable('msgcol_ids')->value,'output'=>$_smarty_tpl->getVariable('msgcol_names')->value,'selected'=>$_smarty_tpl->getVariable('regex')->value['msgcol'],'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Which field in the message to apply the black/white list to.</div>		
	</td>
</tr>

<tr>
	<td><label for="status">Active</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"status",'name'=>'status','values'=>$_smarty_tpl->getVariable('status_ids')->value,'output'=>$_smarty_tpl->getVariable('status_names')->value,'selected'=>$_smarty_tpl->getVariable('regex')->value['status'],'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Only active regexes are applied during the release process.</div>		
	</td>
</tr>

<tr>
	<td><label for="optype">Type</label>:</td>
	<td>
		<?php echo smarty_function_html_radios(array('id'=>"optype",'name'=>'optype','values'=>$_smarty_tpl->getVariable('optype_ids')->value,'output'=>$_smarty_tpl->getVariable('optype_names')->value,'selected'=>$_smarty_tpl->getVariable('regex')->value['optype'],'separator'=>'<br />'),$_smarty_tpl);?>

		<div class="hint">Black will exclude all messages for a group which match this regex. White will include only those which match.</div>		
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Save" />
	</td>
</tr>

</table>

</form>