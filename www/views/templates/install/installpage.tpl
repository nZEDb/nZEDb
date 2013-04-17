<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>{$page->title}</title>
	<link href="../views/styles/install.css" rel="stylesheet" type="text/css" media="screen" />
	<link rel="shortcut icon" type="image/ico" href="../views/images/favicon.ico"/>
	{$page->head}
</head>
<body>
	<h1 id="logo"><img alt="Newznab" src="../views/images/banner.jpg" /></h1>
	<div class="content">	
		<h2>{$page->title}</h2>
		{$page->content}
	
		<div class="footer">
			<p><br /><a href="http://www.newznab.com/">newznab</a> is released under GPL. All rights reserved {$smarty.now|date_format:"%Y"}.</p>
		</div>
	</div>
</body>
</html>
