{if $loggedin=="true"}
	{foreach from=$content item=c}
		{*<h1>{$c->title}</h1>*}
		{$c->body}
	{/foreach}
{/if}