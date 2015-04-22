<!DOCTYPE html>
<html lang="en">
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>{$page->meta_title}{if $site->metatitle != ""} - {$site->metatitle}{/if}</title>
		<meta name="keywords" content="{$page->meta_keywords}{if $site->metakeywords != ""},{$site->metakeywords}{/if}">
		<meta name="description" content="{$page->meta_description}{if $site->metadescription != ""} - {$site->metadescription}{/if}">
		<meta name="application-name" content="nZEDb-v{$site->version}">
		<meta name="viewport" content="width=device-width">
		{*<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css" rel="stylesheet">*}
		<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.0/css/font-awesome.min.css" rel="stylesheet">
		<link href="{$smarty.const.WWW_TOP}/../themes_shared/styles/jquery.accordian.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/../themes/Default/styles/style.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/../themes_shared/styles/admin.css" rel="stylesheet" media="screen">
		<link href="{$smarty.const.WWW_TOP}/../themes_shared/styles/jquery.multiselect.css" rel="stylesheet" media="screen">
		<link rel="shortcut icon" href="{$smarty.const.WWW_TOP}/../themes_shared/images/favicon.ico">
		<!--[if lt IE 9]>
		<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<script>window.html5 || document.write('<script src="{$smarty.const.WWW_TOP}/../themes_shared/scripts/html5shiv.js"><\/script>')</script>
		<![endif]-->
	{$page->head}
	</head>
	<body>
		<div id="logo" style="cursor: pointer;">
			<h1>
				<a href="/"></a>
			</h1>
			<p> <em></em>
			</p>
		</div>
		<hr />
		<div id="header">
			<div id="menu"></div>
			<!-- end #menu -->
		</div>
		<div id="page">
			<div id="adpanel"></div>
			<!--[if lt IE 7]>
				<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
			<![endif]-->
			<div id="content">{$page->content}</div>
			<!-- end #content -->
			<div id="admin_sidebar">
				<ul>
					<li>{$admin_menu}</li>
				</ul>
			</div>
			<!-- end #sidebar -->
			<div style="clear: both;">&nbsp;</div>
		</div>
		<!-- end #page -->
		<script src="//code.jquery.com/jquery-1.9.1.js"></script>
		{literal}
			<script>
				window.jQuery || document.write('<script src="{/literal}{$smarty.const.WWW_TOP}{literal}/../themes_shared/scripts/jquery-1.9.1.js"><\/script>')
			</script>
		{/literal}
		{*<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
		{literal}<script>window.jQuery || document.write('<script src="{/literal}{$smarty.const.WWW_TOP}{literal}/../themes/Default/scripts/bootstrap.min.js"><\/script>')</script>{/literal}*}
		<script src="{$smarty.const.WWW_TOP}/../themes_shared/scripts/sorttable.js"></script>
		<script src="{$smarty.const.WWW_TOP}/../themes_shared/scripts/utils-admin.js"></script>
		<script src="{$smarty.const.WWW_TOP}/../themes_shared/scripts/jquery.multifile.js"></script>
		<script src="{$smarty.const.WWW_TOP}/../themes_shared/scripts/jquery.multiselect.js"></script>
		<script>var WWW_TOP = "{$smarty.const.WWW_TOP}/..";</script>
		{if $site->google_analytics_acc != ''}
			{literal}
				<script>
				/* <![CDATA[ */
				  var _gaq = _gaq || [];
				  _gaq.push(['_setAccount', '{/literal}{$site->google_analytics_acc}{literal}']);
				  _gaq.push(['_trackPageview']);
				  _gaq.push(['_trackPageLoadTime']);

				  (function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				  })();
				/* ]]> */
				</script>
			{/literal}
		{/if}
		{literal}
			<script type="text/javascript">

				(function ($) {
					$(document).ready(function () {
						$('#accordian li.has-sub>a').on('click', function () {
							$(this).removeAttr('href');
							var element = $(this).parent('li');
							if (element.hasClass('open')) {
								element.removeClass('open');
								element.removeClass('active');
								element.find('li').removeClass('open');
								element.find('li').removeClass('active');
								element.find('ul').slideUp();
							}
							else {
								element.addClass('open');
								element.addClass('active');
								element.children('ul').slideDown();
								element.siblings('li').children('ul').slideUp();
								element.siblings('li').removeClass('open');
								element.siblings('li').removeClass('active');
								element.siblings('li').find('li').removeClass('open');
								element.siblings('li').find('li').removeClass('active');
								element.siblings('li').find('ul').slideUp();
							}
						});

						$('#accordian>ul>li.has-sub>a').append('<span class="holder"></span>');

						var el = $('a[href="' + $(location).attr('pathname') + $(location).attr('search') + '"]').closest('ul');

						$('a[href="' + $(location).attr('pathname') + $(location).attr('search') + '"]').closest('li').addClass('active');

						var subel = el.parent().parent().closest('.has-sub').find('a[href="#"]')[0];
						if (subel) {
							subel.click();
						}
						el.closest('.has-sub').addClass('open active');
						el.slideDown();

						(function getColor() {
							var r, g, b;
							var textColor = $('#accordian').css('color');
							textColor = textColor.slice(4);
							r = textColor.slice(0, textColor.indexOf(','));
							textColor = textColor.slice(textColor.indexOf(' ') + 1);
							g = textColor.slice(0, textColor.indexOf(','));
							textColor = textColor.slice(textColor.indexOf(' ') + 1);
							b = textColor.slice(0, textColor.indexOf(')'));
							var l = rgbToHsl(r, g, b);
							if (l > 0.7) {
								$('#accordian>ul>li>a').css('text-shadow',
									'0 1px 1px rgba(0, 0, 0, .35)');
								$('#accordian>ul>li>a>span').css('border-color',
									'rgba(0, 0, 0, .35)');
							}
							else {
								$('#accordian>ul>li>a').css('text-shadow',
									'0 1px 0 rgba(255, 255, 255, .35)');
								$('#accordian>ul>li>a>span').css('border-color',
									'rgba(255, 255, 255, .35)');
							}
						})();

						function rgbToHsl(r, g, b) {
							r /= 255, g /= 255, b /= 255;
							var max = Math.max(r, g, b), min = Math.min(r, g, b);
							var h, s, l = (max + min) / 2;

							if (max == min) {
								h = s = 0;
							}
							else {
								var d = max - min;
								s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
								switch (max) {
									case r:
										h = (g - b) / d + (g < b ? 6 : 0);
										break;
									case g:
										h = (b - r) / d + 2;
										break;
									case b:
										h = (r - g) / d + 4;
										break;
								}
								h /= 6;
							}
							return l;
						}
					});
				})(jQuery);
			</script>
		{/literal}
		{literal}
		<script type="text/javascript">
			$('.top-nav').children().children('select').bind('change', function () {
				$("html, body").animate({scrollTop: $('#' + $(this).val()).offset().top}, "slow");
			});
		</script>
		{/literal}
		{literal}
			<script type="text/javascript">
				$('#xxxgenre_list').multiSelect({
					selectableHeader: "<div>All Genres</div>",
					selectionHeader: "<div>Selected Genres</div>"
				});
			</script>
		{/literal}
	</body>
</html>
