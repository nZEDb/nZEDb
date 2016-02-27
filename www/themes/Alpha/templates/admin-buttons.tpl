<div class="nzb_multi_operations" style="text-align:right;margin-bottom:5px;">
	{if $covgroup != ''}View:
		<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">
			<i class="icon-th-list"></i>
		</a>
		&nbsp;&nbsp;
		<span>
					<i class="icon-align-justify"></i>
				</span>
	{/if}
	{if $isadmin || $ismod}
		&nbsp;&nbsp;
		Admin:
		<button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
		<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete
		</button>
	{/if}
</div>
