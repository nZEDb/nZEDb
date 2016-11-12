<!DOCTYPE html>
<html>
{if isset($error) && $error != ''}
<div class="alert alert-danger">{$error}</div>
{/if}
{if isset($notice) && $notice != ''}
<div class="alert alert-info">{$notice}</div>
{/if}
  <head>
	  <script type="text/javascript">
		  /* <![CDATA[ */
		  var WWW_TOP = "{$smarty.const.WWW_TOP}";
		  var SERVERROOT = "{$smarty.const.WWW_TOP}/";
		  var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		  var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
		  /* ]]> */
	  </script>
    <meta charset="UTF-8">
    <title>{$site->title} | Log in</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
  </head>
  <body class="login-page">
    <div class="login-box">
		<div class="login-logo">
			<a href="{$serverrroot}"><b>{$site->title}</b> | Login</a>
		</div><!-- /.login-logo -->
      <div class="login-box-body">
        <p class="login-box-msg">Please sign in to access the site</p>
        <form action="login" method="post">
          <div class="form-group has-feedback">
            <input id="username" name="username" type="text" class="form-control" placeholder="Username"/>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
          </div>
          <div class="form-group has-feedback">
            <input id="password" name="password" type="password" class="form-control" placeholder="Password"/>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
          </div>
          <div class="row">
            <div class="col-xs-8">
              <div class="checkbox icheck">
                <label>
                  <input id="rememberme" {if isset($rememberme) && $rememberme == 1}checked="checked"{/if} name="rememberme" type="checkbox"> Remember Me
                </label>
				  <hr>
				  {$page->smarty->fetch('captcha.tpl')}
              </div>
            </div><!-- /.col -->
            <div class="col-xs-4">
              <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>

            </div><!-- /.col -->
          </div>
        </form>

		  <a href="{$smarty.const.WWW_TOP}/forgottenpassword" class="text-center">I forgot my password</a><br>
        <a href="{$smarty.const.WWW_TOP}/register" class="text-center">Register a new membership</a>
    <!-- iCheck -->
    <script src="{$smarty.const.WWW_THEMES}/shared/libs/icheck-1.0.x/icheck.min.js" type="text/javascript"></script>
    <script>
      $(function () {
        $('input').iCheck({
          checkboxClass: 'icheckbox_square-blue',
          radioClass: 'iradio_square-blue',
          increaseArea: '20%' // optional
        });
      });
    </script>
  </body>
</html>
