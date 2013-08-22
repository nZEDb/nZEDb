{if $loggedin=="true"}
	{foreach from=$content item=c}
		<h4 style="color: #485459;">{$c->title}</h4>
		{$c->body}
		<br /><br />
	{/foreach}
{/if}
