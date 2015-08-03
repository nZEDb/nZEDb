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
		  var SERVERROOT = "{$serverroot}";
		  var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		  var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
		  /* ]]> */
	  </script>
    <meta charset="UTF-8">
    <title>{$site->title} | Log in</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.4 -->
    <link href="{$smarty.const.WWW_TOP}/themes/omicron/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="{$smarty.const.WWW_TOP}/themes/omicron/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <!-- iCheck -->
    <link href="{$smarty.const.WWW_TOP}/themes/omicron/plugins/iCheck/square/blue.css" rel="stylesheet" type="text/css" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
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
		  <a href="{$serverroot}forgottenpassword" class="text-center">I forgot my password</a><br>
        <a href="{$serverroot}register" class="text-center">Register a new membership</a>
    <!-- jQuery 2.1.4 -->
    <script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="{$smarty.const.WWW_TOP}/themes/omicron/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <!-- iCheck -->
    <script src="{$smarty.const.WWW_TOP}/themes/omicron/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
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