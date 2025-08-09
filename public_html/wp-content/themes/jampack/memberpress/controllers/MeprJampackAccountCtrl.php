<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class MeprJampackAccountCtrl extends MeprBaseCtrl
{
    public function __construct() {
        parent::__construct();
    }

    public function developer_role_to_string() {
        return 'developer';
    }

    public function judge_role_to_string() {
        return 'judge';
    }

    public function statistics() {
        $mepr_options = MeprOptions::fetch();
        $is_current_user_developer = $this->is_current_user_developer();
        return MeprView::render('/account/statistics', get_defined_vars(), [JMP_MEPR_READY_LAUNCH_PATH]);
    }

    public function rest_namespace() {
        return 'jpckaccount/v1';
    }

    public function analitycs_rest_base() {
        return 'analytics';
    }

    public function analitycs_rest_route() {
        return $this->rest_namespace() . '/' . $this->analitycs_rest_base();
    }

    function get_user_analytics(WP_REST_Request $request) {
        global $wpdb;

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return new WP_REST_Response(['message' => 'Unauthorized'], 401);
        }

        // For development only
        // $current_user_id = 1;

        $per_page = absint($request->get_param('per_page') ?? 20);
        $page     = absint($request->get_param('page') ?? 1);
        $orderby  = sanitize_sql_orderby($request->get_param('orderby') ?? 'id');
        $order    = strtoupper($request->get_param('order') ?? 'DESC');

        $allowed_orderby = ['id', 'average', 'votes', 'value'];
        $allowed_order   = ['ASC', 'DESC'];

        if (!in_array($orderby, $allowed_orderby)) $orderby = 'id';
        if (!in_array($order, $allowed_order)) $order = 'DESC';

        $offset = ($page - 1) * $per_page;

        $query = $wpdb->prepare("
        SELECT a.*
        FROM {$wpdb->prefix}rmp_analytics a
        INNER JOIN (
            SELECT post, MAX(time) AS max_time
            FROM {$wpdb->prefix}rmp_analytics
            WHERE user = %d
            GROUP BY post
        ) latest
        ON a.post = latest.post AND a.time = latest.max_time
        WHERE a.user = %d
        ORDER BY a.$orderby $order
        LIMIT %d OFFSET %d
    ", $current_user_id, $current_user_id, $per_page, $offset);

        $results = $wpdb->get_results($query, ARRAY_A);

        foreach ($results as &$item) {
            $item['time'] = date('d-m-Y H:i:s', strtotime($item['time'] . ' UTC'));

            $item['user_display'] = wp_get_current_user()->user_login;

            $post_id = absint($item['post']);
            $item['post_title'] = get_the_title($post_id);
            $item['post_url'] = get_permalink($post_id);

            if ($item['duration'] == -1) {
                $item['duration_display'] = 'AMP - n/a';
            } else {
                $item['duration_display'] = $item['duration'] . ' seconds';
            }
        }

        // Total count for pagination
        $total_query = $wpdb->prepare("
            SELECT COUNT(*) FROM (
                SELECT post
                FROM {$wpdb->prefix}rmp_analytics
                WHERE user = %d
                GROUP BY post
            ) AS latest
        ", $current_user_id);
        $total = $wpdb->get_var($total_query);

        return rest_ensure_response([
            'total'    => (int) $total,
            'per_page' => $per_page,
            'page'     => $page,
            'data'     => $results
        ]);
    }

    private function register_analytics_route()
    {
        register_rest_route($this->rest_namespace(), '/' . $this->analitycs_rest_base(), [
            'methods'  => 'GET',
            'callback' => [$this, 'get_user_analytics'],
            //'permission_callback' => '__return_true'
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);
    }

    public function register_routes() {
        $this->register_analytics_route();
    }

    public function is_developer_user($user) {
        return in_array($this->developer_role_to_string(), (array) $user->roles);
    }

    public function is_current_user_developer() {
        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->exists()) {
            return false;
        }
        return $this->is_developer_user($current_user);
    }

    public function load_hooks() {
        // Implement hook loading logic here if needed
    }
}
