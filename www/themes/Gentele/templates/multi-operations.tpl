<div class="container nzb_multi_operations">
	{$pager}
	<div class="pull-right">
		With Selected:
		<button type="button" class="btn btn-info btn-sm nzb_multi_operations_download" data-toggle="tooltip"
				data-placement="top" title data-original-title="Download NZBs"> Download NZBs
		</button>
		<button type="button" class="btn btn-info btn-sm nzb_multi_operations_cart" data-toggle="tooltip"
				data-placement="top" title data-original-title="Send to my Download Basket"> Send to my Download Basket
		</button>
		{if isset($sabintegrated) && $sabintegrated !=""}
			<button type="button" class="btn btn-success btn-sm nzb_multi_operations_sab" data-toggle="tooltip"
					data-placement="top" title data-original-title="Send to my Queue">Send to my Queue
			</button>
		{/if}
	</div>
</div>
