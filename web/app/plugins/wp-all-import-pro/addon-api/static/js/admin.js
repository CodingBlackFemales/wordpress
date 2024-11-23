import { toggleGroup } from "./groups.js";
import { onSwitcherChange } from "./switchers.js";

addEventListener("DOMContentLoaded", () => {
  if (!document.body.classList.contains("wpallimport-plugin")) return;

  // Watch for group checkbox change
  const checkboxes = document.querySelectorAll(
    ".wpallimport-import-group-checkbox"
  );

  checkboxes.forEach(async (checkbox) => {
    if (checkbox.checked) {
      toggleGroup(checkbox);
    }

    checkbox.addEventListener("change", () => toggleGroup(checkbox));
  });

  addEventListener("click", (event) => {
    if (!event.target.matches(".switcher")) return;
    if (!event.target.closest(".pmxi-switcher")) return;
    onSwitcherChange(event.target);
  });

  // Reveal some elements on change
  addEventListener("change", (event) => {
    if (!event.target.matches(".pmxi-reveal-on-change")) return;

    const target = document.querySelector(`#${event.target.dataset.target}`);

    if (event.target.checked) {
      target.removeAttribute("hidden");
    } else {
      target.setAttribute("hidden", "");
    }
  });
});
