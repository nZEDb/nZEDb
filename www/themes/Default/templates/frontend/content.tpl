{if $loggedin=="true"}
	{foreach from=$content item=c}
		{if $front == false }
			<h4>
				<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content?id={$c->id}">
					{$c->title}
				</a>
			</h4>
		{else}
			<h4 style="color: #485459;">{$c->title}</h4>
		{/if}
		{$c->body}
		{if $front == true}
			<a style="color:#0082E1" href="{$smarty.const.WWW_TOP}content">See more...</a>
		{/if}
	{/foreach}
{/if}