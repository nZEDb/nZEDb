<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="users form large-9 medium-8 columns content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Add User') ?></legend>
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
