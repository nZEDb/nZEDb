<footer class="main-footer">
  <?php if (isset($layout) && $layout == 'top'): ?>
  <div class="container">
  <?php endif; ?>
    <div class="pull-right hidden-xs">
		<!-- replace static version number with git tag lookup. -->
      <b>Version</b> 2.4.5
    </div>
    <strong>Copyright &copy; 2014-2019 <a href="https://nZEDb.com">nZEDb</a>.</strong> Released under the
		GNU General Public License v3.0.
  <?php if (isset($layout) && $layout == 'top'): ?>
  </div>
  <?php endif; ?>
</footer>
