( function() {
  "use strict";

  /**
   * Easy selector helper function
   */
  const select = (el, all = false) => {
    el = el.trim()
    if (all) {
      return [...document.querySelectorAll(el)]
    } else {
      return document.querySelector(el)
    }
  }

  /**
   * Easy event listener function
   */
  const on = (type, el, listener, all = false) => {
    let selectEl = select(el, all)
    if (selectEl) {
      if (all) {
        selectEl.forEach(e => e.addEventListener(type, listener))
      } else {
        selectEl.addEventListener(type, listener)
      }
    }
  }

  /**
   * Easy on scroll event listener 
   */
  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener)
  }

  /**
   * Toggle .header-scrolled class to #header when page is scrolled
   */
  let selectHeader = select('#header')
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled')
      } else {
        selectHeader.classList.remove('header-scrolled')
      }
    }
    window.addEventListener('load', headerScrolled)
    onscroll(document, headerScrolled)
  }
  
  /**
   * Mobile nav toggle
   */
  on('click', '.mobile-nav-toggle', function(e) {
    select('#navbar').classList.toggle('navbar-mobile')
    this.classList.toggle('bi-list')
    this.classList.toggle('bi-x')
  })

  /**
   * Mobile nav dropdowns activate
   */
  on('click', '.navbar .dropdown > a', function(e) {
    if (select('#navbar').classList.contains('navbar-mobile')) {
      e.preventDefault()
      this.nextElementSibling.classList.toggle('dropdown-active')
    }
  }, true)  

})()

// Prüfe, ob der User angemeldet ist
async function updateMailBadge() {
    try {
        const res = await fetch('/includes/plugins/messenger/get_total_unread_count.php');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();

        const badge = document.getElementById('total-unread-badge');
        const icon = document.getElementById('mail-icon');

        const unread = data.total_unread ?? 0; // fallback 0

        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : unread;
            badge.style.display = 'inline-block'; // nur sichtbar wenn >0
            icon.classList.remove('bi-envelope-dash');
            icon.classList.add('bi-envelope-check');
        } else {
            badge.style.display = 'none'; // unsichtbar bei 0
            icon.classList.remove('bi-envelope-check');
            icon.classList.add('bi-envelope-dash');
        }

    } catch (err) {
        console.error("Fehler beim Laden der Mail-Badge:", err);
    }
}

// Direkt beim Laden und alle 30 Sekunden
document.addEventListener('DOMContentLoaded', () => {
    updateMailBadge();
    setInterval(updateMailBadge, 30000);
});


