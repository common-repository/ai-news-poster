<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// List of all the option names to delete
$option_names = array(
    'ainewsposter_bing_api_key',
    'ainewsposter_openai_api_key',
    'ainewsposter_pagepixels_api_key',
    'ainewsposter_news_query',
    'ainewsposter_news_language',
    'ainewsposter_news_count',
    'ainewsposter_news_sortby',
    'ainewsposter_news_freshness',
    'ainewsposter_news_mkt',
    'ainewsposter_openai_model',
    'ainewsposter_article_prompt',
    'ainewsposter_article_author',
    'ainewsposter_auto_publish',
    'ainewsposter_article_categories',
    'ainewsposter_article_tags',
    'ainewsposter_last_tab_index'
);

// Loop through the options and delete them
foreach ($option_names as $option_name) {
    delete_option($option_name);
}
delete_transient('ainewsposter-redirect');
