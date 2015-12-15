{if $showregister != "0"}
	<div class="span6 offset2"
	  style="background-color:#d9edf7; padding:40px; background-color: #f7f7f9; border: 1px solid #e1e1e8;
	    -webkit-border-radius: 4px;
	    -moz-border-radius: 4px;
	    border-radius: 4px; 
	">
	<h3>Register</h3><br/>
        <p>
                Enter you information below, all the fields are required.
        </p>

	<form class="form-horizontal" action="register?action=submit" method="post">
		<table class="data">

		{if $error != ''}
			<div class="alert alert-error">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				  <h4>Error!</h4>
				  {$error}
			</div>
		{/if}
		<input class="input-block-level" autocomplete="off" id="username" name="username" value="{$username}" type="text" placeholder="Username" style="margin-bottom:5px;"/>
		<input class="input-block-level" id="password" name="password" type="password" value="{$password}" placeholder="Password" style="margin-bottom:5px;"/>
		<input id="invitecode" name="invitecode" type="hidden" value="{$invitecode|escape:html_all}" />
		<input class="input-block-level" autocomplete="off" id="confirmpassword" name="confirmpassword" value="{$confirmpassword}" type="password" placeholder="Confim password" style="margin-bottom:5px;"/>
		<input class="input-block-level" autocomplete="off" id="email" name="email" value="{$email}" type="text" placeholder="Email" style="margin-bottom:20px;"/>
		
		{if $site->registerrecaptcha == "1"}
			<div class="well well-mini">
				<center>
					{$recaptcha}
				</center>
			</div>
		{/if}
		
		
			<button type="submit" class="btn btn-success pull-right">Register</button>
		</table>
	</form>
{else}
	<div class="span6 offset2"
	  style="background-color:#d9edf7; padding:40px; background-color: #f2dede; border: 1px solid #eed3d7;
	    -webkit-border-radius: 4px;
	    -moz-border-radius: 4px;
	    border-radius: 4px;
	">

	<h3>Register</h3><br/>

		Registration is currently disabled, please check back again later.<br/>
{/if}

</div>
