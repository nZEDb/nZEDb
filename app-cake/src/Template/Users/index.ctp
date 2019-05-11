<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User[]|\Cake\Collection\CollectionInterface $users
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New User'), ['action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Forum Posts'), ['controller' => 'ForumPosts', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Forum Post'), ['controller' => 'ForumPosts', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Invitations'), ['controller' => 'Invitations', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Invitation'), ['controller' => 'Invitations', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Release Comments'), ['controller' => 'ReleaseComments', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Release Comment'), ['controller' => 'ReleaseComments', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List User Downloads'), ['controller' => 'UserDownloads', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User Download'), ['controller' => 'UserDownloads', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List User Excluded Categories'), ['controller' => 'UserExcludedCategories', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User Excluded Category'), ['controller' => 'UserExcludedCategories', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List User Movies'), ['controller' => 'UserMovies', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User Movie'), ['controller' => 'UserMovies', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List User Requests'), ['controller' => 'UserRequests', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User Request'), ['controller' => 'UserRequests', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List User Series'), ['controller' => 'UserSeries', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User Series'), ['controller' => 'UserSeries', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Releases'), ['controller' => 'Releases', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Release'), ['controller' => 'Releases', 'action' => 'add']) ?></li>
    </ul>
</nav>
<div class="users index large-9 medium-8 columns content">
    <h3><?= __('Users') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('username') ?></th>
                <th scope="col"><?= $this->Paginator->sort('firstname') ?></th>
                <th scope="col"><?= $this->Paginator->sort('lastname') ?></th>
                <th scope="col"><?= $this->Paginator->sort('email') ?></th>
                <th scope="col"><?= $this->Paginator->sort('password') ?></th>
                <th scope="col"><?= $this->Paginator->sort('role') ?></th>
                <th scope="col"><?= $this->Paginator->sort('host') ?></th>
                <th scope="col"><?= $this->Paginator->sort('grabs') ?></th>
                <th scope="col"><?= $this->Paginator->sort('rsstoken') ?></th>
                <th scope="col"><?= $this->Paginator->sort('createddate') ?></th>
                <th scope="col"><?= $this->Paginator->sort('resetguid') ?></th>
                <th scope="col"><?= $this->Paginator->sort('lastlogin') ?></th>
                <th scope="col"><?= $this->Paginator->sort('apiaccess') ?></th>
                <th scope="col"><?= $this->Paginator->sort('invites') ?></th>
                <th scope="col"><?= $this->Paginator->sort('invitedby') ?></th>
                <th scope="col"><?= $this->Paginator->sort('movieview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('xxxview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('musicview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('consoleview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('bookview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('gameview') ?></th>
                <th scope="col"><?= $this->Paginator->sort('saburl') ?></th>
                <th scope="col"><?= $this->Paginator->sort('sabapikey') ?></th>
                <th scope="col"><?= $this->Paginator->sort('sabapikeytype') ?></th>
                <th scope="col"><?= $this->Paginator->sort('sabpriority') ?></th>
                <th scope="col"><?= $this->Paginator->sort('queuetype') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nzbgeturl') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nzbgetusername') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nzbgetpassword') ?></th>
                <th scope="col"><?= $this->Paginator->sort('userseed') ?></th>
                <th scope="col"><?= $this->Paginator->sort('cp_url') ?></th>
                <th scope="col"><?= $this->Paginator->sort('cp_api') ?></th>
                <th scope="col"><?= $this->Paginator->sort('style') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $this->Number->format($user->id) ?></td>
                <td><?= h($user->username) ?></td>
                <td><?= h($user->firstname) ?></td>
                <td><?= h($user->lastname) ?></td>
                <td><?= h($user->email) ?></td>
                <td><?= h($user->password) ?></td>
                <td><?= $this->Number->format($user->role) ?></td>
                <td><?= h($user->host) ?></td>
                <td><?= $this->Number->format($user->grabs) ?></td>
                <td><?= h($user->rsstoken) ?></td>
                <td><?= h($user->createddate) ?></td>
                <td><?= h($user->resetguid) ?></td>
                <td><?= h($user->lastlogin) ?></td>
                <td><?= h($user->apiaccess) ?></td>
                <td><?= $this->Number->format($user->invites) ?></td>
                <td><?= $this->Number->format($user->invitedby) ?></td>
                <td><?= $this->Number->format($user->movieview) ?></td>
                <td><?= $this->Number->format($user->xxxview) ?></td>
                <td><?= $this->Number->format($user->musicview) ?></td>
                <td><?= $this->Number->format($user->consoleview) ?></td>
                <td><?= $this->Number->format($user->bookview) ?></td>
                <td><?= $this->Number->format($user->gameview) ?></td>
                <td><?= h($user->saburl) ?></td>
                <td><?= h($user->sabapikey) ?></td>
                <td><?= h($user->sabapikeytype) ?></td>
                <td><?= h($user->sabpriority) ?></td>
                <td><?= h($user->queuetype) ?></td>
                <td><?= h($user->nzbgeturl) ?></td>
                <td><?= h($user->nzbgetusername) ?></td>
                <td><?= h($user->nzbgetpassword) ?></td>
                <td><?= h($user->userseed) ?></td>
                <td><?= h($user->cp_url) ?></td>
                <td><?= h($user->cp_api) ?></td>
                <td><?= h($user->style) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $user->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
    </div>
</div>
