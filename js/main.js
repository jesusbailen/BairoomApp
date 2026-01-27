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
