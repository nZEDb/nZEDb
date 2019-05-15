<?php

$menu_class = $this->Identity->isLoggedIn() ? ' class="dropdown-menu"' : '';

?>
<?php if ($this->Identity->isLoggedIn()) : ?>
	<li class="dropdown user user-menu">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
			<span class="hidden-xs"><?= $this->Identity->get('username') ?></span>
		</a>
		<ul class="dropdown-menu">
			<!-- User image -->
			<li class="user-header">
				<p>Full Name Here</p>
			</li>
			<!-- Menu Body -->
			<!-- Menu Footer-->
			<li class="user-footer"><a href="#" class="btn btn-default btn-flat">Profile</a></li>
			<li><a href="#" class="btn btn-default btn-flat">Sign out</a></li>
		</ul>

	<!--li class="dropdown-menu"><?= $this->Html->link('Profile', ['controller' => 'Users', 'action' => 'view']) ?></li>
		<li class="dropdown-menu"><?= $this->Html->link('Logout', ['controller' => 'Users', 'action' => 'logout']) ?></li-->
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
