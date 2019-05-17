<?php
/**
* @var \App\View\AppView $this
* @var \App\Model\Entity\User $profile
*/
?>

<!-- Content Header (Page header) -->
<section class="content-header">
	<h1>Profile for <?= $profile->username ?></h1>
</section>

<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-6">
			<!--div class="box"-->
				<div class="box-header">
					<table class="table">
						<tr>
							<th scope="row"><?= __('User name') ?></th>
							<td><?= h($profile->username) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('First name') ?></th>
							<td><?= h($profile->firstname) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Last name') ?></th>
							<td><?= h($profile->lastname) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Role') ?></th>
							<td><?= $this->Number->format($profile->role) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Email') ?></th>
							<td><?= h($profile->email) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Password') ?></th>
							<td><?= h($profile->password) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Host') ?></th>
							<td><?= h($profile->host) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('RSS token') ?></th>
							<td><?= h($profile->rsstoken) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Reset GUUID') ?></th>
							<td><?= h($profile->resetguid) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Userseed') ?></th>
							<td><?= h($profile->userseed) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Style/Theme') ?></th>
							<td><?= h(empty($profile->style) ? __('Site Default') : __
								($profile->style)) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Grabs') ?></th>
							<td><?= $this->Number->format($profile->grabs) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Invites') ?></th>
							<td><?= $this->Number->format($profile->invites) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Invitedby') ?></th>
							<td><?= $this->Number->format($profile->invitedby) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Book view') ?></th>
							<td><?= $this->Number->format($profile->bookview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Console view') ?></th>
							<td><?= $this->Number->format($profile->consoleview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Film/Movie view') ?></th>
							<td><?= $this->Number->format($profile->movieview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Game view') ?></th>
							<td><?= $this->Number->format($profile->gameview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Music view') ?></th>
							<td><?= $this->Number->format($profile->musicview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('XXX view') ?></th>
							<td><?= $this->Number->format($profile->xxxview) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Createddate') ?></th>
							<td><?= h($profile->createddate) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Last login') ?></th>
							<td><?= h($profile->lastlogin->i18nFormat('yyyy-MM-dd HH:mm:ss', 'UTC',
									'UTC')) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Apiaccess') ?></th>
							<td><?= h($profile->apiaccess) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Couch Potato Url') ?></th>
							<td><?= h($profile->cp_url) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Couch Potato Api') ?></th>
							<td><?= h($profile->cp_api) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Queuetype') ?></th>
							<td><?= $profile->queuetype ? __('SABnzb') : __('NZBGet'); ?></td>
						</tr>
						<!-- todo display only SABnzb or nzbget depending on queue type above -->
						<?php if ($profile->queuetype === 0) : ?>
						<tr>
							<th scope="row"><?= __('Nzbgeturl') ?></th>
							<td><?= h($profile->nzbgeturl) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Nzbgetusername') ?></th>
							<td><?= h($profile->nzbgetusername) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Nzbgetpassword') ?></th>
							<td><?= h($profile->nzbgetpassword) ?></td>
						</tr>
						<?php else : ?>
						<tr>
							<th scope="row"><?= __('Saburl') ?></th>
							<td><?= h($profile->saburl) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Sabapikey') ?></th>
							<td><?= h($profile->sabapikey) ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Sabapikeytype') ?></th>
							<td><?= $profile->sabapikeytype ? __('Yes') : __('No'); ?></td>
						</tr>
						<tr>
							<th scope="row"><?= __('Sabpriority') ?></th>
							<td><?= $profile->sabpriority ? __('Yes') : __('No'); ?></td>
						</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /.content -->

<!-- DataTables -->
<?php echo $this->Html->css('AdminLTE./bower_components/datatables.net-bs/css/dataTables.bootstrap.min',
	['block' => 'css']); ?>

<!-- DataTables -->
<?php echo $this->Html->script('AdminLTE./bower_components/datatables.net/js/jquery.dataTables.min',
	['block' => 'script']); ?>
<?php echo $this->Html->script('AdminLTE./bower_components/datatables.net-bs/js/dataTables.bootstrap.min',
	['block' => 'script']); ?>

<?php $this->start('scriptBottom'); ?>

<?php $this->end(); ?>
