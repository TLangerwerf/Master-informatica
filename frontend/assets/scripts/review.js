(function () {
  const scriptTag = document.currentScript;
  const shouldOpen = scriptTag?.dataset.open === '1';

  const modal = document.getElementById('reviewModal');
  if (!modal) return;

  const sub = document.getElementById('modalSub');
  const siteIdInput = document.getElementById('modalSiteId');

  function openModal(siteId, title, myScore, myComment) {
    if (siteIdInput) siteIdInput.value = siteId || '';
    if (sub) sub.textContent = title ? ('Voor: ' + title) : '';

    const scoreEl = modal.querySelector('select[name="score"]');
    const commentEl = modal.querySelector('textarea[name="comment"]');

    if (scoreEl) scoreEl.value = myScore ? String(myScore) : '';
    if (commentEl) commentEl.value = myComment ? String(myComment) : '';

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');

    if (commentEl) commentEl.focus();
  }

  function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', (e) => {

    // zoekt of er op een .js-review knop is geklikt
    const btn = e.target.closest('.js-review');

    if (btn) {
      openModal(
        btn.dataset.siteId,
        btn.dataset.siteTitle,
        btn.dataset.myScore,
        btn.dataset.myComment
      );
      return;
    }

    // sluiten via backdrop of X
    if (e.target.closest('[data-close="1"]')) {
      closeModal();
    }
  });

  // ESC om te sluiten
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('open')) {
      closeModal();
    }
  });

  // Automatisch openen na POST (error of succes)
  if (shouldOpen) {
  // Bij auto-open: zorg dat het site_id veld gevuld blijft uit de HTML (value="<?= $modalSiteId ?>")
  // en zet focus netjes.
  const commentEl = modal.querySelector('textarea[name="comment"]');
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
  if (commentEl) commentEl.focus();
}

})();
