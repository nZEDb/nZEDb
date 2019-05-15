<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Error\Debugger;
use Cake\Http\Exception\NotFoundException;

//$this->layout = false;

if (!Configure::read('debug')) :
    throw new NotFoundException(
        'Please replace src/Template/Pages/home.ctp with your own version or re-enable debug mode.'
    );
endif;

?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>Welcome to nZEDb <!--?= Configure::version() ?-->.</h1>
</section>

<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="callout callout-info">
                <p>Please be aware that this page will not be shown if you disable debug mode
				   unless you replace src/Template/Pages/debug.ctp with your own version.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="callout callout-danger" id="url-rewriting">
                <?php Debugger::checkSecurityKeys(); ?>
                <p class="problem">URL rewriting is not properly configured on your server.</p>
                <p>
                    1) <a target="_blank" href="https://book.cakephp.org/3.0/en/installation.html#url-rewriting">Help me configure it</a>
                </p>
                <p>
                    2) <a target="_blank" href="https://book.cakephp.org/3.0/en/development/configuration.html#general-configuration">I don't / can't use URL rewriting</a>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php
            $class = 'callout-warning';

            if (version_compare(PHP_VERSION, '5.6.0', '>=') && extension_loaded('mbstring') && (extension_loaded('openssl') || extension_loaded('mcrypt')) && extension_loaded('intl')) {
                $class = 'callout-success';
            }
            ?>
            <div class="callout <?= $class ?>">
                <h4>Environment</h4>
                    <?php if (version_compare(PHP_VERSION, '5.6.0', '>=')): ?>
                        <p class="success">Your version of PHP is 5.6.0 or higher (detected <?= PHP_VERSION ?>).</p>
                    <?php else: ?>
                        <p class="problem">Your version of PHP is too low. You need PHP 5.6.0 or higher to use CakePHP (detected <?= PHP_VERSION ?>).</p>
                    <?php endif; ?>

                    <?php if (extension_loaded('mbstring')): ?>
                        <p class="success">Your version of PHP has the mbstring extension loaded.</p>
                    <?php else: ?>
                        <p class="problem">Your version of PHP does NOT have the mbstring extension loaded.</p>;
                    <?php endif; ?>

                    <?php if (extension_loaded('openssl')): ?>
                        <p class="success">Your version of PHP has the openssl extension loaded.</p>
                    <?php elseif (extension_loaded('mcrypt')): ?>
                        <p class="success">Your version of PHP has the mcrypt extension loaded.</p>
                    <?php else: ?>
                        <p class="problem">Your version of PHP does NOT have the openssl or mcrypt extension loaded.</p>
                    <?php endif; ?>

                    <?php if (extension_loaded('intl')): ?>
                        <p class="success">Your version of PHP has the intl extension loaded.</p>
                    <?php else: ?>
                        <p class="problem">Your version of PHP does NOT have the intl extension loaded.</p>
                    <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php
            $settings = Cache::getConfig('_cake_core_');
            $class = 'callout-warning';

            if (is_writable(TMP) && is_writable(LOGS) && !empty($settings)) {
                $class = 'callout-success';
            }
            ?>
            <div class="callout <?= $class ?>">
                <h4>Filesystem</h4>
                <?php if (is_writable(TMP)): ?>
                    <p class="success">Your tmp directory is writable.</p>
                <?php else: ?>
                    <p class="text-red">Your tmp directory is NOT writable.</p>
                <?php endif; ?>

                <?php if (is_writable(LOGS)): ?>
                    <p class="success">Your logs directory is writable.</p>
                <?php else: ?>
                    <p class="text-red">Your logs directory is NOT writable.</p>
                <?php endif; ?>

                <?php if (!empty($settings)): ?>
                    <p class="success">The <em><?= $settings['className'] ?>Engine</em> is being used for core caching.</p>
                <?php else: ?>
                    <p class="problem">Your cache is NOT working. Please check the settings in config/app.php</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php
                try {
                    $connection = ConnectionManager::get('default');
                    $connected = $connection->connect();
                } catch (Exception $connectionError) {
                    $connected = false;
                    $errorMsg = $connectionError->getMessage();
                    if (method_exists($connectionError, 'getAttributes')):
                        $attributes = $connectionError->getAttributes();
                        if (isset($errorMsg['message'])):
                            $errorMsg .= '<br />' . $attributes['message'];
                        endif;
                    endif;
                }
            ?>
                <?php if ($connected): ?>
                    <div class="callout callout-success">
                        <h4>Database</h4>
                        <p class="success">CakePHP is able to connect to the database.</p>
                    </div>
                <?php else: ?>
                    <div class="callout callout-danger">
                        <h4>Database</h4>
                        <p class="problem">CakePHP is NOT able to connect to the database.<br /><br /><?= $errorMsg ?></p>
                    </div>
                <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if (Plugin::isLoaded('DebugKit')): ?>
                <div class="callout callout-success">
                    <h4>DebugKit</h4>
                        <p class="success">DebugKit is loaded.</p>
                </div>
                <?php else: ?>
                <div class="callout callout-danger">
                    <h4>DebugKit</h4>
                    <p class="problem">DebugKit is NOT loaded. You need to either install pdo_sqlite, or define the "debug_kit" connection name.</p>
                </div>
                <?php endif; ?>
        </div>;
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="callout callout-info">
                <h3>Editing this Page</h3>
                <ul>
                    <li>To change the content of this page, edit: src/Template/Pages/debug.ctp.</li>
                 </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="callout callout-info">
                <h3>Getting Started</h3>
                <ul>
                    <li><a target="_blank" href="http://book.cakephp.org/3.0/en/">CakePHP 3.0 Docs</a></li>
                    <li><a target="_blank" href="https://book.cakephp.org/3.0/en/tutorials-and-examples/cms/installation.html">The 20 min CMS Tutorial</a></li>
                </ul>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->
<?php
$this->Html->css('AdminLTE.debug', ['block' => 'css']);
?>
