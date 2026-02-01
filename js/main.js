(() => {
  const openMenu = document.getElementById("openMenu");
  const closeMenu = document.getElementById("closeMenu");
  const mobileMenu = document.getElementById("mobileMenu");

  if (!openMenu || !closeMenu || !mobileMenu) {
    return;
  }

  openMenu.addEventListener("click", () => {
    mobileMenu.classList.add("active");
  });

  closeMenu.addEventListener("click", () => {
    mobileMenu.classList.remove("active");
  });
})();

(() => {
  const cookieFab = document.getElementById("cookieFab");
  const cookiePanel = document.getElementById("cookiePanel");
  const cookieClose = document.getElementById("cookieClose");

  if (!cookieFab || !cookiePanel || !cookieClose) {
    return;
  }

  cookieFab.addEventListener("click", () => {
    cookiePanel.classList.toggle("active");
    cookiePanel.setAttribute(
      "aria-hidden",
      cookiePanel.classList.contains("active") ? "false" : "true",
    );
  });

  cookieClose.addEventListener("click", () => {
    cookiePanel.classList.remove("active");
    cookiePanel.setAttribute("aria-hidden", "true");
  });
})();
