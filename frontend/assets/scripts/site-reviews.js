document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM loaded – site-reviews actief");

  const modal = document.getElementById("reviewsModal");
  const titleEl = document.getElementById("reviewsTitle");
  const subEl = document.getElementById("reviewsSub");
  const bodyEl = document.getElementById("reviewsBody");

  if (!modal || !titleEl || !subEl || !bodyEl) {
    console.error("Reviews modal elementen niet gevonden");
    return;
  }

function openModal() {
  modal.hidden = false;                // mag blijven
  modal.classList.add("open");         // <-- belangrijk
  modal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  modal.classList.remove("open");      // <-- belangrijk
  modal.setAttribute("aria-hidden", "true");
  modal.hidden = true;                 // mag blijven
}

// Sluiten via backdrop of knop (robuster)
modal.addEventListener("click", (e) => {
  if (e.target.closest("[data-close]")) {
    closeModal();
  }
});

  // ESC sluit modal
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.hidden) {
      closeModal();
    }
  });

  async function loadReviews(siteId, siteTitle, groupName) {
    titleEl.textContent = "Reviews";
    subEl.textContent = `${siteTitle} – ${groupName}`;
    bodyEl.textContent = "Laden…";
    openModal();

    try {
      const res = await fetch(`reviews.php?site_id=${encodeURIComponent(siteId)}`);
      const data = await res.json();
      const items = data.items || [];

      if (!items.length) {
        bodyEl.innerHTML = `<div class="notice">Nog geen reviews geplaatst.</div>`;
        return;
      }

      bodyEl.innerHTML = items.map(r => `
        <div class="review-row">
          <div class="review-score">Score: ${r.score}/5</div>
          <div>${r.comment}</div>
          <div class="review-date">${String(r.created_at).slice(0,10)}</div>
        </div>
      `).join("");

    } catch (err) {
      console.error(err);
      bodyEl.innerHTML = `<div class="notice">Fout bij laden van reviews.</div>`;
    }
  }

  document.querySelectorAll("[data-open-reviews]").forEach(btn => {
    btn.addEventListener("click", () => {
      console.log("Klik op reviews", btn.dataset.siteId);
      loadReviews(
        btn.dataset.siteId,
        btn.dataset.siteTitle || "Website",
        btn.dataset.groupName || "Groep"
      );
    });
  });
});
