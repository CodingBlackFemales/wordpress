<?php

namespace WPForms\Pro\Forms\Fields\CustomCaptcha;

/**
 * Custom Captcha field.
 *
 * @since 1.8.7
 */
class Field extends \WPForms_Field {

	/**
	 * The field type.
	 *
	 * @since 1.8.7
	 */
	const TYPE = 'captcha';

	/**
	 * Min & max values to participate in equation and operators.
	 *
	 * @since 1.8.7
	 *
	 * @var array
	 */
	public $math;

	/**
	 * Questions to ask.
	 *
	 * @since 1.8.7
	 *
	 * @var array
	 */
	private $qs;

	/**
	 *
	 * Init class.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Custom Captcha', 'wpforms' );
		$this->keywords = esc_html__( 'spam, math, maths, question', 'wpforms' );
		$this->type     = self::TYPE;
		$this->icon     = 'fa-question-circle';
		$this->order    = 300;
		$this->group    = 'fancy';
		$this->qs       = [
			1 => [
				'question' => esc_html__( 'What is 7+4?', 'wpforms' ),
				'answer'   => esc_html__( '11', 'wpforms' ),
			],
		];
		$this->math     = [
			'min' => 1,
			'max' => 15,
			'cal' => [ '+', '*' ],
		];

		$this->init_objects();
		$this->hooks();
	}

	/**
	 * Initialize sub-objects.
	 *
	 * @since 1.8.7
	 */
	private function init_objects() {

		$is_ajax = wp_doing_ajax();

		if ( $is_ajax || wpforms_is_admin_page( 'builder' ) || wpforms_is_admin_page( 'entries' ) ) {
			( new Builder() )->init( $this );
		}

		if ( $is_ajax || ! is_admin() ) {
			( new Frontend() )->init( $this );
		}
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.7
	 */
	private function hooks() {

		// Apply wpforms_math_captcha filters when theme functions.php is loaded.
		add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ] );

		// Adjust the notice message for deactivating the addon.
		add_filter( 'wpforms_requirements_notice', [ $this, 'get_addon_deactivation_notice' ], 10, 3 );
	}

	/**
	 * Run when theme functions.php is loaded.
	 *
	 * @since 1.8.7
	 */
	public function after_setup_theme() {

		// phpcs:disable WPForms.PHP.ValidateHooks.InvalidHookName
		/**
		 * Allow modifying setting properties applied to the Custom Captcha field.
		 *
		 * @since 1.8.7
		 *
		 * @param array $math Minimum integer, maximum integer, and operators that will be used.
		 */
		$this->math = (array) apply_filters( 'wpforms_math_captcha', $this->math );
		// phpcs:enable WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Whether current field can be populated dynamically.
	 *
	 * @since 1.8.7
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Whether current field can be populated dynamically.
	 *
	 * @since 1.8.7
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.8.7
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {

		// Defaults.
		$format = ! empty( $field['format'] ) ? esc_attr( $field['format'] ) : 'math';
		$qs     = ! empty( $field['questions'] ) ? $field['questions'] : $this->qs;
		$qs     = array_filter( $qs );

		// Field is always required.
		$this->field_element(
			'text',
			$field,
			[
				'type'  => 'hidden',
				'slug'  => 'required',
				'value' => '1',
			]
		);

		/*
		 * Basic field options.
		 */

		// Options open markup.
		$this->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		// Label.
		$this->field_option( 'label', $field );

		// Format.
		$lbl = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'format',
				'value'   => esc_html__( 'Type', 'wpforms' ),
				'tooltip' => esc_html__( 'Select type of captcha to use.', 'wpforms' ),
			],
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			[
				'slug'    => 'format',
				'value'   => $format,
				'options' => [
					'math' => esc_html__( 'Math', 'wpforms' ),
					'qa'   => esc_html__( 'Question and Answer', 'wpforms' ),
				],
			],
			false
		);

		$this->field_element(
			'row',
			$field,
			[
				'slug'    => 'format',
				'content' => $lbl . $fld,
			]
		);

		// Questions.
		$lbl = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'questions',
				'value'   => esc_html__( 'Questions and Answers', 'wpforms' ),
				'tooltip' => esc_html__( 'Add questions to ask the user. Questions are randomly selected.', 'wpforms' ),
			],
			false
		);
		$fld = sprintf(
			'<ul data-next-id="%s" data-field-id="%d" data-field-type="%s" class="choices-list">',
			max( array_keys( $qs ) ) + 1,
			esc_attr( $field['id'] ),
			esc_attr( $this->type )
		);

		foreach ( $qs as $key => $value ) {
			$fld .= '<li data-key="' . absint( $key ) . '">';
			$fld .= sprintf(
				'<input type="text" name="fields[%1$d][questions][%2$s][question]" value="%3$s" data-prev-value="%3$s" class="question" placeholder="%4$s">',
				(int) $field['id'],
				esc_attr( $key ),
				esc_attr( $value['question'] ),
				esc_html__( 'Question', 'wpforms' )
			);
			$fld .= '<a class="add" href="#"><i class="fa fa-plus-circle"></i></a><a class="remove" href="#"><i class="fa fa-minus-circle"></i></a>';
			$fld .= sprintf(
				'<input type="text" name="fields[%d][questions][%s][answer]" value="%s" class="answer" placeholder="%s">',
				(int) $field['id'],
				esc_attr( $key ),
				esc_attr( $value['answer'] ),
				esc_html__( 'Answer', 'wpforms' )
			);
			$fld .= '</li>';
		}
		$fld .= '</ul>';

		$this->field_element(
			'row',
			$field,
			[
				'slug'    => 'questions',
				'content' => $lbl . $fld,
				'class'   => $format === 'math' ? 'wpforms-hidden' : '',
			]
		);

		// Description.
		$this->field_option( 'description', $field );

		// Options close markup.
		$this->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'close',
			]
		);

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$this->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		// Size.
		$this->field_option(
			'size',
			$field,
			[
				'class' => $format === 'math' ? 'wpforms-hidden' : '',
			]
		);

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Hide Label.
		$this->field_option( 'label_hide', $field );

		// Options close markup.
		$this->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'close',
			]
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.8.7
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$format      = ! empty( $field['format'] ) ? $field['format'] : 'math';
		$num1        = wp_rand( $this->math['min'], $this->math['max'] );
		$num2        = wp_rand( $this->math['min'], $this->math['max'] );
		$cal         = $this->math['cal'][ wp_rand( 0, count( $this->math['cal'] ) - 1 ) ];
		$questions   = ! empty( $field['questions'] ) ? $field['questions'] : $this->qs;

		// Label.
		$this->field_preview_option( 'label', $field );

		$first_question = array_shift( $questions );
		?>

		<div class="format-selected-<?php echo esc_attr( $format ); ?> format-selected">

			<span class="wpforms-equation"><?php echo esc_html( "$num1 $cal $num2 = " ); ?></span>

			<p class="wpforms-question"><?php echo wp_kses( $first_question['question'], wpforms_builder_preview_get_allowed_tags() ); ?></p>

			<input type="text" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="primary-input" readonly>

		</div>

		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.8.7
	 *
	 * @param array $field      Field settings.
	 * @param array $deprecated Deprecated array.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary                             = $field['properties']['inputs']['primary'];
		$format                              = $form_data['fields'][ $field['id'] ]['format'];
		$field_id                            = "wpforms-{$form_data['id']}-field_{$field['id']}";
		$desc_id                             = "{$field_id}-question";
		$primary['attr']['aria-describedby'] = empty( $primary['attr']['aria-describedby'] ) ? $desc_id : $primary['attr']['aria-describedby'] . ' ' . $desc_id;

		if ( empty( $this->math ) && $format === 'math' ) {
			return;
		}

		if ( empty( $form_data['fields'][ $field['id'] ]['questions'] ) && $format === 'qa' ) {
			return;
		}

		$format === 'math' ? $this->display_math_captcha( $field, $primary, $desc_id ) : $this->display_qa_captcha( $form_data, $field, $primary, $desc_id );
	}

	/**
	 * Display question and answer captcha.
	 *
	 * @since 1.8.7
	 *
	 * @param array  $form_data Form data and settings.
	 * @param array  $field     Field settings.
	 * @param array  $primary   Primary input settings.
	 * @param string $desc_id   Description ID.
	 */
	private function display_qa_captcha( $form_data, $field, $primary, $desc_id ) {

		// Back-compat: remove invalid questions with empty question or answer value.
		$form_data['fields'][ $field['id'] ]['questions'] = ! empty( $form_data['fields'][ $field['id'] ]['questions'] ) ?
		$this->remove_empty_questions( $form_data['fields'][ $field['id'] ]['questions'] ) :
			[];

		// Do not output the field if, for some reason, all questions have been filtered out as invalid.
		if ( empty( $form_data['fields'][ $field['id'] ]['questions'] ) ) {
			return;
		}

		// Question and Answer captcha.
		$qid = $this->random_question( $field, $form_data );
		$q   = $form_data['fields'][ $field['id'] ]['questions'][ $qid ]['question'];

		printf(
			'<p %s>%s</p>',
			wpforms_html_attributes( $desc_id, [ 'wpforms-captcha-question' ] ),
			esc_html( $q )
		);
		?>

		<?php
		$primary['data']['a'] = esc_attr( $form_data['fields'][ $field['id'] ]['questions'][ $qid ]['answer'] );

		printf(
			'<input type="text" %s %s>',
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
		?>

		<input type="hidden" name="wpforms[fields][<?php echo (int) $field['id']; ?>][q]" value="<?php echo esc_attr( $qid ); ?>">

		<?php
	}

	/**
	 * Display math captcha.
	 *
	 * @since 1.8.7
	 *
	 * @param array  $field   Field settings.
	 * @param array  $primary Primary input settings.
	 * @param string $desc_id Description ID.
	 */
	private function display_math_captcha( $field, $primary, $desc_id ) {
		?>
			<div class="wpforms-captcha-math">
				<span <?php echo wpforms_html_attributes( $desc_id, [ 'wpforms-captcha-equation' ] ); ?>>
					<?php

					if ( defined( 'REST_REQUEST' ) || is_admin() || wp_doing_ajax() ) {

						// Instead of outputting empty tags we can prefill them with random values.
						// This way we'll get correct visual appearance of the field even if JavaScript file wasn't loaded.
						// This is useful for displaying previews in Gutenberg and, potentially, in other page builders.
						printf(
							'<span class="n1">%1$s</span>
							<span class="cal">%2$s</span>
							<span class="n2">%3$s</span>',
							esc_html( wp_rand( $this->math['min'], $this->math['max'] ) ),
							esc_html( $this->math['cal'][ wp_rand( 0, count( $this->math['cal'] ) - 1 ) ] ),
							esc_html( wp_rand( $this->math['min'], $this->math['max'] ) )
						);

					} else {

						?>
						<span class="n1"></span>
						<span class="cal"></span>
						<span class="n2"></span>
						<?php

					}

					?>
					<span class="e">=</span>
				</span>
				<?php
				printf(
					'<input type="text" %s %s>',
					wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_attr( $primary['required'] )
				);
				?>
				<input type="hidden" name="wpforms[fields][<?php echo (int) $field['id']; ?>][cal]" class="cal">
				<input type="hidden" name="wpforms[fields][<?php echo (int) $field['id']; ?>][n2]" class="n2">
				<input type="hidden" name="wpforms[fields][<?php echo (int) $field['id']; ?>][n1]" class="n1">
			</div>
			<?php
	}

	/**
	 * Select a random question.
	 *
	 * @since 1.8.7
	 *
	 * @param array $field     Field settings.
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool|int
	 */
	private function random_question( $field, $form_data ) {

		if ( empty( $form_data['fields'][ $field['id'] ]['questions'] ) ) {
			return false;
		}

		$index = array_rand( $form_data['fields'][ $field['id'] ]['questions'] );

		if (
			! isset(
				$form_data['fields'][ $field['id'] ]['questions'][ $index ]['question'],
				$form_data['fields'][ $field['id'] ]['questions'][ $index ]['answer']
			)
		) {
			$index = $this->random_question( $field, $form_data );
		}

		return $index;
	}

	/**
	 * Validate field on form submit.
	 *
	 * @since 1.8.7
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		// Math captcha.
		if ( $form_data['fields'][ $field_id ]['format'] === 'math' ) {

			$this->validate_math( $field_id, $field_submit, $form_data );
		}

		if ( $form_data['fields'][ $field_id ]['format'] === 'qa' ) {

			$this->validate_qa( $field_id, $field_submit, $form_data );
		}
	}

	/**
	 * Validate question and answer captcha.
	 *
	 * @since 1.8.7
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	private function validate_qa( $field_id, $field_submit, $form_data ) {

		// All fields are required.
		if (
			! isset( $field_submit['q'], $field_submit['a'] ) ||
			(
				empty( $field_submit['q'] ) &&
				$field_submit['q'] !== '0'
			) || (
				empty( $field_submit['a'] ) &&
				$field_submit['a'] !== '0'
			)
		) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = wpforms_get_required_label();

			return;
		}

		if ( strtolower( trim( $field_submit['a'] ) ) !== strtolower( trim( $form_data['fields'][ $field_id ]['questions'][ $field_submit['q'] ]['answer'] ) ) ) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Incorrect answer', 'wpforms' );
		}
	}

	/**
	 * Validate math captcha.
	 *
	 * @since 1.8.7
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	private function validate_math( $field_id, $field_submit, $form_data ) {

		// All calculation fields are required.
		if (
			( empty( $field_submit['a'] ) && $field_submit['a'] !== '0' ) ||
			empty( $field_submit['n1'] ) ||
			empty( $field_submit['cal'] ) ||
			empty( $field_submit['n2'] )
		) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = wpforms_get_required_label();

			return;
		}

		$number_1 = absint( $field_submit['n1'] );
		$number_2 = absint( $field_submit['n2'] );
		$operator = $field_submit['cal'];
		$answer   = (int) trim( $field_submit['a'] );

		if ( ! in_array( $operator, [ '-', '+', '*' ], true ) ) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Incorrect operation', 'wpforms' );

			return;
		}

		$operations = [
			'+' => function ( $a, $b ) {
				return $a + $b;
			},
			'*' => function ( $a, $b ) {
				return $a * $b;
			},
			'-' => function ( $a, $b ) {
				return $a - $b;
			},
		];

		$calculated = $operations[ $operator ]( $number_1, $number_2 );

		if ( $calculated !== $answer ) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Incorrect answer', 'wpforms' );
		}
	}

	/**
	 * Remove invalid questions - with empty question and/or answer value.
	 *
	 * @since 1.8.7
	 *
	 * @param array $questions All questions and answers.
	 *
	 * @return array
	 */
	public function remove_empty_questions( $questions ): array {

		return array_filter(
			(array) $questions,
			static function ( $question ) {

				return isset( $question['question'], $question['answer'] ) &&
					! wpforms_is_empty_string( $question['question'] ) &&
					! wpforms_is_empty_string( $question['answer'] );
			}
		);
	}

	/**
	 * Retrieve the notice message for deactivating the addon.
	 *
	 * @since 1.8.7
	 *
	 * @param string|mixed $notice   Notice message.
	 * @param array        $errors   Errors array.
	 * @param string       $basename Basename of the plugin.
	 *
	 * @return string
	 */
	public function get_addon_deactivation_notice( $notice, array $errors, string $basename ): string {

		if ( ! ( $basename === 'wpforms-captcha/wpforms-captcha.php' && in_array( 'wpforms', $errors, true ) ) ) {
			return (string) $notice;
		}

		return sprintf(
			wp_kses( /* translators: %1$s - URL to the documentation. */
				__( 'WPForms 1.8.7 core includes Custom Captcha. The Custom Captcha addon will only work on WPForms 1.8.6 and earlier versions. <a href="%1$s" target="_blank" rel="noopener noreferrer">Learn More</a>', 'wpforms' ),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'rel'    => [],
					],
				]
			),
			esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-install-and-use-custom-captcha-addon-in-wpforms/', 'Admin Notice', 'Custom Captcha Documentation' ) )
		);
	}
}
