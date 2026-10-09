<?php
/**
 * The glossary entry list and edit screens.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Admin;

use CodingBlackFemales\SemanticGlossary\Assets;
use CodingBlackFemales\SemanticGlossary\Entry\Definition;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\Forms;
use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\Usage;
use WP_Post;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EntryScreen.
 *
 * Entries are edited on the classic screen with purpose-built fields: Term and
 * Abbreviation, alternative forms, a rich-text definition and the slug that
 * fixes the anchor. The post title and content are derived from those fields,
 * so list tables, search and revisions keep working.
 */
final class EntryScreen {

	/**
	 * Nonce action and field name.
	 */
	const NONCE = 'cbf_glossary_entry';

	/**
	 * Transient (per user) carrying a validation error across the redirect.
	 */
	const ERROR_TRANSIENT = 'cbf_glossary_entry_error_';

	/**
	 * Definition editor ID, also its field name.
	 */
	const DEFINITION_FIELD = 'glossary_definition';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'edit_form_after_title', array( __CLASS__, 'render_fields' ) );
		add_action( 'add_meta_boxes_' . PostType::NAME, array( __CLASS__, 'meta_boxes' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_first_used' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'filter_post_data' ), 10, 2 );
		add_action( 'save_post_' . PostType::NAME, array( __CLASS__, 'save_forms' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_error' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_plugins' ) );
		add_filter( 'manage_' . PostType::NAME . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . PostType::NAME . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}


	/**
	 * Term, abbreviation, slug, alternative forms and definition.
	 *
	 * @param WP_Post $post Entry being edited.
	 */
	public static function render_fields( WP_Post $post ): void {
		if ( $post->post_type !== PostType::NAME ) {
			return;
		}

		$entry = Repository::from_post( $post );
		$forms = $entry->forms !== array() ? $entry->forms : array(
			array(
				'term' => '',
				'abbr' => '',
			),
		);

		wp_nonce_field( self::NONCE, self::NONCE );

		include __DIR__ . '/views/entry-fields.php';
	}


	/**
	 * Swap the generic boxes for the "Used in" panel.
	 *
	 * @param WP_Post $post Entry being edited.
	 */
	public static function meta_boxes( WP_Post $post ): void {
		remove_meta_box( 'postcustom', PostType::NAME, 'normal' );
		remove_meta_box( 'slugdiv', PostType::NAME, 'normal' );

		$uses = Usage::uses_of( $post->ID );

		add_meta_box(
			'glossary-usage',
			/* translators: %d: number of posts */
			sprintf( _n( 'Used in %d post', 'Used in %d posts', count( $uses ), 'cbf-semantic-glossary' ), count( $uses ) ),
			array( __CLASS__, 'render_usage' ),
			PostType::NAME,
			'side',
			'default',
			array( 'uses' => $uses )
		);
	}


	/**
	 * The "Used in" panel: each post and the form it used.
	 *
	 * @param WP_Post                                $post Entry.
	 * @param array{args: array{uses: array<int, object>}} $box  Meta box arguments.
	 */
	public static function render_usage( WP_Post $post, array $box ): void {
		$entry = Repository::from_post( $post );
		$uses  = $box['args']['uses'];

		include __DIR__ . '/views/entry-usage.php';
	}


	/**
	 * "First used in", in the Publish panel.
	 *
	 * @param WP_Post $post Post being edited.
	 */
	public static function render_first_used( WP_Post $post ): void {
		if ( $post->post_type !== PostType::NAME ) {
			return;
		}

		$first = Usage::uses_of( $post->ID )[0] ?? null;
		$link  = $first !== null ? get_edit_post_link( (int) $first->post_id ) : '';
		?>
		<div class="misc-pub-section glossary-first-used">
			<?php esc_html_e( 'First used in:', 'cbf-semantic-glossary' ); ?>
			<?php if ( $first === null ) : ?>
				<strong><?php esc_html_e( 'not used yet', 'cbf-semantic-glossary' ); ?></strong>
			<?php else : ?>
				<a href="<?php echo esc_url( (string) $link ); ?>"><strong><?php echo esc_html( get_the_title( (int) $first->post_id ) ); ?></strong></a>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Derive the title, content and slug from the submitted fields.
	 *
	 * Runs inside wp_insert_post(), so there is no second save. A submission
	 * with no term keeps the previous title and reports the error.
	 *
	 * @param array<string, mixed> $data    Slashed post data.
	 * @param array<string, mixed> $postarr Raw submission.
	 * @return array<string, mixed>
	 */
	public static function filter_post_data( array $data, array $postarr ): array {
		if ( $data['post_type'] !== PostType::NAME || ! self::verified() ) {
			return $data;
		}

		$forms                = self::submitted_forms();
		$data['post_content'] = wp_slash( Definition::sanitise( self::posted( self::DEFINITION_FIELD ) ) );

		if ( $forms === array() ) {
			set_transient( self::ERROR_TRANSIENT . get_current_user_id(), __( 'A glossary entry needs a term. The term was not changed.', 'cbf-semantic-glossary' ), MINUTE_IN_SECONDS );
			return $data;
		}

		$slug               = sanitize_title( self::posted( 'glossary_slug' ) );
		$data['post_title'] = wp_slash( $forms[0]['term'] );
		$data['post_name']  = self::slug( $slug !== '' ? $slug : (string) $data['post_name'], $forms[0]['term'], (int) ( $postarr['ID'] ?? 0 ), (string) $data['post_status'] );

		return $data;
	}


	/**
	 * Store the submitted forms.
	 *
	 * @param int $post_id Entry ID.
	 */
	public static function save_forms( int $post_id ): void {
		if ( ! self::verified() || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$forms = self::submitted_forms();

		if ( $forms !== array() ) {
			update_post_meta( $post_id, PostType::FORMS_META, $forms );
		}

		Repository::flush();
	}


	/**
	 * Show a validation error carried across the redirect.
	 */
	public static function render_error(): void {
		$key   = self::ERROR_TRANSIENT . get_current_user_id();
		$error = get_transient( $key );

		if ( ! is_string( $error ) ) {
			return;
		}

		delete_transient( $key );
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
	}


	/**
	 * Load the edit screen's script and styles.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || get_current_screen()?->post_type !== PostType::NAME ) {
			return;
		}

		Assets::register_style( 'cbf-semantic-glossary-admin', 'css/admin/cbf-semantic-glossary-admin.css' );
		wp_enqueue_style( 'cbf-semantic-glossary-admin' );

		if ( Assets::register_script( 'cbf-semantic-glossary-admin', 'js/admin/cbf-semantic-glossary-admin.js' ) ) {
			wp_enqueue_script( 'cbf-semantic-glossary-admin' );
		}
	}


	/**
	 * Add the inline-code button to the definition editor.
	 *
	 * TinyMCE plugins are global, so the button is registered everywhere but
	 * only placed on the definition editor's toolbar.
	 *
	 * @param array<string, string> $plugins TinyMCE plugin URLs.
	 * @return array<string, string>
	 */
	public static function mce_plugins( array $plugins ): array {
		if ( get_current_screen()?->post_type === PostType::NAME ) {
			$plugins['glossary_code'] = Assets::url( 'js/admin/cbf-semantic-glossary-mce.js' );
		}
		return $plugins;
	}


	/**
	 * List table columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function columns( array $columns ): array {
		return array(
			'cb'             => $columns['cb'] ?? '',
			'title'          => __( 'Term', 'cbf-semantic-glossary' ),
			'glossary_abbr'  => __( 'Abbreviation', 'cbf-semantic-glossary' ),
			'glossary_also'  => __( 'Also', 'cbf-semantic-glossary' ),
			'glossary_usage' => __( 'Used in', 'cbf-semantic-glossary' ),
			'glossary_slug'  => __( 'Anchor', 'cbf-semantic-glossary' ),
			'date'           => $columns['date'] ?? __( 'Date', 'cbf-semantic-glossary' ),
		);
	}


	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Entry ID.
	 */
	public static function render_column( string $column, int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$entry  = Repository::from_post( $post );
		$values = array(
			'glossary_abbr'  => fn () => $entry->abbr() !== '' ? $entry->abbr() : '—',
			'glossary_also'  => fn () => implode( ', ', array_map( array( Forms::class, 'to_pair' ), $entry->alternatives() ) ),
			'glossary_usage' => fn () => (string) ( self::usage_counts()[ $post_id ] ?? 0 ),
			'glossary_slug'  => fn () => '#' . $entry->anchor(),
		);

		if ( isset( $values[ $column ] ) ) {
			echo esc_html( $values[ $column ]() );
		}
	}


	/**
	 * Usage counts for every entry on the current list page, fetched once.
	 *
	 * @return array<int, int>
	 */
	private static function usage_counts(): array {
		static $counts = null;

		if ( $counts === null ) {
			global $wp_query;
			$counts = Index::usage_counts( wp_list_pluck( (array) $wp_query->posts, 'ID' ) );
		}

		return $counts;
	}


	/**
	 * Forms from the submission, normalised.
	 *
	 * @return array<int, array{term:string,abbr:string}>
	 */
	private static function submitted_forms(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified by callers; Forms::normalise() sanitises.
		$raw = isset( $_POST['glossary_forms'] ) && is_array( $_POST['glossary_forms'] ) ? wp_unslash( $_POST['glossary_forms'] ) : array();
		return Forms::normalise( array_values( $raw ) );
	}


	/**
	 * A posted text field, unslashed. Callers sanitise for their own use.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified() checked the nonce; callers sanitise.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
	}


	/**
	 * Whether this request carries a valid entry-form nonce.
	 */
	private static function verified(): bool {
		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';
		return $nonce !== '' && wp_verify_nonce( $nonce, self::NONCE );
	}


	/**
	 * The slug to store: the one given, or one made from the term when there is none yet.
	 *
	 * Never regenerated from the term once set, so renaming the term keeps the anchor.
	 *
	 * @param string $slug    Requested or current slug.
	 * @param string $term    Canonical term.
	 * @param int    $post_id Entry ID.
	 * @param string $status  Post status.
	 */
	private static function slug( string $slug, string $term, int $post_id, string $status ): string {
		$slug = $slug !== '' ? $slug : sanitize_title( $term );
		return wp_unique_post_slug( $slug, $post_id, $status, PostType::NAME, 0 );
	}


	/**
	 * Fragment identifier an entry's slug produces, for display.
	 *
	 * @param string $slug Slug.
	 */
	public static function anchor_for( string $slug ): string {
		return Entry::DFN_PREFIX . $slug;
	}
}
