<h1>{$page->title}</h1>
<h2>Getting in touch</h2>
{$msg}{* This is a message that appears if a email is sent. *}
{if $msg == ""}
	{if $site->email != ''}
		<p>
			Please send any questions or comments you have in an email to {mailto address=$site->email text=$site->title}.
		</p>
		<p>
			Alternatively use our contact form to get in touch.
		</p>
	{/if}
	<h2>Contact form</h2>
	<form method="post" action="contact-us">
		<table>
			<tr>
				<td width="100px">Your name: </td>
				<td>
					<input id="username" type="text" name="username" value="" />
				</td>
			</tr>
			<tr>
				<td>Your email address: </td>
				<td>
					<input type="text" name="useremail" value="" />
				</td>
			</tr>
			<tr>
				<td>Your comment or review: </td>
				<td>
					<textarea rows="10" cols="40" name="comment"></textarea>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					{$page->smarty->fetch('captcha.tpl')}
					<input type="submit" value="Submit" />
				</td>
			</tr>
		</table>
	</form>
{/if}