</div> <!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle rotation
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(link => {
    link.addEventListener('click', function() {
      const icon = this.querySelector('.rotate-icon');
      if (icon) {
        const expanded = this.getAttribute('aria-expanded') === 'true';
        icon.style.transform = expanded ? 'rotate(0deg)' : 'rotate(90deg)';
      }
    });
  });
</script>
</body>
</html>