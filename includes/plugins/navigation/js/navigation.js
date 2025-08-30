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
const userID = window.NEXP_USER_ID || 0; // z.B. vom Backend per JS gesetzt

if (userID > 0) {
    function updateUnreadBadge() {
        fetch('/includes/plugins/messenger/get_total_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('total-unread-badge');
                if (!badge) return;
                
                badge.textContent = data.total_unread;
                badge.style.display = data.total_unread > 0 ? 'inline-block' : 'none';
            })
            .catch(err => console.error('Fehler beim Laden der Nachrichten:', err));
    }

    // Direkt beim Laden der Seite aufrufen
    updateUnreadBadge();

    // Optional: alle 30 Sekunden aktualisieren
    setInterval(updateUnreadBadge, 30000);
}
