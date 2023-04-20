/* eslint-disable vars-on-top, camelcase*/
(function ($) {
	/**
	 * @param  element
	 * @param  options
	 * @memberOf $
	 */
	$.wpProQuizFront = function (element, options) {
		const $e = $(element);
		const config = options;
		let result_settings = {};
		const plugin = this;
		let results = new Object();
		let catResults = new Object();
		let startTime = 0;
		let currentQuestion = null;
		let quizSolved = [];
		let lastButtonValue = '';
		let inViewQuestions = false;
		let currentPage = 1;
		let timespent = 0;
		let sending_timer = null;

		let cookie_name = '';
		let cookieSendTimer = false;
		let cookie_value = '';
		let cookieExpireDate = 0;
		let cookieSending = false;
		let quiz_resume_data = {};

		if (config.ld_script_debug == true) {
			console.log('config[%o]', config);
		}

		const bitOptions = {
			randomAnswer: 0,
			randomQuestion: 0,
			disabledAnswerMark: 0,
			checkBeforeStart: 0,
			preview: 0,
			cors: 0,
			isAddAutomatic: 0,
			quizSummeryHide: 0,
			skipButton: 0,
			reviewQustion: 0,
			autoStart: 0,
			forcingQuestionSolve: 0,
			hideQuestionPositionOverview: 0,
			formActivated: 0,
			maxShowQuestion: 0,
			sortCategories: 0,
		};

		const quizStatus = {
			isQuizStart: 0,
			isLocked: 0,
			loadLock: 0,
			isPrerequisite: 0,
			isUserStartLocked: 0,
		};

		const globalNames = {
			check: 'input[name="check"]',
			next: 'input[name="next"]',
			tip: 'input[name="tip"]',
			questionList: '.wpProQuiz_questionList',
			skip: 'input[name="skip"]',
			singlePageLeft: 'input[name="wpProQuiz_pageLeft"]',
			singlePageRight: 'input[name="wpProQuiz_pageRight"]',
		};

		let globalElements = {
			self: $e,
			back: $e.find('input[name="back"]'),
			next: $e.find(globalNames.next),
			quiz: $e.find('.wpProQuiz_quiz'),
			questionList: $e.find('.wpProQuiz_list'),
			results: $e.find('.wpProQuiz_results'),
			sending: $e.find('.wpProQuiz_sending'),
			quizStartPage: $e.find('.wpProQuiz_text'),
			timelimit: $e.find('.wpProQuiz_time_limit'),
			toplistShowInButton: $e.find('.wpProQuiz_toplistShowInButton'),
			listItems: $(),
		};

		const toplistData = {
			token: '',
			isUser: 0,
		};

		const formPosConst = {
			START: 0,
			END: 1,
		};

		/**
		 * @memberOf timelimit
		 */
		const timelimit = {
			counter: config.timelimit,
			intervalId: 0,
			instance: {},
			// set cookie for different users and different quizzes
			timer_cookie:
				'ldadv-time-limit-' + config.user_id + '-' + config.quizId,

			stop() {
				if (this.counter) {
					$.removeCookie(this.timer_cookie);
					window.clearInterval(this.intervalId);
					globalElements.timelimit.hide();
				}
			},
			start() {
				if (!this.counter) {
					return;
				}
				this.timer_cookie;
				$.cookie.raw = true;

				const full = this.counter * 1000;
				let tick = jQuery.cookie(this.timer_cookie);
				let limit = 0;

				if (tick) {
					limit = tick;
				} else {
					limit = this.counter;
				}

				const resume_data = learndash_prepare_quiz_resume_data(config);
				if (resume_data !== false) {
					if (resume_data[this.timer_cookie]) {
						limit = resume_data[this.timer_cookie];
					}
				}

				const x = limit * 1000;

				const $timeText = globalElements.timelimit
					.find('span')
					.text(plugin.methode.parseTime(limit));
				const $timeDiv = globalElements.timelimit.find(
					'.wpProQuiz_progress'
				);

				globalElements.timelimit.show();

				const beforeTime = +new Date();

				this.intervalId = window.setInterval(
					function () {
						const diff = +new Date() - beforeTime;
						const remainingTime = x - diff;

						if (diff >= 500) {
							tick = remainingTime / 1000;
							$timeText.text(
								plugin.methode.parseTime(Math.ceil(tick))
							);
							$.cookie(this.timer_cookie, tick);
						}

						$timeDiv.css(
							'width',
							(remainingTime / full) * 100 + '%'
						);

						if (remainingTime <= 0) {
							this.stop();
							plugin.methode.finishQuiz(true);
						}
					}.bind(this),
					16
				);

				if (config.quiz_resume_enabled === '1' && !cookieSendTimer) {
					plugin.methode.startCookieSendTimer();
				}
			},
		};

		/**
		 * @memberOf reviewBox
		 */
		const reviewBox = new (function () {
			let $contain = [],
				$cursor = [],
				$list = [],
				$items = [];
			let x = 0,
				offset = 0,
				diff = 0,
				top = 0,
				max = 0;
			const itemsStatus = [];

			this.init = function () {
				$contain = $e.find('.wpProQuiz_reviewQuestion');
				$cursor = $contain.find('div');
				$list = $contain.find('ol');
				$items = $list.children();

				if (config.mode != 2) {
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-correct'
					).hide();
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-incorrect'
					).hide();
				}

				this.reset();

				const resume_data = learndash_prepare_quiz_resume_data(config);
				if (config.ld_script_debug == true) {
					console.log('resume_data[%o]', resume_data);
				}

				if (typeof resume_data.reviewBox !== 'undefined') {
					jQuery(resume_data.reviewBox).each(function (idx, item) {
						if (typeof item === 'object') {
							const item_tmp = item;
							item = {};
							if (typeof item_tmp.solved !== 'undefined') {
								item.solved = true;
							}

							if (typeof item_tmp.correct !== 'undefined') {
								item.correct = true;
							}

							if (typeof item_tmp.incorrect !== 'undefined') {
								item.incorrect = true;
							}

							if (typeof item_tmp.skip !== 'undefined') {
								item.skip = true;
							}

							if (typeof item_tmp.review !== 'undefined') {
								item.review = true;
							}
						}
						itemsStatus[idx] = item;
						setColor(idx);
					});
				}

				$cursor.on('mousedown', function (e) {
					e.preventDefault();
					e.stopPropagation();

					offset = e.pageY - $cursor.offset().top + top;

					$(document).on('mouseup.scrollEvent', endScroll);
					$(document).on('mousemove.scrollEvent', moveScroll);
				});

				$items.on('click', function (e) {
					plugin.methode.showQuestion($(this).index());
				});

				$e.on('questionSolved', function (e) {
					itemsStatus[e.values.index].solved = e.values.solved;
					setColor(e.values.index);
					updateItemsStatus();
				});

				$e.on('questionSolvedCorrect', function (e) {
					itemsStatus[e.values.index].correct = true;
					setColor(e.values.index);
					updateItemsStatus();
				});

				$e.on('questionSolvedIncorrect', function (e) {
					itemsStatus[e.values.index].incorrect = true;
					setColor(e.values.index);
					updateItemsStatus();
				});

				$e.on('changeQuestion', function (e) {
					// On Matrix sort questions we need to set the sort capture UL to full height.
					if (e.values.item[0] != 'undefined') {
						const questionItem = e.values.item[0];
						plugin.methode.setupMatrixSortHeights();
					}

					$items.removeClass('wpProQuiz_reviewQuestionTarget');
					//$items.removeClass('wpProQuiz_reviewQuestionSolved');
					//$items.removeClass('wpProQuiz_reviewQuestionReview');

					$items
						.eq(e.values.index)
						.addClass('wpProQuiz_reviewQuestionTarget');
					//updateItemsStatus();

					scroll(e.values.index);
				});

				$e.on('skipQuestion', function (e) {
					itemsStatus[e.values.index].skip =
						!itemsStatus[e.values.index].skip;
					setColor(e.values.index);
					updateItemsStatus();
				});

				$e.on('reviewQuestion', function (e) {
					itemsStatus[e.values.index].review =
						!itemsStatus[e.values.index].review;
					setColor(e.values.index);
					updateItemsStatus();
				});

				/*
				$contain.on('mousewheel DOMMouseScroll', function(e) {
					e.preventDefault();

					var ev = e.originalEvent;
					var w = ev.wheelDelta ? -ev.wheelDelta / 120 : ev.detail / 3;
					var plus = 20 * w;

					var x = top - $list.offset().top  + plus;

					if(x > max)
						x = max;

					if(x < 0)
						x = 0;

					var o = x / diff;

					$list.attr('style', 'margin-top: ' + (-x) + 'px !important');
					$cursor.css({top: o});

					return false;
				});
				*/
			};

			this.show = function (save) {
				if (bitOptions.reviewQustion) {
					$contain.parent().show();
				}

				$e.find('.wpProQuiz_reviewDiv .wpProQuiz_button2').show();

				if (save) {
					return;
				}

				$list.attr('style', 'margin-top: 0px !important');
				$cursor.css({ top: 0 });

				const h = $list.outerHeight();
				const c = $contain.height();
				x = c - $cursor.height();
				offset = 0;
				max = h - c;
				diff = max / x;

				//this.reset();

				if (h > 100) {
					$cursor.show();
				}

				top = $cursor.offset().top;
			};

			this.hide = function () {
				$contain.parent().hide();
			};

			this.toggle = function () {
				if (bitOptions.reviewQustion) {
					// The toggle is called after the quiz submit. So we want to hide the 'review' and 'answered' legend items.
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-current'
					).hide();
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-review'
					).hide();
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-answered'
					).hide();

					// And show the 'correct' and 'incorrect' indicators.
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-correct'
					).show();
					$(
						'.wpProQuiz_reviewLegend li.learndash-quiz-review-legend-item-incorrect'
					).show();

					$contain.parent().toggle();
					$items.removeClass('wpProQuiz_reviewQuestionTarget');
					$e.find('.wpProQuiz_reviewDiv .wpProQuiz_button2').hide();

					$list.attr('style', 'margin-top: 0px !important');
					$cursor.css({ top: 0 });

					const h = $list.outerHeight();
					const c = $contain.height();
					x = c - $cursor.height();
					offset = 0;
					max = h - c;
					diff = max / x;

					if (h > 100) {
						$cursor.show();
					}

					top = $cursor.offset().top;
				}
			};

			this.reset = function () {
				for (let i = 0, c = $items.length; i < c; i++) {
					itemsStatus[i] = {};
				}

				//$items.removeClass('wpProQuiz_reviewQuestionTarget').css('background-color', '');
				$items.removeClass('wpProQuiz_reviewQuestionTarget');
				$items.removeClass('wpProQuiz_reviewQuestionSolved');
				$items.removeClass('wpProQuiz_reviewQuestionReview');
			};

			function scroll(index) {
				/*
				var $item = $items.eq(index);
				var iTop = $item.offset().top;
				var cTop = $contain.offset().top;
				var calc = iTop - cTop;

				if((calc - 4) < 0 || (calc + 32) > 100) {
					var x = cTop - $items.eq(0).offset().top - (cTop - $list.offset().top)  + $item.position().top;

					if(x > max)
						x = max;

					var o = x / diff;

					$list.attr('style', 'margin-top: ' + (-x) + 'px !important');
					$cursor.css({top: o});
				}
				*/
			}

			function setColor(index) {
				const itemStatus = itemsStatus[index];

				$items
					.eq(index)
					.removeClass(
						'wpProQuiz_reviewQuestionSolved wpProQuiz_reviewQuestionReview wpProQuiz_reviewQuestionSkip'
					);

				if (0 === Object.keys(itemStatus).length) {
					return;
				}

				let css_class = '';

				if (itemStatus.correct) {
					css_class = 'wpProQuiz_reviewQuestionSolvedCorrect';
				} else if (itemStatus.incorrect) {
					css_class = 'wpProQuiz_reviewQuestionSolvedIncorrect';
				} else if (
					itemStatus.solved === true ||
					itemStatus.solved === false
				) {
					css_class = 'wpProQuiz_reviewQuestionSolved';
				} else if (itemStatus.review) {
					css_class = 'wpProQuiz_reviewQuestionReview';
				} else if (itemStatus.skip) {
					css_class = 'wpProQuiz_reviewQuestionSkip';
				}

				if (css_class != '') {
					$items.eq(index).addClass(css_class);
				}
			}

			function moveScroll(e) {
				e.preventDefault();

				let o = e.pageY - offset;

				if (o < 0) {
					o = 0;
				}

				if (o > x) {
					o = x;
				}

				const v = diff * o;

				$list.attr('style', 'margin-top: ' + -v + 'px !important');

				$cursor.css({ top: o });
			}

			function endScroll(e) {
				e.preventDefault();

				$(document).unbind('.scrollEvent');
			}

			function updateItemsStatus() {
				plugin.methode.saveMetaDataToCookie({ reviewBox: itemsStatus });
			}
		})();

		function QuestionTimer() {
			let questionStartTime = 0;
			let currentQuestionId = -1;

			let quizStartTimer = 0;
			let isQuizStart = false;

			this.questionStart = function (questionId) {
				if (currentQuestionId != -1) {
					this.questionStop();
				}

				currentQuestionId = questionId;
				questionStartTime = +new Date();
			};

			this.questionStop = function () {
				if (currentQuestionId == -1) {
					return;
				}

				results[currentQuestionId].time += Math.round(
					(new Date() - questionStartTime) / 1000
				);

				currentQuestionId = -1;
			};

			this.startQuiz = function () {
				if (isQuizStart) {
					this.stopQuiz();
				}

				quizStartTimer = +new Date();

				// Use server start time when quiz is resumed.
				if (
					config.quiz_resume_enabled === '1' &&
					typeof config.quiz_resume_quiz_started === 'string'
				) {
					if ('0' !== config.quiz_resume_quiz_started) {
						quizStartTimer = parseInt(
							config.quiz_resume_quiz_started,
							10
						);
					}
				}

				isQuizStart = true;
			};
			this.getQuizStart = function () {
				if (!isQuizStart) {
					return;
				}
				return quizStartTimer;
			};
			this.stopQuiz = function () {
				if (!isQuizStart) {
					return;
				}

				quizEndTimer = +new Date();
				results.comp.quizTime += Math.round(
					(quizEndTimer - quizStartTimer) / 1000
				);

				results.comp.quizEndTimestamp = quizEndTimer;
				results.comp.quizStartTimestamp = quizStartTimer;

				isQuizStart = false;
			};

			this.init = function () {};
		}

		const questionTimer = new QuestionTimer();

		const readResponses = function (
			name,
			data,
			$question,
			$questionList,
			lockResponse
		) {
			if (lockResponse == undefined) {
				lockResponse = true;
			}
			let response = {};
			const func = {
				singleMulti() {
					const input = $questionList.find(
						'.wpProQuiz_questionInput'
					);
					if (lockResponse == true) {
						$questionList
							.find('.wpProQuiz_questionInput')
							.attr('disabled', 'disabled');
					}
					//$questionList.children().each(function(i) {
					// Changed in v2.3 from the above. children() was pickup up some other random HTML elements within the UL like <p></p>.
					// Now we are targetting specifically the .wpProQuiz_questionListItem HTML elements.
					jQuery('.wpProQuiz_questionListItem', $questionList).each(
						function (i) {
							const $item = $(this);
							const index = $item.attr('data-pos');
							if (typeof index !== 'undefined') {
								response[index] = input.eq(i).is(':checked');
							}
						}
					);
				},

				sort_answer() {
					const $items = $questionList.children(
						'li.wpProQuiz_questionListItem'
					);
					let idx = 0;
					$items.each(function (item_idx, item) {
						const data_pos = jQuery(item).data('pos');
						if (typeof data_pos !== 'undefined') {
							response[idx] = data_pos;
							idx++;
						}
					});

					if (lockResponse == true) {
						$questionList.sortable();
						$questionList.sortable('disable');
					}
				},

				matrix_sort_answer() {
					const $items = $questionList.children();
					const matrix = new Array();
					statistcAnswerData = { 0: -1 };

					/*
					// Old broken logic from LD 3.5.0/3.5.1 Replaced with the original code below.
					$items.each( function( index ) {
						var $this = $( this );
						var $stringUl = $this.find( '.wpProQuiz_maxtrixSortCriterion' );
						var $stringItem = $stringUl.children();

						if( $stringItem.length ) {
							var idx = $stringItem.attr('data-pos');
							statistcAnswerData[index] = $items[idx].getAttribute('data-pos');
						} else {
							statistcAnswerData[index] = '';
						}
					} );
					*/

					$items.each(function () {
						const $this = $(this);
						const id = $this.attr('data-pos');
						const $stringUl = $this.find(
							'.wpProQuiz_maxtrixSortCriterion'
						);
						const $stringItem = $stringUl.children();

						if ($stringItem.length) {
							statistcAnswerData[$stringItem.attr('data-pos')] =
								id;
						}

						response = statistcAnswerData;
					});

					response = statistcAnswerData;

					if (lockResponse == true) {
						$question
							.find(
								'.wpProQuiz_sortStringList, .wpProQuiz_maxtrixSortCriterion'
							)
							.sortable();
						$question
							.find(
								'.wpProQuiz_sortStringList, .wpProQuiz_maxtrixSortCriterion'
							)
							.sortable('disable');
					}
				},

				free_answer() {
					const $li = $questionList.children();
					const value = $li.find('.wpProQuiz_questionInput').val();
					if (lockResponse == true) {
						$li.find('.wpProQuiz_questionInput').attr(
							'disabled',
							'disabled'
						);
					}
					response = value;
				},

				cloze_answer() {
					response = {};
					$questionList
						.find('.wpProQuiz_cloze')
						.each(function (i, v) {
							const $this = $(this);
							const cloze = $this.children();
							const input = cloze.eq(0);
							const span = cloze.eq(1);
							const inputText = plugin.methode.cleanupCurlyQuotes(
								input.val()
							);
							response[i] = inputText;
							if (lockResponse == true) {
								input.attr('disabled', 'disabled');
							}
						});
				},

				assessment_answer() {
					correct = true;
					const $input = $questionList.find(
						'.wpProQuiz_questionInput'
					);
					if (lockResponse == true) {
						$questionList
							.find('.wpProQuiz_questionInput')
							.attr('disabled', 'disabled');
					}
					let val = 0;

					$input.filter(':checked').each(function () {
						val += parseInt($(this).val());
					});

					response = val;
				},

				essay() {
					const question_id = $question
						.find('ul.wpProQuiz_questionList')
						.data('question_id');
					if (lockResponse == true) {
						$questionList
							.find('.wpProQuiz_questionEssay')
							.attr('disabled', 'disabled');
					}

					const essayText = $questionList
						.find('.wpProQuiz_questionEssay')
						.val();
					const essayFiles = $questionList
						.find('#uploadEssayFile_' + question_id)
						.val();

					if (typeof essayText !== 'undefined') {
						response = essayText;
					}

					if (typeof essayFiles !== 'undefined') {
						response = essayFiles;
					}
				},
			};

			func[name]();

			return { response };
		};

		// Called from the Cookie handler logic. If the Quiz is loaded and a cookie is present the values of the cookie are used
		// to set the Quiz elements here. Once the value is set we call the trigger 'questionSolved' to update the question overview panel.
		function setResponse(
			question_data,
			question_value,
			question,
			$questionList,
			lockQuestion
		) {
			var lockQuestion = lockQuestion || false;
			if (
				question_data.type == 'single' ||
				question_data.type == 'multiple'
			) {
				$questionList.children().each(function (i) {
					const $item = $(this);
					const index = $item.attr('data-pos');

					if (question_value[index] != undefined) {
						const index_value = question_value[index];
						if (index_value == true) {
							$('.wpProQuiz_questionInput', $item).prop(
								'checked',
								'checked'
							);
							if (lockQuestion) {
								question
									.find('.wpProQuiz_questionInput')
									.attr('disabled', 'disabled');
								question
									.find('.wpProQuiz_questionInput')
									.css('pointer-events', 'none');

								navigationElementslockQuestion(question);
							}
							$e.trigger({
								type: 'questionSolved',
								values: {
									item: question,
									index: question.index(),
									solved: true,
								},
							});
						}
					}
				});
			} else if (question_data.type == 'free_answer') {
				question_value = learndash_decodeHTML(question_value);

				$questionList.children().each(function (i) {
					const $item = $(this);
					$('.wpProQuiz_questionInput', this).val(question_value);
					if (lockQuestion) {
						$('.wpProQuiz_questionInput', this).attr(
							'disabled',
							'disabled'
						);
						$('.wpProQuiz_questionInput', this).css(
							'pointer-events',
							'none'
						);

						navigationElementslockQuestion(question);
					}
				});
				$e.trigger({
					type: 'questionSolved',
					values: {
						item: question,
						index: question.index(),
						solved: true,
					},
				});
			} else if (question_data.type == 'sort_answer') {
				jQuery.each(question_value, function (pos, key) {
					const this_li = $(
						'li.wpProQuiz_questionListItem[data-pos="' + key + '"]',
						$questionList
					);
					const this_li_inner = $('div.wpProQuiz_sortable', this_li);
					const this_li_inner_value = $(this_li_inner).text();

					jQuery($questionList).append(this_li);
					if (lockQuestion) {
						jQuery($questionList).sortable();
						jQuery($questionList).sortable('disable');
						navigationElementslockQuestion(question);
					}
				});
				$e.trigger({
					type: 'questionSolved',
					values: {
						item: question,
						index: question.index(),
						solved: true,
					},
				});
			} else if (question_data.type == 'matrix_sort_answer') {
				jQuery.each(question_value, function (pos, key) {
					const question_response_item = $(
						'.wpProQuiz_matrixSortString .wpProQuiz_sortStringList li[data-pos="' +
							pos +
							'"]',
						question
					);
					const question_destination_outer_li = $(
						'li.wpProQuiz_questionListItem[data-pos="' +
							key +
							'"] ul.wpProQuiz_maxtrixSortCriterion',
						$questionList
					);

					jQuery(question_response_item).appendTo(
						question_destination_outer_li
					);
					if (lockQuestion) {
						jQuery(question_destination_outer_li).sortable();
						jQuery(question_destination_outer_li).sortable(
							'disable'
						);
						navigationElementslockQuestion(question);
					}
				});
				$e.trigger({
					type: 'questionSolved',
					values: {
						item: question,
						index: question.index(),
						solved: true,
					},
				});
			} else if (question_data.type == 'cloze_answer') {
				// Get the input fields within the questionList parent
				jQuery(
					'span.wpProQuiz_cloze input[type="text"]',
					$questionList
				).each(function (index) {
					if (typeof question_value[index] !== 'undefined') {
						$(this).val(
							learndash_decodeHTML(question_value[index])
						);
						if (lockQuestion) {
							$(this).attr('disabled', 'disabled');
							navigationElementslockQuestion(question);
						}
					}
				});
				$e.trigger({
					type: 'questionSolved',
					values: {
						item: question,
						index: question.index(),
						solved: true,
					},
				});
			} else if (question_data.type == 'assessment_answer') {
				$(
					'input.wpProQuiz_questionInput[value="' +
						question_value +
						'"]',
					$questionList
				).attr('checked', 'checked');
				if (lockQuestion) {
					$(
						'input.wpProQuiz_questionInput[value="' +
							question_value +
							'"]',
						$questionList
					).attr('disabled', 'disabled');
					navigationElementslockQuestion(question);
				}
				$e.trigger({
					type: 'questionSolved',
					values: {
						item: question,
						index: question.index(),
						solved: true,
					},
				});
			} else if (question_data.type == 'essay') {
				// The 'essay' value is generic. We need to figure out if this is an upload or inline essay.
				if (
					$questionList.find('#uploadEssayFile_' + question_data.id)
						.length
				) {
					const question_input = $questionList.find(
						'#uploadEssayFile_' + question_data.id
					);
					$(question_input).val(question_value);
					$('<p>' + basename(question_value) + '</p>').insertAfter(
						question_input
					);
					if (lockQuestion) {
						$questionList
							.find('form[name="uploadEssay"]')
							.css('pointer-events', 'none');
						navigationElementslockQuestion(question);
					}
					$e.trigger({
						type: 'questionSolved',
						values: {
							item: question,
							index: question.index(),
							solved: true,
						},
					});
				} else if (
					$questionList.find('.wpProQuiz_questionEssay').length
				) {
					$questionList
						.find('.wpProQuiz_questionEssay')
						.html(question_value);
					if (lockQuestion) {
						$questionList
							.find('.wpProQuiz_questionEssay')
							.attr('disabled', 'disabled');
						navigationElementslockQuestion(question);
					}
					$e.trigger({
						type: 'questionSolved',
						values: {
							item: question,
							index: question.index(),
							solved: true,
						},
					});
				}
			}
			if (lockQuestion) {
				plugin.methode.setCheckedStatusFromData(
					question_data,
					question,
					$questionList
				);
			}
		}

		/**
		 * Manage navigation elements after 'checked' question answers have been set from the cookie.
		 *
		 * @param {Object} question Dom Object
		 */
		function navigationElementslockQuestion(question) {
			question.find(globalNames.check).hide();
			question.find(globalNames.tip).hide();
			question.find(globalNames.skip).hide();
			question.find(globalNames.next).show();
			question.find(globalNames.next).attr('data-question-lock', true);
		}

		function basename(path) {
			if (path != undefined) {
				return path.split('/').reverse()[0];
			}
			return '';
		}

		const formClass = {
			funcs: {
				isEmpty(str) {
					str = str.trim();
					return !str || 0 === str.length;
				},
			},
			typeConst: {
				TEXT: 0,
				TEXTAREA: 1,
				NUMBER: 2,
				CHECKBOX: 3,
				EMAIL: 4,
				YES_NO: 5,
				DATE: 6,
				SELECT: 7,
				RADIO: 8,
			},
			checkForm() {
				let check = true;
				const that = this;
				$e.find(
					'.wpProQuiz_forms input, .wpProQuiz_forms textarea, .wpProQuiz_forms .wpProQuiz_formFields, .wpProQuiz_forms select'
				).each(function () {
					const $this = $(this);
					const isRequired = $this.data('required') == 1;
					const type = $this.data('type');
					let test = true;
					const value = $this.val().trim();

					switch (type) {
						case that.typeConst.TEXT:
						case that.typeConst.TEXTAREA:
						case that.typeConst.SELECT:
							if (isRequired) {
								test = !that.funcs.isEmpty(value);
							}

							break;
						case that.typeConst.NUMBER:
							if (isRequired || !that.funcs.isEmpty(value)) {
								test =
									!that.funcs.isEmpty(value) && !isNaN(value);
							}

							break;
						case that.typeConst.EMAIL:
							if (isRequired || !that.funcs.isEmpty(value)) {
								//test = !funcs.isEmpty(value) && new RegExp(/^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/)
								//		.test(value);

								// Use the same RegEx as the HTML5 email field. Per https://emailregex.com
								test =
									!that.funcs.isEmpty(value) &&
									new RegExp(
										/^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/
									).test(value);
							}

							break;
						case that.typeConst.CHECKBOX:
							if (isRequired) {
								test = $this.is(':checked');
							}

							break;
						case that.typeConst.YES_NO:
						case that.typeConst.RADIO:
							if (isRequired) {
								test =
									$this
										.find('input[type="radio"]:checked')
										.val() !== undefined;
							}

							break;
						case that.typeConst.DATE:
							var num = 0,
								co = 0;

							$this.find('select').each(function () {
								num++;
								co += that.funcs.isEmpty($(this).val()) ? 0 : 1;
							});

							if (isRequired || co > 0) {
								test = num == co;
							}

							break;
					}

					if (test) {
						$this.siblings('.wpProQuiz_invalidate').hide();
					} else {
						check = false;
						$this.siblings('.wpProQuiz_invalidate').show();
					}
				});

				return check;
			},
			getFormData() {
				const data = {};
				const that = this;
				$e.find(
					'.wpProQuiz_forms input, .wpProQuiz_forms textarea, .wpProQuiz_forms .wpProQuiz_formFields, .wpProQuiz_forms select'
				).each(function () {
					const $this = $(this);
					const id = $this.data('form_id');
					const type = $this.data('type');

					switch (type) {
						case that.typeConst.TEXT:
						case that.typeConst.TEXTAREA:
						case that.typeConst.SELECT:
						case that.typeConst.NUMBER:
						case that.typeConst.EMAIL:
							data[id] = $this.val();
							break;
						case that.typeConst.CHECKBOX:
							data[id] = $this.is(':checked') ? 1 : 0;
							break;
						case that.typeConst.YES_NO:
						case that.typeConst.RADIO:
							data[id] = $this
								.find('input[type="radio"]:checked')
								.val();
							break;
						case that.typeConst.DATE:
							data[id] = {
								day: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_day"]'
									)
									.val(),
								month: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_month"]'
									)
									.val(),
								year: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_year"]'
									)
									.val(),
							};
							break;
					}
				});

				return data;
			},
			saveFormDataToCookie() {
				const formData = {};
				const that = this;
				$e.find(
					'.wpProQuiz_forms input, .wpProQuiz_forms textarea, .wpProQuiz_forms .wpProQuiz_formFields, .wpProQuiz_forms select'
				).each(function () {
					const $this = $(this);
					const id = $this.data('form_id');
					// Don't save duplicated form fields.
					if (typeof id === 'undefined') {
						return;
					}
					const type = $this.data('type');

					formData.id = id;
					switch (type) {
						case that.typeConst.TEXT:
						case that.typeConst.TEXTAREA:
						case that.typeConst.SELECT:
						case that.typeConst.NUMBER:
						case that.typeConst.EMAIL:
							formData.value = $this.val();
							formData.type = type;
							break;
						case that.typeConst.CHECKBOX:
							formData.value = $this.is(':checked') ? 1 : 0;
							formData.type = type;
							break;
						case that.typeConst.YES_NO:
						case that.typeConst.RADIO:
							formData.value = $this
								.find('input[type="radio"]:checked')
								.val();
							formData.type = type;
							break;
						case that.typeConst.DATE:
							formData.value = {
								day: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_day"]'
									)
									.val(),
								month: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_month"]'
									)
									.val(),
								year: $this
									.find(
										'select[name="wpProQuiz_field_' +
											id +
											'_year"]'
									)
									.val(),
							};
							formData.type = type;
							break;
					}

					plugin.methode.CookieSaveResponse(
						'formData' + formData.id,
						formData.id,
						formData.type,
						formData.value
					);
				});
			},
			setFormDataFromServer() {
				if (config.quiz_resume_enabled !== '1') {
					return;
				}

				if (config.quiz_resume_data == undefined) {
					return;
				}

				const resume_data = learndash_prepare_quiz_resume_data(config);
				if (resume_data === false) {
					return;
				}

				const that = this;
				$e.find(
					'.wpProQuiz_forms input, .wpProQuiz_forms textarea, .wpProQuiz_forms .wpProQuiz_formFields, .wpProQuiz_forms select'
				).each(function () {
					const $this = $(this);
					const id = $this.data('form_id');
					const type = $this.data('type');
					const formId = 'formData' + id;
					if (resume_data[formId]) {
						if (resume_data[formId].type === type) {
							switch (type) {
								case that.typeConst.TEXT:
								case that.typeConst.TEXTAREA:
								case that.typeConst.SELECT:
								case that.typeConst.NUMBER:
								case that.typeConst.EMAIL:
									var form_value = resume_data[formId].value;
									form_value =
										learndash_decodeHTML(form_value);
									$this.val(form_value);
									break;
								case that.typeConst.CHECKBOX:
									if (resume_data[formId].value) {
										$this.attr('checked', true);
									}

									break;
								case that.typeConst.YES_NO:
								case that.typeConst.RADIO:
									if (resume_data[formId].value) {
										$this
											.find(
												'input[type="radio"][value="' +
													resume_data[formId].value +
													'"]'
											)
											.attr('checked', true);
									}

									break;
								case that.typeConst.DATE:
									$this
										.find(
											'select[name="wpProQuiz_field_' +
												id +
												'_day"]'
										)
										.val(resume_data[formId].value.day);
									$this
										.find(
											'select[name="wpProQuiz_field_' +
												id +
												'_month"]'
										)
										.val(resume_data[formId].value.month);
									$this
										.find(
											'select[name="wpProQuiz_field_' +
												id +
												'_year"]'
										)
										.val(resume_data[formId].value.year);

									break;
							}
						}
					}
				});
			},
		};

		const fetchAllAnswerData = function (resultData) {
			$e.find('.wpProQuiz_questionList').each(function () {
				const $this = $(this);
				const questionId = $this.data('question_id');
				const type = $this.data('type');
				const data = {};

				if (type == 'single' || type == 'multiple') {
					$this.find('.wpProQuiz_questionListItem').each(function () {
						data[$(this).attr('data-pos')] = +$(this)
							.find('.wpProQuiz_questionInput')
							.is(':checked');
					});
				} else if (type == 'free_answer') {
					data[0] = $this.find('.wpProQuiz_questionInput').val();
				} else if (type == 'sort_answer') {
					return true;
					//					$this.find('.wpProQuiz_questionListItem').each(function() {
					//						data[$(this).index()] = $(this).attr('data-pos');
					//					});
				} else if (type == 'matrix_sort_answer') {
					return true;
					//					$this.find('.wpProQuiz_questionListItem').each(function() {
					//						data[$(this).attr('data-pos')] = $(this).find('.wpProQuiz_answerCorrect').length;
					//					});
				} else if (type == 'cloze_answer') {
					let i = 0;
					$this.find('.wpProQuiz_cloze input').each(function () {
						data[i++] = $(this).val();
					});
				} else if (type == 'assessment_answer') {
					data[0] = '';

					$this
						.find('.wpProQuiz_questionInput:checked')
						.each(function () {
							data[$(this).data('index')] = $(this).val();
						});
				} else if (type == 'essay') {
					return;
				}

				resultData[questionId].data = data;
			});
		};

		plugin.methode = {
			/**
			 * @memberOf plugin.methode
			 */

			parseBitOptions() {
				if (config.bo) {
					bitOptions.randomAnswer = config.bo & (1 << 0);
					bitOptions.randomQuestion = config.bo & (1 << 1);
					bitOptions.disabledAnswerMark = config.bo & (1 << 2);
					bitOptions.checkBeforeStart = config.bo & (1 << 3);
					bitOptions.preview = config.bo & (1 << 4);
					bitOptions.isAddAutomatic = config.bo & (1 << 6);
					bitOptions.reviewQustion = config.bo & (1 << 7);
					bitOptions.quizSummeryHide = config.bo & (1 << 8);
					bitOptions.skipButton = config.bo & (1 << 9);
					bitOptions.autoStart = config.bo & (1 << 10);
					bitOptions.forcingQuestionSolve = config.bo & (1 << 11);
					bitOptions.hideQuestionPositionOverview =
						config.bo & (1 << 12);
					bitOptions.formActivated = config.bo & (1 << 13);
					bitOptions.maxShowQuestion = config.bo & (1 << 14);
					bitOptions.sortCategories = config.bo & (1 << 15);

					const cors = config.bo & (1 << 5);

					if (
						cors &&
						jQuery.support != undefined &&
						jQuery.support.cors != undefined &&
						jQuery.support.cors == false
					) {
						bitOptions.cors = cors;
					}
				}
			},

			setClozeStyle() {
				$e.find('.wpProQuiz_cloze input').each(function () {
					const $this = $(this);
					let word = '';
					const wordLen = $this.data('wordlen');

					for (let i = 0; i < wordLen; i++) {
						word += 'w';
					}

					const clone = $(document.createElement('span'))
						.css('visibility', 'hidden')
						.text(word)
						.appendTo($('body'));

					const width = clone.width();

					clone.remove();

					$this.width(width + 5);
				});
			},

			parseTime(sec) {
				let seconds = parseInt(sec % 60);
				let minutes = parseInt((sec / 60) % 60);
				let hours = parseInt((sec / 3600) % 24);

				seconds = (seconds > 9 ? '' : '0') + seconds;
				minutes = (minutes > 9 ? '' : '0') + minutes;
				hours = (hours > 9 ? '' : '0') + hours;

				return hours + ':' + minutes + ':' + seconds;
			},

			cleanupCurlyQuotes(str) {
				str = str.replace(/\u2018/, "'");
				str = str.replace(/\u2019/, "'");

				str = str.replace(/\u201C/, '"');
				str = str.replace(/\u201D/, '"');

				//return str.trim().toLowerCase();

				// Changes in v2.5 to NOT set cloze answers to lowercase
				return str.trim();
			},

			resetMatrix(selector) {
				selector.each(function () {
					const $this = $(this);
					const $list = $this.find('.wpProQuiz_sortStringList');

					$this.find('.wpProQuiz_sortStringItem').each(function () {
						$list.append($(this));
					});
				});
			},

			marker(e, correct) {
				if (!bitOptions.disabledAnswerMark) {
					if (correct === true) {
						e.addClass('wpProQuiz_answerCorrect');
					} else if (correct === false) {
						e.addClass('wpProQuiz_answerIncorrect');
					} else {
						e.addClass(correct);
					}
				}
			},

			startQuiz(loadData) {
				if (config.ld_script_debug == true) {
					console.log('in startQuiz');
				}

				if (quizStatus.loadLock) {
					quizStatus.isQuizStart = 1;

					return;
				}

				quizStatus.isQuizStart = 0;

				if (quizStatus.isLocked) {
					globalElements.quizStartPage.hide();
					$e.find('.wpProQuiz_lock').show();

					return;
				}

				if (quizStatus.isPrerequisite) {
					globalElements.quizStartPage.hide();
					$e.find('.wpProQuiz_prerequisite').show();

					return;
				}

				if (quizStatus.isUserStartLocked) {
					globalElements.quizStartPage.hide();
					$e.find('.wpProQuiz_startOnlyRegisteredUser').show();

					return;
				}

				if (bitOptions.maxShowQuestion && !loadData) {
					if (config.formPos == formPosConst.START) {
						if (!formClass.checkForm()) {
							return;
						}
					}

					globalElements.quizStartPage.hide();
					$e.find('.wpProQuiz_loadQuiz').show();

					plugin.methode.loadQuizDataAjax(true);

					return;
				}

				if (
					bitOptions.formActivated &&
					config.formPos == formPosConst.START
				) {
					if (!formClass.checkForm()) {
						return;
					}
				}

				plugin.methode.loadQuizData();
				quiz_resume_data = learndash_prepare_quiz_resume_data(config);

				if (quiz_resume_data === false) {
					quiz_resume_data = {};
				}

				// Init our Cookies
				//if (config.quiz_resume_enabled === "1") {
				//	cookie_name = "ld_save_" + config.quizId + "_quiz_responses";
				//	plugin.methode.CookieInit();
				//} else if (config.timelimitcookie !== 0) {
				//	cookie_name = "ld_" + config.quizId + "_quiz_responses";
				//	plugin.methode.CookieInit();
				//}

				questionTimer.startQuiz();

				if (
					bitOptions.randomQuestion &&
					jQuery.isEmptyObject(quiz_resume_data) &&
					!quiz_resume_data.randomQuestions
				) {
					plugin.methode.random(
						globalElements.questionList,
						'question'
					);
				}

				if (bitOptions.randomAnswer) {
					plugin.methode.random($e.find(globalNames.questionList));
				}

				if (bitOptions.sortCategories) {
					plugin.methode.sortCategories();
				}

				// randomize the matrix sort question items
				plugin.methode.random($e.find('.wpProQuiz_sortStringList'));

				// randomize the sort question answers
				//plugin.methode.random($e.find('.wpProQuiz_questionList[data-type="sort_answer"]'));

				$e.find('.wpProQuiz_listItem').each(function (i, v) {
					const $this = $(this);
					$this
						.find('.wpProQuiz_question_page span:eq(0)')
						.text(i + 1);
					$this.find('> h5 span').text(i + 1);

					$this
						.find('.wpProQuiz_questionListItem')
						.each(function (i, v) {
							$(this)
								.find('> span:not(.wpProQuiz_cloze)')
								.text(i + 1 + '. ');
						});
				});

				globalElements.next = $e.find(globalNames.next);

				switch (config.mode) {
					case 3:
						$e.find('input[name="checkSingle"]').show();
						break;
					case 2:
						$e.find(globalNames.check).show();

						if (
							!bitOptions.skipButton &&
							bitOptions.reviewQustion
						) {
							$e.find(globalNames.skip).show();
						}

						break;
					case 1:
						$e.find('input[name="back"]').slice(1).show();
					case 0:
						globalElements.next.show();
						break;
				}

				if (
					bitOptions.hideQuestionPositionOverview ||
					config.mode == 3
				) {
					$e.find('.wpProQuiz_question_page').hide();
				}

				//Change last name
				const $lastButton = globalElements.next.last();
				lastButtonValue = $lastButton.val();
				$lastButton.val(config.lbn);

				const $listItem = globalElements.questionList.children();

				globalElements.listItems = $e.find('.wpProQuiz_list > li');

				//quiz_resume_data.lastQuestion = parseInt(quiz_resume_data.lastQuestion);

				if (
					config.mode === 3 &&
					'number' !== typeof quiz_resume_data.lastQuestion
				) {
					plugin.methode.showSinglePage(0);
				}

				if ('number' !== typeof quiz_resume_data.lastQuestion) {
					quiz_resume_data.lastQuestion = 0;
				}

				// Original
				/*
				if ( config.mode !== 3 ) {
					currentQuestion = $listItem.eq( 0 ).show();
					var questionId = currentQuestion.find( globalNames.questionList ).data( 'question_id' );
					questionTimer.questionStart( questionId );
				}
				*/

				if (config.mode !== 3) {
					if (config.ld_script_debug == true) {
						console.log(
							'line 1359: quiz_resume_data.lastQuestion[%o]',
							quiz_resume_data.lastQuestion
						);
					}

					if (quiz_resume_data.lastQuestion > 0) {
						$listItem.each(function (q_idx, q_item) {
							const q_id = $(q_item)
								.find('.wpProQuiz_questionList')
								.data('question_id');

							if (config.ld_script_debug == true) {
								console.log(
									'q_id[%o] quiz_resume_data.lastQuestion[%o]',
									q_id,
									quiz_resume_data.lastQuestion
								);
							}

							if (q_id === quiz_resume_data.lastQuestion) {
								currentQuestion = $listItem.eq(q_idx);
								if (config.ld_script_debug == true) {
									console.log(
										'currentQuestion[%o]',
										currentQuestion
									);
									console.log('$listItem[%o]', $listItem);
								}

								//$listItem.eq(q_idx).show();
								return false;
							}
						});
					} else {
						if (config.ld_script_debug == true) {
							console.log(
								'line 1382: ELSE: quiz_resume_data.lastQuestion zero'
							);
							console.log('$listItem[%o]', $listItem);
						}
						currentQuestion = $listItem.eq(0);

						if (config.ld_script_debug == true) {
							console.log('currentQuestion[%o]', currentQuestion);
						}

						$listItem.eq(0).show();
					}
				} else {
					if (config.ld_script_debug == true) {
						console.log('line 1390: ELSE: condif.mode = 3');
					}
					currentQuestion = $listItem.eq(0);
				}
				if (config.ld_script_debug == true) {
					console.log('after currentQuestion[%o]', currentQuestion);
				}

				$e.find('.wpProQuiz_sortable')
					.parents('ul')
					.sortable({
						scroll: true,
						scrollSensitivity: 10 || config.scrollSensitivity,
						scrollSpeed: 10 || config.scrollSpeed,
						update(event, ui) {
							const $p = $(this).parents('.wpProQuiz_listItem');
							$e.trigger({
								type: 'questionSolved',
								values: {
									item: $p,
									index: $p.index(),
									solved: true,
								},
							});
						},
					})
					.disableSelection();

				$e.find(
					'.wpProQuiz_sortStringList, .wpProQuiz_maxtrixSortCriterion'
				)
					.sortable({
						connectWith:
							'.wpProQuiz_maxtrixSortCriterion:not(:has(li)), .wpProQuiz_sortStringList',
						placeholder: 'wpProQuiz_placehold',
						scroll: true,
						scrollSensitivity: 10 || config.scrollSensitivity,
						scrollSpeed: 10 || config.scrollSpeed,
						update(event, ui) {
							const $p = $(this).parents('.wpProQuiz_listItem');
							$e.trigger({
								type: 'questionSolved',
								values: {
									item: $p,
									index: $p.index(),
									solved: true,
								},
							});
						},
					})
					.disableSelection();

				quizSolved = [];

				timelimit.start();

				startTime = +new Date();

				results = {
					comp: { points: 0, correctQuestions: 0, quizTime: 0 },
				};

				$e.find('.wpProQuiz_questionList').each(function () {
					const questionId = $(this).data('question_id');

					results[questionId] = { time: 0 };
				});

				catResults = {};

				$.each(options.catPoints, function (i, v) {
					catResults[i] = 0;
				});

				globalElements.quizStartPage.hide();
				$e.find('.wpProQuiz_loadQuiz').hide();
				globalElements.quiz.show();
				reviewBox.show();

				// Init our Cookies
				if (config.timelimitcookie !== 0) {
					cookie_name = 'ld_' + config.quizId + '_quiz_responses';
					plugin.methode.CookieInit();
				}

				if (config.quiz_resume_enabled === '1') {
					cookie_name =
						'ld_save_' + config.quizId + '_quiz_responses';
					plugin.methode.CookieInit();
				}

				//$('li.wpProQuiz_listItem', globalElements.questionList).each( function (idx, questionItem) {
				plugin.methode.setupMatrixSortHeights();
				//});

				if (config.ld_script_debug == true) {
					console.log(
						'currentQuestion.index [%o] currentQuestion[%o]',
						currentQuestion.index(),
						currentQuestion
					);
				}
				if (config.mode != 3) {
					$e.trigger({
						type: 'changeQuestion',
						values: {
							item: currentQuestion,
							index: currentQuestion.index(),
						},
					});
				}
			},
			showSingleQuestion(question) {
				//console.log('in showSingleQuestion question[%o]', question);

				const page = question ? Math.ceil(question / config.qpp) : 1;

				if (
					config.mode === 3 &&
					'number' !== typeof quiz_resume_data.lastQuestion
				) {
					this.showSinglePage(page);
				} else {
					this.singlePageNavigationQuizResume(question);
				}
			},
			showSinglePage(page) {
				$listItem = globalElements.questionList.children().hide();

				if (!config.qpp) {
					$listItem.show();

					return;
				}

				page = page ? +page : 1;

				const maxPage = Math.ceil(
					$e.find('.wpProQuiz_list > li').length / config.qpp
				);

				if (page > maxPage) {
					return;
				}

				const pl = $e.find(globalNames.singlePageLeft).hide();
				const pr = $e.find(globalNames.singlePageRight).hide();
				const cs = $e.find('input[name="checkSingle"]').hide();

				if (page > 1) {
					pl.val(pl.data('text').replace(/%d/, page - 1)).show();
				}

				if (page == maxPage) {
					cs.show();
				} else {
					pr.val(pr.data('text').replace(/%d/, page + 1)).show();
				}

				currentPage = page;

				const start = config.qpp * (page - 1);

				$listItem.slice(start, start + config.qpp).show();
				//plugin.methode.scrollTo( globalElements.quizStartPage );

				/**
				 * LEARNDASH-6591 On Quiz Start the scroll to the top of the page
				 * because the quiz element is hidden. So within the scrollTo()
				 * function the element position can't determined.
				 *
				 * Also changed the scroll element to the outer quiz wrapper.
				 */
				if (globalElements.self !== undefined) {
					if (globalElements.self.is(':hidden')) {
						globalElements.self.show();
					}
					plugin.methode.scrollTo(globalElements.self);
				}
			},

			nextQuestion() {
				if (
					config.timelimitcookie !== 0 ||
					config.quiz_resume_enabled === '1'
				) {
					if (
						currentQuestion
							.find(globalNames.next)
							.data('question-lock') === true
					) {
						plugin.methode.CookieProcessQuestionResponse(
							currentQuestion,
							true
						);
					} else {
						plugin.methode.CookieProcessQuestionResponse(
							currentQuestion
						);
					}
				}

				if (config.quiz_resume_enabled === '1') {
					// current Question (before pressing Next)
					plugin.methode.saveMetaDataToCookie({
						lastQuestion: jQuery(currentQuestion[0]).data(
							'question-meta'
						).question_pro_id,
					});

					// next Question
					const nextQuestion = jQuery(currentQuestion.next()[0]);
					if (
						typeof nextQuestion.data('question-meta') !==
						'undefined'
					) {
						plugin.methode.saveMetaDataToCookie({
							nextQuestion:
								nextQuestion.data('question-meta')
									.question_pro_id,
						});
					}
				}

				jQuery('.mejs-pause').trigger('click');
				this.showQuestionObject(currentQuestion.next());
			},

			prevQuestion() {
				this.showQuestionObject(currentQuestion.prev());
			},

			showQuestion(index) {
				const $element = globalElements.listItems.eq(index);

				if (config.mode == 3 || inViewQuestions) {
					if (config.qpp) {
						plugin.methode.showSingleQuestion(index + 1);
					}
					plugin.methode.scrollTo($element, 1);
					questionTimer.startQuiz();
					return;
				}

				this.showQuestionObject($element);
			},

			showQuestionObject(obj) {
				if (config.ld_script_debug == true) {
					//console.trace();
					console.log('showQuestionObject: obj[%o]', obj);
				}

				// We hide the current question IF it is set.
				if (
					typeof currentQuestion !== 'undefined' &&
					currentQuestion.length > 0
				) {
					globalElements.questionList.children().each(function () {
						if (
							$(this).data('question-meta').question_pro_id !==
							currentQuestion.data('question-meta')
								.question_pro_id
						) {
							$(this).hide();
						}
					});
				}

				if (
					!obj.length &&
					bitOptions.forcingQuestionSolve &&
					bitOptions.quizSummeryHide &&
					bitOptions.reviewQustion
				) {
					// First get all the questions...
					list = globalElements.questionList.children();

					if (list != null) {
						list.each(function () {
							const $this = $(this);
							const $questionList = $this.find(
								globalNames.questionList
							);
							const question_id =
								$questionList.data('question_id');
							const data =
								config.json[$questionList.data('question_id')];

							// Within the following logic. If the question type is 'sort_answer' there is a chance
							// the sortable answers will be displayed in the correct order. In that case the user will click
							// the next button.
							// The trigger to set the question was answered is normally a function of the sort/drag action
							// by the user. So we need to set the question answered flag in the case the Quiz summary is enabled.
							//if (data.type == 'sort_answer') {
							//	$e.trigger({type: 'questionSolved', values: {item: $this, index: $this.index(), solved: true}});
							//}
						});
					}

					for (
						let i = 0, c = $e.find('.wpProQuiz_listItem').length;
						i < c;
						i++
					) {
						if (!quizSolved[i]) {
							alert(WpProQuizGlobal.questionsNotSolved);
							return false;
						}
					}
				}

				//globalElements.questionList.children().hide();
				currentQuestion.hide();

				currentQuestion = obj.show();

				plugin.methode.scrollTo(globalElements.quiz);

				$e.trigger({
					type: 'changeQuestion',
					values: {
						item: currentQuestion,
						index: currentQuestion.index(),
					},
				});

				if (!currentQuestion.length) {
					plugin.methode.showQuizSummary();
				} else {
					const questionId = currentQuestion
						.find(globalNames.questionList)
						.data('question_id');
					questionTimer.questionStart(questionId);
				}
			},

			skipQuestion() {
				$e.trigger({
					type: 'skipQuestion',
					values: {
						item: currentQuestion,
						index: currentQuestion.index(),
					},
				});
				plugin.methode.nextQuestion();
			},
			reviewQuestion() {
				$e.trigger({
					type: 'reviewQuestion',
					values: {
						item: currentQuestion,
						index: currentQuestion.index(),
					},
				});
			},

			uploadFile(event) {
				const question_id = event.currentTarget.id.replace(
					'uploadEssaySubmit_',
					''
				);
				const file = $('#uploadEssay_' + question_id)[0].files[0];

				if (typeof file !== 'undefined') {
					const nonce = $('#_uploadEssay_nonce_' + question_id).val();
					const uploadEssaySubmit = $(
						'#uploadEssaySubmit_' + question_id
					);

					const uploadEssayMessage = $(
						'#uploadEssayMessage_' + question_id
					);
					uploadEssayMessage.removeClass('uploadEssayMessage_fail');
					uploadEssayMessage.removeClass(
						'uploadEssayMessage_success'
					);
					uploadEssayMessage.html(config.essayUploading);

					const data = new FormData();
					data.append('action', 'learndash_upload_essay');
					data.append('nonce', nonce);
					data.append('question_id', question_id);
					data.append('course_id', config.course_id);
					data.append('essayUpload', file);

					$.ajax({
						method: 'POST',
						type: 'POST',
						url: WpProQuizGlobal.ajaxurl,
						data,
						cache: false,
						contentType: false,
						processData: false,
						success(response) {
							// Update the response message. Then later apply the class for the color.
							if (typeof response.data.message !== 'undefined') {
								uploadEssayMessage.html(response.data.message);
							}

							if (
								response.success == true &&
								typeof response.data.filelink !== 'undefined'
							) {
								uploadEssayMessage.addClass(
									'uploadEssayMessage_success'
								);
								$('#uploadEssayFile_' + question_id).val(
									response.data.filelink
								);

								// disable the upload button. Only one file per quiz.
								uploadEssaySubmit.attr('disabled', 'disabled');

								const $item = $(
									'#uploadEssayFile_' + question_id
								).parents('.wpProQuiz_listItem');
								$e.trigger({
									type: 'questionSolved',
									values: {
										item: $item,
										index: $item.index(),
										solved: true,
									},
								});
							} else {
								uploadEssayMessage.addClass(
									'uploadEssayMessage_fail'
								);
								uploadEssaySubmit.removeAttr('disabled');
							}
						},
					});
				}
				event.preventDefault();
			},

			showQuizSummary() {
				questionTimer.questionStop();
				questionTimer.stopQuiz();
				cookieSending = true;

				if (bitOptions.quizSummeryHide || !bitOptions.reviewQustion) {
					if (
						bitOptions.formActivated &&
						config.formPos == formPosConst.END
					) {
						reviewBox.hide();
						globalElements.quiz.hide();
						plugin.methode.scrollTo(
							$e.find('.wpProQuiz_infopage').show()
						);
					} else {
						plugin.methode.finishQuiz();
					}

					return;
				}

				const quizSummary = $e.find('.wpProQuiz_checkPage');

				// Clone the Review questions and legand and add to the
				//$e.find('.wpProQuiz_checkPage .wpProQuiz_reviewSummary').html('');
				$e.find('.wpProQuiz_checkPage .wpProQuiz_reviewSummary').append(
					$e
						.find('.wpProQuiz_reviewDiv .wpProQuiz_reviewQuestion')
						.clone()
				);

				$e.find('.wpProQuiz_checkPage .wpProQuiz_reviewSummary').append(
					$e
						.find('.wpProQuiz_reviewDiv .wpProQuiz_reviewLegend')
						.clone()
				);

				$e.find(
					'.wpProQuiz_checkPage .wpProQuiz_reviewSummary .wpProQuiz_reviewQuestion li'
				).removeClass('wpProQuiz_reviewQuestionTarget');

				$e.find(
					'.wpProQuiz_checkPage .wpProQuiz_reviewSummary .wpProQuiz_reviewQuestion li'
				).on('click', function (event) {
					$e.find(
						'.wpProQuiz_checkPage .wpProQuiz_reviewSummary'
					).html('');

					quizSummary.hide();
					globalElements.quiz.show();
					reviewBox.show(true);

					const index = $(this).index();
					plugin.methode.showQuestion(index);
				});

				let cSolved = 0;

				for (let i = 0, c = quizSolved.length; i < c; i++) {
					if (quizSolved[i]) {
						cSolved++;
					}
				}

				quizSummary.find('span:eq(0)').text(cSolved);

				reviewBox.hide();
				globalElements.quiz.hide();

				quizSummary.show();

				plugin.methode.scrollTo(quizSummary);
			},

			finishQuiz(timeover) {
				// LEARNDASH-4412 : Disable final buttons on submit.
				globalElements.next.last().attr('disabled', 'disabled');
				$e.find('input[name="checkSingle"]').attr(
					'disabled',
					'disabled'
				);

				questionTimer.questionStop();
				questionTimer.stopQuiz();
				timelimit.stop();
				cookieSending = true;

				let time = (+new Date() - startTime) / 1000;
				time =
					config.timelimit && time > config.timelimit
						? config.timelimit
						: time;
				timespent = time;
				$e.find('.wpProQuiz_quiz_time span').text(
					plugin.methode.parseTime(time)
				);

				if (timeover) {
					globalElements.results
						.find('.wpProQuiz_time_limit_expired')
						.show();
				}

				plugin.methode.checkQuestion(
					globalElements.questionList.children(),
					true
				);
			},
			finishQuizEnd() {
				$e.find('.wpProQuiz_correct_answer').text(
					results.comp.correctQuestions
				);

				results.comp.result =
					Math.round(
						(results.comp.points / config.globalPoints) * 100 * 100
					) / 100;

				let hasNotGradedQuestion = false;
				$.each(results, function () {
					if (
						typeof this.graded_status !== 'undefined' &&
						this.graded_status == 'not_graded'
					) {
						hasNotGradedQuestion = true;
					}
				});

				if (
					typeof certificate_details !== 'undefined' &&
					certificate_details.certificateLink != undefined &&
					certificate_details.certificateLink != ''
				) {
					const certificateContainer = $e.find(
						'.wpProQuiz_certificate'
					);
					if (
						results.comp.result >=
						certificate_details.certificate_threshold * 100
					) {
						//if (true == hasNotGradedQuestion && typeof certificate_pending !== 'undefined') {
						//	certificateContainer.append('<br />'+certificate_pending );
						//}
						certificateContainer.show();
					} else if (
						true == hasNotGradedQuestion &&
						typeof certificate_pending !== 'undefined'
					) {
						certificateContainer.html(certificate_pending);
						certificateContainer.show();
					}
				}

				const quiz_continue_link = $e.find('.quiz_continue_link');

				let show_quiz_continue_buttom_on_fail = false;
				if (
					jQuery(quiz_continue_link).hasClass(
						'show_quiz_continue_buttom_on_fail'
					)
				) {
					show_quiz_continue_buttom_on_fail = true;
				}

				if (
					typeof options.passingpercentage !== 'undefined' &&
					parseFloat(options.passingpercentage) >= 0.0
				) {
					if (
						results.comp.result >= options.passingpercentage ||
						show_quiz_continue_buttom_on_fail
					) {
						$e.addClass('ld-quiz-result-passed');
						$e.removeClass('ld-quiz-result-failed');

						$e.trigger({
							type: 'learndash-quiz-finished',
							values: {
								status: 'passed',
								item: $e,
								results,
							},
						});
						$e.trigger({
							type: 'learndash-quiz-finished-passed',
							values: {
								status: 'passed',
								item: $e,
								results,
							},
						});

						//For now, Just append the HTML to the page
						if (typeof continue_details !== 'undefined') {
							$e.find('.quiz_continue_link').html(
								continue_details
							);
							$e.find('.quiz_continue_link').show();
						}
					} else {
						$e.removeClass('ld-quiz-result-passed');
						$e.addClass('ld-quiz-result-failed');

						$e.trigger({
							type: 'learndash-quiz-finished',
							values: {
								status: 'failed',
								item: $e,
								results,
							},
						});
						$e.trigger({
							type: 'learndash-quiz-finished-failed',
							values: {
								status: 'failed',
								item: $e,
								results,
							},
						});

						$e.find('.quiz_continue_link').hide();
					}
				} else if (typeof continue_details !== 'undefined') {
					$e.find('.quiz_continue_link').html(continue_details);
					$e.find('.quiz_continue_link').show();
				}

				$pointFields = $e.find('.wpProQuiz_points span');
				$gradedPointsFields = $e.find('.wpProQuiz_graded_points span');

				$pointFields.eq(0).text(results.comp.points);
				$pointFields.eq(1).text(config.globalPoints);
				$pointFields.eq(2).text(results.comp.result + '%');

				$gradedQuestionCount = 0;
				$gradedQuestionPoints = 0;

				$.each(results, function (question_id, result) {
					if (!isNaN(question_id) && result.graded_id) {
						const possible = result.possiblePoints - result.points;
						if (possible > 0) {
							$gradedQuestionPoints += possible;
							$gradedQuestionCount++;
						}
					}
				});

				if ($gradedQuestionCount > 0) {
					$('.wpProQuiz_points').hide();
					$('.wpProQuiz_graded_points').show();
					$gradedPointsFields.eq(0).text(results.comp.points);
					$gradedPointsFields.eq(1).text(config.globalPoints);
					$gradedPointsFields.eq(2).text(results.comp.result + '%');
					$gradedPointsFields.eq(3).text($gradedQuestionCount);
					$gradedPointsFields.eq(4).text($gradedQuestionPoints);
				}

				$e.find('.wpProQuiz_resultsList > li')
					.eq(plugin.methode.findResultIndex(results.comp.result))
					.show();

				plugin.methode.setAverageResult(results.comp.result, false);

				this.setCategoryOverview();

				plugin.methode.sendCompletedQuiz();

				if (bitOptions.isAddAutomatic && toplistData.isUser) {
					plugin.methode.addToplist();
				}

				reviewBox.hide();

				$e.find('.wpProQuiz_checkPage, .wpProQuiz_infopage').hide();
				globalElements.quiz.hide();
			},
			sending(start, end, step_size) {
				globalElements.sending.show();
				const sending_progress_bar = globalElements.sending.find(
					'.sending_progress_bar'
				);
				let i;
				if (typeof start === undefined || start == null) {
					i =
						parseInt(
							(sending_progress_bar.width() * 100) /
								sending_progress_bar.offsetParent().width()
						) + 156;
				} else {
					i = start;
				}

				if (end == undefined) {
					var end = 80;
				}

				if (step_size == undefined) {
					step_size = 1;
				}

				if (
					sending_timer != null &&
					typeof sending_timer !== undefined
				) {
					clearInterval(sending_timer);
				}
				sending_timer = setInterval(function () {
					const currentWidth = parseInt(
						(sending_progress_bar.width() * 100) /
							sending_progress_bar.offsetParent().width()
					);
					if (currentWidth >= end) {
						clearInterval(sending_timer);
						if (currentWidth >= 100) {
							setTimeout(plugin.methode.showResults(), 2000);
						}
					}
					sending_progress_bar.css('width', i + '%');
					i = i + step_size;
				}, 300);
			},
			showResults() {
				globalElements.sending.hide();
				globalElements.results.show();
				plugin.methode.scrollTo(globalElements.results);
			},
			setCategoryOverview() {
				results.comp.cats = {};

				$e.find('.wpProQuiz_catOverview li').each(function () {
					const $this = $(this);
					const catId = $this.data('category_id'); // cspell:disable-line

					if (config.catPoints[catId] === undefined) {
						// cspell:disable-line
						$this.hide();
						return true;
					}

					const r =
						Math.round(
							(catResults[catId] / config.catPoints[catId]) *
								100 *
								100
						) / 100; // cspell:disable-line

					results.comp.cats[catId] = r; // cspell:disable-line

					$this.find('.wpProQuiz_catPercent').text(r + '%');

					$this.show();
				});
			},

			questionSolved(e) {
				//if (config.ld_script_debug == true) {
				//	console.log('questionSolved: e.values[%o]', e.values);
				//}

				quizSolved[e.values.index] = e.values.solved;
			},

			sendCompletedQuiz() {
				if (bitOptions.preview) {
					return;
				}
				// Prevent sending cookie data during quiz submission.
				cookieSending = true;

				//console.log('sendCompletedQuiz: results[%o]', results);

				fetchAllAnswerData(results);

				const formData = formClass.getFormData();

				// Add tip / hint count to results object.
				// Server-side data

				var quiz_resume_data =
					learndash_prepare_quiz_resume_data(config);
				if (config.ld_script_debug == true) {
					console.log(
						'line: 1936 quiz_resume_data[%o]',
						quiz_resume_data
					);
				}
				if (quiz_resume_data === false) {
					quiz_resume_data = {};
				}

				if ('undefined' !== typeof config.quiz_resume_data) {
					try {
						var quiz_resume_data = JSON.parse(
							config.quiz_resume_data
						);
					} catch (exception) {
						console.log('JSON.parse error [%o]', exception);
						var quiz_resume_data = {};
					}
					if (!jQuery.isEmptyObject(quiz_resume_data)) {
						for (var element in quiz_resume_data) {
							if (element.startsWith('tip')) {
								var id = quiz_resume_data[element].question_id;
								results[id].tip = 1;
							}
						}
					}
				}

				// Cookie data
				if (
					'undefined' !== typeof cookie_value &&
					'' !== cookie_value
				) {
					for (var element in cookie_value) {
						if (element.startsWith('tip')) {
							var id = cookie_value[element].question_id;
							results[id].tip = 1;
						}
					}
				}

				jQuery.ajax({
					type: 'POST',
					url: WpProQuizGlobal.ajaxurl,
					dataType: 'json',
					cache: false,
					data: {
						action: 'wp_pro_quiz_completed_quiz',
						course_id: config.course_id,
						lesson_id: config.lesson_id,
						topic_id: config.topic_id,
						quiz: config.quiz,
						quizId: config.quizId,
						results: JSON.stringify(results),
						timespent,
						forms: formData,
						quiz_nonce: config.quiz_nonce,
					},
					success(json) {
						if (json != null) {
							if (typeof config.quizId !== 'undefined') {
								const quiz_pro_id = parseInt(config.quizId);
								if (
									typeof json[quiz_pro_id] !== 'undefined' &&
									typeof json[quiz_pro_id]
										.quiz_result_settings !== 'undefined'
								) {
									result_settings =
										json[quiz_pro_id].quiz_result_settings;
									plugin.methode.afterSendUpdateIU(
										result_settings
									);
								}
							}
						}

						plugin.methode.sending(null, 100, 15); //Complete the remaining progress bar faster and show results

						// Clear Cookie on restart
						plugin.methode.CookieDelete();
					},
				});
			},

			afterSendUpdateIU(quiz_result_settings) {
				if (
					typeof quiz_result_settings.showAverageResult !==
					'undefined'
				) {
					if (!quiz_result_settings.showAverageResult) {
						$e.find('.wpProQuiz_resultTable').remove();
					}
				}

				if (
					typeof quiz_result_settings.showCategoryScore !==
					'undefined'
				) {
					if (!quiz_result_settings.showCategoryScore) {
						$e.find('.wpProQuiz_catOverview').remove();
					}
				}

				if (
					typeof quiz_result_settings.showRestartQuizButton !==
					'undefined'
				) {
					if (!quiz_result_settings.showRestartQuizButton) {
						$e.find('input[name="restartQuiz"]').remove();
					}
				}

				if (
					typeof quiz_result_settings.showResultPoints !== 'undefined'
				) {
					if (!quiz_result_settings.showResultPoints) {
						$e.find('.wpProQuiz_points').remove();
					}
				}

				if (
					typeof quiz_result_settings.showResultQuizTime !==
					'undefined'
				) {
					if (!quiz_result_settings.showResultQuizTime) {
						$e.find('.wpProQuiz_quiz_time').remove();
					}
				}

				if (
					typeof quiz_result_settings.showViewQuestionButton !==
					'undefined'
				) {
					if (!quiz_result_settings.showViewQuestionButton) {
						$e.find('input[name="reShowQuestion"]').remove();
					}
				}

				if (
					typeof quiz_result_settings.showContinueButton !==
					'undefined'
				) {
					if (!quiz_result_settings.showContinueButton) {
						$e.find('.quiz_continue_link').remove();
					}
				}
			},
			findResultIndex(p) {
				const r = config.resultsGrade;
				let index = -1;
				let diff = 999999;

				for (let i = 0; i < r.length; i++) {
					const v = r[i];

					if (p >= v && p - v < diff) {
						diff = p - v;
						index = i;
					}
				}

				return index;
			},

			showQustionList() {
				inViewQuestions = !inViewQuestions;
				globalElements.toplistShowInButton.hide();
				globalElements.quiz.toggle();
				$e.find('.wpProQuiz_QuestionButton').hide();
				globalElements.questionList.children().show();
				reviewBox.toggle();

				$e.find('.wpProQuiz_question_page').hide();
			},
			random(group, type) {
				var type = type || false;
				let randomized;
				group.each(function () {
					const answer_type = $(this).data('type');
					let e;
					if (answer_type !== '' && answer_type !== 'sort_answer') {
						e = $(this)
							.children()
							.get()
							.sort(function () {
								return Math.round(Math.random()) - 0.5;
							});
					} else {
						e = $(this).children().get();
					}
					$(e).appendTo(e[0].parentNode);
					randomized = e;
				});
				if (config.quiz_resume_enabled === '1') {
					if (
						'undefined' !== typeof randomized &&
						type === 'question'
					) {
						plugin.methode.saveRandomQuestions(randomized);
					}
				}
			},
			saveRandomQuestions(questions) {
				const orderedQuestions = [];
				jQuery(questions).each(function (index, question) {
					const id =
						jQuery(question).data('question-meta').question_pro_id;
					orderedQuestions.push(id);
				});

				if (orderedQuestions.length > 0) {
					plugin.methode.saveMetaDataToCookie({
						randomQuestions: true,
						randomOrder: orderedQuestions,
					});
				}
			},
			sortCategories() {
				const e = $('.wpProQuiz_list')
					.children()
					.get()
					.sort(function (a, b) {
						const aQuestionId = $(a)
							.find('.wpProQuiz_questionList')
							.data('question_id');
						const bQuestionId = $(b)
							.find('.wpProQuiz_questionList')
							.data('question_id');

						return (
							config.json[aQuestionId].catId -
							config.json[bQuestionId].catId
						); // cspell:disable-line
					});

				$(e).appendTo(e[0].parentNode);
			},

			restartQuiz() {
				globalElements.results.hide();
				//globalElements.quizStartPage.show();
				globalElements.questionList.children().hide();
				globalElements.toplistShowInButton.hide();
				reviewBox.hide();

				$e.find('.wpProQuiz_questionInput, .wpProQuiz_cloze input')
					.removeAttr('disabled')
					.removeAttr('checked')
					.css('background-color', '');

				// Reset all the question types to empty values. This really should be moved into a reset function to be called at other times like at Quiz Init.
				//				$e.find('.wpProQuiz_cloze input').val('');
				$e.find('.wpProQuiz_questionListItem input[type="text"]').val(
					''
				);

				$e.find(
					'.wpProQuiz_answerCorrect, .wpProQuiz_answerIncorrect'
				).removeClass(
					'wpProQuiz_answerCorrect wpProQuiz_answerIncorrect'
				);

				$e.find('.wpProQuiz_listItem').data('check', false);
				$e.find('textarea.wpProQuiz_questionEssay').val('');
				$e.find('input.uploadEssayFile').val('');
				$e.find('input.wpProQuiz_upload_essay').val('');

				$e.find('.wpProQuiz_response').hide().children().hide();

				plugin.methode.resetMatrix($e.find('.wpProQuiz_listItem'));

				$e.find(
					'.wpProQuiz_sortStringItem, .wpProQuiz_sortable'
				).removeAttr('style');

				$e.find(
					'.wpProQuiz_clozeCorrect, .wpProQuiz_QuestionButton, .wpProQuiz_resultsList > li'
				).hide();

				$e.find('.wpProQuiz_question_page, input[name="tip"]').show();

				$e.find('.wpProQuiz_certificate').attr(
					'style',
					'display: none !important'
				);

				globalElements.results
					.find('.wpProQuiz_time_limit_expired')
					.hide();

				globalElements.next.last().val(lastButtonValue);

				inViewQuestions = false;

				// Clear Cookie on restart
				//plugin.methode.CookieDelete();

				// LEARNDASH-3201 - Added reload to force check on Quiz Repeats / Run Once logic.
				window.location.reload(true);
			},
			showSpinner() {
				$e.find('.wpProQuiz_spinner').show();
			},
			hideSpinner() {
				$e.find('.wpProQuiz_spinner').hide();
			},
			checkQuestion(list, endCheck) {
				const finishQuiz = list == undefined ? false : true;
				const responses = {};
				const r = {};

				list = list == undefined ? currentQuestion : list;

				list.each(function () {
					const $this = $(this);
					const question_index = $this.index();
					const $questionList = $this.find(globalNames.questionList);
					const question_id = $questionList.data('question_id');
					const data = config.json[$questionList.data('question_id')];
					let name = data.type;

					questionTimer.questionStop();

					if ($this.data('check')) {
						return true;
					}

					if (data.type == 'single' || data.type == 'multiple') {
						name = 'singleMulti';
					}
					//if (config.ld_script_debug == true) {
					//	console.log('checkQuestion: calling readResponses');
					//}

					responses[question_id] = readResponses(
						name,
						data,
						$this,
						$questionList,
						true
					);
					responses[question_id].question_pro_id = data.id;
					responses[question_id].question_post_id =
						data.question_post_id;
					//console.log('responses[%o]', responses);

					if (
						config.timelimitcookie !== 0 ||
						config.quiz_resume_enabled === '1'
					) {
						plugin.methode.CookieSaveResponse(
							question_id,
							question_index,
							data.type,
							responses[question_id],
							true
						);
					}
				});
				//console.log('responses[%o]', responses);
				config.checkAnswers = {
					list,
					responses,
					endCheck,
					finishQuiz,
				};

				if (finishQuiz) {
					plugin.methode.sending(1, 80, 3);
				} else {
					plugin.methode.showSpinner();
				}

				//console.log('config.json[%o]', config.json);

				plugin.methode.ajax(
					{
						action: 'ld_adv_quiz_pro_ajax',
						func: 'checkAnswers',
						data: {
							quizId: config.quizId,
							quiz: config.quiz,
							course_id: config.course_id,
							quiz_nonce: config.quiz_nonce,
							responses: JSON.stringify(responses),
						},
					},
					function (json) {
						//console.log('json[%o]', json);

						plugin.methode.hideSpinner();
						const list = config.checkAnswers.list;
						const responses = config.checkAnswers.responses;
						const r = config.checkAnswers.r;
						const endCheck = config.checkAnswers.endCheck;
						const finishQuiz = config.checkAnswers.finishQuiz;

						list.each(function () {
							const $this = $(this);
							//console.log('this[%o]', $this);

							const $questionList = $this.find(
								globalNames.questionList
							);
							const question_id =
								$questionList.data('question_id');
							//var data = {id: question_id};

							if ($this.data('check')) {
								return true;
							}

							if (typeof json[question_id] !== 'undefined') {
								const result = json[question_id];

								data =
									config.json[
										$questionList.data('question_id')
									];

								$this.find('.wpProQuiz_response').show();
								$this.find(globalNames.check).hide();
								$this.find(globalNames.skip).hide();
								$this.find(globalNames.next).show();
								$this
									.find(globalNames.next)
									.attr('data-question-lock', true);

								results[data.id].points = result.p;
								if (typeof result.p_nonce !== 'undefined') {
									results[data.id].p_nonce = result.p_nonce;
								} else {
									results[data.id].p_nonce = '';
								}

								results[data.id].correct = Number(result.c);
								results[data.id].data = result.s;
								if (typeof result.a_nonce !== 'undefined') {
									results[data.id].a_nonce = result.a_nonce;
								} else {
									results[data.id].a_nonce = '';
								}
								results[data.id].possiblePoints =
									result.e.possiblePoints;

								// If the sort_answer or matrix_sort_answer question type is not 100% correct then the returned
								// result.s object will be empty. So in order to pass the user's answers to the server for the
								// sendCompletedQuiz AJAX call we need to grab the result.e.r object and store into results.
								if (
									jQuery.isEmptyObject(results[data.id].data)
								) {
									if (
										result.e.type != undefined &&
										(result.e.type == 'sort_answer' ||
											result.e.type ==
												'matrix_sort_answer')
									) {
										results[data.id].data = result.e.r;
									}
								}

								if (
									typeof result.e.graded_id !== 'undefined' &&
									result.e.graded_id > 0
								) {
									results[data.id].graded_id =
										result.e.graded_id;
								}

								if (
									typeof result.e.graded_status !==
									'undefined'
								) {
									results[data.id].graded_status =
										result.e.graded_status;
								}

								results.comp.points += result.p;

								$this.find('.wpProQuiz_response').show();
								$this.find(globalNames.check).hide();
								$this.find(globalNames.skip).hide();
								$this.find(globalNames.next).show();
								$this
									.find(globalNames.next)
									.attr('data-question-lock', true);

								//results[data.id].points = result.p;
								//results[data.id].correct = Number(result.c);
								//results[data.id].data = result.s;

								// If the sort_answer or matrix_sort_answer question type is not 100% correct then the returned
								// result.s object will be empty. So in order to pass the user's answers to the server for the
								// sendCompletedQuiz AJAX call we need to grab the result.e.r object and store into results.
								if (
									jQuery.isEmptyObject(results[data.id].data)
								) {
									if (typeof result.e.type !== 'undefined') {
										if (
											result.e.type == 'sort_answer' ||
											result.e.type ==
												'matrix_sort_answer'
										) {
											if (
												typeof result.e.r !==
												'undefined'
											) {
												results[data.id].data =
													result.e.r;
											}
										}

										if (result.e.type == 'essay') {
											if (
												typeof result.e.graded_id !==
												'undefined'
											) {
												results[data.id].data = {
													graded_id:
														result.e.graded_id,
												};
											}
										}
									}
								}

								catResults[data.catId] += result.p; // cspell:disable-line

								if (config.quiz_resume_enabled === '1') {
									plugin.methode.saveMetaDataToCookie(
										'checked' + question_id,
										result
									);
								}

								//Marker
								plugin.methode.markCorrectIncorrect(
									result,
									$this,
									$questionList
								);

								if (result.c) {
									if (
										typeof result.e.AnswerMessage !==
										'undefined'
									) {
										$this
											.find('.wpProQuiz_correct')
											.find('.wpProQuiz_AnswerMessage')
											.html(result.e.AnswerMessage);
										$this
											.find('.wpProQuiz_correct')
											.trigger(
												'learndash-quiz-answer-response-contentchanged'
											);
									}

									//if(!endCheck) {
									//	$e.trigger({type: 'questionSolved', values: {item: $this, index: $this.index(), solved: true}});
									//}
									$e.trigger({
										type: 'questionSolvedCorrect',
										values: {
											item: $this,
											index: $this.index(),
											solved: true,
											result,
										},
									});

									$this.find('.wpProQuiz_correct').show();
									results.comp.correctQuestions += 1;
								} else {
									if (
										typeof result.e.AnswerMessage !==
										'undefined'
									) {
										$this
											.find('.wpProQuiz_incorrect')
											.find('.wpProQuiz_AnswerMessage')
											.html(result.e.AnswerMessage);
										$this
											.find('.wpProQuiz_incorrect')
											.trigger(
												'learndash-quiz-answer-response-contentchanged'
											);
									}

									//if (!endCheck) {
									//	$e.trigger({ type: 'questionSolved', values: { item: $this, index: $this.index(), solved: true } });
									//}
									$e.trigger({
										type: 'questionSolvedIncorrect',
										values: {
											item: $this,
											index: $this.index(),
											solved: true,
											result,
										},
									});

									$this.find('.wpProQuiz_incorrect').show();
								}

								$this
									.find('.wpProQuiz_responsePoints')
									.text(result.p);

								$this.data('check', true);
							}
						});

						// Set a default just in case.
						//results.comp.p_nonce = '';

						//if ( typeof json['comp'] !== 'undefined' ) {
						//	if ( ( typeof json['comp']['p'] !== 'undefined' ) && ( typeof json['comp']['p_nonce'] !== 'undefined' ) ) {
						//		results.comp.points = json['comp']['p'];
						//		results.comp.p_nonce = json['comp']['p_nonce'];
						//	}
						//}

						if (finishQuiz) {
							plugin.methode.finishQuizEnd();
						}
					}
				);
			},
			markCorrectIncorrect(result, $question, $questionList) {
				if (typeof result.e.c === 'undefined') {
					return;
				}

				switch (result.e.type) {
					case 'single':
					case 'multiple':
						$questionList.children().each(function (i) {
							const $item = $(this);
							const index = $item.attr('data-pos');

							if (result.e.c[index]) {
								const checked = $(
									'input.wpProQuiz_questionInput',
									$item
								).is(':checked');
								if (checked) {
									plugin.methode.marker($item, true);
								} else {
									plugin.methode.marker(
										$item,
										'wpProQuiz_answerCorrectIncomplete'
									);
								}
							} else if (!result.c && result.e.r[index]) {
								plugin.methode.marker($item, false);
							}
						});
						break;
					case 'free_answer':
						var $li = $questionList.children();
						if (result.c) {
							plugin.methode.marker($li, true);
						} else {
							plugin.methode.marker($li, false);
						}
						if (
							typeof result.e.c !== 'undefined' &&
							typeof result.e.c !== 'undefined' &&
							result.e.c.length > 0
						) {
							$question
								.find('span.wpProQuiz_freeCorrect')
								.html(result.e.c.join(', '))
								.show();
						}
						break;

					case 'cloze_answer':
						$questionList
							.find('.wpProQuiz_cloze')
							.each(function (i, v) {
								const $this = $(this);
								const cloze = $this.children();
								const input = cloze.eq(0);
								const span = cloze.eq(1);
								const inputText =
									plugin.methode.cleanupCurlyQuotes(
										input.val()
									);

								if (result.s[i]) {
									//input.css('background-color', '#B0DAB0');
									input.addClass('wpProQuiz_answerCorrect');
								} else {
									input.addClass('wpProQuiz_answerIncorrect');
									//input.css('background-color', '#FFBABA');

									if (typeof result.e.c[i] !== 'undefined') {
										span.html(
											'(' + result.e.c[i].join() + ')'
										);
										span.show();
									}
								}
								input.attr('disabled', 'disabled');
							});
						break;
					case 'sort_answer':
						var sortlist_container = jQuery(
							'div.wpProQuiz_questionList_containers',
							$question
						);
						if (
							typeof sortlist_container !== 'undefined' &&
							sortlist_container.length
						) {
							// LD 3.6.0: New logic to handle showing the "student" vs. "correct" answers.

							// Clone the student answert list in order to show the correct order.
							const $questionList_correct = $questionList.clone();
							$questionList_correct.insertAfter($questionList);
							$questionList_correct.addClass(
								'wpProQuiz_questionList_correct'
							);

							var $items = $questionList_correct.children(
								'li.wpProQuiz_questionListItem'
							);
							var index = new Array();
							jQuery.each(result.e.c, function (i, v) {
								index[v] = i;
							});
							$items.sort(function (a, b) {
								return index[$(a).attr('data-pos')] >
									index[$(b).attr('data-pos')]
									? 1
									: -1;
							});
							$questionList_correct.append($items);
							$questionList_correct
								.children('li.wpProQuiz_questionListItem')
								.addClass('wpProQuiz_answerCorrect');
							// Show the correct/incorrect indicators on the student answers.
							jQuery.each(
								result.e.c,
								function (correct_item_idx, correct_item_key) {
									const student_item_el =
										$questionList.children(
											'li.wpProQuiz_questionListItem'
										)[correct_item_idx];

									if (
										typeof student_item_el !==
											'undefined' &&
										student_item_el !== ''
									) {
										if (
											correct_item_key ==
											jQuery(student_item_el).data('pos')
										) {
											plugin.methode.marker(
												jQuery(student_item_el),
												true
											);
										} else {
											plugin.methode.marker(
												jQuery(student_item_el),
												false
											);
										}
									}
								}
							);

							jQuery(sortlist_container)
								.find(
									'.wpProQuiz_questionList_container_student'
								)
								.append($questionList);
							jQuery(sortlist_container)
								.find(
									'.wpProQuiz_questionList_container_correct'
								)
								.append($questionList_correct);

							jQuery(sortlist_container)
								.find(
									'.wpProQuiz_questionList_container_student'
								)
								.show();
							jQuery(sortlist_container)
								.find(
									'.wpProQuiz_questionList_container_correct'
								)
								.hide();

							jQuery(sortlist_container)
								.find(
									'input.wpProQuiz_questionList_containers_view_student'
								)
								.on('click', function (e) {
									jQuery(sortlist_container)
										.find(
											'input.wpProQuiz_questionList_containers_view_student'
										)
										.addClass(
											'wpProQuiz_questionList_containers_view_active'
										);
									jQuery(sortlist_container)
										.find(
											'input.wpProQuiz_questionList_containers_view_correct'
										)
										.removeClass(
											'wpProQuiz_questionList_containers_view_active'
										);

									jQuery(sortlist_container)
										.find(
											'.wpProQuiz_questionList_container_correct'
										)
										.hide();
									jQuery(sortlist_container)
										.find(
											'.wpProQuiz_questionList_container_student'
										)
										.show();
									e.preventDefault();
								});

							jQuery(sortlist_container)
								.find(
									'input.wpProQuiz_questionList_containers_view_correct'
								)
								.on('click', function (e) {
									jQuery(sortlist_container)
										.find(
											'input.wpProQuiz_questionList_containers_view_student'
										)
										.removeClass(
											'wpProQuiz_questionList_containers_view_active'
										);
									jQuery(sortlist_container)
										.find(
											'input.wpProQuiz_questionList_containers_view_correct'
										)
										.addClass(
											'wpProQuiz_questionList_containers_view_active'
										);

									jQuery(sortlist_container)
										.find(
											'.wpProQuiz_questionList_container_student'
										)
										.hide();
									jQuery(sortlist_container)
										.find(
											'.wpProQuiz_questionList_container_correct'
										)
										.show();
									e.preventDefault();
								});
							jQuery(sortlist_container).show();
						} else {
							// Legacy logic in case the new 'div.wpProQuiz_questionList_containers' element is not present.

							var $items = $questionList.children(
								'li.wpProQuiz_questionListItem'
							);

							$items.each(function (i, v) {
								const $this = $(this);

								if (result.e.c[i] == $this.attr('data-pos')) {
									plugin.methode.marker($this, true);
								} else {
									plugin.methode.marker($this, false);
								}
							});

							$items
								.children()
								.css({ 'box-shadow': '0 0', cursor: 'auto' });

							//						$questionList.sortable("destroy");

							var index = new Array();
							jQuery.each(result.e.c, function (i, v) {
								index[v] = i;
							});
							$items.sort(function (a, b) {
								return index[$(a).attr('data-pos')] >
									index[$(b).attr('data-pos')]
									? 1
									: -1;
							});

							$questionList.append($items);
						}

						break;
					case 'matrix_sort_answer':
						var $items = $questionList.children();
						var matrix = new Array();
						statistcAnswerData = { 0: -1 };

						$items.each(function () {
							const $this = $(this);
							const id = $this.attr('data-pos');
							const $stringUl = $this.find(
								'.wpProQuiz_maxtrixSortCriterion'
							);
							const $stringItem = $stringUl.children();
							const i = $stringItem.attr('data-pos');

							if (
								$stringItem.length &&
								result.e.c[i] == $this.attr('data-pos')
							) {
								plugin.methode.marker($this, true);
							} else {
								plugin.methode.marker($this, false);
							}

							matrix[i] = $stringUl;
						});

						plugin.methode.resetMatrix($question);

						$question
							.find('.wpProQuiz_sortStringItem')
							.each(function () {
								const x = matrix[$(this).attr('data-pos')];
								if (x != undefined) {
									x.append(this);
								}
							})
							.css({ 'box-shadow': '0 0', cursor: 'auto' });
						$question
							.find(
								'.wpProQuiz_sortStringList, .wpProQuiz_maxtrixSortCriterion'
							)
							.sortable();
						$question
							.find(
								'.wpProQuiz_sortStringList, .wpProQuiz_maxtrixSortCriterion'
							)
							.sortable('destroy');
						break;
				}
			},
			setCheckedStatusFromData(data, question, list) {
				if (config.ld_script_debug == true) {
					console.log('setCheckedStatusFromData data[%o]', data);
					console.log(
						'setCheckedStatusFromData question[%o]',
						question
					);
					console.log('setCheckedStatusFromData list[%o]', list);
				}
				const dataObjects = [quiz_resume_data, cookie_value];
				jQuery(dataObjects).each(function (index, dataObject) {
					if (dataObject.hasOwnProperty('checked' + data.id)) {
						for (const element in dataObject) {
							if (element.startsWith('checked')) {
								const elementId = element.replace(
									'checked',
									''
								);
								if (elementId == data.id) {
									questionResult = dataObject[element];
									plugin.methode.markCorrectIncorrect(
										questionResult,
										question,
										list
									);

									if (questionResult.c) {
										if (
											typeof questionResult.e
												.AnswerMessage !== 'undefined'
										) {
											question
												.find('.wpProQuiz_correct')
												.find(
													'.wpProQuiz_AnswerMessage'
												)
												.html(
													learndash_decodeHTML(
														questionResult.e
															.AnswerMessage
													)
												);
											question
												.find('.wpProQuiz_correct')
												.trigger(
													'learndash-quiz-answer-response-contentchanged'
												);
										}

										question
											.find('.wpProQuiz_response')
											.show();
										question
											.find('.wpProQuiz_correct')
											.show();
									} else {
										if (
											typeof questionResult.e
												.AnswerMessage !== 'undefined'
										) {
											question
												.find('.wpProQuiz_incorrect')
												.find(
													'.wpProQuiz_AnswerMessage'
												)
												.html(
													learndash_decodeHTML(
														questionResult.e
															.AnswerMessage
													)
												);
											question
												.find('.wpProQuiz_incorrect')
												.trigger(
													'learndash-quiz-answer-response-contentchanged'
												);
										}

										question
											.find('.wpProQuiz_response')
											.show();
										question
											.find('.wpProQuiz_incorrect')
											.show();
									}
									// question.find( '.wpProQuiz_responsePoints' ).text( questionResult.p );
								}
							}
						}
					}
				});
			},
			showTip() {
				const $this = $(this);
				const id = $this
					.siblings('.wpProQuiz_question')
					.find(globalNames.questionList)
					.data('question_id');

				$this.siblings('.wpProQuiz_tipp').toggle('fast');

				results[id].tip = 1;
				if (config.quiz_resume_enabled === '1') {
					plugin.methode.saveMetaDataToCookie('tip' + id, {
						question_id: id,
					});
				}

				$(document).on('mouseup.tipEvent', function (e) {
					const $tip = $e.find('.wpProQuiz_tipp');
					const $btn = $e.find('input[name="tip"]');

					if (
						!$tip.is(e.target) &&
						$tip.has(e.target).length == 0 &&
						!$btn.is(e.target)
					) {
						$tip.hide('fast');
						$(document).unbind('.tipEvent');
					}
				});
			},

			ajax(data, success, dataType) {
				dataType = dataType || 'json';

				if (bitOptions.cors) {
					jQuery.support.cors = true;
				}

				if (data.quiz === undefined) {
					data.quiz = config.quiz;
				}
				if (data.course_id === undefined) {
					data.course_id = config.course_id;
				}
				if (data.quiz_nonce === undefined) {
					data.quiz_nonce = config.quiz_nonce;
				}
				$.ajax({
					method: 'POST',
					type: 'POST',
					url: WpProQuizGlobal.ajaxurl,
					data,
					success,
					dataType,
				});

				if (bitOptions.cors) {
					jQuery.support.cors = false;
				}
			},

			checkQuizLock() {
				quizStatus.loadLock = 1;

				plugin.methode.ajax(
					{
						action: 'wp_pro_quiz_check_lock',
						quizId: config.quizId,
					},
					function (json) {
						if (json.lock != undefined) {
							quizStatus.isLocked = json.lock.is;

							/**
							 * Removed as part of LEARNDASH-4027
							 * The restart button does not need to be removed. It can be hidden
							 * via the Quiz setting.
							 */

							/*
						if(json.lock.pre) {
							$e.find('input[name="restartQuiz"]').hide();
						}
						*/
						}

						if (json.prerequisite != undefined) {
							quizStatus.isPrerequisite = 1;
							$e.find('.wpProQuiz_prerequisite span').text(
								json.prerequisite
							);
						}

						if (json.startUserLock != undefined) {
							quizStatus.isUserStartLocked = json.startUserLock;
						}

						quizStatus.loadLock = 0;

						if (quizStatus.isQuizStart) {
							plugin.methode.startQuiz();
						}
					}
				);
			},

			loadQuizData() {
				plugin.methode.ajax(
					{
						action: 'wp_pro_quiz_load_quiz_data',
						quizId: config.quizId,
						quiz_nonce: config.quiz_nonce,
					},
					function (json) {
						if (json.toplist) {
							plugin.methode.handleToplistData(json.toplist);
						}

						if (json.averageResult != undefined) {
							plugin.methode.setAverageResult(
								json.averageResult,
								true
							);
						}
					}
				);
			},

			setAverageResult(p, g) {
				const v = $e.find(
					'.wpProQuiz_resultValue:eq(' + (g ? 0 : 1) + ') > * '
				);

				v.eq(1).text(p + '%');
				v.eq(0).css('width', (240 * p) / 100 + 'px');
			},

			handleToplistData(json) {
				const $tp = $e.find('.wpProQuiz_addToplist');
				const $addBox = $tp
					.find('.wpProQuiz_addBox')
					.show()
					.children('div');

				if (json.canAdd) {
					$tp.show();
					$tp.find('.wpProQuiz_addToplistMessage').hide();
					$tp.find('.wpProQuiz_toplistButton').show();

					toplistData.token = json.token;
					toplistData.isUser = 0;

					if (json.userId) {
						$addBox.hide();
						toplistData.isUser = 1;

						if (bitOptions.isAddAutomatic) {
							$tp.hide();
						}
					} else {
						$addBox.show();

						const $captcha = $addBox.children().eq(1);

						if (json.captcha) {
							$captcha
								.find('input[name="wpProQuiz_captchaPrefix"]')
								.val(json.captcha.code);
							$captcha
								.find('.wpProQuiz_captchaImg')
								.attr('src', json.captcha.img);
							$captcha
								.find('input[name="wpProQuiz_captcha"]')
								.val('');

							$captcha.show();
						} else {
							$captcha.hide();
						}
					}
				} else {
					$tp.hide();
				}
			},

			scrollTo(e, h) {
				const x = e.offset().top - 100;

				if (h || (window.pageYOffset || document.body.scrollTop) > x) {
					$('html,body').clearQueue().animate({ scrollTop: x }, 300);
				}
			},

			addToplist() {
				if (bitOptions.preview) {
					return;
				}

				const $addToplistMessage = $e
					.find('.wpProQuiz_addToplistMessage')
					.text(WpProQuizGlobal.loadData)
					.show();
				const $addBox = $e.find('.wpProQuiz_addBox').hide();

				plugin.methode.ajax(
					{
						action: 'wp_pro_quiz_add_toplist',
						quizId: config.quizId,
						quiz: config.quiz,
						quiz_nonce: config.quiz_nonce,
						token: toplistData.token,
						name: $addBox
							.find('input[name="wpProQuiz_toplistName"]')
							.val(),
						email: $addBox
							.find('input[name="wpProQuiz_toplistEmail"]')
							.val(),
						captcha: $addBox
							.find('input[name="wpProQuiz_captcha"]')
							.val(),
						prefix: $addBox
							.find('input[name="wpProQuiz_captchaPrefix"]')
							.val(),
						//points: 99, //results.comp.points, // LD v2.4.3 Calculated on server
						results: JSON.stringify(results),
						//p_nonce: results.comp.p_nonce, // LD v2.4.3 Calculated on server
						//totalPoints:config.globalPoints, // LD v2.4.3 Calculated on server
						timespent,
					},
					function (json) {
						$addToplistMessage.text(json.text);

						if (json.clear) {
							$addBox.hide();
							plugin.methode.updateToplist();
						} else {
							$addBox.show();
						}

						if (json.captcha) {
							$addBox
								.find('.wpProQuiz_captchaImg')
								.attr('src', json.captcha.img);
							$addBox
								.find('input[name="wpProQuiz_captchaPrefix"]')
								.val(json.captcha.code);
							$addBox
								.find('input[name="wpProQuiz_captcha"]')
								.val('');
						}
					}
				);
			},

			updateToplist() {
				if (typeof wpProQuiz_fetchToplist === 'function') {
					wpProQuiz_fetchToplist();
				}
			},

			registerSolved() {
				// Free Input field
				$e.find('.wpProQuiz_questionInput[type="text"]').on(
					'change',
					function (e) {
						const $this = $(this);
						const $p = $this.parents('.wpProQuiz_listItem');
						let s = false;

						if ($this.val() != '') {
							s = true;
						}

						$e.trigger({
							type: 'questionSolved',
							values: { item: $p, index: $p.index(), solved: s },
						});
					}
				);

				// Single Choice field
				$e.find(
					'.wpProQuiz_questionList[data-type="single"] .wpProQuiz_questionInput, .wpProQuiz_questionList[data-type="assessment_answer"] .wpProQuiz_questionInput'
				).on('change', function (e) {
					const $this = $(this);
					const $p = $this.parents('.wpProQuiz_listItem');
					const s = this.checked;

					$e.trigger({
						type: 'questionSolved',
						values: { item: $p, index: $p.index(), solved: s },
					});
				});

				// Cloze field
				$e.find('.wpProQuiz_cloze input').on('change', function () {
					const $this = $(this);
					const $p = $this.parents('.wpProQuiz_listItem');
					let s = true;

					$p.find('.wpProQuiz_cloze input').each(function () {
						if ($(this).val() == '') {
							s = false;
							return false;
						}
					});

					$e.trigger({
						type: 'questionSolved',
						values: { item: $p, index: $p.index(), solved: s },
					});
				});

				// ?? field
				$e.find(
					'.wpProQuiz_questionList[data-type="multiple"] .wpProQuiz_questionInput'
				).on('change', function (e) {
					const $this = $(this);
					const $p = $this.parents('.wpProQuiz_listItem');
					let c = 0;

					$p.find(
						'.wpProQuiz_questionList[data-type="multiple"] .wpProQuiz_questionInput'
					).each(function (e) {
						if (this.checked) {
							c++;
						}
					});

					$e.trigger({
						type: 'questionSolved',
						values: {
							item: $p,
							index: $p.index(),
							solved: c ? true : false,
						},
					});
				});

				// Essay textarea field. For Essay file uploads look into uploadFile() function
				$e.find(
					'.wpProQuiz_questionList[data-type="essay"] textarea.wpProQuiz_questionEssay'
				).on('change', function (e) {
					const $this = $(this);
					const $p = $this.parents('.wpProQuiz_listItem');
					let s = false;

					if ($this.val() != '') {
						s = true;
					}
					$e.trigger({
						type: 'questionSolved',
						values: { item: $p, index: $p.index(), solved: s },
					});
				});
			},

			loadQuizDataAjax(quizStart) {
				plugin.methode.ajax(
					{
						action: 'wp_pro_quiz_admin_ajax_load_data',
						func: 'quizLoadData',
						data: {
							quizId: config.quizId,
							quiz: config.quiz,
							quiz_nonce: config.quiz_nonce,
						},
					},
					function (json) {
						config.globalPoints = json.globalPoints;
						config.catPoints = json.catPoints;
						config.json = json.json;

						globalElements.quiz.remove();

						$e.find('.wpProQuiz_quizAnker').after(json.content);

						$(
							'table.wpProQuiz_toplistTable caption span.wpProQuiz_max_points'
						).html(config.globalPoints);

						//Reinit globalElements
						globalElements = {
							back: $e.find('input[name="back"]'),
							next: $e.find(globalNames.next),
							quiz: $e.find('.wpProQuiz_quiz'),
							questionList: $e.find('.wpProQuiz_list'),
							results: $e.find('.wpProQuiz_results'),
							sending: $e.find('.wpProQuiz_sending'),
							quizStartPage: $e.find('.wpProQuiz_text'),
							timelimit: $e.find('.wpProQuiz_time_limit'),
							toplistShowInButton: $e.find(
								'.wpProQuiz_toplistShowInButton'
							),
							listItems: $(),
						};

						plugin.methode.initQuiz();

						if (quizStart) {
							plugin.methode.startQuiz(true);
						}

						// load script to show player for ajax content
						const data = json.content;
						const audiotag = data.search('wp-audio-shortcode');
						const videotag = data.search('wp-video-shortcode');
						if (audiotag != '-1' || videotag != '-1') {
							$.getScript(
								json.site_url +
									'/wp-includes/js/mediaelement/mediaelement-and-player.min.js'
							);
							$.getScript(
								json.site_url +
									'/wp-includes/js/mediaelement/wp-mediaelement.js'
							);
							$('<link/>', {
								rel: 'stylesheet',
								type: 'text/css',
								href:
									json.site_url +
									'/wp-includes/js/mediaelement/mediaelementplayer.min.css',
							}).appendTo('head');
						}
					}
				);
			},
			nextQuestionClicked() {
				const $questionList = currentQuestion.find(
					globalNames.questionList
				);
				const data = config.json[$questionList.data('question_id')];

				// Within the following logic. If the question type is 'sort_answer' there is a chance
				// the sortable answers will be displayed in the correct order. In that case the user will click
				// the next button.
				// The trigger to set the question was answered is normally a function of the sort/drag action
				// by the user. So we need to set the question answered flag in the case the Quiz summary is enabled.
				//if (data.type == 'sort_answer') {
				//	var question_index = currentQuestion.index();
				//	if ( typeof quizSolved[question_index] === 'undefined') {
				//		$e.trigger({type: 'questionSolved', values: {item: currentQuestion, index: question_index, solved: true}});
				//	}
				//}

				if (
					bitOptions.forcingQuestionSolve &&
					!quizSolved[currentQuestion.index()] &&
					(bitOptions.quizSummeryHide || !bitOptions.reviewQustion)
				) {
					// Would really like to do something more stylized instead of a simple alert popup. yuk!
					alert(WpProQuizGlobal.questionNotSolved);
					return false;
				}

				plugin.methode.nextQuestion();
			},
			initQuiz() {
				//if (config.ld_script_debug == true) {
				//	console.log('in initQuiz');
				//}

				//plugin.methode.setClozeStyle();
				plugin.methode.registerSolved();

				globalElements.next.on(
					'click',
					plugin.methode.nextQuestionClicked
				);

				globalElements.back.on('click', function (e) {
					//console.log('back button clicked [%o]', e);
					plugin.methode.prevQuestion();
				});

				$e.find(globalNames.check).on('click', function () {
					if (
						bitOptions.forcingQuestionSolve &&
						!quizSolved[currentQuestion.index()]
					) {
						alert(WpProQuizGlobal.questionNotSolved);
						return false;
					}
					plugin.methode.checkQuestion();
				});

				$e.find('input[name="checkSingle"]').on('click', function () {
					// First get all the questions...
					const list = globalElements.questionList.children();
					if (list != null) {
						list.each(function () {
							const $this = $(this);
							const $questionList = $this.find(
								globalNames.questionList
							);
							const question_id =
								$questionList.data('question_id');
							const data =
								config.json[$questionList.data('question_id')];

							// Within the following logic. If the question type is 'sort_answer' there is a chance
							// the sortable answers will be displayed in the correct order. In that case the user will click
							// the next button.
							// The trigger to set the question was answered is normally a function of the sort/drag action
							// by the user. So we need to set the question answered flag in the case the Quiz summary is enabled.
							//if (data.type == 'sort_answer') {
							//	//var question_index = $this.index();
							//	//if ( typeof quizSolved[question_index] === 'undefined') {
							//		$e.trigger({type: 'questionSolved', values: {item: $this, index: $this.index(), solved: true}});
							//	//}
							//}
						});
					}

					if (
						bitOptions.forcingQuestionSolve &&
						(bitOptions.quizSummeryHide ||
							!bitOptions.reviewQustion)
					) {
						for (
							let i = 0,
								c = $e.find('.wpProQuiz_listItem').length;
							i < c;
							i++
						) {
							if (!quizSolved[i]) {
								alert(WpProQuizGlobal.questionsNotSolved);
								return false;
							}
						}
					}

					plugin.methode.showQuizSummary();
				});

				$e.find('input[name="tip"]').on(
					'click',
					plugin.methode.showTip
				);
				$e.find('input[name="skip"]').on(
					'click',
					plugin.methode.skipQuestion
				);

				$e.find('input[name="wpProQuiz_pageLeft"]').on(
					'click',
					function () {
						plugin.methode.showSinglePage(currentPage - 1);
						plugin.methode.setupMatrixSortHeights();
					}
				);

				$e.find('input[name="wpProQuiz_pageRight"]').on(
					'click',
					function () {
						plugin.methode.showSinglePage(currentPage + 1);

						plugin.methode.setupMatrixSortHeights();
					}
				);

				$e.find('input[id^="uploadEssaySubmit"]').on(
					'click',
					plugin.methode.uploadFile
				);

				// Added in LD v2.4 to allow external notification when quiz init happens.
				$e.trigger('learndash-quiz-init');
			},
			// Setup the Cookie specific to the Quiz ID.
			CookieInit() {
				// Comment out to force clear cookie on init.
				// jQuery.cookie(cookie_name, '');

				if (config.ld_script_debug == true) {
					console.log('in CookieInit');
				}

				cookie_value = jQuery.cookie(cookie_name);

				if (
					!cookie_value ||
					cookie_value == undefined ||
					cookie_value === '%7B%7D'
				) {
					cookie_value = {};
				} else {
					try {
						cookie_value = JSON.parse(cookie_value);
						//console.log("cookie_value[%o]", cookie_value );
					} catch (exception) {
						console.log('JSON.parse error [%o]', exception);
						cookie_value = {};
					}
				}

				if (config.ld_script_debug == true) {
					console.log('after parse: cookie_value[%o]', cookie_value);
				}

				// If we have form entries at the start, save them to cookie.
				if (
					bitOptions.formActivated &&
					config.formPos === formPosConst.START &&
					formClass.checkForm()
				) {
					formClass.saveFormDataToCookie();
				}

				plugin.methode.CookieSetResponses();
				plugin.methode.CookieResponseTimer();
			},
			CookieDelete() {
				//if (config.ld_script_debug == true) {
				//	console.log('CookieDelete: config.timelimitcookie[%o]', config.timelimitcookie);
				//}
				//if (config.ld_script_debug == true) {
				//	console.log('CookieDelete: cookie_name[%o]', cookie_name);
				//}
				jQuery.cookie(cookie_name, '');
			},
			CookieProcessQuestionResponse(list, lockQuestion) {
				var lockQuestion = lockQuestion || false;
				if (list != null) {
					list.each(function () {
						const $this = $(this);

						const question_index = $this.index();
						const $questionList = $this.find(
							globalNames.questionList
						);
						const question_id = $questionList.data('question_id');
						const data =
							config.json[$questionList.data('question_id')];
						let name = data.type;
						if (data.type == 'single' || data.type == 'multiple') {
							name = 'singleMulti';
						}

						const question_response = readResponses(
							name,
							data,
							$this,
							$questionList,
							false
						);

						plugin.methode.saveMetaDataToCookie({
							lastQuestion: question_id,
						});
						plugin.methode.CookieSaveResponse(
							question_id,
							question_index,
							data.type,
							question_response,
							lockQuestion
						);
					});
				}
			},
			// Save the answer(response) to the cookie. This is called from 'checkQuestion' and cookie timer functions.
			CookieSaveResponse(
				question_id,
				question_index,
				question_type,
				question_response,
				lockQuestion
			) {
				var lockQuestion = lockQuestion || false;
				// set the value
				if (question_id === 'formData' + question_index) {
					cookie_value[question_id] = {
						index: question_index,
						value: question_response,
						type: question_type,
					};
				} else {
					cookie_value[question_id] = {
						index: question_index,
						value: question_response.response,
						type: question_type,
						lockQuestion,
					};
				}

				// Calculate the cookie date to expire
				plugin.methode.calculateCookieExpiry();

				// store the values.
				jQuery.cookie(cookie_name, JSON.stringify(cookie_value), {
					expires: cookieExpireDate,
				});

				if (config.quiz_resume_enabled === '1' && !cookieSendTimer) {
					plugin.methode.startCookieSendTimer();
				}
			},
			calculateCookieExpiry() {
				cookieExpireDate = new Date();
				if (
					config.timelimitcookie &&
					!config.quiz_resume_cookie_expiration
				) {
					cookieExpireDate.setTime(
						cookieExpireDate.getTime() +
							config.timelimitcookie * 1000
					);
				}

				if (
					config.quiz_resume_cookie_expiration &&
					!config.timelimitcookie
				) {
					cookieExpireDate.setTime(
						cookieExpireDate.getTime() +
							config.quiz_resume_cookie_expiration * 1000
					);
				}
			},
			saveMetaDataToCookie(id, metadata) {
				var metadata = metadata || false;
				// Calculate the cookie date to expire.
				plugin.methode.calculateCookieExpiry();

				if (!cookie_value || cookie_value == undefined) {
					cookie_value = {};
				}

				if (typeof id === 'string' && typeof metadata === 'object') {
					cookie_value[id] = {};
					for (var element in metadata) {
						// @todo Test in IE11
						const obj = { [element]: metadata[element] };
						Object.assign(cookie_value[id], obj);
					}
				} else if (typeof id === 'string' && !metadata) {
					cookie_value[id] = true;
				} else if (typeof id === 'object') {
					for (var element in id) {
						cookie_value[element] = id[element];
					}
				}

				// store the values.
				jQuery.cookie(cookie_name, JSON.stringify(cookie_value), {
					expires: cookieExpireDate,
				});

				if (config.quiz_resume_enabled === '1' && !cookieSendTimer) {
					plugin.methode.startCookieSendTimer();
				}
			},
			// The cookie timer loops every 5 seconds to save the last response from the user.
			// This only effect Essay questions as there is some current logic where once
			// 'readResponses' is called the question is locked.
			CookieResponseTimer() {
				// Hook into the 'questionSolved' triggered event. This is much better than
				// a timer to grab the answer values. With the event trigger we only process the
				// single question when the user makes a change.
				$e.on('questionSolved', function (e) {
					//if (config.ld_script_debug == true) {
					//	console.log('CookieResponseTimer: e.values[%o]', e.values);
					//}
					if (
						config.timelimitcookie !== 0 ||
						config.quiz_resume_enabled === '1'
					) {
						plugin.methode.CookieProcessQuestionResponse(
							e.values.item
						);
					}
				});
			},
			// Load the Cookie (if present) and sets the values of the Quiz questions to the cookie saved value
			CookieSetResponses() {
				if (config.ld_script_debug == true) {
					console.log('In CookieSetResponses');
				}

				if (
					(cookie_value == undefined ||
						!Object.keys(cookie_value).length) &&
					!config.quiz_resume_id
				) {
					// console.log( 'returning' );
					return;
				}

				const list = globalElements.questionList.children();
				list.each(function () {
					const $this = $(this);

					const $questionList = $this.find(globalNames.questionList);
					const form_question_id = $questionList.data('question_id');

					// Handle server-side data
					if (quiz_resume_data[form_question_id] != undefined) {
						const quiz_resume_question_data =
							quiz_resume_data[form_question_id];
						var form_question_data =
							config.json[$questionList.data('question_id')];

						if (
							form_question_data.type ===
							quiz_resume_question_data.type
						) {
							setResponse(
								form_question_data,
								quiz_resume_question_data.value,
								$this,
								$questionList,
								quiz_resume_question_data.lockQuestion
							);
						}
					}
					// Handle cookie data
					if (cookie_value[form_question_id] != undefined) {
						const cookie_question_data =
							cookie_value[form_question_id];

						var form_question_data =
							config.json[$questionList.data('question_id')];
						if (
							form_question_data.type ===
							cookie_question_data.type
						) {
							setResponse(
								form_question_data,
								cookie_question_data.value,
								$this,
								$questionList,
								cookie_question_data.lockQuestion
							);
						}
					}

					// Move to next unanswered question
					if (
						typeof quiz_resume_data !== 'undefined' &&
						typeof cookie_value !== 'undefined'
					) {
						if (config.ld_script_debug == true) {
							console.log(
								"CookieSet: cookie_value['nextQuestion'][%o]",
								cookie_value.nextQuestion
							);
							console.log(
								'CookieSet: quiz_resume_data[%o]',
								quiz_resume_data
							);
						}
						if (
							typeof cookie_value.nextQuestion !== 'undefined' &&
							cookie_value.nextQuestion
						) {
							//console.log('cookie_value[%o]', cookie_value);
							plugin.methode.moveToNextUnansweredQuestion(
								cookie_value
							);
						} else {
							//console.log('quiz_resume_data[%o]', quiz_resume_data);
							plugin.methode.moveToNextUnansweredQuestion(
								quiz_resume_data
							);
						}
					}
				});
			},
			startCookieSendTimer() {
				// Immediately send the first cookie entry to the server
				// to trigger the Continue Quiz logic when no server-side
				// data exists.
				if (
					!cookieSendTimer &&
					(undefined === quiz_resume_data ||
						quiz_resume_data.length === 0)
				) {
					plugin.methode.prepareSendCookieData();
				}
				// We only need to start the timer once per page load.
				cookieSendTimer = true;

				// Start the Timer
				setInterval(function () {
					if (!cookieSending) {
						plugin.methode.prepareSendCookieData();
					}
				}, config.quiz_resume_cookie_send_timer * 1000);
			},
			prepareSendCookieData() {
				// Get Timelimit cookie data.
				if (config.timelimit) {
					plugin.methode.addTimelimitCookieData();
				}

				const cookieLength =
					plugin.methode.getObjectLength(cookie_value);
				if (cookieLength > 0) {
					const cookieKeys =
						plugin.methode.getObjectKeys(cookie_value);

					// Flag we are sending an ajax request.
					cookieSending = true;
					plugin.methode.sendCookieData(cookieKeys);
				}
			},
			getObjectLength(object) {
				return Object.keys(object).length;
			},
			getObjectKeys(object) {
				return Object.keys(object);
			},
			excludeKeysFromCount(object, exclude) {
				const allKeys = plugin.methode.getObjectKeys(object);
				let count = 0;
				allKeys.forEach(function (key) {
					if (!key.startsWith(exclude)) {
						count++;
					}
				});
				return count;
			},
			addTimelimitCookieData() {
				const quizTimelimitCookie = jQuery.cookie(
					timelimit.timer_cookie
				);
				if ('undefined' !== typeof quizTimelimitCookie) {
					plugin.methode.saveMetaDataToCookie({
						[timelimit.timer_cookie]:
							JSON.parse(quizTimelimitCookie),
					});
				}
			},
			moveToNextUnansweredQuestion(data) {
				if (config.ld_script_debug == true) {
					console.log('moveToNextUnansweredQuestion: data[%o]', data);
				}

				if (typeof data !== 'undefined') {
					let nextQuestion =
						typeof data.nextQuestion === 'number'
							? data.nextQuestion
							: 0;
					const lastQuestion =
						typeof data.lastQuestion === 'number'
							? data.lastQuestion
							: 0;

					if (config.ld_script_debug == true) {
						console.log(
							'moveToNextUnansweredQuestion: nextQuestion[%o]',
							nextQuestion
						);
						console.log(
							'moveToNextUnansweredQuestion: lastQuestion[%o]',
							lastQuestion
						);
						console.log(
							'moveToNextUnansweredQuestion: config.mode[%o]',
							config.mode
						);
					}

					if (config.mode === 3) {
						if (!config.qpp) {
							jQuery(globalElements.questionList)
								.children()
								.show();
							jQuery(globalElements.listItems).each(function (
								index,
								listItem
							) {
								if (
									$(listItem).data('question-meta')
										.question_pro_id === lastQuestion
								) {
									plugin.methode.scrollTo(
										globalElements.listItems.eq(index),
										1
									);
								}
							});
						} else {
							jQuery(globalElements.listItems).each(function (
								index,
								listItem
							) {
								if (
									$(listItem).data('question-meta')
										.question_pro_id === lastQuestion
								) {
									plugin.methode.singlePageNavigationQuizResume(
										index
									);
								}
							});
						}
					} else {
						if (config.mode == 1 && nextQuestion > 0) {
							nextQuestion = lastQuestion;
						} else if (nextQuestion == 0 && lastQuestion > 0) {
							nextQuestion = lastQuestion;
						}
						jQuery(globalElements.listItems).each(function (
							index,
							listItem
						) {
							if (
								$(listItem).data('question-meta')
									.question_pro_id === nextQuestion
							) {
								if (config.ld_script_debug == true) {
									console.log(
										'moveToNextUnansweredQuestion: match: listItem[%o]',
										listItem
									);
								}

								currentQuestion =
									globalElements.listItems.eq(index);

								const questionId = currentQuestion
									.find(globalNames.questionList)
									.data('question_id');

								questionTimer.questionStart(questionId);
								plugin.methode.showQuestionObject(
									currentQuestion
								);

								return false; // break out of the loop.
							}
							// if (config.ld_script_debug == true) {
							// 	console.log("moveToNextUnansweredQuestion: not match: listItem[%o]", listItem);
							// }
						});
					}
				}
			},
			// Variation of showSinglePage()
			singlePageNavigationQuizResume(index) {
				const answeredQuestions = config.qpp >= 1 ? index : index + 1;
				const maxPage = Math.ceil(
					$e.find('.wpProQuiz_list > li').length / config.qpp
				);
				const activePage = Math.ceil(answeredQuestions / config.qpp);

				if (activePage <= maxPage) {
					const $listItem = globalElements.questionList
						.children()
						.hide();
					const pl = $e.find(globalNames.singlePageLeft).hide();
					const pr = $e.find(globalNames.singlePageRight).hide();
					const cs = $e.find('input[name="checkSingle"]').hide();

					if (activePage > 1) {
						pl.val(
							pl.data('text').replace(/%d/, activePage - 1)
						).show();
					}

					if (activePage === maxPage) {
						cs.show();
					} else {
						pr.val(
							pr.data('text').replace(/%d/, activePage + 1)
						).show();
					}

					currentPage = activePage;

					const start = config.qpp * (activePage - 1);

					$listItem.slice(start, start + config.qpp).show();
					if (globalElements.listItems.length === index) {
						index = index - 1;
					}
					plugin.methode.scrollTo(
						globalElements.listItems.eq(index),
						0
					);
				}
			},
			sendCookieData(keys) {
				jQuery.ajax({
					type: 'POST',
					url: WpProQuizGlobal.ajaxurl,
					dataType: 'json',
					cache: false,
					data: {
						action: 'wp_pro_quiz_cookie_save_quiz',
						course_id: config.course_id,
						quiz: config.quiz,
						quizId: config.quizId,
						quiz_started: questionTimer.getQuizStart(),
						results: JSON.stringify(cookie_value),
						quiz_nonce: config.quiz_nonce,
					},
					success(response) {
						if (response.success) {
							if (
								plugin.methode.compareObjectKeys(
									response.data,
									cookie_value
								)
							) {
								plugin.methode.deleteCookieKeys(keys);
							}
							cookieSending = false;
						}
					},
					error(xhr) {
						console.log('xhr[%o]', xhr);
						const response = JSON.parse(xhr.responseText);
						console.log(response.data.message);
						cookieSending = false;
					},
				});
			},
			compareObjectKeys(a, b) {
				const keysObjectA = Object.keys(a).sort();
				const keysObjectB = Object.keys(b).sort();
				return (
					JSON.stringify(keysObjectA) === JSON.stringify(keysObjectB)
				);
			},
			deleteCookieKeys(keys) {
				// Remove saved keys from cookie object
				keys.forEach(function (key) {
					delete cookie_value[key];
				});

				// Write changed cookie object to cookie
				jQuery.cookie(cookie_name, JSON.stringify(cookie_value), {
					expires: cookieExpireDate,
				});
			},
			setupMatrixSortHeights() {
				/**
				 * Here we have to do all the questions because the current logic when using X questions
				 * per page doesn't allow that information.
				 */
				$('li.wpProQuiz_listItem', globalElements.questionList).each(
					function (idx, questionItem) {
						const question_type = $(questionItem).data('type');
						if ('matrix_sort_answer' === question_type) {
							// On the draggable items get the items max height and set the parent ul to that.
							let sortitems_height = 0;
							$(
								'ul.wpProQuiz_sortStringList li',
								questionItem
							).each(function (idx, el) {
								const el_height = $(el).outerHeight();
								if (el_height > sortitems_height) {
									sortitems_height = el_height;
								}
							});
							if (sortitems_height > 0) {
								$(
									'ul.wpProQuiz_sortStringList',
									questionItem
								).css('min-height', sortitems_height);
							}

							$(
								'ul.wpProQuiz_maxtrixSortCriterion',
								questionItem
							).each(function (idx, el) {
								const parent_td = $(el).parent('td');
								if (typeof parent_td !== 'undefined') {
									const parent_td_height =
										$(parent_td).height();
									if (parent_td_height) {
										$(el).css('height', parent_td_height);
										$(el).css(
											'min-height',
											parent_td_height
										);
									}
								}
							});
						}
					}
				);
			},
		};

		/**
		 * @memberOf plugin
		 */
		plugin.preInit = function () {
			plugin.methode.parseBitOptions();
			reviewBox.init();

			$e.find('input[name="startQuiz"]').on('click', function () {
				plugin.methode.startQuiz();
				return false;
			});

			if (bitOptions.checkBeforeStart && !bitOptions.preview) {
				plugin.methode.checkQuizLock();
			}

			$e.find('input[name="reShowQuestion"]').on('click', function () {
				plugin.methode.showQustionList();
			});

			$e.find('input[name="restartQuiz"]').on('click', function () {
				plugin.methode.restartQuiz();
			});

			$e.find('input[name="review"]').on(
				'click',
				plugin.methode.reviewQuestion
			);

			$e.find('input[name="wpProQuiz_toplistAdd"]').on(
				'click',
				plugin.methode.addToplist
			);

			$e.find('input[name="quizSummary"]').on(
				'click',
				plugin.methode.showQuizSummary
			);

			$e.find('input[name="endQuizSummary"]').on('click', function () {
				if (bitOptions.forcingQuestionSolve) {
					// First get all the questions...
					list = globalElements.questionList.children();
					if (list != null) {
						list.each(function () {
							const $this = $(this);
							const $questionList = $this.find(
								globalNames.questionList
							);
							const question_id =
								$questionList.data('question_id');
							const data =
								config.json[$questionList.data('question_id')];

							// Within the following logic. If the question type is 'sort_answer' there is a chance
							// the sortable answers will be displayed in the correct order. In that case the user will click
							// the next button.
							// The trigger to set the question was answered is normally a function of the sort/drag action
							// by the user. So we need to set the question answered flag in the case the Quiz summary is enabled.
							//if (data.type == 'sort_answer') {
							//	var question_index = $this.index();
							//	if ( typeof quizSolved[question_index] === 'undefined') {
							//		$e.trigger({type: 'questionSolved', values: {item: $this, index: question_index, solved: true}});
							//	}
							//}
						});
					}

					for (
						let i = 0, c = $e.find('.wpProQuiz_listItem').length;
						i < c;
						i++
					) {
						if (!quizSolved[i]) {
							alert(WpProQuizGlobal.questionsNotSolved);
							return false;
						}
					}
				}

				if (
					bitOptions.formActivated &&
					config.formPos == formPosConst.END &&
					!formClass.checkForm()
				) {
					return;
				}

				plugin.methode.finishQuiz();
			});

			$e.find('input[name="endInfopage"]').on('click', function () {
				if (formClass.checkForm()) {
					plugin.methode.finishQuiz();
				}
			});

			$e.find('input[name="showToplist"]').on('click', function () {
				globalElements.quiz.hide();
				globalElements.toplistShowInButton.toggle();
			});

			$e.on('questionSolved', plugin.methode.questionSolved);

			if (!bitOptions.maxShowQuestion) {
				plugin.methode.initQuiz();
			}

			if (bitOptions.autoStart) {
				plugin.methode.startQuiz();
			}

			// If the form is shown before the quiz, we need to check if we have saved data.
			if (
				bitOptions.formActivated &&
				config.formPos === formPosConst.START
			) {
				formClass.setFormDataFromServer();
			}
		};

		plugin.preInit();
	};

	$.fn.wpProQuizFront = function (options) {
		return this.each(function () {
			if (undefined == $(this).data('wpProQuizFront')) {
				$(this).data(
					'wpProQuizFront',
					new $.wpProQuizFront(this, options)
				);
			}
		});
	};
})(jQuery);

var learndash_prepare_quiz_resume_data = function (config) {
	if (config.quiz_resume_enabled === 'undefined') {
		return false;
	}
	if (config.quiz_resume_enabled !== '1') {
		return false;
	}

	if (config.quiz_resume_data === 'undefined') {
		return false;
	}

	let resume_data = {};
	if (config.ld_script_debug == true) {
		console.log(
			'config.quiz_resume_data (raw)[%o]',
			config.quiz_resume_data
		);
	}
	try {
		resume_data = JSON.parse(config.quiz_resume_data);
		if (config.ld_script_debug == true) {
			console.log('resume_data (parsed)[%o]', resume_data);
		}
	} catch (exception) {
		console.log('JSON.parse error [%o]', exception);
	}

	return resume_data;
};

var learndash_decodeHTML = function (html) {
	html = html || '';

	if (html.length > 0 && typeof html === 'string') {
		const txt = document.createElement('textarea');
		txt.innerHTML = html;

		html = txt.value;
	}

	return html;
};
