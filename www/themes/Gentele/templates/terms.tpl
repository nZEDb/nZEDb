<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle}</title>
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
</head>
<body class="skin-blue layout-boxed">
<div class="wrapper">
	<div class="header">
		<h2>View > <strong>{$page->title}</strong></h2>
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ {$page->title}
			</ol>
		</div>
	</div>
	<div class="box-body">
		<div class="box-content"
		<div class="row">
			<div class="box col-md-12">
				<div class="box-content">
					<div class="row">
						<div class="col-xlg-12 portlets">
							<div class="panel panel-default">
								<div class="panel-body pagination2">
									<p>{$site->tandc}</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
