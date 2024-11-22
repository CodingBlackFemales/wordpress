export function onSwitcherChange(switcher) {
  const container = switcher.closest(".pmxi-switcher");
  const parent = switcher.closest(".pmxi-switcher-radio-item");
  const target = container.querySelector(".switcher-target-" + switcher.id);

  if (!target) return;

  if (switcher.checked) {
    const allSwitchers = Array.from(
      switcher
        .closest(".pmxi-addon-input-wrap")
        .querySelectorAll(`.switcher[name="${switcher.name}"]`)
    );

    // Hide all other switchers
    allSwitchers.filter((el) => el !== switcher).forEach(onSwitcherChange);

    parent.classList.add("active");
    target.style.display = "block";

    container.classList.add(`is-switcher-${switcher.id}`);
  } else {
    const clearEl = target.querySelector(".clear-on-switch");
    if (clearEl) clearEl.value = "";

    parent.classList.remove("active");
    target.style.display = "none";

    container.classList.remove(`is-switcher-${switcher.id}`);
  }
}

export function refreshSwitchers(container) {
  container.querySelectorAll(".switcher").forEach(onSwitcherChange);
}

export function refreshShowSearchInMedia(container) {
  container
    .querySelectorAll(".pmxi-search-in-media-input")
    .forEach((input) => {
      input.addEventListener("change", (event) =>
        maybeShowSearchInMedia(event.target)
      );
      maybeShowSearchInMedia(input);
    });
}

function maybeShowSearchInMedia(checkbox) {
  const related = checkbox
    .closest(".pmxi-addon-subfields")
    .querySelector(".pmxi-search-logic");

  if (checkbox.checked) {
    related.style.display = "block";
  } else {
    related.style.display = "none";
  }
}
