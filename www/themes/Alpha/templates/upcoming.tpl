{include file='elements/ads.tpl' ad=$site->adbrowse}

<p>
    <a href="{$smarty.const.WWW_TOP}/upcoming/1">In Theatres</a> |
    <a href="{$smarty.const.WWW_TOP}/upcoming/2">Upcoming</a>
</p>
<p>
    Sort by: <a href="{$smarty.const.WWW_TOP}/upcoming/{$selection}?sort=pop">Popularity</a> | <a
            href="{$smarty.const.WWW_TOP}/upcoming/{$selection}?sort=date">Newest</a>
</p>
{if isset($nodata)}
    {$nodata}
{elseif $data|@count > 0}
    <table class="table table-condensed data icons" id="coverstable">
        <thead>
        <tr>
            <th></th>
            <th>Name</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$data item=movie}
            {if $movie->title == ''}
                {continue}
            {/if}
            {if $selection == '2' and $movie->release_ts <= $smarty.now}
                {continue}
            {/if}
            {if $movie->release_ts <= $date_cutoff}
                {continue}
            {/if}
            <tr>
                <td style="width:150px;padding:10px;text-align:center;">
                    <div class="movcover">
                        <img class="shadow img-thumbnail" src="{$imgbase}{$movie->poster_path}" width="154"
                             border="0" alt="{$movie->title|escape:"htmlall"}"/>

                        <div class="movextra">

                            {if $movie->vote_average > 5}
                                <img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/fresh.png">
                            {else}
                                <img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/rotten.png">
                            {/if}

                            <a
                                    target="_blank"
                                    href="{$site->dereferrer_link}http://www.imdb.com/title/{$movie->imdb_id}/"
                                    name="imdb{$result->alternate_ids->imdb}"
                                    title="View imdb page"><img
                                        src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/imdb.png">
                            </a>
                            <a
                                    target="_blank"
                                    href="{$site->dereferrer_link}http://trakt.tv/search/imdb/{$movie->imdb_id}/"
                                    name="trakt{$result->alternate_ids->imdb}"
                                    title="View trakt page"><img
                                        src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/trakt.png">
                            </a>
                            {if !empty($cpurl) && !empty($cpapi)}
                                <a
                                        id="imdb{$movie->imdb_id}"
                                        href="javascript:;"
                                        class="sendtocouch"
                                        title="Add to CouchPotato">
                                    <img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/couch.png">
                                </a>
                            {/if}
                        </div>
                    </div>
                </td>
                <td colspan="3">
                    <h2>
                        <a href="{$smarty.const.WWW_TOP}/movies?title={$movie->title}&year={$movie->release_date|truncate:4:''}">{$movie->title|escape:"htmlall"}</a>
                        (<a class="title" title="{$result->year}"
                            href="{$smarty.const.WWW_TOP}/movies?year={$movie->release_date|truncate:4:''}">{$movie->release_date|truncate:4:''}</a>) {if $movie->average_rating > 0}{$movie->average_rating}/10{/if}
                    </h2>
                    {$movie->overview}
                    {if $movie->casts|@count > 0}
                        <br/>
                        <br/>
                        <b>Starring:</b>
                        {foreach from=$movie->casts->cast item=cast name="cast"}
                            {if $cast->name != ''}
                                <a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}"
                                   title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>{if $smarty.foreach.cast.index == 5 or $smarty.foreach.cast.index == ($movie->casts->cast|count - 1)}
                                <br/>
                                {break}{else},
                            {/if}
                            {/if}
                            {foreachelse}
                            No cast data.
                            <br/>
                        {/foreach}
                        <b>Directed By:</b>
                        {foreach from=$movie->casts->crew item=crew name="crew"}
                            {if $crew->name != ''}
                                {if $crew->job == "Director"}
                                    <a href="{$smarty.const.WWW_TOP}/movies?director={$crew->name|escape:"htmlall"}"
                                       title="Search for movies directed by {$crew->name|escape:"htmlall"}">{$crew->name|escape:"htmlall"}</a>
                                {/if}
                            {/if}
                            {foreachelse}
                            No crew data.
                            <br/>
                        {/foreach}
                        <br/>
                        <br/>
                    {else}
                        <br/>
                    {/if}
                    <b>Release Date:</b>
                    {$movie->release_date}
                    <br/>
                    {if $selection != '2'}
                        {if $movie->revenue > 0}
                            <b>Box Office:</b>
                            ${$movie->revenue|number_format}
                        {/if}
                    {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    <h2>No results</h2>
{/if}
