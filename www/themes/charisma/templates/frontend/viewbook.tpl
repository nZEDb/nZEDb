<div id="musicinfo">
	<img src="{$smarty.const.WWW_TOP}/covers/book/{if $book.cover == 1}{$book.id}{else}no-cover{/if}.jpg" class="cover"
		 alt="{$book.title|ss}"/>
	<h1>{$book.author} - {$book.title}</h1>
	{if $book.publisher != ""}<h3>Publisher: {$book.publisher}</h3>{/if}
	{if $book.publishdate != ""}<h3>Published: {$book.publishdate|date_format}</h3>{/if}
	{if $book.pages != ""}<h3>Pages: {$book.pages}</h3>{/if}
	{if $book.review != ""}
		<h3>Review: </h3>
		<p>{$book.review}</p>
	{/if}
</div>