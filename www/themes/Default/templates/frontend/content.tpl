{if $loggedin=="true"}
	{foreach from=$content item=c}
			<div class="page-header">
				<h1>{$c->title}</h1>
			</div>
			{$c->body}
	{/foreach}
{/if}
