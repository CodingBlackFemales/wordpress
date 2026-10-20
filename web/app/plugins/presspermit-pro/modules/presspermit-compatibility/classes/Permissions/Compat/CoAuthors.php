<?php
namespace PublishPress\Permissions\Compat;

class CoAuthors
{
    function __construct()
    {
        add_filter('posts_clauses_request', [$this, 'fltPostsClauses'], 60, 3);
        add_filter('presspermit_construct_posts_request_clauses', [$this, 'fltConstructPostsRequest'], 60, 2);
        add_filter('presspermit_posts_request', [$this, 'fltPostsRequest'], 50, 2);
    }

    private function isCoauthorsQuery($query)
    {
        global $coauthors_plus;

        if (empty($coauthors_plus) || (!empty($query->query_vars['post_type']) 
        && !is_object_in_taxonomy($query->query_vars['post_type'], $coauthors_plus->coauthor_taxonomy))
        ) {
            return false;
        }

        return true;
    }

    private function getAuthorTerms()
    {
        global $current_user, $coauthors_plus;
        $author_name = $current_user->user_login;

        $terms = [];
        $coauthor = $coauthors_plus->get_coauthor_by('user_nicename', $author_name);
        if ($author_term = $coauthors_plus->get_author_term($coauthor))
            $terms[] = $author_term;
        // If this coauthor has a linked account, we also need to get posts with those terms
        if (!empty($coauthor->linked_account)) {
            $linked_account = get_user_by('login', $coauthor->linked_account);
            if ($guest_author_term = $coauthors_plus->get_author_term($linked_account))
                $terms[] = $guest_author_term;
        }

        return $terms;
    }

    private function maybebothQuery($where)
    {
        global $coauthors_plus;

        // Whether or not to include the original 'post_author' value in the query
        // Don't include it if we're forcing guest authors, or it's obvious our query is for a guest author's posts
        if ($coauthors_plus->force_guest_authors || stripos($where, '.post_author = 0)'))
            $maybe_both = false;
        else
            $maybe_both = apply_filters('coauthors_plus_should_query_post_author', true);

        return $maybe_both ? '$1 OR' : '';
    }

    function fltPostsClauses($clauses, $_wp_query = false, $args = [])
    {
        if (!$_wp_query) {
            global $wp_query;
            $_wp_query = $wp_query;
        }

        $clauses['distinct'] = 'DISTINCT ';
        $clauses['where'] = $this->postsWhereFilter($clauses['where'], $_wp_query);
        $clauses['join'] = $this->postsJoinFilter($clauses['join'], $_wp_query);

        return $clauses;
    }

    function fltConstructPostsRequest($clauses, $args = [])
    {
        global $wp_query;

        $clauses['where'] = $this->postsWhereFilter($clauses['where'], $wp_query);
        $clauses['join'] = $this->postsJoinFilter($clauses['join'], $wp_query);

        return $clauses;
    }

    /**
     * Modify the author query posts SQL to include posts co-authored
     */
    // ported from Coauthors Plus due to inability to call selectively outside author query
    private function postsJoinFilter($join, $query)
    {
        global $wpdb, $coauthors_plus;

        if (!$this->isCoauthorsQuery($query) || empty($coauthors_plus->having_terms))
            return $join;

        // Check to see that JOIN hasn't already been added. Props michaelingp and nbaxley
        $term_relationship_join = " INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";

        $term_taxonomy_join = " INNER JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

        if (strpos($join, trim("JOIN {$wpdb->term_relationships} ON")) === false) {
            $join .= str_replace("INNER JOIN", "LEFT JOIN", $term_relationship_join);
        }
        if (strpos($join, trim("JOIN {$wpdb->term_taxonomy} ON")) === false) {
            $join .= str_replace("INNER JOIN", "LEFT JOIN", $term_taxonomy_join);
        }

        return $join;
    }

    /**
     * Modify the author query posts SQL to include posts co-authored
     */
    // ported from Coauthors Plus due to inability to call selectively outside author query
    private function postsWhereFilter($where, $query)
    {
        global $wpdb, $coauthors_plus;

        if (!$this->isCoauthorsQuery($query))
            return $where;

        if (!$terms = $this->getAuthorTerms())
            return $where;

        $maybe_both_query = $this->maybebothQuery($where);

        $terms_implode = '';
        $this->having_terms = '';
        foreach ($terms as $term) {
            $terms_implode .= '(' . $wpdb->term_taxonomy . '.taxonomy = \'' . $coauthors_plus->coauthor_taxonomy . '\' AND ' . $wpdb->term_taxonomy . '.term_id = \'' . $term->term_id . '\') OR ';
            $coauthors_plus->having_terms .= ' ' . $wpdb->term_taxonomy . '.term_id = \'' . $term->term_id . '\' OR ';
        }
        $terms_implode = rtrim($terms_implode, ' OR');
        $coauthors_plus->having_terms = rtrim($coauthors_plus->having_terms, ' OR');
        $where = preg_replace('/(\b(?:' . $wpdb->posts . '\.)?post_author\s*=\s*(\d+))/', '(' . $maybe_both_query . ' ' . $terms_implode . ')', $where, 1); #' . $wpdb->postmeta . '.meta_id IS NOT NULL AND

        return $where;
    }

    // ported from Coauthors Plus to include Coauthor posts in post count
    // For posts query, Coauthors Plus join/where filters are called directly. But when PP's filtering done at the request level (post count query), use subselect instead of inserting a join clause. 
    function fltPostsRequest($request, $args = [])
    {
        global $wp_query, $wpdb, $coauthors_plus;

        if (empty($coauthors_plus) || strpos($request, "taxonomy = '$coauthors_plus->coauthor_taxonomy'"))
            return $request;

        if (!empty($wp_query->query_vars['post_type']) 
        && !is_object_in_taxonomy($wp_query->query_vars['post_type'], $coauthors_plus->coauthor_taxonomy)
        ) {
            return $request;
        }

        if (!$terms = $this->getAuthorTerms())
            return $request;

        $maybe_both_query = $this->maybebothQuery($where);

        $tt_ids = [];
        $coauthors_plus->having_terms = '';
        foreach ($terms as $term) {
            $tt_ids [] = $term->term_taxonomy_id;
            $coauthors_plus->having_terms .= ' ' . $wpdb->term_taxonomy . '.term_id = \'' . $term->term_id . '\' OR ';
        }

        $terms_implode = "$wpdb->posts.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ('" 
        . implode("','", $tt_ids) 
        . "') )";
        
        $request = preg_replace(
            '/(\b(?:' . $wpdb->posts . '\.)?post_author\s*=\s*(\d+))/', 
            '(' . $maybe_both_query . ' ' . $terms_implode . ')', 
            $request, 
            1
        ); #' . $wpdb->postmeta . '.meta_id IS NOT NULL AND

        $coauthors_plus->having_terms = rtrim($coauthors_plus->having_terms, ' OR');

        return $request;
    }
}
