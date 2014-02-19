<div class="category-movie" style="padding-bottom:20px;">
    <h2 class="main-title">
        <a class="see-more" href="#">see more &raquo;</a>
        The <strong>newest releases</strong> for <strong>Movies</strong>...
    </h2>
    <div class="main-wrapper">
        <div class="main-content">
            <!-- library -->
            <div class="library-wrapper">
                {foreach from=$newestmovies item=result}
                    <div class="library-show">
                        <div class="poster">
                            <a class="titleinfo" title="{$result.guid}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}"><img alt="" src="{$smarty.const.WWW_TOP}/covers/movies/{$result.imdbid}-cover.jpg" /></a>
                        </div>
                        <div class="rating-pod" id="guid{$result.guid}">
                            <div class="icons">
                                <div class="icon icon_imdb"><a title="View on IMDB" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbid}/" target="_blank" ></a></div>
                                <div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"url"}"></a></div>
                                <div class="icon icon_cart" title="Add to Cart"></div>
                                {if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
                            </div>
                        </div>
                        <a class="plays" href="#"></a>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
</div>
