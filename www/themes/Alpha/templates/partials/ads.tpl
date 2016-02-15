{if $ad != ''}
	{if $type == 'base'}
			{$ad}
	{elseif $type == 'superrow'}

				{include file='partials/_ad.tpl' ad=$ad}

		<br>
	{elseif $type == 'subrow'}

				{include file='partials/_ad.tpl' ad=$ad}

		<br>
	{else}
			{include file='partials/_ad.tpl' ad=$ad}
		<br>
	{/if}
{/if}