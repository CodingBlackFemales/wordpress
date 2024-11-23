import { refreshDatePickers } from "./date.js";
import { refreshSwitchers, refreshShowSearchInMedia } from "./switchers.js";
import { refreshRepeater } from "./repeaters.js";

/**
 * @param {string} addon
 */
export function getAddonEl(addon) {
  return document.querySelector(`.pmxi-addon[data-addon="${addon}"]`);
}

/**
 * @param {string} addon
 */
export function getGroupEl(addon, group) {
  const containerEl = getAddonEl(addon);
  return containerEl.querySelector(`.pmxi-addon-group[data-group="${group}"]`);
}

/**
 * @param {Object} params
 * @param {string} params.addon
 * @param {string} params.group
 * @param {string} params.type
 * @param {string} params.subtype
 * @param {string} params.nonce
 * @returns {Promise<void>}
 */
export async function loadGroup({
  addon,
  group,
  type,
  subtype,
  nonce,
  checkbox,
}) {
  const output = getAddonEl(addon).querySelector(".pmxi-addon-groups-output");

  const url = new URL(window.pmxiAddon.ajaxUrl);
  const params = new URLSearchParams(url.search);
  const id = new URLSearchParams(location.search).get("id");

  params.append("addon", addon);
  params.append("group", group);
  params.append("type", type);
  params.append("subtype", subtype);
  params.append("action", true);

  if (id) {
    params.append("id", id);
  }

  url.search = params.toString();

  // Show loader
  const loader = createLoader(output, checkbox.dataset.label);

  // Load group
  const response = await fetch(url.href, {
    credentials: "same-origin",
    headers: { "X-WP-Nonce": nonce },
  });

  const body = await response.json();
  output.insertAdjacentHTML("beforeend", body.html);

  loader.remove();
}

/**
 * @param {string} addon
 * @param {string} group
 * @param {HTMLElement} checkbox
 */
export function clearGroup({ addon, group, checkbox }) {
  if (confirm("Confirm removal?")) {
    const groupEl = getGroupEl(addon, group);
    groupEl.remove();
  } else {
    checkbox.checked = true;
  }
}

/**
 * @param {HTMLElement} checkbox
 */
export async function toggleGroup(checkbox) {
  const container = checkbox.closest(".pmxi-addon");
  const group = checkbox.dataset.group;
  const { addon, type, subtype, nonce } = container.dataset;

  if (checkbox.checked) {
    await loadGroup({ addon, group, type, subtype, nonce, checkbox });
    afterGroupLoaded(container);
  } else {
    clearGroup({ addon, group, checkbox });
  }
}

function afterGroupLoaded(container) {
  const customEvent = new CustomEvent("pmxi-group-loaded", {
    bubbles: true,
    detail: { container },
  });

  dispatchEvent(customEvent);

  // Refresh switchers
  refreshSwitchers(container);

  // Refresh datepickers
  refreshDatePickers(container);

  // Refresh repeaters
  container
    .querySelectorAll(".pmxi-repeater")
    .forEach((repeater) => refreshRepeater(repeater));

  // Double click to insert element
  container.addEventListener("focusin", (event) => {
    const isInput = event.target.matches("input, textarea");
    const selected = document.querySelector(".xml-element.selected");
    if (!isInput || !selected) return;
    event.target.value += selected.title.replace(/\/[^\/]*\//, "{") + "}";
    selected.classList.remove("selected");
  });

  // Reveal some elements on page load
  const revealOnLoad = document.querySelectorAll(".pmxi-reveal-on-change");

  revealOnLoad.forEach((element) => {
    const target = document.querySelector(`#${element.dataset.target}`);

    if (element.checked) {
      target.removeAttribute("hidden");
    }
  });

  // Search in media logic
  refreshShowSearchInMedia(container);
}

function createLoader(container, label = "Loading...") {
  const loader = document.createElement("div");
  loader.classList.add(...["postbox", "default", "rad4", "pmxi-addon-group"]);
  loader.innerHTML = `<h3 class="postbox-title"><span>${label}</span></h3> <span class="pxmi-progress-bar"></span>`;
  container.insertAdjacentElement("beforeend", loader);
  return loader;
}
