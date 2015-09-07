<!DOCTYPE html>
<html>
{if isset($error) && $error != ''}
<div class="alert alert-danger">{$error}</div>
{/if}
{if isset($notice) && $notice != ''}
<div class="alert alert-info">{$notice}</div>
{/if}
{if isset($sent) && $sent != ''}
<div class="alert alert-info">A link to reset your password has been sent to your e-mail account.</div>
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
    <title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle} | Registration Page</title>
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
{if $showregister != "0"}
  <body class="register-page">
    <div class="register-box">
      <div class="register-logo">
        <a href="{$serverroot}"><b>{$site->title}</b></a>
      </div>
      <div class="register-box-body">
        <p class="login-box-msg">Register a new membership</p>
        <form method="post" action="register?action=submit{$invite_code_query}">
          <div class="form-group has-feedback">
            <input autocomplete="off" id="username" name="username" value="{$username}" type="text" class="form-control" placeholder="Username"/>
            <span class="glyphicon glyphicon-user form-control-feedback"></span>
			  <div class="hint">Should be at least three characters and start with a letter.</div>
          </div>
          <div class="form-group has-feedback">
            <input autocomplete="off" id="email" name="email" value="{$email}" type="email" class="form-control" placeholder="Email"/>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
          </div>
          <div class="form-group has-feedback">
            <input id="password" autocomplete="off" name="password" value="{$password}" type="password" class="form-control" placeholder="Password"/>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
			  <div class="hint">Should be at least six characters long.</div>
          </div>
          <div class="form-group has-feedback">
            <input autocomplete="off" id="confirmpassword" name="confirmpassword" value="{$confirmpassword}" type="password" class="form-control" placeholder="Retype password"/>
            <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
          </div>
          <div class="row">
            <div class="col-xs-8">
              <div class="checkbox icheck">
                <label>
                  <input type="checkbox"> I agree to the <a href="{$serverroot}terms-and-conditions">terms</a>
                </label>
              </div>
            </div><!-- /.col -->
            <div class="col-xs-4">
              <button type="submit" value="Register" class="btn btn-primary btn-block btn-flat">Register</button>
            </div><!-- /.col -->
			  <hr>
			  {$page->smarty->fetch('captcha.tpl')}
          </div>
			<a href="{$serverroot}login" class="text-center">I already have a membership</a>
        </form>
      </div><!-- /.form-box -->
    </div><!-- /.register-box -->
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
{/if}
</html>