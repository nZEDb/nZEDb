<?php

?>
<?php if ($this->Identity->isLoggedIn()) : ?>
	<li class="dropdown user user-menu">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
			<span class="fa fa-user"> </span><span class="hidden-xs" style="vertical-align: top"><?=
				$this->Identity->get('username')
				?></span>
			<span class="fa fa-angle-double-down pull-right" style="vertical-align: bottom;
			font-size: 1.5em;"></span>
		</a>
		<ul class="dropdown-menu">
			<!-- User image -->
			<li class="user-header">
				<p><?= $this->Identity->get('firstname') ?> <?= $this->Identity->get('lastname') ?></p>
			</li>
			<!-- Menu Body -->
			<!-- Menu Footer-->
			<li class="user-footer"><?= $this->Html->link('Profile',
					['controller' => 'Profiles', 'action' => 'view'],
					['class' => 'btn btn-default btn-flat']) ?></li>
			<li><?= $this->Html->link('Sign out',
					['controller' => 'Users', 'action' => 'logout'],
					['class' => 'btn btn-default btn-flat']) ?></a></li>
		</ul>
	</li>
<?php else : ?>
	<li class="user-body">
		<div class="pull-left">
	<?= $this->Html->link('Login',
		[
			'controller' => 'Users',
		 	'action' => 'login',
		],
		['class' => 'btn']
	) ?>
		</div>
		<div class="pull-right">
		<?= $this->Html->link('Sign Up',
			[
				'controller' => 'Users',
				'action'     => 'add',
			],
			[
				'class'      => 'btn'
			]
		) ?>
		</div>
	</li>
<?php endif; ?>
