/**
 * CBF Slides Importer — Admin JS
 *
 * Implements the Drive file picker, import job list, and per-job configuration
 * UI (Phase 3) using vanilla JS + the Google Picker API.
 *
 * Globals injected by Admin\Assets::add_scripts() via wp_localize_script:
 *   cbf_slides_importer_admin_params.rest_url     — plugin REST namespace base (cbf-si/v1/)
 *   cbf_slides_importer_admin_params.wp_rest_url  — WP REST root (…/wp-json/)
 *   cbf_slides_importer_admin_params.nonce        — wp_rest nonce for X-WP-Nonce header
 *   cbf_slides_importer_admin_params.ajax_url     — (reserved for legacy calls)
 *
 * @package CodingBlackFemales/SlidesImporter
 */

/* global cbf_slides_importer_admin_params, google, gapi */
(function () {
  "use strict";

  // ── Bootstrap ─────────────────────────────────────────────────────────────

  /**
   * Safe DOM-ready bootstrap.
   *
   * WP admin pages can have 70+ synchronous body scripts ahead of ours, so
   * DOMContentLoaded may have already fired by the time this IIFE runs.
   * Checking readyState handles both the early-script and late-script cases.
   */
  function bootstrap() {
    const app = document.getElementById("cbf-si-app");
    if (!app) {
      return;
    }
    CbfSiApp.init(app);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootstrap);
  } else {
    // DOM already parsed — invoke immediately.
    bootstrap();
  }

  // ── REST helper ───────────────────────────────────────────────────────────

  const Api = {
    /**
     * Fetch a cbf-si REST endpoint with the wp_rest nonce.
     *
     * @param {string} path   Relative to rest_url (e.g. 'drive/picker-config').
     * @param {object} [opts] fetch() options override.
     * @returns {Promise<any>}
     */
    fetch(path, opts) {
      opts = opts || {};
      const url = cbf_slides_importer_admin_params.rest_url + path;
      const headers = Object.assign(
        {
          "X-WP-Nonce": cbf_slides_importer_admin_params.nonce,
          "Content-Type": "application/json",
        },
        opts.headers || {},
      );
      const fetchOpts = Object.assign({}, opts, { headers: headers });
      return fetch(url, fetchOpts).then(async (res) => {
        const json = await res.json();
        if (!res.ok) {
          throw new Error(json.message || "HTTP " + res.status);
        }
        return json;
      });
    },

    pickerConfig() {
      return this.fetch("drive/picker-config");
    },

    createJob(driveFileId, deckName) {
      return this.fetch("jobs", {
        method: "POST",
        body: JSON.stringify({
          drive_file_id: driveFileId,
          deck_name: deckName,
        }),
      });
    },

    getJob(id) {
      return this.fetch("jobs/" + id);
    },

    listJobs(page) {
      page = page || 1;
      return this.fetch("jobs?page=" + page + "&per_page=20");
    },

    /**
     * Trigger the import phase for a parsed job.
     *
     * @param {number} id       Job ID.
     * @param {object} [config] Optional { mode, course_id, post_title } overrides.
     * @returns {Promise<any>}
     */
    triggerImport(id, config) {
      return this.fetch("jobs/" + id + "/import", {
        method: "POST",
        body: JSON.stringify(config || {}),
      });
    },

    cancelJob(id) {
      return this.fetch("jobs/" + id + "/cancel", { method: "POST" });
    },

    /**
     * Fetch LearnDash courses for the course selector dropdown.
     * Uses the standard WP REST API (sfwd-courses CPT).
     *
     * @returns {Promise<Array>}
     */
    getCourses() {
      const url =
        cbf_slides_importer_admin_params.wp_rest_url +
        "wp/v2/sfwd-courses?per_page=100&orderby=title&order=asc&status=publish";
      return fetch(url, {
        headers: {
          "X-WP-Nonce": cbf_slides_importer_admin_params.nonce,
        },
      }).then(function (res) {
        return res.ok ? res.json() : [];
      });
    },
  };

  // ── Google Picker ─────────────────────────────────────────────────────────

  const Picker = {
    _config: null,
    _gapiReady: false,

    /**
     * Load the Google API client library, then open the picker.
     *
     * @param {function} onSelected  Called with (fileId, fileName) on selection.
     * @param {function} onDismissed Called with no arguments when the picker is
     *                               closed without a selection.
     */
    open(onSelected, onDismissed) {
      Api.pickerConfig()
        .then((cfg) => {
          this._config = cfg;
          if (this._gapiReady) {
            this._buildAndShow(onSelected, onDismissed);
          } else {
            gapi.load("picker", () => {
              this._gapiReady = true;
              this._buildAndShow(onSelected, onDismissed);
            });
          }
        })
        .catch((err) => {
          CbfSiApp.showError("Could not load Drive picker: " + err.message);
          if (onDismissed) {
            onDismissed();
          }
        });
    },

    _buildAndShow(onSelected, onDismissed) {
      const { access_token, folder_id } = this._config;

      // Save scroll position — the picker iframe can cause the browser to jump.
      const savedScrollX = window.scrollX;
      const savedScrollY = window.scrollY;

      // Show only Google Slides presentations inside the configured folder.
      const view = new google.picker.DocsView(
        google.picker.ViewId.PRESENTATIONS,
      )
        .setParent(folder_id)
        .setIncludeFolders(false);

      const picker = new google.picker.PickerBuilder()
        .addView(view)
        .setOAuthToken(access_token)
        .setTitle("Select a slide deck to import")
        .setCallback((data) => {
          if (data.action === google.picker.Action.PICKED) {
            const doc = data.docs[0];
            onSelected(doc.id, doc.name);
          } else if (data.action === google.picker.Action.CANCEL) {
            if (onDismissed) {
              onDismissed();
            }
          }
        })
        .build();

      picker.setVisible(true);

      // Restore scroll position after the picker iframe is injected.
      window.scrollTo(savedScrollX, savedScrollY);
      requestAnimationFrame(() => window.scrollTo(savedScrollX, savedScrollY));
    },
  };

  // ── Main app ──────────────────────────────────────────────────────────────

  const CbfSiApp = {
    _root: null,
    _jobListEl: null,
    _statusEl: null,
    _pollTimers: {},
    _courses: null, // cached LearnDash courses for the config dropdown

    init(root) {
      this._root = root;
      this._render();
      this._loadJobs();

      // Show success notice if redirected back from OAuth.
      const params = new URLSearchParams(window.location.search);
      if (params.get("oauth") === "success") {
        this.showNotice("✓ Google Drive connected successfully.", "success");
      }
    },

    // ── Render skeleton ────────────────────────────────────────────────────

    _render() {
      this._root.innerHTML =
        '<div id="cbf-si-notices"></div>' +
        '<div class="cbf-si-actions" style="margin:16px 0;">' +
        '<button id="cbf-si-pick-btn" class="button button-primary button-large">' +
        "⇪ Choose Slide Deck from Drive" +
        "</button>" +
        "</div>" +
        '<h2 style="margin-top:24px;">Import Jobs</h2>' +
        '<div id="cbf-si-job-list"><p class="cbf-si-loading">Loading…</p></div>';

      document
        .getElementById("cbf-si-pick-btn")
        .addEventListener("click", () => this._openPicker());

      this._jobListEl = document.getElementById("cbf-si-job-list");
      this._statusEl = document.getElementById("cbf-si-notices");
    },

    // ── Picker flow ────────────────────────────────────────────────────────

    _openPicker() {
      const btn = document.getElementById("cbf-si-pick-btn");
      const resetBtn = () => {
        btn.disabled = false;
        btn.textContent = "⇪ Choose Slide Deck from Drive";
      };

      btn.disabled = true;
      btn.textContent = "Loading picker…";

      Picker.open(
        (fileId, fileName) => {
          resetBtn();
          this._confirmAndCreate(fileId, fileName);
        },
        () => {
          // Picker dismissed without a selection.
          resetBtn();
        },
      );
    },

    _confirmAndCreate(fileId, fileName) {
      if (!window.confirm('Import "' + fileName + '" into LearnDash?')) {
        return;
      }
      this.showNotice('Creating import job for "' + fileName + '"…');

      Api.createJob(fileId, fileName)
        .then((job) => {
          this.showNotice(
            "✓ Job #" + job.id + " queued — downloading and parsing…",
            "success",
          );
          this._loadJobs();
          this._pollJob(job.id);
        })
        .catch((err) => this.showError("Could not create job: " + err.message));
    },

    // ── Courses cache ──────────────────────────────────────────────────────

    _loadCourses() {
      if (this._courses !== null) {
        return Promise.resolve(this._courses);
      }
      return Api.getCourses()
        .then((courses) => {
          this._courses = Array.isArray(courses) ? courses : [];
          return this._courses;
        })
        .catch(() => {
          this._courses = [];
          return [];
        });
    },

    // ── Job list ───────────────────────────────────────────────────────────

    _loadJobs() {
      Api.listJobs()
        .then((jobs) => this._renderJobList(jobs))
        .catch((err) => {
          this._jobListEl.innerHTML =
            '<p class="cbf-si-error">Could not load jobs: ' +
            this._esc(err.message) +
            "</p>";
        });
    },

    _renderJobList(jobs) {
      if (!jobs.length) {
        this._jobListEl.innerHTML =
          "<p>No import jobs yet. Choose a slide deck above to get started.</p>";
        return;
      }

      const rows = jobs.map((j) => this._jobRow(j)).join("");
      this._jobListEl.innerHTML =
        '<table class="wp-list-table widefat fixed striped" style="margin-top:8px;">' +
        "<thead><tr>" +
        '<th style="width:50px">#</th>' +
        "<th>Deck name</th>" +
        '<th style="width:130px">Status</th>' +
        '<th style="width:160px">Created</th>' +
        '<th style="width:240px">Actions</th>' +
        "</tr></thead>" +
        "<tbody>" +
        rows +
        "</tbody>" +
        "</table>" +
        '<p style="margin-top:8px;">' +
        '<button class="button" id="cbf-si-refresh-btn">↻ Refresh</button>' +
        "</p>";

      document
        .getElementById("cbf-si-refresh-btn")
        .addEventListener("click", () => this._loadJobs());

      this._bindJobActions();
    },

    _jobRow(j) {
      const badge = this._statusBadge(j.status);
      const created = new Date(j.created_at + "Z").toLocaleString();
      const actions = this._jobActions(j);
      return (
        '<tr id="cbf-si-job-' +
        j.id +
        '" data-deck-name="' +
        this._esc(j.deck_name || "") +
        '">' +
        "<td>" +
        j.id +
        "</td>" +
        "<td>" +
        this._esc(j.deck_name || j.drive_file_id) +
        "</td>" +
        "<td>" +
        badge +
        "</td>" +
        "<td>" +
        created +
        "</td>" +
        "<td>" +
        actions +
        "</td>" +
        "</tr>"
      );
    },

    _statusBadge(status) {
      const colours = {
        pending: "#888",
        downloading: "#0073aa",
        parsing: "#0073aa",
        parsed: "#00a32a",
        importing: "#f0ad4e",
        done: "#00a32a",
        failed: "#d63638",
      };
      const c = colours[status] || "#888";
      return (
        '<span style="display:inline-block;padding:2px 8px;border-radius:3px;' +
        "background:" +
        c +
        ';color:#fff;font-size:12px;">' +
        (status || "-") +
        "</span>"
      );
    },

    _jobActions(j) {
      const btns = [];

      if (j.status === "parsed") {
        // Phase 3: show "Configure & Import" which opens the config panel.
        btns.push(
          '<button class="button button-primary button-small" ' +
            'data-action="configure-import" data-id="' +
            j.id +
            '">Configure &amp; Import…</button>',
        );
      }
      if (j.status === "pending") {
        btns.push(
          '<button class="button button-small" data-action="cancel" data-id="' +
            j.id +
            '">Cancel</button>',
        );
      }
      if (j.status === "done" && j.created_post_ids) {
        try {
          const ids = JSON.parse(j.created_post_ids);
          if (ids.length) {
            btns.push(
              '<span style="color:#00a32a;font-size:12px;">✓ ' +
                ids.length +
                " post" +
                (ids.length > 1 ? "s" : "") +
                " created</span>",
            );
          }
        } catch (e) {
          // Malformed JSON — skip the post-count badge.
        }
      }
      if (j.status === "failed" && j.error_message) {
        btns.push(
          '<span style="color:#d63638;font-size:12px;" title="' +
            this._esc(j.error_message) +
            '">✗ ' +
            this._esc(j.error_message.substring(0, 40)) +
            "…</span>",
        );
      }

      return (
        '<span data-job-actions="' + j.id + '">' + btns.join(" ") + "</span>"
      );
    },

    // ── Config panel (Phase 3) ─────────────────────────────────────────────

    /**
     * Open (or close) the inline configuration panel below a job row.
     *
     * Fetches available LearnDash courses and renders a mode toggle and
     * course selector so the user can configure the import before triggering it.
     *
     * @param {number} jobId  Job ID.
     */
    _openConfigPanel(jobId) {
      // Toggle: close the panel if already open.
      const existing = document.getElementById("cbf-si-config-panel-" + jobId);
      if (existing) {
        existing.remove();
        return;
      }

      // Find the job row to insert the panel below it.
      const jobRow = document.getElementById("cbf-si-job-" + jobId);
      if (!jobRow) {
        return;
      }

      // Show loading placeholder immediately so the button doesn't feel broken.
      const panel = document.createElement("tr");
      panel.id = "cbf-si-config-panel-" + jobId;
      panel.innerHTML =
        '<td colspan="5" style="background:#f6f7f7;padding:16px 20px;">' +
        "<em>Loading courses…</em>" +
        "</td>";
      jobRow.insertAdjacentElement("afterend", panel);

      // Read the deck name from the job row so it can be the title default.
      const deckName = jobRow.dataset.deckName || "";

      this._loadCourses().then((courses) => {
        const courseOptions =
          '<option value="0">— No course —</option>' +
          courses
            .map(
              (c) =>
                '<option value="' +
                c.id +
                '">' +
                this._esc(
                  c.title && c.title.rendered ? c.title.rendered : String(c.id),
                ) +
                "</option>",
            )
            .join("");

        panel.innerHTML =
          '<td colspan="5" style="background:#f6f7f7;padding:16px 20px;border-top:1px solid #ddd;">' +
          '<strong style="font-size:13px;">Import Configuration</strong>' +
          '<table style="margin-top:12px;border-collapse:collapse;">' +
          "<tr>" +
          '<th style="text-align:left;padding:6px 12px 6px 0;white-space:nowrap;font-weight:600;">Title</th>' +
          "<td>" +
          '<input type="text" id="cbf-si-title-' +
          jobId +
          '" value="' +
          this._esc(deckName) +
          '" style="min-width:320px;max-width:500px;" placeholder="Lesson title">' +
          "</td>" +
          "</tr>" +
          "<tr>" +
          '<th style="text-align:left;padding:6px 12px 6px 0;white-space:nowrap;font-weight:600;">Mode</th>' +
          "<td>" +
          '<label style="margin-right:20px;">' +
          '<input type="radio" name="cbf-si-mode-' +
          jobId +
          '" value="lesson-only" checked style="margin-right:4px;">' +
          "Lesson only" +
          "</label>" +
          "<label>" +
          '<input type="radio" name="cbf-si-mode-' +
          jobId +
          '" value="lesson-with-topics" style="margin-right:4px;">' +
          "Lesson + Topics" +
          "</label>" +
          "</td>" +
          "</tr>" +
          "<tr>" +
          '<th style="text-align:left;padding:6px 12px 6px 0;white-space:nowrap;font-weight:600;">Course</th>' +
          "<td>" +
          '<select id="cbf-si-course-' +
          jobId +
          '" style="min-width:260px;max-width:400px;">' +
          courseOptions +
          "</select>" +
          "</td>" +
          "</tr>" +
          "</table>" +
          '<p style="margin-top:14px;margin-bottom:0;">' +
          '<button class="button button-primary" data-action="do-import" data-id="' +
          jobId +
          '">Import into LearnDash</button>' +
          '<button class="button" data-action="cancel-config" data-id="' +
          jobId +
          '" style="margin-left:8px;">Cancel</button>' +
          "</p>" +
          "</td>";
      });
    },

    // ── Job actions (delegated) ────────────────────────────────────────────

    _bindJobActions() {
      this._jobListEl.removeEventListener("click", this._onJobAction);
      this._onJobAction = (e) => {
        const btn = e.target.closest("[data-action]");
        if (!btn) {
          return;
        }
        const action = btn.dataset.action;
        const id = parseInt(btn.dataset.id, 10);

        if (action === "configure-import") {
          this._openConfigPanel(id);
        } else if (action === "do-import") {
          this._doImport(id);
        } else if (action === "cancel-config") {
          const panel = document.getElementById("cbf-si-config-panel-" + id);
          if (panel) {
            panel.remove();
          }
        } else if (action === "cancel") {
          this._doCancel(id);
        }
      };
      this._jobListEl.addEventListener("click", this._onJobAction);
    },

    _doImport(id) {
      // Read config from the panel.
      const titleEl = document.getElementById("cbf-si-title-" + id);
      const modeEl = document.querySelector(
        'input[name="cbf-si-mode-' + id + '"]:checked',
      );
      const courseEl = document.getElementById("cbf-si-course-" + id);
      const postTitle = titleEl ? titleEl.value.trim() : "";
      const mode = modeEl ? modeEl.value : "lesson-only";
      const courseId = courseEl ? parseInt(courseEl.value, 10) : 0;

      // Validate: course_id required for lesson-with-topics (warn, not block).
      if (mode === "lesson-with-topics" && !courseId) {
        if (
          !window.confirm(
            "No course selected. The topics will be created without being assigned to a course.\n\nProceed?",
          )
        ) {
          return;
        }
      }

      if (
        !window.confirm(
          "Import this deck into LearnDash? " +
            "This will create " +
            (mode === "lesson-with-topics" ? "lesson and topic" : "lesson") +
            " posts." +
            (courseId
              ? ""
              : "\n\nNote: no course selected — posts will not be linked to a course."),
        )
      ) {
        return;
      }

      const importConfig = { mode: mode, course_id: courseId };
      if (postTitle) {
        importConfig.post_title = postTitle;
      }

      Api.triggerImport(id, importConfig)
        .then(() => {
          // Close config panel.
          const panel = document.getElementById("cbf-si-config-panel-" + id);
          if (panel) {
            panel.remove();
          }
          this.showNotice("✓ Import triggered for job #" + id, "success");
          this._pollJob(id);
          this._loadJobs();
        })
        .catch((err) => this.showError("Import failed: " + err.message));
    },

    _doCancel(id) {
      Api.cancelJob(id)
        .then(() => {
          this.showNotice("Job #" + id + " cancelled.");
          this._loadJobs();
        })
        .catch((err) => this.showError("Cancel failed: " + err.message));
    },

    // ── Polling ────────────────────────────────────────────────────────────

    _pollJob(id) {
      clearTimeout(this._pollTimers[id]);
      const TERMINAL = new Set(["done", "failed", "parsed"]);
      const poll = () => {
        Api.getJob(id)
          .then((job) => {
            this._loadJobs(); // refresh entire list (keeps it simple)
            if (!TERMINAL.has(job.status)) {
              this._pollTimers[id] = setTimeout(poll, 3000);
            } else if (job.status === "parsed") {
              this.showNotice(
                "✓ Job #" +
                  id +
                  " parsed — click “Configure & Import…” to set import options and start.",
                "success",
              );
            } else if (job.status === "done") {
              this.showNotice("✓ Job #" + id + " complete!", "success");
            } else if (job.status === "failed") {
              this.showError(
                "Job #" +
                  id +
                  " failed: " +
                  (job.error_message || "unknown error"),
              );
            }
          })
          .catch(() => {
            // Network blip — retry.
            this._pollTimers[id] = setTimeout(poll, 5000);
          });
      };
      this._pollTimers[id] = setTimeout(poll, 3000);
    },

    // ── Notices ────────────────────────────────────────────────────────────

    showNotice(msg, type) {
      type = type || "info";
      const colours = { info: "#0073aa", success: "#00a32a", error: "#d63638" };
      const c = colours[type] || colours.info;
      this._statusEl.innerHTML =
        '<div class="notice" style="border-left-color:' +
        c +
        ';padding:8px 12px;margin:8px 0;">' +
        "<p>" +
        this._esc(msg) +
        "</p>" +
        "</div>";
    },

    showError(msg) {
      this.showNotice(msg, "error");
    },

    _esc(str) {
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
    },
  };
})();
