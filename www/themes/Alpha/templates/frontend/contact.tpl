<div class="container">
	<div class="col-sm-6 col-sm-offset-3">
		<div class="well">
			{$msg} {* This is a message that appears if a email is sent. *}
			{if $msg == ""}
				<h2 class="form-signin-heading">Getting in touch</h2>
				{if $site->email != ''}
					<p>Please send any questions or comments you have in an email to {mailto address=$site->email text=$site->title}.</p>
					<p>Alternatively use our contact form to get in touch.</p>
				{/if}
				<form class="form-signin" action="contact-us" method="post">
					<fieldset>
						<div class="form-group">
							<label for="username">Your Name</label>
							<input id="username" name="username" placeholder="" class="form-control" type="text">
						</div>
						<div class="form-group">
							<label for="useremail">Email Address</label>
							<input id="useremail" name="useremail" placeholder="" class="form-control" type="email" value="">
						</div>
						<div class="form-group">
							<label for="comment">Comment or Review</label>
							<textarea rows="3" id="comment" class="form-control" name="comment" value=""></textarea>
						</div>
						{$page->smarty->fetch('captcha.tpl')}
						<div class="form-group">
							<button id="submit" name="submit" class="btn btn-success">Submit</button>
						</div>
					</fieldset>
				</form>
			{/if}
		</div>
	</div>
</div><!-- container -->