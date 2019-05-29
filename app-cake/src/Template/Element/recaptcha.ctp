<?php

use Cake\Core\Configure;

// @var string $recaptcha_sitekey
?>
<?php if (Configure::read('recaptcha.enabled') !== false): ?>
<div class="g-recaptcha row" data-sitekey="<?= Configure::read('recaptcha.keys.site') ?>"></div>
<?php endif; ?>
