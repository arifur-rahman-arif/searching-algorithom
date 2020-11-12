<?php

/* Custom Functionnality */


add_action('wp_ajax_ab_form_search', 'ab_form_search');
add_action('wp_ajax_nopriv_ab_form_search', 'ab_form_search');


function ab_form_search() {

    if ($_POST['action'] != 'ab_form_search') {
        wp_die();
    }
    parse_str($_POST['form_data'], $parsed_data);

    if (!isset($parsed_data['ab_search_nonce']) || !wp_verify_nonce($parsed_data['ab_search_nonce'],  'ab_search_nonce_action')) {
        wp_die();
    }
    $sanitzed_parsed_data = [
        's_appointment' => sanitize_text_field($parsed_data['s_appointment']),
    ];

    $result = [];

    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        's' => $sanitzed_parsed_data['s_appointment']
    ]);
    if ($posts) {
        foreach ($posts as $post) {
            $result[] = [
                'ID' => $post->ID,
                'post_titie' => $post->post_title,
            ];
        }
    } else {
        global $wpdb;
        $db_results = [];
        $res = $wpdb->get_results('SELECT * FROM wp_posts WHERE  post_type="post" AND post_status="publish"');
        if ($res) {
            foreach ($res as $post) {
                $db_results[] = [
                    'ID' => $post->ID,
                    'post_titie' => $post->post_title,
                    'count' => 0
                ];
            }
        }
        $search_keys = explode(" ", $sanitzed_parsed_data['s_appointment']);
        foreach ($search_keys as $search_key) {
            array_walk($db_results, function (&$db_result, $key, $search_key) {
                $length = (2 + strlen($search_key));
                $matched_count = substr_count((" " . strtolower($db_result['post_titie']) . " "), str_pad(strtolower($search_key), $length, " ", STR_PAD_BOTH));
                if ($matched_count == 0) {
                    $matched_count = substr_count((strtolower($db_result['post_titie'])), strtolower($search_key));
                }
                $db_result['count'] = ($db_result['count'] + $matched_count);
            }, $search_key);
        };

        usort($db_results, function ($a, $b) {
            return $a['count'] < $b['count'];
        });

        $result = array_filter($db_results, function ($val) {
            return $val['count'] > 0;
        });
    }


    print_r($result);

    wp_die();
}
