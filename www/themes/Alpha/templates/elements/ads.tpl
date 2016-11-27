{if $ad != ''}
	{if $type == 'base'}
			{$ad}
	{else}
			{include file='elements/_ad.tpl' ad=$ad}
		<br>
	{/if}
{/if}
