import { refreshShowSearchInMedia, refreshSwitchers } from "./switchers.js";
import { refreshDatePickers } from "./date.js";

addEventListener("pmxi-refresh-repeater", (event) => {
  const { node } = event.detail;

  // Add event listeners
  refreshSwitchers(node);
  refreshShowSearchInMedia(node);
  refreshDatePickers(node);
});

function addRow(repeater) {
  const container = repeater.querySelector(".pmxi-repeater-rows");
  const template = repeater.querySelector(".pmxi-repeater-template");

  const clone = template.content.cloneNode(true);
  const index = container.children.length;

  // Update index
  clone.querySelector(".pmxi-repeater-row-index").textContent = `#${index + 1}`;

  // Update names to match index
  clone.querySelectorAll("input,select,textarea,div").forEach((element) => {
    for (let attr of element.attributes) {
      attr.value = attr.value.replace("__index__", index);
    }
  });

  clone.querySelectorAll("label").forEach((label) => {
    label.htmlFor = label.htmlFor.replace("__index__", index);
  });

  // Add event listeners
  refreshSwitchers(clone);
  refreshShowSearchInMedia(clone);
  refreshDatePickers(clone);

  // Then add to DOM
  container.appendChild(clone);
}

function removeRow(row) {
  const container = row.parentElement;
  row.remove();

  // Refresh indexes
  [...container.children].forEach((row, index) => {
    row.querySelector(".pmxi-repeater-row-index").textContent = `#${index + 1}`;

    row.querySelectorAll("input, select, textarea").forEach((input) => {
      input.name = input.name.replace(
        new RegExp(`\\[\\d+\\]`, "g"),
        `[${index}]`
      );
    });
  });
}

function removeAllRowsExceptFirst(repeater) {
  const rows = repeater.querySelectorAll(".pmxi-repeater-row");
  [...rows].filter((r, i) => i > 0).forEach((r) => r.remove());
}

function getModeSwitchers(repeater) {
  return [
    ...repeater.parentElement.querySelectorAll(".pmxi-repeater-mode .switcher"),
  ];
}

function getMode(repeater) {
  const switchers = getModeSwitchers(repeater);

  // Get the first checked switcher
  return switchers
    .filter((i) => i.checked)
    .map((i) => i.value)
    .at(0);
}

function updateUI(repeater, addButton) {
  const mode = getMode(repeater);
  const rows = repeater.querySelectorAll(".pmxi-repeater-row");

  if (mode === "fixed") {
    repeater.classList.add("is-fixed");
  } else {
    repeater.classList.remove("is-fixed");

    if (rows.length === 0) {
      addRow(repeater);
    }

    removeAllRowsExceptFirst(repeater);
  }
}

/**
 * @param {HTMLElement} repeater
 */
export function refreshRepeater(repeater) {
  if (repeater.classList.contains("pmxi-repeater-initialized")) return;

  repeater.classList.add("pmxi-repeater-initialized");

  const addButton = repeater.querySelector(".pmxi-repeater-add-row");
  const switchers = getModeSwitchers(repeater);

  addButton.addEventListener("click", () => {
    addRow(repeater);
  });

  switchers.forEach((switcher) => {
    switcher.addEventListener("change", () => updateUI(repeater, addButton));
  });

  repeater.addEventListener("click", (event) => {
    if (!event.target.matches(".pmxi-repeater-remove-row")) return;
    removeRow(event.target.closest(".pmxi-repeater-row"));
  });

  updateUI(repeater, switchers, addButton);
}
