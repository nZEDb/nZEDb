			<h2>Getting in touch</h2>

			<p>Please send any questions or comments you have in an email to {mailto address=$site->email text=$site->title}.</p>

			{$msg}

			{if $msg == ""}
<p>Alternatively use our contact form to get in touch.</p>


<div class="container">
<form class="form-horizontal form-signin" method="post" action="contact-us">
<fieldset>
<!-- Text input-->
<div class="control-group" style="margin-bottom:0">
  <label class="control-label" for="username">Your Name</label>
  <div class="controls">
    <input id="username" name="username" placeholder="" class="input-large" type="text">
  </div>
</div>
<!-- Text input-->
<div class="control-group" style="margin-bottom:0">
  <label class="control-label" for="useremail">Email Address</label>
  <div class="controls">
    <input id="useremail" name="useremail" placeholder="" class="input-large" type="text" value="">
  </div>
</div>
<!-- Textarea -->
<div class="control-group">
  <label class="control-label" for="comment">Comment or Review</label>
  <div class="controls">
    <textarea rows="3" id="comment" name="comment" value=""></textarea>
  </div>
</div>
<!-- Button -->
<div class="control-group">
  <label class="control-label" for="submit"></label>
  <div class="controls">
    <button id="submit" name="submit" class="btn btn-default">Submit</button>
  </div>
</div>
</fieldset>
</form>
</div><!-- container -->





			{/if}