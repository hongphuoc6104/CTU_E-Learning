    </main>
  </div><!-- /.ml-64 -->
</div><!-- /.flex min-h-screen -->

<script>
(function() {
  var sidebar = document.getElementById('adminSidebar');
  var overlay = document.getElementById('adminSidebarOverlay');
  var toggleBtn = document.getElementById('adminSidebarToggle');

  function openAdminSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (overlay) overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.add('hidden');
    document.body.style.overflow = '';
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openAdminSidebar);

  // Expose for inline onclick
  window.closeAdminSidebar = closeAdminSidebar;
})();
</script>

</body>
</html>
