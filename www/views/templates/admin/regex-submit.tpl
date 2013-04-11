 
<h1>{$page->title}</h1>

<p>Use this feature to submit any regex you have added locally to newznab. We'll have a look at integrating them into the master list. No data other than regex's with an ID greater than 10000 will be sent.</p>

{if $upload_status eq 'OK'}
<div style="background-color: #CDEB8B; color: #fff; padding: 20px">
  <strong>Your regexes were uploaded. Thank you for contributing.</strong>
</div>
<br />
{/if}

{if $upload_status eq 'BAD'}
<div style="background-color: #CC0000; color: #fff; padding: 20px">
  <strong>Failed to upload your regexes :-( - please try again.</strong>
</div>
<br />
{/if}

{if $regex_error}
<p><strong>No avalible user regex's. Please add some and visit this page again.</strong></p>
{else}
<form action="{$SCRIPT_NAME}" method="post" name="submit_regex">
  <input type="hidden" name="regex_submit_please" value="1" />
  <input type="submit" name="submit" value="Submit regular expressions" />
</form>

<br />

<p>
  <strong>Regexes to be submitted:</strong>
  <br />
  <pre>
  {$regex_contents|print_r}
  </pre>
</p>
{/if}

