{if $loggedin=="false"}
	{foreach from=$content item=c}
		{if $c->role == 0}{$c->body}{/if}
	{/foreach}
{else}
	{foreach from=$content item=c}
		{if $c->role == 0 || $c->role == 1 || ($c->role == 2 && $admin == 'true')}
			{if $front == false}
				<h3>
					<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content?id={$c->id}">{$c->title}</a>
				</h3>
			{/if}
			{$c->body}
		{/if}
	{/foreach}
	{if $front == true}
		<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content">See more...</a>
	{/if}
{/if}