<div class="container nzb_multi_operations">
	{$pager}
	<div class="pull-right">
		With Selected:
		<button type="button" class="btn btn-info btn-sm nzb_multi_operations_download">Download NZBs</button>
		<button type="button" class="btn btn-info btn-sm nzb_multi_operations_cart">Add to Cart</button>
		{if isset($sabintegrated)}
			<button type="button" class="btn btn-success btn-sm nzb_multi_operations_sab">Send to my Queue</button>
		{/if}
	</div>
</div>