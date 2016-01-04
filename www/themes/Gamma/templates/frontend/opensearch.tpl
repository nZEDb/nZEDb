<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName>{$site->title|escape}</ShortName>
	<Description>{$site->title|escape} Search Facility</Description>
	<Url type="text/html" method="get" template="{$smarty.const.WWW_TOP}/search/{literal}{searchTerms}{/literal}"/>
	<Contact>{$site->email}</Contact>
	<Image width="16" height="16">{$smarty.const.WWW_TOP}/themes/Gamma/images/favicon.ico</Image>
	<Developer>newznab.com</Developer>
	<InputEncoding>UTF-8</InputEncoding>
	<moz:SearchForm>{$smarty.const.WWW_TOP}/</moz:SearchForm>
	<moz:UpdateUrl>{$smarty.const.WWW_TOP}/opensearch</moz:UpdateUrl>
	<moz:IconUpdateUrl>{$smarty.const.WWW_TOP}/themes/Gamma/images/favicon.ico</moz:IconUpdateUrl>
	<moz:UpdateInterval>7</moz:UpdateInterval>
</OpenSearchDescription>