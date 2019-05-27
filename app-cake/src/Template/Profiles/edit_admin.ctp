<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $profile
 */

$form = $this->Form;

function booleanOption()
{
	return [
		0 => __('No'),
		1 => __('Yes')
	];
}

function select($form, $fieldName, $value, $options): string
{
	return $form->control($fieldName,
		[
			'id'		=> $fieldName,
			'label'		=> false,
			'name'		=> $fieldName,
			'options'	=> selectOptions((int)$value, $options),
			'required'	=> true,
			'type'		=> 'select',
		]
	);
}

function selectOptions($current, array $entries): array
{
	$options = [];
	foreach ($entries as $key => $value):
		$options[] = [
			'label'		=> false,
			'selected'	=> $key === $current,
			'text'		=> $value,
			'value'		=> $key,
		];
	endforeach;

	return $options;
}

function showNZBget($form): void
{
	tr($form, 'nzbgeturl', 'NZBGet URL');
	tr($form, 'nzbgetusername', 'NZBGet user name');
	tr($form, 'nzbgetpassword', 'NZBGet password');
}

function showSABnzb($form): void
{
	tr($form, 'saburl', 'SABnzbd URL');
	tr($form, 'sabapikey', 'SABnzbd API key');
	tr($form, 'sabapikeytype', 'SABnzbd API key type');
	tr($form, 'sabpriority', 'SABnzbs priority');
}

function tr($form, $field, $label): void
{
	echo '<tr class=\"form-group\">' . PHP_EOL;
	echo $form->label($field, __($label));
	echo $form->control($field, ['label' => false, 'placeholder' => $label]);
	echo "</tr>\n";
}

?>
<section class="content">
	<div class="row">
		<div class="col-md-6">
			<!--div class="box"-->
			<div class="box-header">
			<?= $form->create($profile, ['templates' => 'edit_form']) ?>
				<fieldset>
					<legend><?= __('Edit User') ?></legend>
					<table class="table">
						<tr class="form-group">
							<?= $form->label('username', __('User name')) ?>
							<?= $form->control('username', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('firstname', __('First name')) ?>
							<?= $form->control('firstname', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('lastname', __('Last name')) ?>
							<?= $form->control('lastname', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('email', __('e-mail')) ?>
							<?= $form->control('email', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('password', __('Password')) ?>
							<?= $form->control('password', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('role_id', __('Role')) ?>
							<!--?= $form->control('role_id', ['label' => false, 'placeholder' =>
								'unknown']) ?-->
							<?= select($form, 'role_id', $profile->role_id, $roles) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('style', __('Style/Theme')) ?>
							<?= $form->control('style', ['label' => false, 'placeholder' =>
								'Site Default']) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('host', __('Host')) ?>
							<?= $form->control('host', ['label' => false, 'placeholder' => 'not set']) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('invitedby', __('Invited by')) ?>
							<?= $form->control('invitedby', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('resetguid', __('Reset gUUID')) ?>
							<?= $form->control('resetguid', ['label' => false]) ?>
						</tr>

						<tr class="form-group">
							<?= $form->label('bookview', __('Book view')) ?>
							<?= select($form, 'bookview', $profile->bookview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('consoleview', __('Console view')) ?>
							<?= select($form, 'consoleview', $profile->consoleview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('movieview', __('Film/Movie view')) ?>
							<?= select($form, 'movieview', $profile->movieview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('gameview', __('Game view')) ?>
							<?= select($form, 'gameview', $profile->gameview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('musicview', __('Music view')) ?>
							<?= select($form, 'musicview', $profile->musicview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('xxxview', __('XXX view')) ?>
							<?= select($form, 'xxxview', $profile->xxxview, booleanOption()) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('', __('Queue type')) ?>
							<?= select($form, 'queuetype', $profile->queuetype, [
								0 => 'NZBget',
								1 => 'SABnzbd'
							]) ?>
						</tr>
						<!-- Show the active queue type first. -->
						<?php
							if ((int)$profile->queuetype === 1) :
								showSABnzb($form);

								showNZBget($form);
							else:
								showNZBget($form);

								showSABnzb($form);
							endif
						?>
						<tr class="form-group">
							<?= $form->label('cp.api', __('CouchPotato API')) ?>
							<?= $form->control('cp.api', ['label' => false, 'placeholder' => 'CouchPotato API']) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('cp.url', __('CouchPotato URL')) ?>
							<?= $form->control('cp_url', ['label' => false, 'placeholder' => 'CouchPotato URL']) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('grabs', __('Grabs')) ?>
							<?= $form->control('grabs', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('invites', __('Invites')) ?>
							<?= $form->control('invites', ['label' => false]) ?>
						</tr>
						<tr class="form-group">
							<?= $form->label('', __('RSS token')) ?>
							<?= $form->control('rsstoken', ['label' => false, 'placeholder' => 'not set']) ?>
						</tr>
					</table>
				</fieldset>
				<?= $form->button(__('Submit')) ?>
				<?= $form->end() ?>
			</div>
		</div>
	</div>
</section>
