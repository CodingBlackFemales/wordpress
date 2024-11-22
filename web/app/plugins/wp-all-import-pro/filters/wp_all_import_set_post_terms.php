<?php

/**
 * @param $assign_taxes
 * @param $tx_name
 * @param $pid
 * @param $import_id
 * @return array
 */
function pmxi_wp_all_import_set_post_terms($assign_taxes, $tx_name, $pid, $import_id){
    if (empty($assign_taxes) && $tx_name == 'category'){
        $term = is_exists_term('uncategorized', $tx_name, 0);
        if ( !empty($term) and ! is_wp_error($term) ) {
            $assign_taxes[] = $term['term_taxonomy_id'];
        }
    }
    return $assign_taxes;
}
