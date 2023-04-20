
/**
 * LearnDash Exam Javascript
 * @since 4.0.0
 */
const LearnDash_Exam = {
	exam_el: null,

	exam_section_results: null,
	exam_section_header: null,
	exam_section_questions: null,
	exam_section_footer: null,

	exam_list_questions: null,
	exam_progress: null,

	exam_button_start: null,
	exam_button_next: null,
	exam_button_submit: null,
	exam_button_results: null,

	exam_current_question_idx: 0,

	/**
	 * Main init for Exam functionality.
	 * @param {*} element
	 */
	init(element) {
		if ("undefined" === typeof element) {
			return null;
		}

		if (!element.classList.contains("ld-exam-content")) {
			return null;
		}

		this.exam_el = element;

		this.exam_section_results = this.exam_el.getElementsByClassName(
			"ld-exam-result-message"
		)[0];
		if ("undefined" === typeof this.exam_section_results) {
			console.debug("this.exam_section_results is undefined");
			return null;
		}

		this.exam_section_header =
			this.exam_el.getElementsByClassName("ld-exam-header")[0];
		if ("undefined" === typeof this.exam_section_header) {
			console.debug("this.exam_section_header is undefined");
			return null;
		}

		this.exam_section_questions =
			this.exam_el.getElementsByClassName("ld-exam-questions")[0];
		if ("undefined" === typeof this.exam_section_questions) {
			console.debug("this.exam_section_questions is undefined");
			return null;
		}

		this.exam_section_footer =
			this.exam_el.getElementsByClassName("ld-exam-footer")[0];
		if ("undefined" === typeof this.exam_section_footer) {
			console.debug("this.exam_section_footer is undefined");
			return null;
		}

		this.exam_list_questions = this.exam_section_questions.querySelectorAll(
			"li.ld-exam-question"
		);
		if ("undefined" === typeof this.exam_list_questions) {
			console.debug("this.exam_list_questions collection is undefined");
			return null;
		}
		if (this.exam_list_questions.length < 1) {
			console.log(
				"DEBUG: this.exam_list_questions collection is empty [%o]",
				this.exam_list_questions.length
			);
			return null;
		}

		this.exam_progress =
			this.exam_el.getElementsByClassName("ld-exam-progress")[0];

		this.exam_button_start = this.exam_el.querySelectorAll(
			'button[class="ld-exam-button-start"]'
		)[0];
		if ("undefined" === typeof this.exam_button_start) {
			console.debug("this.exam_button_start is undefined");
			return null;
		}

		this.exam_button_next = this.exam_el.querySelectorAll(
			'button[class="ld-exam-button-next"]'
		)[0];
		if ("undefined" === typeof this.exam_button_next) {
			console.debug("this.exam_button_next) is undefined");
			return null;
		}

		this.exam_button_results = this.exam_el.querySelectorAll(
			'button[class="ld-exam-button-results"]'
		)[0];
		if ("undefined" === typeof this.exam_button_results) {
			console.debug("this.exam_button_results is undefined");
			return null;
		}

		this.exam_button_submit = this.exam_el.querySelectorAll(
			'button[class="ld-exam-button-submit"]'
		)[0];
		if ("undefined" === typeof this.exam_button_submit) {
			console.debug("this.exam_button_submit is undefined");
			return null;
		}

		if (this.is_exam_graded()) {
			this.init_graded();
		} else {
			this.init_started();
		}
	},
	/**
	 * Init Exam at graded state.
	 */
	init_graded() {
		this.exam_show_el(this.exam_section_questions, false);
		this.exam_show_el(this.exam_section_footer, false);
		this.exam_show_el(this.exam_button_start, false);
		this.exam_show_el(this.exam_progress, false);

		this.exam_button_results.addEventListener(
			"click",
			(event) => {
				this.button_click_results(event);
			},
			false
		);
	},
	/**
	 * Init Exam at started state.
	 */
	init_started() {
		this.exam_show_el(this.exam_section_header, true);
		this.exam_show_el(this.exam_progress, false);

		this.exam_show_el(this.exam_section_questions, false);
		this.exam_show_el(this.exam_section_footer, false);

		this.exam_show_el(this.exam_button_results, false);
		this.exam_show_el(this.exam_button_next, false);
		this.exam_show_el(this.exam_button_submit, false);
		this.exam_show_el(this.exam_button_start, true);

		this.exam_button_start.addEventListener(
			"click",
			(event) => {
				this.button_click_start(event);
			},
			false
		);
		this.exam_button_next.addEventListener(
			"click",
			(event) => {
				this.button_click_next(event);
			},
			false
		);
	},
	/**
	 * Start button click handler
	 * @param {Event} event
	 */
	button_click_start(event) {
		event.preventDefault();

		this.set_form_start_time();

		// Hide the start button.
		this.exam_show_el(this.exam_button_start, false);

		// Show the progress section
		if (this.exam_list_questions.length > 0) {
			this.exam_show_el(this.exam_progress, true);
		}

		this.exam_current_question_idx = 0;

		this.exam_question_show_by_idx(this.exam_current_question_idx);

		this.exam_show_el(this.exam_section_questions, true);
		this.exam_show_el(this.exam_section_footer, true);

		return false;
	},
	/**
	 * Next button click handler
	 * @param {Event} event
	 */
	button_click_next(event) {
		event.preventDefault();

		this.exam_current_question_idx += 1;

		this.exam_question_show_by_idx(this.exam_current_question_idx);

		return false;
	},
	/**
	 * Results button click handler
	 * @param {Event} event
	 */
	button_click_results(event) {
		event.preventDefault();

		if (this.exam_list_questions.length > 0) {
			const this_el = this; // Because 'this' is not available within in the callback function.
			Array.from(this.exam_list_questions).forEach(function (
				question_element,
				index,
				array
			) {
				this_el.exam_show_el(question_element, true);
			});
		}
		if ("none" == this.exam_section_questions.style.display) {
			this.exam_show_el(this.exam_section_questions, true);
		} else {
			this.exam_show_el(this.exam_section_questions, false);
		}

		return false;
	},
	/**
	 * Show single Question by index.
	 * @param {int} question_idx Question index to show
	 */
	exam_question_show_by_idx(question_idx) {
		if (this.exam_list_questions.length > 0) {
			const this_el = this; // Because 'this' is not available within in the callback function.
			Array.from(this.exam_list_questions).forEach(function (
				question_element,
				index,
				array
			) {
				if (index === question_idx) {
					this_el.exam_show_el(question_element, true);
				} else {
					this_el.exam_show_el(question_element, false);
				}
			});

			const question_idx_local = question_idx + 1;

			if (question_idx_local < this.exam_list_questions.length) {
				this.exam_show_el(this.exam_button_next, true);
				this.exam_show_el(this.exam_button_submit, false);
			} else {
				this.exam_show_el(this.exam_button_next, false);
				this.exam_show_el(this.exam_button_submit, true);
			}
			this.exam_update_progress();
		}
	},
	exam_update_progress() {
		if ("undefined" !== typeof this.exam_progress) {
			let question_idx_local = this.exam_current_question_idx;

			const exam_progress_text_current =
				this.exam_progress.getElementsByClassName(
					"ld-exam-progress-text-current"
				)[0];
			if ("undefined" !== typeof exam_progress_text_current) {
				exam_progress_text_current.innerHTML = question_idx_local+1;
			}
			const exam_progress_text_total =
				this.exam_progress.getElementsByClassName(
					"ld-exam-progress-text-total"
				)[0];
			if ("undefined" !== typeof exam_progress_text_total) {
				exam_progress_text_total.innerHTML = this.exam_list_questions.length;
			}
			const exam_progress_text_percentage =
				this.exam_progress.getElementsByClassName(
					"ld-exam-progress-text-percentage"
				)[0];
			if (
				this.exam_list_questions.length > 0 &&
				"undefined" !== typeof exam_progress_text_percentage
			) {
				const progress_percent = (
					(question_idx_local / this.exam_list_questions.length) *
					100
				).toFixed(0);
				exam_progress_text_percentage.innerHTML = progress_percent;

				const exam_progress_bar_fill =
					this.exam_progress.getElementsByClassName(
						"ld-exam-progress-bar-fill"
					)[0];
				if ("undefined" !== typeof exam_progress_bar_fill) {
					exam_progress_bar_fill.style.width = progress_percent + "%";
				}
			}
		}
	},
	/**
	 * Utility function to check if the exam is graded.
	 * @returns
	 */
	is_exam_graded() {
		if (this.exam_el.classList.contains("ld-exam-graded")) {
			return true;
		}
		return false;
	},
	/**
	 * Utility function to show/hide an element.
	 * @param {el} element
	 * @param {boolean} show - true to show, false to hide
	 */
	exam_show_el(el, show) {
		if ("undefined" === typeof el) {
			return null;
		}

		if (true === show) {
			el.style.display = "block";
		} else {
			el.style.display = "none";
		}
	},
	set_form_start_time() {
		let start_time = +new Date();
		const form_started = document.getElementById("ld-form-exam-started");
		if ("undefined" !== typeof form_started) {
			form_started.value = start_time;
		}
	},
};

const learndash_exams = [];
Array.from(document.getElementsByClassName("ld-exam-content")).forEach(
	function (element, index, array) {
		learndash_exams[index] = Object.create(LearnDash_Exam);
		learndash_exams[index].init(element);
	}
);
