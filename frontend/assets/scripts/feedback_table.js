(() => {
  const table = document.getElementById("feedbackTable");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const headers = Array.from(table.querySelectorAll("thead th"));

  let current = { index: -1, dir: "desc" };

  function getCellValue(row, index, type) {
    const th = headers[index];
    // Gebruik data-* voor betrouwbare sort (ipv text parsing)
    if (index === 0) return row.dataset.name || "";
    if (index === 1) return row.dataset.class || "";
    if (index === 2) return Number(row.dataset.count || "0");
    if (index === 3) return row.dataset.last || "";

    // fallback
    const cell = row.children[index];
    return cell ? cell.textContent.trim() : "";
  }

  function sortRows(index, dir, type) {
    const rows = Array.from(tbody.querySelectorAll("tr"));

    rows.sort((a, b) => {
      const va = getCellValue(a, index, type);
      const vb = getCellValue(b, index, type);

      if (type === "number") {
        return dir === "asc" ? (va - vb) : (vb - va);
      }

      const sa = String(va).toLowerCase();
      const sb = String(vb).toLowerCase();
      if (sa < sb) return dir === "asc" ? -1 : 1;
      if (sa > sb) return dir === "asc" ? 1 : -1;
      return 0;
    });

    // DOM update
    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }

  function clearHeaderState() {
    headers.forEach(h => {
      h.classList.remove("sort-asc", "sort-desc");
    });
  }

  headers.forEach((th, index) => {
    th.addEventListener("click", () => {
      const type = th.dataset.type || "text";

      // toggle dir als je dezelfde kolom klikt
      if (current.index === index) {
        current.dir = current.dir === "asc" ? "desc" : "asc";
      } else {
        current.index = index;
        current.dir = (type === "number") ? "desc" : "asc";
      }

      clearHeaderState();
      th.classList.add(current.dir === "asc" ? "sort-asc" : "sort-desc");

      sortRows(index, current.dir, type);
    });
  });

  // Start: zet indicator op "Aantal feedback" (kolom 2) desc
  headers[2].classList.add("sort-desc");
})();
