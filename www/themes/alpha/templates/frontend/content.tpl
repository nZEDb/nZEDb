{if $loggedin=="true"}
	{foreach from=$content item=c}
		{if $front == false }
			<h3>
				<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content?id={$c->id}">{$c->title}</a>
			</h3>
		{/if}
		{$c->body}
		{if $front == true}
			<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content">See more...</a>
		{/if}
	{/foreach}
{/if}