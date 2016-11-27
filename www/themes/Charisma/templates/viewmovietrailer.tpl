<div id="movieinfo">
	<object style="width:800px; height:450px;"
			data="http{if $page->secure_connection}s{/if}://www.youtube.com/v/{$movie.trailer}?modestbranding=0&amp;rel=0&amp;showinfo=0&amp;autohide=1&amp;vq=hd720"
			type="application/x-shockwave-flash">
		<param name="src"
			   value="http{if $page->secure_connection}s{/if}://www.youtube.com/v/{$movie.trailer}?modestbranding=0&amp;rel=0&amp;showinfo=0&amp;autohide=1&amp;vq=hd720"/>
		<param name="allowFullScreen" value="true"/>
	</object>
</div>