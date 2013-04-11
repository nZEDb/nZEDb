 
			<h1>{$page->title}</h1>
			<h2>Getting in touch</h2>

			<p>
				Please send any questions or comments you have in an email to {mailto address=$site->email text=$site->title}.
			</p>

			{$msg}

			{if $msg == ""}
			<h2>Contact form</h2>
			<p>
				Alternatively use our contact form to get in touch.
			</p>
			
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
							<input type="submit" value="Submit" />
						</td>
					</tr>
				</table>
			</form>
			{/if}