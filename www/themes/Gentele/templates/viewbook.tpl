<div id="musicinfo">
	<img src="{$smarty.const.WWW_TOP}/covers/book/{if $boo.cover == 1}{$boo.id}{else}no-cover{/if}.jpg" class="cover"
		 alt="{$boo.title|ss}"/>
	<h1>{$boo.author} - {$boo.title}</h1>
	{if $boo.publisher != ""}<h3>Publisher: {$boo.publisher}</h3>{/if}
	{if $boo.publishdate != ""}<h3>Published: {$boo.publishdate|date_format}</h3>{/if}
	{if $boo.pages != ""}<h3>Pages: {$boo.pages}</h3>{/if}
	{if $boo.review != ""}
		<h3>Review: </h3>
		<p>{$boo.review}</p>
	{/if}
</div>
