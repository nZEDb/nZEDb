{if $ad != ''}
	{if $type == 'base'}
			{$ad}
	{else}
			{include file='partials/_ad.tpl' ad=$ad}
		<br>
	{/if}
{/if}