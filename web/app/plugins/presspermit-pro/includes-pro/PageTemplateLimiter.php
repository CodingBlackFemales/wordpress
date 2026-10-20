<?php
namespace PublishPress\Permissions;

/*
 * Description: In the Page Edit form, limit Page Template selection based on user's WP role or PressPermit group(s). 
 * Currently no admin GUI - edit source code to configure.
 * Author: Kevin Behrens
 */
class PageTemplateLimiter
{
	function __construct() {
		add_filter('theme_templates', array(&$this, 'fltThemeTemplates'), 10, 4 );
		add_filter('pre_post_page_template', array(&$this, 'filter_submission') );
	}
	
	function fltThemeTemplates($page_templates, $theme_obj, $post, $post_type ) {
		return $this->filter_templates($page_templates);
	}

	private function filter_templates($page_templates) {
		global $current_user;
		
		$cfg = apply_filters('presspermit_page_template_restrictions', ['exempt_roles' => ['administrator']]);

		foreach(['restricted_templates', 'restricted_prefixes', 'allow_by_role', 'allow_prefix_by_role', 'exempt_roles'] as $var) {
			if (isset($cfg[$var])) {
				$$var = (!empty($cfg[$var]) && is_array($cfg[$var])) ? $cfg[$var] : [];
			}
		}

		$allowed = array();
		$allowed_prefix = array();
		
		// Does this user have a role that is exempted from all restrictions?
		if ($exempt_roles && is_array($exempt_roles)) {
			foreach( $exempt_roles as $role => $allow_all ) {
				if ( $allow_all && ! empty($current_user->allcaps[$role]) ) {
					return $page_templates;
				}
			}
		}

		if ( empty($restricted_templates) && empty($restricted_prefixes) )
			return $page_templates;
		
		if ( ! empty($allow_by_role) )
			foreach( array_keys($allow_by_role) as $role )
				if ( ! empty($current_user->allcaps[$role]) )
					$allowed = array_merge($allowed, $allow_by_role[$role]);
		
		if ( ! empty($allow_prefix_by_role) )
			foreach( array_keys($allow_prefix_by_role) as $role )
				if ( ! empty($current_user->allcaps[$role]) )
					$allowed_prefix = array_merge($allowed_prefix, $allow_prefix_by_role[$role]);

		if ( defined('PRESSPERMIT_VERSION') ) {
			if ( ! empty($allow_by_group) )
				if ( $allow_by_group = array_intersect_key($allow_by_group, $current_user->groups) )
					foreach ( array_keys($allow_by_group) as $group_id )
						$allowed = array_merge($allowed, $allow_by_group[$group_id]);

			if ( ! empty($allow_prefix_by_group) )
				if ( $allow_prefix_by_group = array_intersect_key($allow_prefix_by_group, $current_user->groups) )
					foreach( array_keys($allow_prefix_by_group) as $group_id )
						$allowed_prefix = array_merge($allowed_prefix, $allow_prefix_by_group[$group_id]);
		}
		
		if ( $allowed )
			$allowed = array_unique($allowed);
		
		if ( $allowed_prefix )
			$allowed_prefix = array_unique($allowed_prefix);

		foreach ( $page_templates as $key => $page_template ) {
			$restricted = false;
			if ( ! empty($restricted_templates[$key]) )
				$restricted = true;
			elseif ( ! empty($restricted_prefixes) ) {
				foreach ( $restricted_prefixes as $pfx ) {
					if ( 0 === strpos($page_template, $pfx) ) {
						$restricted = true;
						break;
					}
				}
			}
			
			if ( $restricted ) {
				if ( $allowed && in_array($key, $allowed) )
					continue;
					
				if ( $allowed_prefix )
					foreach ( $allowed_prefix as $pfx )
						if ( 0 === strpos($page_template, $pfx) )
							continue 2;
				
				// didn't match allowed filenames or allowed prefixes
				unset($page_templates[$key]);
			}
		}
					
		return $page_templates;
	}
	
	function filter_submission($page_template) {
		if ( $valid = $this->filter_templates((array)$page_template ) )
			return $page_template;
			
		// if this user cannot set template, revert to stored setting
		if ($post_id = PWP::POST_int('post_ID')) {
			if ($post = get_post($post_id)) {
				if (isset($post->page_template)) {
					return $post->page_template;
				}
			}
		}

		return '';
	}
}
