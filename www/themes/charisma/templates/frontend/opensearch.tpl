<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
					   xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName>{$site->title|escape}</ShortName>
	<Description>{$site->title|escape} Search Facility</Description>
	<Url type="text/html" method="get" template="{$serverroot}search/{literal}{searchTerms}{/literal}"/>
	<Contact>{$site->email}</Contact>
	<Image width="16" height="16">{$serverroot}themes/charisma/images/favicon.ico</Image>
	<Developer>newznab.com</Developer>
	<InputEncoding>UTF-8</InputEncoding>
	<moz:SearchForm>{$serverroot}</moz:SearchForm>
	<moz:UpdateUrl>{$serverroot}opensearch</moz:UpdateUrl>
	<moz:IconUpdateUrl>{$serverroot}themes/charisma/images/favicon.ico</moz:IconUpdateUrl>
	<moz:UpdateInterval>7</moz:UpdateInterval>
</OpenSearchDescription>