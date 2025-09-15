(function() {
  "use strict";

  //if (messengerActive) {
    async function updateMailBadge() {
      try {
        const res = await fetch('/includes/plugins/messenger/get_total_unread_count.php');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();

        const badge = document.getElementById('total-unread-badge');
        const icon = document.getElementById('mail-icon');

        const unread = data.total_unread ?? 0;

        if (unread > 0) {
          badge.textContent = unread > 99 ? '99+' : unread;
          badge.style.display = 'inline-block';
          icon.classList.remove('bi-envelope-dash');
          icon.classList.add('bi-envelope-check');
        } else {
          badge.style.display = 'none';
          icon.classList.remove('bi-envelope-check');
          icon.classList.add('bi-envelope-dash');
        }

      } catch (err) {
        console.error("Fehler beim Laden der Mail-Badge:", err);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateMailBadge();
      setInterval(updateMailBadge, 30000);
    });
  //}
})();
