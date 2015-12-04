{if $overview['nzbs']|@count gt 0}
	{foreach from=$overview['nzbs'] item=nzb}
		<div style="border-top: 2px solid #eee; margin: 0 0 5px 0; position: relative">
			<div id="vortex-overlay-{$nzb['id']}"
				 style="position: absolute; background-color: #000; opacity: 0.2; width: 100%; height: 100%; display: none"></div>
			<div class="vortex-nzb" style="padding: 5px 0 5px 0">
				<i>{$nzb['uiTitle']}</i>
				<br/>
				<div style="width: 20px; float: left; margin: 7px 0 0 0">
					{if $nzb['isPaused'] eq 1}
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/blah.png"
							 style="float: left; margin: 0 5px 0 0"/>
					{else}
						<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/bigsmile.png"
							 style="float: left; margin: 0 5px 0 0"/>
					{/if}
				</div>
				<div class="vortex-progressbar"
					 style="margin: 5px 0 0 0; background-color: #eee; height: 15px; padding: 3px; border-radius: 2px; float: left; width: 720px">
					<div style="float: left; background-color: {if $nzb['isPaused'] eq 1}#FB8084{else}#91BA98{/if}; height: 15px; width: {$nzb['progress']}%"></div>
				</div>
				<br style="clear: both"/>
				<strong>{$nzb['state']}{if $nzb['statusText'] neq ''} ({$nzb['statusText']|lower}){/if}</strong>: {$nzb['progress']|round}
				% of {math|string_format:"%.2f" equation="size / 1024 / 1024" size=$nzb['totalDownloadSize']}
				MB {if $nzb['transferedSpeed'] neq 0}@ {math|string_format:"%.2f" equation="size / 1024 / 1024" size=$nzb['transferedSpeed']} MB/s{/if}
				<div class="vortex-controls" style="margin: 5px 0 0 0">
					<div style="border-right: 2px solid #eee; width: 41px; float: left; margin: 0 5px 0 0">
						{if $nzb['isPaused'] eq 1}
							<a class="vortex-resume" title="Resume" href="{$nzb['id']}"><img
										src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/play.png"/></a>
						{else}
							<a class="vortex-pause" title="Pause" href="{$nzb['id']}"><img
										src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/pause.png"/></a>
						{/if}
						<a class="vortex-filelist" title="View filelist" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/tv.png"/></a>
					</div>
					<div class="vortex-controls" style="float: left">
						<a class="vortex-moveup" title="Move up in queue" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/arrow2_n.png"/></a>
						<a class="vortex-movedown" title="Move down in queue" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/arrow2_s.png"/></a>
						<a class="vortex-movebottom" title="Move to bottom of queue" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/arrow3_s.png"/></a>
						<a class="vortex-movetop" title="Move to top of queue" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/arrow3_n.png"/></a>
						<a class="vortex-trash" title="Cancel and delete NZB" href="{$nzb['id']}"><img
									src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/vortex/trash.png"/></a>
					</div>
					<br style="clear: both"/>
				</div>
			</div>
		</div>
	{/foreach}
{else}
	<div id="vortex-info" style="background-color: #2A8FBD; text-align: center; padding: 5px; color: #eee">
		Nothing in queue, go ahead and add something!
	</div>
{/if}