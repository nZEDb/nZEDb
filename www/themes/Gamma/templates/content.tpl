{if ($loggedin)=="true"}
	{if $smarty.server.REQUEST_URI == "/"}
		{foreach $content as $c}
			<div class="header">
				<h2><strong>{$c->title}</strong></h2>
				</br>
			</div>
			{$c->body}
		{/foreach}
	{else}
		{foreach $content as $c}
			<div class="header">
				<h2>Help > <strong>{$c->title}</strong></h2>
				<div class="breadcrumb-wrapper">
					<ol class="breadcrumb">
						/ {$c->title}
					</ol>
				</div>
			</div>
			{$c->body}
		{/foreach}
	{/if}
{else}
{/if}
