<?php
// Returns the list of sitemap URLs to ping
function kaya_get_sitemaps_to_ping() {
    return array(
        'https://kayagafar.com/sitemap.xml',
        'https://kayagafar.com/news-sitemap.xml'
    );
}

// Sends a ping request to Google for each sitemap URL
function kaya_ping_google_sitemaps($trigger_context = '') {
    $sitemaps = kaya_get_sitemaps_to_ping();
    $success = false;

    foreach ($sitemaps as $sitemap_url) {
        // Send GET request to Google's sitemap ping endpoint
        $response = wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url), array(
            'timeout' => 5,
            'user-agent' => 'KayaGafarPingBot/1.0'
        ));

        // Check if the request succeeded
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $success = true;
        }
    }

    // Set a transient if at least one ping was successful
    if ($success) {
        set_transient('kaya_ping_success_' . sanitize_key($trigger_context), true, 30);
    }
}

// Hook triggered when a post or page is saved
function kaya_ping_google_sitemaps_on_change($post_id) {
    // Skip auto-saves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    // Skip revisions
    if (wp_is_post_revision($post_id)) return;

    $post_type = get_post_type($post_id);
    // Allow filtering of post types that trigger pings
    $valid_types = apply_filters('kaya_ping_post_types', array('post', 'page', 'product', 'portfolio', 'case_study'));

    // Only ping if the post type is in the allowed list
    if (!in_array($post_type, $valid_types, true)) {
        return;
    }

    // Trigger the sitemap ping with context 'post'
    kaya_ping_google_sitemaps('post');
}
add_action('save_post', 'kaya_ping_google_sitemaps_on_change');

// Hook triggered when a taxonomy term is created or edited
function kaya_ping_google_on_term_change($term_id, $tt_id, $taxonomy) {
    // Trigger the sitemap ping with context 'term'
    kaya_ping_google_sitemaps('term');
}
add_action('created_term', 'kaya_ping_google_on_term_change', 10, 3);
add_action('edited_term', 'kaya_ping_google_on_term_change', 10, 3);

// Displays admin notice if a ping was recently successful
function kaya_admin_ping_notice() {
    if (get_transient('kaya_ping_success_post')) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Google Sitemap Ping Sent Successfully (Post Change)!</p></div>';
        delete_transient('kaya_ping_success_post');
    }
    if (get_transient('kaya_ping_success_term')) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Google Sitemap Ping Sent Successfully (Taxonomy Change)!</p></div>';
        delete_transient('kaya_ping_success_term');
    }
}
add_action('admin_notices', 'kaya_admin_ping_notice');
