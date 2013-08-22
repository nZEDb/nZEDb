{if $loggedin=="true"}
	{foreach from=$content item=c}
		<h3>{$c->title}</h3>
		{$c->body}
		<br /><br />
	{/foreach}
{/if}
