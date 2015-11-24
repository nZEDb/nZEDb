<div class="header">
	<h2>My > <strong>Movies</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ My Movies
	</div>
</div>
<div class="row">
	<div class="box col-md-12">
		<div class="box-content">
			<div class="row">
				<div class="col-xlg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							<div class="alert alert-info">
								Using 'My Movies' you can search for movies, and add them to a wishlist. If the movie
								becomes available it will be added to an <strong><a
											href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS
										Feed</a></strong> you can use to automatically download. You can <strong><a
											href="{$smarty.const.WWW_TOP}/mymoviesedit">Manage Your Movie
										List</a></strong> to remove old items.
							</div>
							<div class="well">
								<form id="frmMyMovieLookup">
									<div class="input-append">
										<input class="form-control" style="width:300px;" type="text" id="txtsearch"
											   placeholder="Movie Title or IMDB Id"/>
										<input id="btnsearch" class="btn btn-primary" type="submit" value="Search"/>
									</div>
								</form>
							</div>
						</div>
						<div id="divMovResults">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>