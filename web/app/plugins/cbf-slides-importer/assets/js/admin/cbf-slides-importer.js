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
     * Fetch slide metadata for a job (populated after parsing).
     *
     * @param {number} id  Job ID.
     * @returns {Promise<{slides: Array}>}
     */
    getJobSlides(id) {
      return this.fetch("jobs/" + id + "/slides");
    },

    /**
     * Refresh and fetch the rendered block HTML preview for a parsed job.
     *
     * POSTs the current config (slide overrides, mode, etc.) so the server
     * saves them and re-generates the preview before returning it.  This
     * ensures the preview always reflects the current slide-map selections
     * even before the user clicks "Import".
     *
     * Returns { lesson_html, topics } on success or rejects with an error.
     * If the server returns 202 (still generating), rejects with a sentinel
     * error whose message starts with "RETRY:" so the caller can back-off.
     *
     * @param {number} id      Job ID.
     * @param {object} config  Current UI config { mode, slide_overrides, post_title, overwrite }.
     * @returns {Promise<{lesson_html: string, topics: Array}>}
     */
    getJobPreview(id, config) {
      return this.fetch("jobs/" + id + "/preview", {
        method: "POST",
        body: JSON.stringify(config || {}),
      }).then((data) => {
        if (data && data.retry_after) {
          return Promise.reject(new Error("RETRY:" + (data.retry_after || 3)));
        }
        return data;
      });
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
     * @param {function} onReady     Called once the picker UI is visible.
     * @param {number}   scrollX     Scroll position to restore (captured before
     *                               async work begins, so gapi.load() can't skew it).
     * @param {number}   scrollY     Scroll position to restore.
     */
    open(onSelected, onDismissed, onReady, scrollX, scrollY) {
      Api.pickerConfig()
        .then((cfg) => {
          this._config = cfg;
          if (this._gapiReady) {
            this._buildAndShow(
              onSelected,
              onDismissed,
              onReady,
              scrollX,
              scrollY,
            );
          } else {
            gapi.load("picker", () => {
              this._gapiReady = true;
              this._buildAndShow(
                onSelected,
                onDismissed,
                onReady,
                scrollX,
                scrollY,
              );
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

    _buildAndShow(onSelected, onDismissed, onReady, scrollX, scrollY) {
      const { access_token, folder_id } = this._config;

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

      // Guard against the browser scrolling to the picker iframe when it gains
      // focus. The scroll can be asynchronous (fires after setVisible returns),
      // so a synchronous or rAF-based restore arrives too early. Instead,
      // intercept the scroll event itself and immediately scroll back.
      // { once: true } auto-removes the listener on first fire so it cannot
      // interfere with normal scrolling while the picker is open.
      // The setTimeout is a cleanup in case no scroll event fires at all
      // (user was already at the top, or subsequent opens where the iframe
      // already exists and no focus-scroll occurs).
      const scrollGuard = () => window.scrollTo(scrollX, scrollY);
      window.addEventListener("scroll", scrollGuard, { once: true });

      picker.setVisible(true);

      setTimeout(() => window.removeEventListener("scroll", scrollGuard), 1000);

      // Notify the caller that the picker is now visible (e.g. to update the
      // trigger button label while keeping it disabled).
      if (onReady) {
        onReady();
      }
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

      // Capture scroll now — before any async work — so gapi.load() cannot
      // skew the saved position.
      const savedScrollX = window.scrollX;
      const savedScrollY = window.scrollY;

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
        () => {
          // Picker is now visible — revert label (button stays disabled until
          // the user picks a file or closes the picker).
          btn.textContent = "⇪ Choose Slide Deck from Drive";
        },
        savedScrollX,
        savedScrollY,
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
          } else {
            // No posts created — check whether any were skipped due to existing
            // title matches (overwrite was off).
            let skippedIds = [];
            try {
              const summary = JSON.parse(j.result_summary || "{}");
              skippedIds = Array.isArray(summary.skipped_post_ids)
                ? summary.skipped_post_ids
                : [];
            } catch (_) {
              // ignore malformed JSON
            }
            if (skippedIds.length) {
              btns.push(
                '<span style="color:#996800;font-size:12px;" title="' +
                  skippedIds.length +
                  " existing post" +
                  (skippedIds.length > 1 ? "s were" : " was") +
                  " found with a matching title. Enable &ldquo;Overwrite existing content&rdquo; and re-import to update " +
                  (skippedIds.length > 1 ? "them" : "it") +
                  '.">⚠ 0 created — existing post' +
                  (skippedIds.length > 1 ? "s" : "") +
                  " found (enable Overwrite to update)</span>",
              );
            }
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

      Promise.all([
        this._loadCourses(),
        Api.getJobSlides(jobId).catch(() => ({ slides: [] })),
      ]).then(([courses, slideData]) => {
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

        const slides = slideData.slides || [];

        // Build slide map HTML (only when slides are available).
        let slideMapHtml = "";
        if (slides.length) {
          const typeOpts = [
            { value: "", label: "— auto —" },
            { value: "cover", label: "Cover" },
            { value: "heading", label: "Heading / Topic" },
            { value: "body", label: "Content" },
            { value: "hidden", label: "Hidden" },
          ];
          const typeLabel = {
            cover: "Cover",
            heading: "Heading",
            body: "Content",
            hidden: "Hidden",
            section: "Section",
          };
          const rows = slides
            .map((s) => {
              const detected = typeLabel[s.slide_type] || s.slide_type || "—";
              const badge =
                s.slide_type === "cover"
                  ? "background:#7e56c2;color:#fff;"
                  : s.slide_type === "heading"
                    ? "background:#2271b1;color:#fff;"
                    : s.slide_type === "hidden"
                      ? "background:#777;color:#fff;"
                      : "background:#e8f0fe;color:#1a56db;";
              const selectOpts = typeOpts
                .map((o) => {
                  const sel =
                    o.value && o.value === s.override ? " selected" : "";
                  return (
                    '<option value="' +
                    o.value +
                    '"' +
                    sel +
                    ">" +
                    o.label +
                    "</option>"
                  );
                })
                .join("");
              return (
                "<tr>" +
                '<td style="padding:4px 8px 4px 0;width:36px;text-align:right;color:#888;font-size:12px;">' +
                s.slide_number +
                "</td>" +
                '<td style="padding:4px 8px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' +
                this._esc(s.title || "—") +
                "</td>" +
                '<td style="padding:4px 8px;color:#888;font-size:12px;">' +
                this._esc(s.layout_name || "—") +
                "</td>" +
                '<td style="padding:4px 8px;">' +
                '<span style="display:inline-block;padding:1px 7px;border-radius:3px;font-size:11px;' +
                badge +
                '">' +
                this._esc(detected) +
                "</span></td>" +
                '<td style="padding:4px 0;">' +
                '<select data-slide-override="' +
                s.slide_number +
                '" style="font-size:12px;">' +
                selectOpts +
                "</select>" +
                "</td>" +
                "</tr>"
              );
            })
            .join("");

          slideMapHtml =
            '<details style="margin-top:16px;">' +
            '<summary style="cursor:pointer;font-weight:600;font-size:13px;margin-bottom:8px;">Slide Map (' +
            slides.length +
            " slides)</summary>" +
            '<div style="max-height:320px;overflow-y:auto;margin-top:8px;">' +
            '<table style="border-collapse:collapse;width:100%;font-size:13px;" id="cbf-si-slidemap-' +
            jobId +
            '">' +
            "<thead><tr>" +
            '<th style="text-align:right;padding:4px 8px 4px 0;width:36px;color:#888;">#</th>' +
            '<th style="text-align:left;padding:4px 8px;">Title</th>' +
            '<th style="text-align:left;padding:4px 8px;">Layout</th>' +
            '<th style="text-align:left;padding:4px 8px;">Detected type</th>' +
            '<th style="text-align:left;padding:4px 0;">Override</th>' +
            "</tr></thead>" +
            "<tbody>" +
            rows +
            "</tbody>" +
            "</table>" +
            "</div>" +
            "</details>";
        }

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
          '" style="min-width:320px;max-width:500px;" placeholder="Lesson title (required)">' +
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
          slideMapHtml +
          '<p style="margin-top:16px;margin-bottom:6px;">' +
          "<label>" +
          '<input type="checkbox" id="cbf-si-overwrite-' +
          jobId +
          '" style="margin-right:6px;">' +
          "<strong>Overwrite existing content</strong> — update posts that already share this title rather than skipping them" +
          "</label>" +
          "</p>" +
          '<p style="margin-top:10px;margin-bottom:0;">' +
          '<button class="button button-primary" data-action="do-import" data-id="' +
          jobId +
          '">Import into LearnDash</button>' +
          '<button class="button" data-action="show-preview" data-id="' +
          jobId +
          '" style="margin-left:8px;">Preview content…</button>' +
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
        } else if (action === "show-preview") {
          this._doPreview(id);
        } else if (action === "back-to-config") {
          this._restoreConfigPanel(id);
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
      // Read config from the panel (shared with _doPreview via _readConfigFromPanel).
      const config = this._readConfigFromPanel(id);
      const { mode, course_id: courseId, post_title: postTitle } = config;

      // Validate: title is required (P3.5).
      if (!postTitle) {
        const titleEl = document.getElementById("cbf-si-title-" + id);
        window.alert("Please enter a lesson title before importing.");
        if (titleEl) {
          titleEl.focus();
        }
        return;
      }

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

      Api.triggerImport(id, config)
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

    // ── Preview panel (Phase 4) ────────────────────────────────────────────

    /**
     * Collect the current slide map overrides and other config from the panel.
     *
     * @param {number} id  Job ID.
     * @returns {{ mode, course_id, post_title, overwrite, slide_overrides }}
     */
    _readConfigFromPanel(id) {
      const titleEl = document.getElementById("cbf-si-title-" + id);
      const modeEl = document.querySelector(
        'input[name="cbf-si-mode-' + id + '"]:checked',
      );
      const courseEl = document.getElementById("cbf-si-course-" + id);
      const overwriteEl = document.getElementById("cbf-si-overwrite-" + id);
      const slideMapEl = document.getElementById("cbf-si-slidemap-" + id);

      const slideOverrides = {};
      if (slideMapEl) {
        slideMapEl.querySelectorAll("[data-slide-override]").forEach((sel) => {
          const num = parseInt(sel.dataset.slideOverride, 10);
          const val = sel.value;
          if (num > 0 && val) {
            slideOverrides[num] = val;
          }
        });
      }

      const config = {
        mode: modeEl ? modeEl.value : "lesson-only",
        course_id: courseEl ? parseInt(courseEl.value, 10) : 0,
        post_title: titleEl ? titleEl.value.trim() : "",
        overwrite: overwriteEl ? overwriteEl.checked : false,
      };
      if (Object.keys(slideOverrides).length) {
        config.slide_overrides = slideOverrides;
      }
      return config;
    },

    /**
     * Fetch the rendered block HTML preview for a job and display it in the
     * config panel row, replacing the config form.  POSTs the current slide-map
     * config so the preview reflects the user's current selections.  Saves the
     * config form's current innerHTML so it can be restored via "← Back to Configure".
     *
     * @param {number} id  Job ID.
     */
    _doPreview(id) {
      const panel = document.getElementById("cbf-si-config-panel-" + id);
      if (!panel) {
        return;
      }
      const cell = panel.querySelector("td");
      if (!cell) {
        return;
      }

      // Collect the current UI config BEFORE clearing the panel DOM so that
      // slide-map <select> elements are still accessible when we read them.
      const config = this._readConfigFromPanel(id);

      // Cache the current config panel HTML so we can restore it.
      this._configPanelCache = this._configPanelCache || {};
      this._configPanelCache[id] = cell.innerHTML;

      cell.innerHTML =
        '<em style="color:#888;font-size:13px;">Loading preview…</em>';

      Api.getJobPreview(id, config)
        .then((data) => {
          const lessonHtml = (data.lesson_html || "").trim();
          const topics = Array.isArray(data.topics) ? data.topics : [];

          const hasContent =
            lessonHtml.length > 0 ||
            topics.some((t) => (t.html || "").trim().length > 0);

          let previewHtml = "";

          if (!hasContent) {
            previewHtml =
              '<p style="color:#996800;background:#fff8e1;padding:10px 14px;border-left:4px solid #f0b849;margin:0 0 12px;">' +
              "⚠ No content was generated from the current config. Try a different mode or adjust the slide map." +
              "</p>";
          } else if (topics.length > 0) {
            // lesson-with-topics mode.
            const topicsHtml = topics
              .map(
                (t, i) =>
                  "<details" +
                  (i === 0 ? " open" : "") +
                  ' style="margin-bottom:8px;border:1px solid #ddd;border-radius:4px;">' +
                  '<summary style="padding:8px 12px;cursor:pointer;font-weight:600;font-size:13px;background:#f9f9f9;border-radius:4px;">' +
                  this._esc(t.title || "Topic " + (i + 1)) +
                  "</summary>" +
                  '<div class="cbf-si-preview-content" style="padding:12px 16px;max-height:360px;overflow-y:auto;font-size:13px;background:#fff;">' +
                  '<div class="cbf-si-preview-inner">' +
                  (t.html || "<em style='color:#888'>No content</em>") +
                  "</div>" +
                  "</div>" +
                  "</details>",
              )
              .join("");
            previewHtml =
              '<p style="font-size:12px;color:#888;margin:0 0 8px;">' +
              "Lesson-with-topics — " +
              topics.length +
              " topic" +
              (topics.length !== 1 ? "s" : "") +
              "</p>" +
              topicsHtml;
          } else {
            // lesson-only mode.
            previewHtml =
              '<div class="cbf-si-preview-content" style="max-height:480px;overflow-y:auto;padding:12px 16px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:#fff;">' +
              '<div class="cbf-si-preview-inner">' +
              (lessonHtml || "<em style='color:#888'>No content</em>") +
              "</div>" +
              "</div>";
          }

          cell.innerHTML =
            '<strong style="font-size:13px;">Content Preview</strong>' +
            '<p style="font-size:12px;color:#888;margin:4px 0 12px;">Rendered block HTML for the current configuration. Styling may differ from the live site.</p>' +
            previewHtml +
            '<p style="margin-top:12px;margin-bottom:0;">' +
            '<button class="button button-primary" data-action="do-import" data-id="' +
            id +
            '">Import into LearnDash</button>' +
            '<button class="button" data-action="back-to-config" data-id="' +
            id +
            '" style="margin-left:8px;">← Back to Configure</button>' +
            "</p>";
        })
        .catch((err) => {
          const msg = err.message || "";
          if (msg.startsWith("RETRY:")) {
            const delay = parseInt(msg.slice(6), 10) || 3;
            cell.innerHTML =
              '<em style="color:#888;font-size:13px;">Preview is generating… refreshing in ' +
              delay +
              "s</em>";
            setTimeout(() => this._doPreview(id), delay * 1000);
          } else {
            cell.innerHTML =
              '<p style="color:#d63638;font-size:13px;">⚠ Preview unavailable: ' +
              this._esc(msg) +
              "</p>" +
              '<p style="margin-top:8px;margin-bottom:0;">' +
              '<button class="button" data-action="back-to-config" data-id="' +
              id +
              '">← Back to Configure</button>' +
              "</p>";
          }
        });
    },

    /**
     * Restore the config panel to its previous state after viewing the preview.
     *
     * @param {number} id  Job ID.
     */
    _restoreConfigPanel(id) {
      const panel = document.getElementById("cbf-si-config-panel-" + id);
      const cell = panel && panel.querySelector("td");
      if (!cell) {
        return;
      }
      const cached = this._configPanelCache && this._configPanelCache[id];
      if (cached) {
        cell.innerHTML = cached;
      }
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
