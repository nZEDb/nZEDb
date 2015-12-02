<div class="page-header">
	<h1>{$page->title}</h1> 
</div>
<table class="table span8">
{foreach from=$sitemaps item=sitemap}
	{if $last_type != $sitemap->type}
		{assign var=last_type value=$sitemap->type}
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style="width:120px;">
		{$sitemap->type} \
	{else}
	<tr>
		<td style="width:120px;">
	{/if}
		</td>
		<td>
			<a title="{$sitemap->type} - {$sitemap->name}" href="{$smarty.const.WWW_TOP}{$sitemap->loc}">{$sitemap->name}</a>
		</td>
	</tr>	
{/foreach}
</table>

