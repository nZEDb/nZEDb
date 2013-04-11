<h1>{$page->title}</h1> 

{if $menulist}

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>name</th>
		<th>href</th>
		<th>tooltip</th>
		<th>role</th>
		<th>ordinal</th>
		<th>new window</th>
		<th>options</th>
	</tr>
	
	{foreach from=$menulist item=menu}
	<tr class="{cycle values=",alt"}">
		<td title="Edit {$menu.title}"><a href="{$smarty.const.WWW_TOP}/menu-edit.php?id={$menu.ID}">{$menu.title|escape:"htmlall"}</a></td>
		<td>{$menu.href}</td>
		<td>{$menu.tooltip}</td>
		<td>{if $menu.role == 0}Guests{elseif $menu.role == 1}Users{elseif $menu.role == 2}Admin{else}Other{/if}</td>
		<td>{$menu.ordinal}</td>
		<td class="mid">{if $menu.newwindow == 1}Yes{else}No{/if}</td>
		<td><a href="{$smarty.const.WWW_TOP}/menu-delete.php?id={$menu.ID}">delete</a></td>
	</tr>
	{/foreach}

</table>
{else}
<p>No menus available.</p>
{/if}
