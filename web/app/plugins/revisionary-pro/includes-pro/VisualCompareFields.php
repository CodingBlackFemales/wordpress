<?php

/**
 * Builds the Pro-only custom-field payload for Visual Compare.
 */
class RevisionaryVisualCompareFields {
    public static function skipMetaFields() {
        $skip_meta_fields = (array) apply_filters('revisionary_compare_skip_meta_fields', ['classic-editor-remember']);
        $saved_fields = get_user_meta(get_current_user_id(), 'rvy_compare_ignore_meta_fields', true);
        if (!is_array($saved_fields)) {
            $saved_fields = [];
        }

        return array_values(array_unique(array_filter(array_merge($skip_meta_fields, $saved_fields), 'is_string')));
    }

    public static function filterPayload($payload, $current_post, $slider_posts) {
        if (!is_array($payload) || !$current_post instanceof WP_Post) {
            return $payload;
        }

		$authorization_revision_id = !empty($payload['authorizationRevisionId'])
			? (int) $payload['authorizationRevisionId']
			: (int) ($payload['revision']['id'] ?? 0);
		$authorized = $authorization_revision_id && class_exists('PublishPress\\Visual_Post_Compare')
			? \PublishPress\Visual_Post_Compare::authorized_comparison($authorization_revision_id)
			: new WP_Error('vpc_invalid_revision');
		if (is_wp_error($authorized)
		|| (int) $authorized['current_post']->ID !== (int) $current_post->ID
		|| !current_user_can('read_post', $current_post->ID)
		) {
			return $payload;
		}

        $comparisons = [];
        $archive_notices = [];
        $comparison_base = !empty($payload['comparisonBase']['id'])
            ? get_post((int) $payload['comparisonBase']['id'])
            : $current_post;
        if (!$comparison_base instanceof WP_Post) {
            $comparison_base = $current_post;
        }

        $has_comparison_base = empty($payload['isPastComparison'])
            || !empty($payload['compareToCurrent'])
            || !empty($payload['hasPreviousRevision']);
        $post_data = [];
        foreach ((array) ($payload['posts'] ?? []) as $item) {
            if (!empty($item['id'])) {
                $post_data[(int) $item['id']] = $item;
            }
        }
        if (!empty($payload['revision']['id'])) {
            $post_data[(int) $payload['revision']['id']] = $payload['revision'];
        }

		if (!empty($payload['isCurrentSelectionComparison']) && $has_comparison_base) {
			$groups = self::compare($comparison_base, $current_post);
			if ($groups) {
				$comparisons[(string) $current_post->ID] = $groups;
			}
		}

        foreach ((array) $slider_posts as $post) {
            if (!$post instanceof WP_Post || $post->ID === $current_post->ID) {
                continue;
            }
			$post_parent_id = wp_is_post_revision($post)
				?: (rvy_in_revision_workflow($post) ? rvy_post_id($post) : 0);
			if ((int) $post_parent_id !== (int) $current_post->ID
			|| !current_user_can('read_post', $post->ID)
			) {
				continue;
			}

            if (!empty($payload['isPastComparison'])
            && empty($payload['compareToCurrent'])
            && (int) $post->ID !== (int) ($payload['revision']['id'] ?? 0)
            ) {
                continue;
            }

            if ($has_comparison_base) {
                $groups = self::compare($comparison_base, $post);
                if ($groups) {
                    $comparisons[(string) $post->ID] = $groups;
                }
            }

            if (!empty($payload['isPastComparison'])
            && wp_is_post_revision($post->ID)
            && !self::hasStoredComparableMetadata($current_post, $post)
            ) {
                $revision_data = (array) ($post_data[$post->ID] ?? []);
                $publication_archive = !empty($revision_data['from_revision_workflow'])
                    || !empty($revision_data['parent_in_revision_workflow'])
                    || !empty($revision_data['parent_from_revision_workflow']);
                $archive_option = $publication_archive ? 'archive_postmeta' : 'archive_postmeta_on_edit';

                if (!rvy_get_option($archive_option)) {
                    $archive_notices[(string) $post->ID] = [
                        'publication' => $publication_archive,
                        'url' => admin_url('admin.php?page=revisionary-settings&ppr_tab=ppr-tab-archive#ppr-tab-archive'),
                    ];
                }
            }
        }

        $payload['fieldComparisons'] = $comparisons;
        $payload['fieldArchiveNotices'] = $archive_notices;
        return $payload;
    }

    public static function revisionTriggerMetadata($post) {
        if (!$post instanceof WP_Post) {
            return [];
        }

        $providers = self::providers($post);
        $metadata = get_post_meta($post->ID);
        $keys = array_keys($metadata);
        $selected = [];
        $claimed = [];

        foreach ($providers as $provider_id => $provider) {
            if (empty($provider['active'])) {
                continue;
            }

            $provider_keys = array_values(array_filter($keys, function ($key) use ($provider) {
                return call_user_func($provider['matches'], $key);
            }));

            if ('other' === $provider_id) {
                $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($providers) {
                    return !self::recognizedProviderKey($providers, $key, ['other']);
                }));
            }

            if ('wordpress' === $provider_id) {
                $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($providers) {
                    foreach ($providers as $id => $candidate) {
                        if ('wordpress' !== $id
                        && 'other' !== $id
                        && !empty($candidate['active'])
                        && call_user_func($candidate['matches'], $key)
                        ) {
                            return false;
                        }
                    }
                    return true;
                }));
            }

            $provider_keys = array_values(array_diff($provider_keys, $claimed));
            $provider_keys = apply_filters('revisionary_visual_compare_field_whitelist', $provider_keys, $provider_id, $post, $post);
            $provider_keys = self::removeSuppressedProviderKeys($provider_keys, $provider, $claimed);
            $blacklist = (array) apply_filters(
                'revisionary_visual_compare_field_blacklist',
                self::defaultBlacklist($provider_id, $provider),
                $provider_id,
                $post,
                $post
            );
            $provider_keys = array_diff($provider_keys, $blacklist);
            $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($provider) {
                return !self::providerBlacklistsKey($provider, $key);
            }));
            $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($provider_id, $provider, $post) {
                return !in_array(
                    self::metaClassification($key, $provider_id, $post, $post, $provider),
                    ['internal', 'sensitive'],
                    true
                );
            }));

            if ('yoast' === $provider_id) {
                $provider_keys = array_values(array_filter($provider_keys, function ($key) {
                    return false === strpos($key, 'score')
                        && '_yoast_wpseo_linkdex' !== $key
                        && '_yoast_wpseo_estimated-reading-time-minutes' !== $key;
                }));
            }

            foreach ($provider_keys as $key) {
                $selected[$key] = $metadata[$key];
                $claimed[] = $key;
            }
        }

        ksort($selected);
        return (array) apply_filters('revisionary_past_revision_trigger_meta', $selected, $post);
    }

    public static function isRevisionTriggerMetaKey($post, $meta_key) {
        if (!$post instanceof WP_Post) {
            return false;
        }

        $providers = self::providers($post);
        foreach ($providers as $provider_id => $provider) {
            if (empty($provider['active']) || !call_user_func($provider['matches'], $meta_key)) {
                continue;
            }

            if (self::providerSuppressesComparison($provider, $meta_key)) {
                return false;
            }

            if ('other' === $provider_id
            && self::recognizedProviderKey($providers, $meta_key, ['other'])
            ) {
                continue;
            }

            if ('wordpress' === $provider_id) {
                foreach ($providers as $id => $candidate) {
                    if ('wordpress' !== $id
                    && 'other' !== $id
                    && !empty($candidate['active'])
                    && call_user_func($candidate['matches'], $meta_key)
                    ) {
                        continue 2;
                    }
                }
            }

            $keys = apply_filters('revisionary_visual_compare_field_whitelist', [$meta_key], $provider_id, $post, $post);
            $blacklist = (array) apply_filters(
                'revisionary_visual_compare_field_blacklist',
                self::defaultBlacklist($provider_id, $provider),
                $provider_id,
                $post,
                $post
            );
            if (!in_array($meta_key, $keys, true)
            || in_array($meta_key, $blacklist, true)
            || self::providerBlacklistsKey($provider, $meta_key)
            ) {
                return false;
            }

            if ('yoast' === $provider_id
            && (false !== strpos($meta_key, 'score')
                || '_yoast_wpseo_linkdex' === $meta_key
                || '_yoast_wpseo_estimated-reading-time-minutes' === $meta_key)
            ) {
                return false;
            }

            if (in_array(
                self::metaClassification($meta_key, $provider_id, $post, $post, $provider),
                ['internal', 'sensitive'],
                true
            )) {
                return false;
            }

            return true;
        }

        return false;
    }

    private static function compare($current_post, $revision_post) {
        $providers = self::providers($current_post);
        $current_meta = self::metadata($current_post->ID);
        $revision_meta = self::metadata($revision_post->ID);
        $all_keys = array_unique(array_merge(array_keys($current_meta), array_keys($revision_meta)));
        $is_past_revision = (bool) wp_is_post_revision($revision_post->ID);
        $groups = [];
		$claimed = [];
        $skip_meta_fields = self::skipMetaFields();

        foreach ($providers as $provider_id => $provider) {
            if (empty($provider['active'])) {
                continue;
            }

            $keys = array_values(array_filter($all_keys, function ($key) use ($provider) {
                return call_user_func($provider['matches'], $key);
            }));
			if ('other' === $provider_id) {
				$keys = array_values(array_filter($keys, function ($key) use ($providers) {
					return !self::recognizedProviderKey($providers, $key, ['other']);
				}));
			}
			if ('wordpress' === $provider_id) {
				$keys = array_values(array_filter($keys, function ($key) use ($providers) {
					foreach ($providers as $id => $candidate) {
						if ('wordpress' !== $id && 'other' !== $id && !empty($candidate['active']) && call_user_func($candidate['matches'], $key)) {
							return false;
						}
					}
					return true;
				}));
			}
			$keys = array_values(array_diff($keys, $claimed));
            $keys = apply_filters('revisionary_visual_compare_field_whitelist', $keys, $provider_id, $current_post, $revision_post);
            $keys = self::removeSuppressedProviderKeys($keys, $provider, $claimed);
            $blacklist = (array) apply_filters(
                'revisionary_visual_compare_field_blacklist',
                self::defaultBlacklist($provider_id, $provider),
                $provider_id,
                $current_post,
                $revision_post
            );
            $keys = array_diff($keys, $blacklist, $skip_meta_fields);
			$keys = array_values(array_filter($keys, function ($key) use ($provider) {
				return !self::providerBlacklistsKey($provider, $key);
			}));
			$keys = array_values(array_filter($keys, function ($key) use ($provider_id, $provider, $current_post, $revision_post) {
				return self::canViewMetaKey($key, $provider_id, $current_post, $revision_post, $provider);
			}));
			if ('yoast' === $provider_id) {
				$keys = array_values(array_filter($keys, function ($key) {
					return false === strpos($key, 'score')
						&& '_yoast_wpseo_linkdex' !== $key
						&& '_yoast_wpseo_estimated-reading-time-minutes' !== $key;
				}));
			}

            foreach ($keys as $key) {
                // Older Past Revisions may not have archived metadata. Absence is not
                // evidence that restoring the revision should delete the live value.
                if ($is_past_revision && !array_key_exists($key, $revision_meta)) {
                    continue;
                }

                $from = array_key_exists($key, $current_meta) ? $current_meta[$key] : null;
                $to = array_key_exists($key, $revision_meta) ? $revision_meta[$key] : null;
                if (self::same($from, $to)) {
                    continue;
                }

                $definition = self::definition($provider_id, $key, $current_post, $provider);
                $definition = apply_filters(
                    'revisionary_visual_compare_field_definition',
                    $definition,
                    $provider_id,
                    $key,
                    $current_post,
                    $revision_post
                );
                if (empty($definition['label'])) {
                    $definition['label'] = self::caption($key, $provider['caption_prefixes'] ?? []);
                }
                if (empty($definition['group'])) {
                    $definition['group'] = esc_html__('Other', 'revisionary-pro');
                }

				$current_image = self::imageUrl($from, $definition);
				$revision_image = self::imageUrl($to, $definition);
				$current_image_original = self::imageOriginalUrl($from, $definition);
				$revision_image_original = self::imageOriginalUrl($to, $definition);
				$from = self::readableValue($from, $definition);
				$to = self::readableValue($to, $definition);
                $groups[$provider_id]['id'] = $provider_id;
                $groups[$provider_id]['label'] = $provider['label'];
                $groups[$provider_id]['groups'][$definition['group']][] = [
                    'key' => $key,
                    'label' => $definition['label'],
                    'current' => $from,
                    'revision' => $to,
                    'type' => !empty($definition['type']) ? $definition['type'] : self::inferType($from, $to),
					'currentImage' => $current_image,
					'revisionImage' => $revision_image,
					'currentImageOriginal' => $current_image_original,
					'revisionImageOriginal' => $revision_image_original,
                ];
				$claimed[] = $key;
            }
        }

        foreach ($groups as &$provider) {
            $provider['groups'] = array_map(function ($label, $fields) {
                return ['label' => $label, 'fields' => $fields];
            }, array_keys($provider['groups']), array_values($provider['groups']));
        }

        return array_values($groups);
    }

    private static function metadata($post_id) {
        $meta = get_post_meta($post_id);
        $meta = apply_filters('revisionary_compare_meta_from', $meta, $post_id);
        foreach ($meta as $key => $values) {
            $decoded = array_map('maybe_unserialize', (array) $values);
            $meta[$key] = 1 === count($decoded) ? reset($decoded) : $decoded;
        }
        return $meta;
    }

    private static function providers($post) {
        $providers = [
            'wordpress' => [
                'label' => esc_html__('WordPress', 'revisionary-pro'),
                'active' => true,
                'matches' => function ($key) {
                    return in_array($key, self::wordpressKeys(), true);
                },
            ],
            'acf' => [
                'label' => esc_html__('Advanced Custom Fields', 'revisionary-pro'),
                'active' => function_exists('acf_get_field'),
                'matches' => function ($key) use ($post) {
					return (bool) self::acfField($key, $post->ID);
                },
            ],
            'acf_extended' => [
                'label' => esc_html__('ACF Extended', 'revisionary-pro'),
                'active' => defined('ACFE_VERSION') || function_exists('acfe_get_setting'),
                'matches' => function ($key) {
                    return 0 === strpos($key, 'acfe_') || 0 === strpos($key, '_acfe_');
                },
            ],
            'pods' => [
                'label' => esc_html__('Pods', 'revisionary-pro'),
                'active' => function_exists('pods'),
                'matches' => function ($key) use ($post) {
                    if (!function_exists('pods')) {
                        return false;
                    }
                    try {
                        $pod = pods($post->post_type, $post->ID);
                        return $pod && isset($pod->fields[$key]);
                    } catch (Throwable $e) {
                        return false;
                    }
                },
            ],
            'cptui' => [
                'label' => esc_html__('Custom Post Type UI', 'revisionary-pro'),
                'active' => defined('CPTUI_VERSION') || function_exists('cptui_get_post_type_data'),
                'matches' => function ($key) { return 0 === strpos($key, 'cptui_') || 0 === strpos($key, '_cptui_'); },
            ],
            'woocommerce' => [
                'label' => esc_html__('WooCommerce', 'revisionary-pro'),
                'active' => class_exists('WooCommerce'),
                'matches' => function ($key) { return in_array($key, self::woocommerceKeys(), true); },
            ],
            'yoast' => [
                'label' => esc_html__('Yoast SEO', 'revisionary-pro'),
                'active' => defined('WPSEO_VERSION'),
                'matches' => function ($key) { return 0 === strpos($key, '_yoast_wpseo_'); },
            ],
            'publishpress_cart' => [
                'label' => esc_html__('PublishPress Cart', 'revisionary-pro'),
                'active' => defined('PPCART_VERSION') || function_exists('ppcart_meta_key'),
                'matches' => function ($key) { return 0 === strpos($key, '_ppcart_'); },
            ],
            'other' => [
                'label' => esc_html__('Other Fields', 'revisionary-pro'),
                'active' => true,
                'matches' => function ($key) { return true; },
            ],
        ];

        require_once REVISIONARY_PRO_ABSPATH . '/includes-pro/VisualCompareFieldProviders.php';
        $other_provider = $providers['other'];
        unset($providers['other']);

        foreach (RevisionaryVisualCompareFieldProviders::providers() as $provider_id => $provider) {
            $field_keys = array_keys($provider['fields'] ?? []);
            $blacklist = array_values((array) ($provider['blacklist'] ?? []));
            $prefixes = array_values(array_unique(array_merge(
                (array) ($provider['prefixes'] ?? []),
                array_keys((array) ($provider['classification_prefixes'] ?? []))
            )));
            $blacklist_prefixes = array_values((array) ($provider['blacklist_prefixes'] ?? []));
            $post_types = array_values((array) ($provider['post_types'] ?? []));
            $match_keys = array_values(array_unique(array_merge(
                $field_keys,
                $blacklist,
                array_keys($provider['classifications'] ?? [])
            )));

            $provider['active'] = !array_key_exists('active', $provider) || !empty($provider['active']);
            $provider['matches'] = function ($key) use ($post, $post_types, $match_keys, $prefixes, $blacklist_prefixes) {
                if ($post_types && !in_array($post->post_type, $post_types, true)) {
                    return false;
                }
                if (in_array($key, $match_keys, true)) {
                    return true;
                }
                foreach (array_merge($prefixes, $blacklist_prefixes) as $prefix) {
                    if (0 === strpos((string) $key, $prefix)) {
                        return true;
                    }
                }
                return false;
            };
            $provider['definitions'] = $provider['fields'] ?? [];
            $providers[$provider_id] = $provider;
        }

        $providers['other'] = $other_provider;

        foreach (self::registeredProviders($post) as $provider_id => $registered_provider) {
            if (empty($registered_provider['active'])) {
                continue;
            }

            $registered_keys = array_keys($registered_provider['fields']);

            if (isset($providers[$provider_id])) {
                $built_in_matches = $providers[$provider_id]['matches'];
                $providers[$provider_id]['matches'] = function ($key) use ($built_in_matches, $registered_keys) {
                    return in_array($key, $registered_keys, true) || call_user_func($built_in_matches, $key);
                };
                $providers[$provider_id]['registered_fields'] = $registered_provider['fields'];
                $providers[$provider_id]['active'] = true;
                continue;
            }

            $providers = [
                $provider_id => [
                    'label' => $registered_provider['label'],
                    'active' => $registered_provider['active'],
                    'matches' => function ($key) use ($registered_keys) {
                        return in_array($key, $registered_keys, true);
                    },
                    'registered_fields' => $registered_provider['fields'],
                ],
            ] + $providers;
        }

        $providers = (array) apply_filters('revisionary_visual_compare_field_plugins', $providers, $post);

        // The catch-all provider must remain last so filtered-in providers can
        // claim their fields before unrecognized metadata is collected.
        if (isset($providers['other'])) {
            $other = $providers['other'];
            unset($providers['other']);
            $providers['other'] = $other;
        }

        return $providers;
    }

    /**
     * Returns normalized third-party Visual Compare field registrations.
     *
     * @see docs/visual-compare-field-registration.md
     */
    private static function registeredProviders($post) {
        /**
         * Filters fields registered by other plugins for Visual Compare.
         *
         * Providers are keyed by a unique slug and contain a provider `label`
         * and a `fields` array keyed by the post meta key. Each field accepts
         * `group`, `label`, `choices`, `type`, and `suppress_comparison`.
         *
         * @param array   $providers Registered field providers.
         * @param WP_Post $post      Current published post being compared.
         */
        $providers = (array) apply_filters('revisionary_visual_compare_field_registry', [], $post);
        $normalized = [];

        foreach ($providers as $provider_id => $provider) {
            $provider_id = sanitize_key((string) $provider_id);
            if (!$provider_id || !is_array($provider) || empty($provider['fields']) || !is_array($provider['fields'])) {
                continue;
            }

            $label = isset($provider['label']) && is_scalar($provider['label'])
                ? trim((string) $provider['label'])
                : '';
            if (!$label) {
                continue;
            }

            $fields = [];
            foreach ($provider['fields'] as $meta_key => $field) {
                if (!is_string($meta_key) || '' === $meta_key || !is_array($field)) {
                    continue;
                }

                $fields[$meta_key] = [
                    'label' => isset($field['label']) && is_scalar($field['label']) ? (string) $field['label'] : '',
                    'group' => isset($field['group']) && is_scalar($field['group']) ? (string) $field['group'] : '',
                    'type' => isset($field['type']) && is_scalar($field['type']) ? (string) $field['type'] : '',
                    'choices' => isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : [],
                    'suppress_comparison' => !empty($field['suppress_comparison']),
                    'classification' => isset($field['classification']) && in_array($field['classification'], ['content', 'internal', 'sensitive', 'unknown'], true)
                        ? $field['classification']
                        : '',
                ];
            }

            if ($fields) {
                $normalized[$provider_id] = [
                    'label' => $label,
                    'active' => !array_key_exists('active', $provider) || !empty($provider['active']),
                    'fields' => $fields,
                ];
            }
        }

        return $normalized;
    }

    private static function providerSuppressesComparison($provider, $key) {
        return !empty($provider['registered_fields'][$key]['suppress_comparison']);
    }

    private static function providerBlacklistsKey($provider, $key) {
        foreach ((array) ($provider['blacklist_prefixes'] ?? []) as $prefix) {
            if (0 === strpos((string) $key, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private static function removeSuppressedProviderKeys($keys, $provider, &$claimed) {
        $suppressed = array_values(array_filter((array) $keys, function ($key) use ($provider) {
            return self::providerSuppressesComparison($provider, $key);
        }));

        if ($suppressed) {
            // Claim suppressed keys so they cannot fall through to a later
            // overlapping provider and become comparable there.
            $claimed = array_values(array_unique(array_merge($claimed, $suppressed)));
        }

        return array_values(array_diff((array) $keys, $suppressed));
    }

    private static function defaultBlacklist($provider, $provider_config = []) {
        $common = ['_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_rvy_base_post_id', '_rvy_source_post_id'];
        $by_provider = [
            'wordpress' => $common,
            'acf' => [],
            'acf_extended' => [],
            'pods' => [],
            'cptui' => [],
            'woocommerce' => ['_product_version', '_wc_average_rating', '_wc_review_count', '_wc_rating_count'],
            'yoast' => ['_yoast_wpseo_content_score', '_yoast_wpseo_estimated-reading-time-minutes'],
            'publishpress_cart' => ['_ppcart_order_log', '_ppcart_refund_log', '_ppcart_checkout_complete_claimed'],
            'other' => $common,
        ];
        $blacklist = isset($by_provider[$provider]) ? $by_provider[$provider] : [];
        $blacklist = array_merge($blacklist, (array) ($provider_config['blacklist'] ?? []));
        return array_values(array_unique($blacklist));
    }

    private static function definition($provider, $key, $post, $provider_config = []) {
        $definition = ['label' => '', 'group' => '', 'type' => '', 'choices' => []];
		if (!empty($provider_config['registered_fields'][$key])) {
            $definition = array_merge($definition, array_intersect_key(
                $provider_config['registered_fields'][$key],
                $definition
            ));
        } elseif (!empty($provider_config['definitions'][$key])) {
            $definition = array_merge($definition, array_intersect_key(
                $provider_config['definitions'][$key],
                $definition
            ));
        } elseif ('acf' === $provider && function_exists('acf_get_field')) {
			$field = self::acfField($key, $post->ID);
            if (is_array($field)) {
                $definition['label'] = !empty($field['label']) ? $field['label'] : '';
                $definition['group'] = !empty($field['parent']) && function_exists('acf_get_field_group')
                    ? (string) (acf_get_field_group($field['parent'])['title'] ?? esc_html__('Fields', 'revisionary-pro'))
                    : esc_html__('Fields', 'revisionary-pro');
                $definition['type'] = $field['type'] ?? '';
                $definition['choices'] = $field['choices'] ?? [];
            }
        } elseif ('pods' === $provider && function_exists('pods')) {
            try {
                $pod = pods($post->post_type, $post->ID);
                $field = $pod && isset($pod->fields[$key]) ? $pod->fields[$key] : [];
                $definition['label'] = $field['label'] ?? '';
				$definition['group'] = self::podsGroupLabel($field['group'] ?? '', $post->post_type);
                $definition['type'] = $field['type'] ?? '';
                $definition['choices'] = $field['pick_custom'] ?? [];
            } catch (Throwable $e) {
                // Fall through to the readable meta-key caption.
            }
        } else {
            $maps = self::definitions();
            if (isset($maps[$provider][$key])) {
                $definition = array_merge($definition, $maps[$provider][$key]);
            }
			if ('publishpress_cart' === $provider && 'ppcart_product' === $post->post_type) {
				if ('_ppcart_product_name' === $key) {
					$definition['label'] = esc_html__('Public Product Name', 'revisionary-pro');
					$definition['group'] = esc_html__('General', 'revisionary-pro');
				} elseif ('_ppcart_pay_options' === $key) {
					$definition['label'] = esc_html__('Payment Plans', 'revisionary-pro');
					$definition['group'] = esc_html__('Payment Plans', 'revisionary-pro');
				}
			}
        }

        if (empty($definition['group'])) {
            foreach ((array) ($provider_config['prefix_groups'] ?? []) as $prefix => $group) {
                if (0 === strpos((string) $key, $prefix)) {
                    $definition['group'] = (string) $group;
                    break;
                }
            }
            if (empty($definition['group']) && !empty($provider_config['prefix_group'])) {
                $definition['group'] = (string) $provider_config['prefix_group'];
            }
        }

        if (empty($definition['label'])) {
            foreach ((array) ($provider_config['prefix_label_suffixes'] ?? []) as $suffix => $label) {
                if ($suffix && substr((string) $key, -strlen($suffix)) === $suffix) {
                    $definition['label'] = (string) $label;
                    break;
                }
            }
        }
        return $definition;
    }

    private static function definitions() {
        return [
            'wordpress' => [
                '_thumbnail_id' => ['label' => esc_html__('Featured image', 'revisionary-pro'), 'group' => esc_html__('Media', 'revisionary-pro'), 'type' => 'image'],
                '_wp_page_template' => ['label' => esc_html__('Template', 'revisionary-pro'), 'group' => esc_html__('Page attributes', 'revisionary-pro')],
                '_wp_attachment_image_alt' => ['label' => esc_html__('Alternative text', 'revisionary-pro'), 'group' => esc_html__('Media', 'revisionary-pro')],
            ],
            'woocommerce' => [
                '_sku' => ['label' => 'SKU', 'group' => esc_html__('Inventory', 'revisionary-pro')],
                '_regular_price' => ['label' => esc_html__('Regular price', 'revisionary-pro'), 'group' => esc_html__('General', 'revisionary-pro'), 'type' => 'number'],
                '_sale_price' => ['label' => esc_html__('Sale price', 'revisionary-pro'), 'group' => esc_html__('General', 'revisionary-pro'), 'type' => 'number'],
                '_stock' => ['label' => esc_html__('Stock quantity', 'revisionary-pro'), 'group' => esc_html__('Inventory', 'revisionary-pro'), 'type' => 'number'],
                '_stock_status' => ['label' => esc_html__('Stock status', 'revisionary-pro'), 'group' => esc_html__('Inventory', 'revisionary-pro'), 'choices' => ['instock' => esc_html__('In stock', 'revisionary-pro'), 'outofstock' => esc_html__('Out of stock', 'revisionary-pro'), 'onbackorder' => esc_html__('On backorder', 'revisionary-pro')]],
                '_manage_stock' => ['label' => esc_html__('Manage stock', 'revisionary-pro'), 'group' => esc_html__('Inventory', 'revisionary-pro'), 'choices' => ['yes' => esc_html__('Yes', 'revisionary-pro'), 'no' => esc_html__('No', 'revisionary-pro')]],
                '_virtual' => ['label' => esc_html__('Virtual', 'revisionary-pro'), 'group' => esc_html__('General', 'revisionary-pro'), 'choices' => ['yes' => esc_html__('Yes', 'revisionary-pro'), 'no' => esc_html__('No', 'revisionary-pro')]],
                '_downloadable' => ['label' => esc_html__('Downloadable', 'revisionary-pro'), 'group' => esc_html__('General', 'revisionary-pro'), 'choices' => ['yes' => esc_html__('Yes', 'revisionary-pro'), 'no' => esc_html__('No', 'revisionary-pro')]],
            ],
            'yoast' => [
                '_yoast_wpseo_title' => ['label' => esc_html__('SEO title', 'revisionary-pro'), 'group' => esc_html__('Search appearance', 'revisionary-pro')],
                '_yoast_wpseo_metadesc' => ['label' => esc_html__('Meta description', 'revisionary-pro'), 'group' => esc_html__('Search appearance', 'revisionary-pro')],
                '_yoast_wpseo_focuskw' => ['label' => esc_html__('Focus keyphrase', 'revisionary-pro'), 'group' => esc_html__('SEO analysis', 'revisionary-pro')],
                '_yoast_wpseo_meta-robots-noindex' => ['label' => esc_html__('Show in search results', 'revisionary-pro'), 'group' => esc_html__('Advanced', 'revisionary-pro'), 'choices' => ['1' => esc_html__('No', 'revisionary-pro'), '2' => esc_html__('Yes', 'revisionary-pro')]],
            ],
            'publishpress_cart' => self::cartDefinitions(),
        ];
    }

    private static function cartDefinitions() {
        $groups = [
			'General' => ['product_name', 'hide_title', 'header_color', 'header_image', 'disable_single_page', 'purchase_note'],
			'Payment Plans' => ['price', 'sale_price', 'pay_options', 'currency', 'sign_up_fee', 'free_trial_days'],
			'Purchase Restrictions' => ['manage_stock', 'limit', 'customer_purchase_limit', 'customer_limit', 'customer_limit_message', 'checkout_starts', 'checkout_ends', 'checkout_ended_action'],
			'Form Fields & Settings' => ['display', 'button_text', 'show_address_fields', 'custom_fields', 'terms_setting', 'privacy_setting', 'accept_terms', 'consent'],
			'Confirmation' => ['confirmation', 'confirmation_message', 'confirmation_page', 'redirect', 'success_url', 'cancel_url'],
			'Marketing' => ['coupons', 'order_bumps'],
            'Customer' => ['firstname', 'lastname', 'email', 'phone', 'country', 'address1', 'address2', 'city', 'state', 'zip', 'vat_number'],
            'Subscription' => ['sub_status', 'sub_amount', 'sub_interval', 'sub_frequency', 'sub_installments', 'sub_next_bill_date', 'sub_end_date'],
            'Order' => ['product_id', 'product_name', 'item_name', 'amount', 'tax_amount', 'vat_amount', 'payment_status', 'pay_method'],
        ];
        $definitions = [];
        foreach ($groups as $group => $keys) {
            foreach ($keys as $key) {
                $definitions['_ppcart_' . $key] = ['label' => self::caption($key), 'group' => $group];
            }
        }
        $definitions['_ppcart_display']['choices'] = ['two_step' => esc_html__('Two-step checkout', 'revisionary-pro'), 'single_step' => esc_html__('Single-step checkout', 'revisionary-pro')];
		$definitions['_ppcart_header_image']['type'] = 'image';
		$definitions['_ppcart_confirmation']['choices'] = ['message' => esc_html__('Display a message', 'revisionary-pro'), 'redirect' => esc_html__('Redirect to a URL', 'revisionary-pro'), 'page' => esc_html__('Show a page', 'revisionary-pro')];
		$definitions['_ppcart_checkout_ended_action']['choices'] = ['message' => esc_html__('Display a message', 'revisionary-pro'), 'redirect' => esc_html__('Redirect to a URL', 'revisionary-pro')];
        return $definitions;
    }

    private static function woocommerceKeys() {
        return ['_sku', '_regular_price', '_sale_price', '_price', '_virtual', '_downloadable', '_manage_stock', '_stock', '_stock_status', '_backorders', '_sold_individually', '_weight', '_length', '_width', '_height', '_tax_status', '_tax_class', '_product_attributes', '_downloadable_files', '_download_limit', '_download_expiry', '_sale_price_dates_from', '_sale_price_dates_to', '_purchase_note', '_featured', '_visibility'];
    }

    private static function wordpressKeys() {
        return ['_thumbnail_id', '_wp_page_template', '_wp_attachment_image_alt'];
    }

    private static function recognizedProviderKey($providers, $key, $excluded = []) {
        foreach ($providers as $provider_id => $provider) {
            if (in_array($provider_id, $excluded, true)) {
                continue;
            }
            if (call_user_func($provider['matches'], $key)) {
                return true;
            }
        }
        return false;
    }

    private static function internalMetaKey($key) {
		foreach ([
			'_rvy_', '_revisionary_', '_edit_', '_wp_old_', '_wp_trash_meta_', '_oembed_',
		] as $prefix) {
            if (0 === strpos($key, $prefix)) {
                return true;
            }
        }

        return in_array($key, ['_encloseme', '_pingme'], true);
    }

	private static function suppressedMetaFields() {
		$fields = get_option('rvy_compare_suppress_meta_fields', []);
		return is_array($fields) ? array_values(array_unique(array_filter($fields, 'is_string'))) : [];
	}

	private static function sensitiveMetaKey($key) {
		$key = strtolower((string) $key);
		$exact = [
			'_application_passwords', '_auth_token', '_access_token', '_refresh_token',
			'_api_secret', '_client_secret', '_private_key', '_webhook_secret',
		];
		if (in_array($key, $exact, true)) {
			return true;
		}

		return (bool) preg_match('/(?:^|_)(?:password|passwd|api_key|api_secret|client_secret|consumer_key|consumer_secret|license_key|private_key|secret_key|access_token|refresh_token|webhook_secret)(?:_|$)/i', $key);
	}

	private static function metaClassification($key, $provider_id, $current_post, $revision_post, $provider = []) {
		if (self::sensitiveMetaKey($key)) {
			$classification = 'sensitive';
		} elseif (!empty($provider['registered_fields'][$key]['classification'])) {
			$classification = (string) $provider['registered_fields'][$key]['classification'];
		} elseif (!empty($provider['classifications'][$key])) {
			$classification = (string) $provider['classifications'][$key];
		} elseif ($prefix_classification = self::providerPrefixClassification($provider, $key)) {
			$classification = $prefix_classification;
		} elseif (self::internalMetaKey($key)) {
			$classification = 'internal';
		} elseif ('other' !== $provider_id) {
			$classification = 'content';
		} else {
			$classification = 'unknown';
		}

		$classification = (string) apply_filters(
			'revisionary_visual_compare_meta_classification',
			$classification,
			$key,
			$provider_id,
			$current_post,
			$revision_post
		);

		return in_array($classification, ['content', 'internal', 'sensitive', 'unknown'], true)
			? $classification
			: 'unknown';
	}

	private static function providerPrefixClassification($provider, $key) {
		foreach ((array) ($provider['classification_prefixes'] ?? []) as $prefix => $classification) {
			if (0 === strpos((string) $key, (string) $prefix)
			&& in_array($classification, ['content', 'internal', 'sensitive', 'unknown'], true)
			) {
				return (string) $classification;
			}
		}

		return '';
	}

	private static function canViewMetaKey($key, $provider_id, $current_post, $revision_post, $provider = []) {
		$classification = self::metaClassification($key, $provider_id, $current_post, $revision_post, $provider);
		$unidentified_internal = 'unknown' === $classification && 0 === strpos((string) $key, '_');
		$can_manage_post = current_user_can('edit_post', $current_post->ID);
		if (!$can_manage_post && ($type_obj = get_post_type_object($current_post->post_type))) {
			$base_cap = (int) $current_post->post_author === get_current_user_id()
				? ($type_obj->cap->edit_posts ?? '')
				: ($type_obj->cap->edit_others_posts ?? '');
			if ($base_cap) {
				$approve_cap = str_replace('edit_', 'approve_', $base_cap);
				$can_manage_post = current_user_can($approve_cap);
			}
		}
		$can_manage_post = (bool) apply_filters(
			'revisionary_visual_compare_can_view_other_internal_fields',
			$can_manage_post,
			$current_post,
			get_current_user_id()
		);
		$allowed = !in_array($classification, ['internal', 'sensitive'], true);

		if ($unidentified_internal) {
			$allowed = (bool) rvy_get_option('rvy_compare_other_internal_fields') && $can_manage_post;
		}
		$allowed = (bool) apply_filters(
			'revisionary_visual_compare_can_view_meta_key',
			$allowed,
			$key,
			$provider_id,
			$current_post,
			$revision_post,
			get_current_user_id(),
			$classification
		);

		return $allowed && !in_array($key, self::suppressedMetaFields(), true);
	}

    private static function hasStoredComparableMetadata($current_post, $revision_post) {
        $providers = self::providers($current_post);
        $keys = array_keys(self::metadata($revision_post->ID));
        $claimed = [];
        $skip_meta_fields = (array) apply_filters(
            'revisionary_compare_skip_meta_fields',
            ['classic-editor-remember']
        );

        foreach ($providers as $provider_id => $provider) {
            if (empty($provider['active'])) {
                continue;
            }

            $provider_keys = array_values(array_filter($keys, function ($key) use ($provider) {
                return call_user_func($provider['matches'], $key);
            }));
            if ('other' === $provider_id) {
                $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($providers) {
                    return !self::recognizedProviderKey($providers, $key, ['other']);
                }));
            }
            $provider_keys = array_values(array_diff($provider_keys, $claimed));
            $provider_keys = apply_filters(
                'revisionary_visual_compare_field_whitelist',
                $provider_keys,
                $provider_id,
                $current_post,
                $revision_post
            );
            $provider_keys = self::removeSuppressedProviderKeys($provider_keys, $provider, $claimed);
            $blacklist = (array) apply_filters(
                'revisionary_visual_compare_field_blacklist',
                self::defaultBlacklist($provider_id, $provider),
                $provider_id,
                $current_post,
                $revision_post
            );
            $provider_keys = array_values(array_diff($provider_keys, $blacklist, $skip_meta_fields));
            $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($provider) {
                return !self::providerBlacklistsKey($provider, $key);
            }));
            $provider_keys = array_values(array_filter($provider_keys, function ($key) use ($provider_id, $provider, $current_post, $revision_post) {
                return self::canViewMetaKey($key, $provider_id, $current_post, $revision_post, $provider);
            }));
            if ('yoast' === $provider_id) {
                $provider_keys = array_values(array_filter($provider_keys, function ($key) {
                    return false === strpos($key, 'score')
                        && '_yoast_wpseo_linkdex' !== $key
                        && '_yoast_wpseo_estimated-reading-time-minutes' !== $key;
                }));
            }

            if ($provider_keys) {
                return true;
            }
            $claimed = array_merge($claimed, $provider_keys);
        }

        return false;
    }

	private static function acfField($key, $post_id) {
		if (!function_exists('acf_get_field')) {
			return false;
		}
		if ($field = acf_get_field($key)) {
			return $field;
		}
		$field_key = get_post_meta($post_id, '_' . ltrim($key, '_'), true);
		if ($field_key && is_string($field_key)) {
			return acf_get_field($field_key);
		}
		if (function_exists('get_field_object')) {
			return get_field_object($key, $post_id, false, false);
		}
		return false;
	}

	private static function podsGroupLabel($group, $pod_name) {
		if (!$group) {
			return esc_html__('Fields', 'revisionary-pro');
		}
		if (!is_numeric($group)) {
			return (string) $group;
		}
		if (function_exists('pods_api')) {
			try {
				$loaded = pods_api()->load_group(['id' => (int) $group, 'pod' => $pod_name]);
				if (is_array($loaded)) {
					return (string) ($loaded['label'] ?? $loaded['name'] ?? esc_html__('Fields', 'revisionary-pro'));
				}
			} catch (Throwable $e) {
				// Use the generic group caption below.
			}
		}
		return esc_html__('Fields', 'revisionary-pro');
	}

    private static function readableValue($value, $definition) {
		if ('image' === ($definition['type'] ?? '') && is_numeric($value)) {
			$title = get_the_title((int) $value);
			return $title ? $title : sprintf(esc_html__('Image #%d', 'revisionary-pro'), (int) $value);
		}
        $choices = !empty($definition['choices']) && is_array($definition['choices']) ? $definition['choices'] : [];
        $map = function ($item) use ($choices) {
            $lookup = is_bool($item) ? ($item ? '1' : '0') : (string) $item;
            return array_key_exists($lookup, $choices) ? $choices[$lookup] : $item;
        };
        return self::mapDepth($value, $map, 0);
    }

    private static function mapDepth($value, $callback, $depth) {
        if (!is_array($value)) {
            return is_scalar($value) || null === $value ? $callback($value) : (string) $value;
        }
        if ($depth >= 3) {
            return '[Array]';
        }
        foreach ($value as $key => $item) {
            $value[$key] = self::mapDepth($item, $callback, $depth + 1);
        }
        return $value;
    }

    private static function imageUrl($value, $definition) {
        if ('image' !== ($definition['type'] ?? '')) {
            return '';
        }
        if (is_numeric($value)) {
            return (string) wp_get_attachment_image_url((int) $value, 'medium');
        }
        if (is_array($value) && !empty($value['url'])) {
            return esc_url_raw($value['url']);
        }
        return is_string($value) && preg_match('#^https?://#', $value) ? esc_url_raw($value) : '';
    }

    private static function imageOriginalUrl($value, $definition) {
        if ('image' !== ($definition['type'] ?? '')) {
            return '';
        }
        if (is_numeric($value)) {
            $url = function_exists('wp_get_original_image_url')
                ? wp_get_original_image_url((int) $value)
                : wp_get_attachment_image_url((int) $value, 'full');
            return $url ? (string) $url : '';
        }
        if (is_array($value) && !empty($value['url'])) {
            return esc_url_raw($value['url']);
        }
        return is_string($value) && preg_match('#^https?://#', $value) ? esc_url_raw($value) : '';
    }

    private static function inferType($from, $to) {
        return is_numeric($from) && is_numeric($to) ? 'number' : 'text';
    }

    private static function same($a, $b) {
        return wp_json_encode($a) === wp_json_encode($b);
    }

    private static function caption($key, $prefixes = []) {
        foreach ((array) $prefixes as $prefix) {
            if (0 === strpos((string) $key, $prefix)) {
                $key = substr((string) $key, strlen($prefix));
                break;
            }
        }
        $key = preg_replace('/^(?:_ppcart_|_yoast_wpseo_|_acfe_|_cptui_|_)/', '', (string) $key);
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }
}
