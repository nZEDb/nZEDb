<h3>{$type|ucwords} {$show.releasetitle|escape:"htmlall"} in:</h3>
<form id="myshows" action="{$smarty.const.WWW_TOP}/myshows/do{$type}" method="post">
	<input type="hidden" name="subpage" value="{$rid}" />
	{if $from}<input type="hidden" name="from" value="{$from}" />{/if}
	{html_checkboxes name='category' values=$cat_ids output=$cat_names selected=$cat_selected separator='<br />'}
	
	<br />
	<input type="submit" name="{$type}" value="{$type|ucwords}" />
</form>