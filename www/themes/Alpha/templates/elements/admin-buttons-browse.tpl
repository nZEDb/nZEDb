<div class="nzb_multi_operations" style="text-align:right;margin-bottom:5px;">
	{if !empty($covgroup)}View:
		<span class="fa fa-align-justify"></span>
		&nbsp;&nbsp;
		<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">
			<span class="fa fa-th-list"></span>
		</a>
	{else} View: <a href="{$smarty.const.WWW_TOP}/browse?t={$category}">
		<span class="fa fa-align-justify"></span>
		</a>
		&nbsp;&nbsp;
		<span class="fa fa-th-list"></span>
	{/if}
	{if $isadmin || $ismod}
		&nbsp;&nbsp;
		Admin:
		<button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
		<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete
		</button>
	{/if}
</div>
