<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete User'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Users'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User'), ['action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Forum Posts'), ['controller' => 'ForumPosts', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Forum Post'), ['controller' => 'ForumPosts', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Invitations'), ['controller' => 'Invitations', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Invitation'), ['controller' => 'Invitations', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Release Comments'), ['controller' => 'ReleaseComments', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Release Comment'), ['controller' => 'ReleaseComments', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List User Downloads'), ['controller' => 'UserDownloads', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User Download'), ['controller' => 'UserDownloads', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List User Excluded Categories'), ['controller' => 'UserExcludedCategories', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User Excluded Category'), ['controller' => 'UserExcludedCategories', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List User Movies'), ['controller' => 'UserMovies', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User Movie'), ['controller' => 'UserMovies', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List User Requests'), ['controller' => 'UserRequests', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User Request'), ['controller' => 'UserRequests', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List User Series'), ['controller' => 'UserSeries', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User Series'), ['controller' => 'UserSeries', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Releases'), ['controller' => 'Releases', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Release'), ['controller' => 'Releases', 'action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="users view large-9 medium-8 columns content">
    <h3><?= h($user->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Username') ?></th>
            <td><?= h($user->username) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Firstname') ?></th>
            <td><?= h($user->firstname) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Lastname') ?></th>
            <td><?= h($user->lastname) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Email') ?></th>
            <td><?= h($user->email) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Password') ?></th>
            <td><?= h($user->password) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Host') ?></th>
            <td><?= h($user->host) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Rsstoken') ?></th>
            <td><?= h($user->rsstoken) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Resetguid') ?></th>
            <td><?= h($user->resetguid) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Saburl') ?></th>
            <td><?= h($user->saburl) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Sabapikey') ?></th>
            <td><?= h($user->sabapikey) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Nzbgeturl') ?></th>
            <td><?= h($user->nzbgeturl) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Nzbgetusername') ?></th>
            <td><?= h($user->nzbgetusername) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Nzbgetpassword') ?></th>
            <td><?= h($user->nzbgetpassword) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Userseed') ?></th>
            <td><?= h($user->userseed) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cp Url') ?></th>
            <td><?= h($user->cp_url) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cp Api') ?></th>
            <td><?= h($user->cp_api) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Style') ?></th>
            <td><?= h($user->style) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($user->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Role') ?></th>
            <td><?= $this->Number->format($user->role) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Grabs') ?></th>
            <td><?= $this->Number->format($user->grabs) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Invites') ?></th>
            <td><?= $this->Number->format($user->invites) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Invitedby') ?></th>
            <td><?= $this->Number->format($user->invitedby) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Movieview') ?></th>
            <td><?= $this->Number->format($user->movieview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Xxxview') ?></th>
            <td><?= $this->Number->format($user->xxxview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Musicview') ?></th>
            <td><?= $this->Number->format($user->musicview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Consoleview') ?></th>
            <td><?= $this->Number->format($user->consoleview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Bookview') ?></th>
            <td><?= $this->Number->format($user->bookview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Gameview') ?></th>
            <td><?= $this->Number->format($user->gameview) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Createddate') ?></th>
            <td><?= h($user->createddate) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Lastlogin') ?></th>
            <td><?= h($user->lastlogin) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Apiaccess') ?></th>
            <td><?= h($user->apiaccess) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Sabapikeytype') ?></th>
            <td><?= $user->sabapikeytype ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Sabpriority') ?></th>
            <td><?= $user->sabpriority ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Queuetype') ?></th>
            <td><?= $user->queuetype ? __('Yes') : __('No'); ?></td>
        </tr>
    </table>
    <div class="related">
        <h4><?= __('Related Releases') ?></h4>
        <?php if (!empty($user->releases)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Categories Id') ?></th>
                <th scope="col"><?= __('Name') ?></th>
                <th scope="col"><?= __('Searchname') ?></th>
                <th scope="col"><?= __('Totalpart') ?></th>
                <th scope="col"><?= __('Groups Id') ?></th>
                <th scope="col"><?= __('Size') ?></th>
                <th scope="col"><?= __('Postdate') ?></th>
                <th scope="col"><?= __('Adddate') ?></th>
                <th scope="col"><?= __('Updatetime') ?></th>
                <th scope="col"><?= __('Guid') ?></th>
                <th scope="col"><?= __('Leftguid') ?></th>
                <th scope="col"><?= __('Fromname') ?></th>
                <th scope="col"><?= __('Completion') ?></th>
                <th scope="col"><?= __('Videos Id') ?></th>
                <th scope="col"><?= __('Tv Episodes Id') ?></th>
                <th scope="col"><?= __('Imdbid') ?></th>
                <th scope="col"><?= __('Xxxinfo Id') ?></th>
                <th scope="col"><?= __('Musicinfo Id') ?></th>
                <th scope="col"><?= __('Consoleinfo Id') ?></th>
                <th scope="col"><?= __('Gamesinfo Id') ?></th>
                <th scope="col"><?= __('Bookinfo Id') ?></th>
                <th scope="col"><?= __('Anidbid') ?></th>
                <th scope="col"><?= __('Predb Id') ?></th>
                <th scope="col"><?= __('Grabs') ?></th>
                <th scope="col"><?= __('Comments') ?></th>
                <th scope="col"><?= __('Passwordstatus') ?></th>
                <th scope="col"><?= __('Rarinnerfilecount') ?></th>
                <th scope="col"><?= __('Haspreview') ?></th>
                <th scope="col"><?= __('Nfostatus') ?></th>
                <th scope="col"><?= __('Jpgstatus') ?></th>
                <th scope="col"><?= __('Videostatus') ?></th>
                <th scope="col"><?= __('Audiostatus') ?></th>
                <th scope="col"><?= __('Dehashstatus') ?></th>
                <th scope="col"><?= __('Reqidstatus') ?></th>
                <th scope="col"><?= __('Nzb Guid') ?></th>
                <th scope="col"><?= __('Nzbstatus') ?></th>
                <th scope="col"><?= __('Iscategorized') ?></th>
                <th scope="col"><?= __('Isrenamed') ?></th>
                <th scope="col"><?= __('Ishashed') ?></th>
                <th scope="col"><?= __('Isrequestid') ?></th>
                <th scope="col"><?= __('Proc Pp') ?></th>
                <th scope="col"><?= __('Proc Sorter') ?></th>
                <th scope="col"><?= __('Proc Par2') ?></th>
                <th scope="col"><?= __('Proc Nfo') ?></th>
                <th scope="col"><?= __('Proc Files') ?></th>
                <th scope="col"><?= __('Proc Uid') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->releases as $releases): ?>
            <tr>
                <td><?= h($releases->id) ?></td>
                <td><?= h($releases->categories_id) ?></td>
                <td><?= h($releases->name) ?></td>
                <td><?= h($releases->searchname) ?></td>
                <td><?= h($releases->totalpart) ?></td>
                <td><?= h($releases->groups_id) ?></td>
                <td><?= h($releases->size) ?></td>
                <td><?= h($releases->postdate) ?></td>
                <td><?= h($releases->adddate) ?></td>
                <td><?= h($releases->updatetime) ?></td>
                <td><?= h($releases->guid) ?></td>
                <td><?= h($releases->leftguid) ?></td>
                <td><?= h($releases->fromname) ?></td>
                <td><?= h($releases->completion) ?></td>
                <td><?= h($releases->videos_id) ?></td>
                <td><?= h($releases->tv_episodes_id) ?></td>
                <td><?= h($releases->imdbid) ?></td>
                <td><?= h($releases->xxxinfo_id) ?></td>
                <td><?= h($releases->musicinfo_id) ?></td>
                <td><?= h($releases->consoleinfo_id) ?></td>
                <td><?= h($releases->gamesinfo_id) ?></td>
                <td><?= h($releases->bookinfo_id) ?></td>
                <td><?= h($releases->anidbid) ?></td>
                <td><?= h($releases->predb_id) ?></td>
                <td><?= h($releases->grabs) ?></td>
                <td><?= h($releases->comments) ?></td>
                <td><?= h($releases->passwordstatus) ?></td>
                <td><?= h($releases->rarinnerfilecount) ?></td>
                <td><?= h($releases->haspreview) ?></td>
                <td><?= h($releases->nfostatus) ?></td>
                <td><?= h($releases->jpgstatus) ?></td>
                <td><?= h($releases->videostatus) ?></td>
                <td><?= h($releases->audiostatus) ?></td>
                <td><?= h($releases->dehashstatus) ?></td>
                <td><?= h($releases->reqidstatus) ?></td>
                <td><?= h($releases->nzb_guid) ?></td>
                <td><?= h($releases->nzbstatus) ?></td>
                <td><?= h($releases->iscategorized) ?></td>
                <td><?= h($releases->isrenamed) ?></td>
                <td><?= h($releases->ishashed) ?></td>
                <td><?= h($releases->isrequestid) ?></td>
                <td><?= h($releases->proc_pp) ?></td>
                <td><?= h($releases->proc_sorter) ?></td>
                <td><?= h($releases->proc_par2) ?></td>
                <td><?= h($releases->proc_nfo) ?></td>
                <td><?= h($releases->proc_files) ?></td>
                <td><?= h($releases->proc_uid) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'Releases', 'action' => 'view', $releases->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'Releases', 'action' => 'edit', $releases->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'Releases', 'action' => 'delete', $releases->id], ['confirm' => __('Are you sure you want to delete # {0}?', $releases->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related Forum Posts') ?></h4>
        <?php if (!empty($user->forum_posts)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Forumid') ?></th>
                <th scope="col"><?= __('Parentid') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Subject') ?></th>
                <th scope="col"><?= __('Message') ?></th>
                <th scope="col"><?= __('Locked') ?></th>
                <th scope="col"><?= __('Sticky') ?></th>
                <th scope="col"><?= __('Replies') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col"><?= __('Updateddate') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->forum_posts as $forumPosts): ?>
            <tr>
                <td><?= h($forumPosts->id) ?></td>
                <td><?= h($forumPosts->forumid) ?></td>
                <td><?= h($forumPosts->parentid) ?></td>
                <td><?= h($forumPosts->user_id) ?></td>
                <td><?= h($forumPosts->subject) ?></td>
                <td><?= h($forumPosts->message) ?></td>
                <td><?= h($forumPosts->locked) ?></td>
                <td><?= h($forumPosts->sticky) ?></td>
                <td><?= h($forumPosts->replies) ?></td>
                <td><?= h($forumPosts->createddate) ?></td>
                <td><?= h($forumPosts->updateddate) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'ForumPosts', 'action' => 'view', $forumPosts->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'ForumPosts', 'action' => 'edit', $forumPosts->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'ForumPosts', 'action' => 'delete', $forumPosts->id], ['confirm' => __('Are you sure you want to delete # {0}?', $forumPosts->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related Invitations') ?></h4>
        <?php if (!empty($user->invitations)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Guid') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->invitations as $invitations): ?>
            <tr>
                <td><?= h($invitations->id) ?></td>
                <td><?= h($invitations->guid) ?></td>
                <td><?= h($invitations->user_id) ?></td>
                <td><?= h($invitations->createddate) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'Invitations', 'action' => 'view', $invitations->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'Invitations', 'action' => 'edit', $invitations->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'Invitations', 'action' => 'delete', $invitations->id], ['confirm' => __('Are you sure you want to delete # {0}?', $invitations->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related Release Comments') ?></h4>
        <?php if (!empty($user->release_comments)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Releases Id') ?></th>
                <th scope="col"><?= __('Text') ?></th>
                <th scope="col"><?= __('Text Hash') ?></th>
                <th scope="col"><?= __('Username') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col"><?= __('Host') ?></th>
                <th scope="col"><?= __('Shared') ?></th>
                <th scope="col"><?= __('Shareid') ?></th>
                <th scope="col"><?= __('Siteid') ?></th>
                <th scope="col"><?= __('Nzb Guid') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->release_comments as $releaseComments): ?>
            <tr>
                <td><?= h($releaseComments->id) ?></td>
                <td><?= h($releaseComments->releases_id) ?></td>
                <td><?= h($releaseComments->text) ?></td>
                <td><?= h($releaseComments->text_hash) ?></td>
                <td><?= h($releaseComments->username) ?></td>
                <td><?= h($releaseComments->user_id) ?></td>
                <td><?= h($releaseComments->createddate) ?></td>
                <td><?= h($releaseComments->host) ?></td>
                <td><?= h($releaseComments->shared) ?></td>
                <td><?= h($releaseComments->shareid) ?></td>
                <td><?= h($releaseComments->siteid) ?></td>
                <td><?= h($releaseComments->nzb_guid) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'ReleaseComments', 'action' => 'view', $releaseComments->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'ReleaseComments', 'action' => 'edit', $releaseComments->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'ReleaseComments', 'action' => 'delete', $releaseComments->id], ['confirm' => __('Are you sure you want to delete # {0}?', $releaseComments->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related User Downloads') ?></h4>
        <?php if (!empty($user->user_downloads)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Timestamp') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->user_downloads as $userDownloads): ?>
            <tr>
                <td><?= h($userDownloads->id) ?></td>
                <td><?= h($userDownloads->user_id) ?></td>
                <td><?= h($userDownloads->timestamp) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'UserDownloads', 'action' => 'view', $userDownloads->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'UserDownloads', 'action' => 'edit', $userDownloads->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'UserDownloads', 'action' => 'delete', $userDownloads->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userDownloads->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related User Excluded Categories') ?></h4>
        <?php if (!empty($user->user_excluded_categories)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Categories Id') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->user_excluded_categories as $userExcludedCategories): ?>
            <tr>
                <td><?= h($userExcludedCategories->id) ?></td>
                <td><?= h($userExcludedCategories->user_id) ?></td>
                <td><?= h($userExcludedCategories->categories_id) ?></td>
                <td><?= h($userExcludedCategories->createddate) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'UserExcludedCategories', 'action' => 'view', $userExcludedCategories->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'UserExcludedCategories', 'action' => 'edit', $userExcludedCategories->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'UserExcludedCategories', 'action' => 'delete', $userExcludedCategories->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userExcludedCategories->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related User Movies') ?></h4>
        <?php if (!empty($user->user_movies)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Imdbid') ?></th>
                <th scope="col"><?= __('Categories') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->user_movies as $userMovies): ?>
            <tr>
                <td><?= h($userMovies->id) ?></td>
                <td><?= h($userMovies->user_id) ?></td>
                <td><?= h($userMovies->imdbid) ?></td>
                <td><?= h($userMovies->categories) ?></td>
                <td><?= h($userMovies->createddate) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'UserMovies', 'action' => 'view', $userMovies->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'UserMovies', 'action' => 'edit', $userMovies->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'UserMovies', 'action' => 'delete', $userMovies->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userMovies->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related User Requests') ?></h4>
        <?php if (!empty($user->user_requests)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Request') ?></th>
                <th scope="col"><?= __('Timestamp') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->user_requests as $userRequests): ?>
            <tr>
                <td><?= h($userRequests->id) ?></td>
                <td><?= h($userRequests->user_id) ?></td>
                <td><?= h($userRequests->request) ?></td>
                <td><?= h($userRequests->timestamp) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'UserRequests', 'action' => 'view', $userRequests->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'UserRequests', 'action' => 'edit', $userRequests->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'UserRequests', 'action' => 'delete', $userRequests->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userRequests->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related User Series') ?></h4>
        <?php if (!empty($user->user_series)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('User Id') ?></th>
                <th scope="col"><?= __('Videos Id') ?></th>
                <th scope="col"><?= __('Categories') ?></th>
                <th scope="col"><?= __('Createddate') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->user_series as $userSeries): ?>
            <tr>
                <td><?= h($userSeries->id) ?></td>
                <td><?= h($userSeries->user_id) ?></td>
                <td><?= h($userSeries->videos_id) ?></td>
                <td><?= h($userSeries->categories) ?></td>
                <td><?= h($userSeries->createddate) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'UserSeries', 'action' => 'view', $userSeries->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'UserSeries', 'action' => 'edit', $userSeries->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'UserSeries', 'action' => 'delete', $userSeries->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userSeries->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
