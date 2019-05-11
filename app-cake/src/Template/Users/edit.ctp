<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $user->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Users'), ['action' => 'index']) ?></li>
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
<div class="users form large-9 medium-8 columns content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Edit User') ?></legend>
        <?php
            echo $this->Form->control('username');
            echo $this->Form->control('firstname');
            echo $this->Form->control('lastname');
            echo $this->Form->control('email');
            echo $this->Form->control('password');
            echo $this->Form->control('role');
            echo $this->Form->control('host');
            echo $this->Form->control('grabs');
            echo $this->Form->control('rsstoken');
            echo $this->Form->control('createddate');
            echo $this->Form->control('resetguid');
            echo $this->Form->control('lastlogin', ['empty' => true]);
            echo $this->Form->control('apiaccess', ['empty' => true]);
            echo $this->Form->control('invites');
            echo $this->Form->control('invitedby');
            echo $this->Form->control('movieview');
            echo $this->Form->control('xxxview');
            echo $this->Form->control('musicview');
            echo $this->Form->control('consoleview');
            echo $this->Form->control('bookview');
            echo $this->Form->control('gameview');
            echo $this->Form->control('saburl');
            echo $this->Form->control('sabapikey');
            echo $this->Form->control('sabapikeytype');
            echo $this->Form->control('sabpriority');
            echo $this->Form->control('queuetype');
            echo $this->Form->control('nzbgeturl');
            echo $this->Form->control('nzbgetusername');
            echo $this->Form->control('nzbgetpassword');
            echo $this->Form->control('userseed');
            echo $this->Form->control('cp_url');
            echo $this->Form->control('cp_api');
            echo $this->Form->control('style');
            echo $this->Form->control('releases._ids', ['options' => $releases]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
