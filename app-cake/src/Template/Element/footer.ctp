<footer class="main-footer">
  <?php if (isset($layout) && $layout == 'top'): ?>
  <div class="container">
  <?php endif; ?>
    <div class="pull-right hidden-xs">
		<!-- replace static version number with git tag lookup. -->
		<b>Version</b> 2.4.5
    </div>
    <p style="text-align: center"><strong>Copyright <span class="fa fa-copyright"></span> 2014-2019
		<a href="https://nZEDb.com">nZEDb</a>.</strong>
		Released under the GNU General Public License v2.0.</p>
  <?php if (isset($layout) && $layout == 'top'): ?>
  </div>
  <?php endif; ?>
</footer>
