<?php
/**
 * Glossary entry capabilities.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capabilities.
 *
 * Entries have their own capability type, so sites can decide per role who may
 * create, edit and publish them (with PublishPress Capabilities, for example)
 * without granting the matching rights over ordinary posts. WordPress maps the
 * meta capabilities (edit_post, publish_post, …) onto these primitives.
 *
 * Administrators and Editors get every primitive on activation, which keeps
 * the original behaviour: entries are shared by every post that references
 * them, so by default only Editors and above manage them.
 */
final class Capabilities {

	/**
	 * Singular and plural capability type.
	 */
	const TYPE = array( 'glossary_term', 'glossary_terms' );

	/**
	 * Roles granted every primitive capability on activation.
	 */
	const DEFAULT_ROLES = array( 'administrator', 'editor' );


	/**
	 * Every primitive capability of the post type.
	 *
	 * Creating maps to edit_glossary_terms; reading stays at `read`.
	 *
	 * @return string[]
	 */
	public static function primitives(): array {
		return array(
			'edit_glossary_terms',
			'edit_others_glossary_terms',
			'edit_published_glossary_terms',
			'edit_private_glossary_terms',
			'publish_glossary_terms',
			'read_private_glossary_terms',
			'delete_glossary_terms',
			'delete_others_glossary_terms',
			'delete_published_glossary_terms',
			'delete_private_glossary_terms',
		);
	}


	/**
	 * Grant every primitive to the default roles. Safe to run repeatedly.
	 */
	public static function grant_defaults(): void {
		foreach ( self::DEFAULT_ROLES as $name ) {
			$role = get_role( $name );

			if ( $role === null ) {
				continue;
			}

			foreach ( self::primitives() as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}


	/**
	 * Remove every primitive from every role, on uninstall.
	 */
	public static function revoke_all(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			foreach ( self::primitives() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}


	/**
	 * Whether the current user may publish entries.
	 *
	 * Entries created by anyone else start as drafts.
	 */
	public static function can_publish(): bool {
		return current_user_can( 'publish_glossary_terms' );
	}


	/**
	 * Whether the current user may create entries.
	 */
	public static function can_create(): bool {
		return current_user_can( 'edit_glossary_terms' );
	}


	/**
	 * Whether the current user can edit any kind of content.
	 *
	 * Looking entries up and referencing them is part of editing content, so it
	 * is allowed to anyone who can edit something. That includes roles that
	 * only hold another post type's capabilities: LearnDash lessons, for
	 * example, use `edit_courses` rather than `edit_posts`.
	 */
	public static function can_edit_content(): bool {
		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $type ) {
			if ( current_user_can( $type->cap->edit_posts ) ) {
				return true;
			}
		}

		return false;
	}
}
