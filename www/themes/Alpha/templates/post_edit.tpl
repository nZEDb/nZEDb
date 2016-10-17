<div class="well well-sm">
	<div id="new" tabindex="-1" role="dialog" aria-labelledby="myLabel" aria-hidden="true">
		<div class="header">
			<h3 id="myLabel">Edit Post</h3>
		</div>
		<div class="body">
			<form id="forum-post-edit" class="form-horizontal" action="" method="POST">
				<div class="control-group">
					<label class="control-label" for="addMessage">Edit Post</label>
					<div class="controls">
						<textarea id="addMessage" name="addMessage">{$result.message}</textarea>
					</div>
					<input class="btn btn-success" type="submit" value="Submit"/>
					<input class="btn btn-warning" value="Cancel"
						   onclick="if(confirm('Are you SURE you wish to cancel?')) history.back();"/>
				</div>
			</form>
		</div>
	</div>
</div>
