<?php
/**
 * Plugin Name: AI News Poster
 * Plugin URI: https://ainewsposter.com/
 * Description: AI news summarization and aggregation service for WordPress. 
 * Version: 1.0.0
 * Author: Osiris Development LLC
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'classes/news_fetcher.php';
require_once plugin_dir_path(__FILE__) . 'classes/article_processor.php';
require_once plugin_dir_path(__FILE__) . 'classes/article_generator.php';

// Function to add a submenu item in the Settings menu
function ainewsposter_admin_menu() {
  add_management_page(
    'AI News Poster',
    'AI News Poster',
    'manage_options',
    'ainewsposter-setup',
    'ainewsposter_page_dispatcher'
  );
}
add_action('admin_menu', 'ainewsposter_admin_menu');

function ainewsposter_configuration_page() {
  // Enqueue jQuery UI for tabs
  wp_enqueue_script('jquery-ui-tabs');
  wp_enqueue_style('jquery-ui-css', plugins_url('css/vendor/jquery-ui.css', __FILE__));
  wp_enqueue_style('select2', plugins_url('css/vendor/select2.min.css', __FILE__));
  wp_enqueue_script('select2', plugins_url('js/vendor/select2.min.js', __FILE__), array('jquery'));
  $last_tab_index = get_option('ainewsposter_last_tab_index', 0);
  // Get options for Post Configuration summary
  $news_query = get_option('ainewsposter_news_query');
  $article_prompt = get_option('ainewsposter_article_prompt');
  $article_author = get_option('ainewsposter_article_author') ? get_user_by('id', get_option('ainewsposter_article_author'))->display_name : 'Random Author';
  $news_count = get_option('ainewsposter_news_count');
  $selected_categories = get_option('ainewsposter_article_categories', array());
  $selected_tags = get_option('ainewsposter_article_tags', array());

  ?>
  <div class="wrap ainewsposter-container">
    <h1>AI News Poster</h1>

    <form method="post" action="options.php">
      <?php wp_nonce_field('ainewsposter_nonce_action', 'ainewsposter_nonce'); ?>
      <?php settings_fields('ainewsposter_config_group'); ?>
      <!-- Hidden field to store the last active tab index -->
      <input type="hidden" id="ainewsposter_last_tab_index" name="ainewsposter_last_tab_index" value="<?php echo esc_attr($last_tab_index); ?>">

      <!-- Tab Navigation -->
      <div id="ainewsposter-tabs">
        <ul>
          <li><a href="#tab-keys">API Keys</a></li>
          <li><a href="#tab-article">Post Configuration</a></li>
          <li><a href="#tab-bing">Bing Search API</a></li>
          <li><a href="#tab-openai">OpenAI API</a></li>
          <li><a href="#tab-generate-articles">Generate Articles</a></li>
        </ul>
        <!-- API Keys -->
        <div id="tab-keys">
          <h2 class="generate-articles-header">API Keys</h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">Bing News API Key:</th>
              <td>
                <input type="text" name="ainewsposter_bing_api_key" value="<?php echo esc_attr(get_option('ainewsposter_bing_api_key')); ?>" />
                <p class="description"><a href="https://www.microsoft.com/en-us/bing/apis/bing-news-search-api" tabindex="-1" target="_blank">Bing News Search API</a> retrieves the latest articles for rewriting / summarization</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">PagePixels API Key:</th>
              <td>
                <input type="text" name="ainewsposter_pagepixels_api_key" value="<?php echo esc_attr(get_option('ainewsposter_pagepixels_api_key')); ?>" />
                <p class="description"><a href="https://pagepixels.com/app/documentation" tabindex="-1" target="_blank">PagePixels</a> retrieves the article's content for rewriting / summarization. After creating your account, you can find your API key in your <a href='https://pagepixels.com/app/users' tabindex="-1" target="_blank">user profile</a>. You'll want to use the Private API Key.</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">OpenAI API Key:</th>
              <td>
                <input type="text" name="ainewsposter_openai_api_key" value="<?php echo esc_attr(get_option('ainewsposter_openai_api_key')); ?>" />
                <p class="description"><a href="https://platform.openai.com/apps" tabindex="-1" target="_blank">OpenAI</a> rewrites / summarizes the articles.</p>
              </td>
            </tr>
          </table>
        </div>
        <!-- Article Configuration Tab -->
        <div id="tab-article">
          <h2 class="generate-articles-header">Post Configuration</h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">News Topics:</th>
              <td>
                <input type="text" name="ainewsposter_news_query" value="<?php echo esc_attr(get_option('ainewsposter_news_query')); ?>" />
                <p class="description">What sort of news do you want to aggregate?</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Total Posts to Generate:</th>
              <td>
                <input type="number" name="ainewsposter_news_count" value="<?php echo esc_attr(get_option('ainewsposter_news_count')); ?>" />
                <p class="description">How many articles should be retrieved and rewritten / summarized? <br />If you prefer a fully automated solution that will post articles when you decide, <a href="https://ainewsposter.com" target="_blank">contact us</a>, we can help.</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Automatically Publish:</th>
              <td>
                <?php $auto_publish = get_option('ainewsposter_auto_publish', 'no'); ?>
                <div>
                  <label for="ainewsposter_auto_publish_yes">
                    <input type="radio" name="ainewsposter_auto_publish" id="ainewsposter_auto_publish_yes" value="yes" <?php checked($auto_publish, 'yes'); ?> />
                    Publish
                  </label>
                </div>
                <div>
                  <label for="ainewsposter_auto_publish_no">
                    <input type="radio" name="ainewsposter_auto_publish" id="ainewsposter_auto_publish_no" value="no" <?php checked($auto_publish, 'no'); ?> />
                    Draft
                  </label>
                </div>
                <p class="description">Should the articles be published immediately upon generation, or should they be created as drafts?<br />Content retrieval and summarization may not be perfect on every site. Be sure to double check the post's content.</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">How should the AI rewrite / summarize the articles?</th>
              <td>
                <input type="text" name="ainewsposter_article_prompt" value="<?php echo esc_attr(get_option('ainewsposter_article_prompt')); ?>" />
                <p class="description">Tell the AI how to write / summarize the articles. (e.g., Write a quick blurb about this article containing 2 or more paragraphs)</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Post Author:</th>
              <td>
                <?php 
                  wp_dropdown_users(array(
                    'name' => 'ainewsposter_article_author',
                    'selected' => get_option('ainewsposter_article_author'),
                    'show_option_none' => 'Random Author',
                    'option_none_value' => '0' // 0 or another value to indicate random
                  ));
                ?>
                <p class="description">Select the author for the generated articles. If the 'Random Author' option is selected, AI News Poster will select a random wordpress Author for each new article.</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Article Categories:</th>
              <td>
                <select name="ainewsposter_article_categories[]" id="ainewsposter_article_categories" class="ainewsposter-select2" multiple="multiple" style="width:100%;">
                  <?php 
                  $categories = get_categories(array('hide_empty' => 0));
                  foreach ($categories as $category) {
                      $selected = gettype($selected_categories) == "string" ? false : (in_array($category->term_id, $selected_categories) ? ' selected' : '');
                      echo '<option value="' . esc_attr($category->term_id) . '"' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                  }
                  ?>
                </select>
              </td>
            </tr>

            <tr valign="top">
              <th scope="row">Article Tags:</th>
              <td>
                <select name="ainewsposter_article_tags[]" id="ainewsposter_article_tags" class="ainewsposter-select2" multiple="multiple" style="width:100%;">
                  <?php 
                  $tags = get_tags(array('hide_empty' => 0));
                  foreach ($tags as $tag) {
                      $selected = gettype($selected_tags) == "string" ? false : (in_array($tag->term_id, $selected_tags) ? ' selected' : '');
                      echo '<option value="' . esc_attr($tag->term_id) . '"' . esc_attr($selected) . '>' . esc_html($tag->name) . '</option>';
                  }
                  ?>
                </select>
              </td>
            </tr>
          </table>
        </div>

        <!-- Bing Search API Tab -->
        <div id="tab-bing">
          <h2 class="generate-articles-header">Bing Search API</h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">Language:</th>
              <td>
                <input type="text" name="ainewsposter_news_language" value="<?php echo esc_attr(get_option('ainewsposter_news_language')); ?>" />
                <p class="description">Specify the language (e.g., 'en').</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Sort By:</th>
              <td>
                <input type="text" name="ainewsposter_news_sortby" value="<?php echo esc_attr(get_option('ainewsposter_news_sortby')); ?>" />
                <p class="description">Sort the articles (e.g., 'Date').</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Freshness:</th>
              <td>
                <input type="text" name="ainewsposter_news_freshness" value="<?php echo esc_attr(get_option('ainewsposter_news_freshness')); ?>" />
                <p class="description">Filter by freshness (e.g., 'Day').</p>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row">Market:</th>
              <td>
                <input type="text" name="ainewsposter_news_mkt" value="<?php echo esc_attr(get_option('ainewsposter_news_mkt')); ?>" />
                <p class="description">Specify the market (e.g., 'en-US').</p>
              </td>
            </tr>
          </table>
        </div>

        <!-- OpenAI API Tab -->
        <div id="tab-openai">
          <h2 class="generate-articles-header">OpenAI API</h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">OpenAI Chat Model:</th>
              <td>
                <input type="text" name="ainewsposter_openai_model" value="<?php echo esc_attr(get_option('ainewsposter_openai_model')); ?>" />
                <p class="description">Enter the model to use for rewriting / summarizing (e.g., 'gpt-3.5-turbo').</p>
              </td>
            </tr>
          </table>
        </div>
      <!-- Generate Articles Tab -->
        <div id="tab-generate-articles" class="generate-articles-container">
          <h2 class="generate-articles-header">Generate Articles</h2>
          <p class="generate-articles-summary">Summary of Post Configuration:</p>
          <ul class="generate-articles-list">
            <li class="generate-articles-list-item"><strong>News Topics:</strong> <?php echo esc_html($news_query); ?></li>
            <li class="generate-articles-list-item"><strong>Article Prompt:</strong> <?php echo esc_html($article_prompt); ?></li>
            <li class="generate-articles-list-item"><strong>Article Author:</strong> <?php echo esc_html($article_author); ?></li>
            <li class="generate-articles-list-item"><strong>Total Posts to Generate:</strong> <?php echo esc_html($news_count); ?></li>
          </ul>
          <button type="button" class="button button-primary generate-articles-button">Generate Articles</button>
          <div class="loader" style="display: none;">
            <div></div><div></div><div></div><div></div>
          </div>
        </div>
        <input type="submit" name="submit" id="submit" class="button button-primary ainewsposter-save-button" value="Save Configuration">
      </div>
    </form>
  </div>

  <script>
    jQuery(document).ready(function($) {
      $('.ainewsposter-select2').select2();
      
      var tabs = $("#ainewsposter-tabs").tabs({
        activate: function(event, ui) {
          var active = tabs.tabs("option", "active");
          $("#ainewsposter_last_tab_index").val(active);

          // Toggle the Save Configuration button based on the active tab
          if (ui.newPanel.attr('id') === 'tab-generate-articles') {
            $('.ainewsposter-save-button').hide();
          } else {
            $('.ainewsposter-save-button').show();
          }
        }
      });

      // Initial set up: hide the button if the initial tab is Generate Articles
      var lastTabIndex = $("#ainewsposter_last_tab_index").val();
      if (lastTabIndex !== undefined) {
        tabs.tabs("option", "active", parseInt(lastTabIndex));
        if (parseInt(lastTabIndex) === $('#ainewsposter-tabs a[href="#tab-generate-articles"]').parent().index()) {
          $('.ainewsposter-save-button').hide();
        }
      }
    });
  </script>
  <?php
}
add_action('admin_init', 'ainewsposter_register_config_settings');
function ainewsposter_save_last_tab_index() {
  if ( ! isset( $_POST['ainewsposter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['ainewsposter_nonce'] ) ) , 'ainewsposter_nonce_action' ) ) {
    return;
  }elseif (isset($_POST['ainewsposter_last_tab_index'])) {
    update_option('ainewsposter_last_tab_index', sanitize_text_field($_POST['ainewsposter_last_tab_index']));
  }
}
add_action('admin_init', 'ainewsposter_save_last_tab_index');

function ainewsposter_ajax_fetch_articles() {
  check_ajax_referer('ainewsposter_ajax_nonce', 'nonce');
  // Retrieve configuration options
  $fetch_api_key = get_option('ainewsposter_bing_api_key');
  $query = get_option('ainewsposter_news_query');
  $language = get_option('ainewsposter_news_language', 'en');
  $count = get_option('ainewsposter_news_count', 30);
  $sortBy = get_option('ainewsposter_news_sortby', 'Date');
  $freshness = get_option('ainewsposter_news_freshness', 'Day');
  $mkt = get_option('ainewsposter_news_mkt', 'en-US');

  // Initialize AINewsPosterNewsFetcher with the configuration options
  $news_fetcher = new AINewsPosterNewsFetcher($fetch_api_key, $query, $language, $count, $sortBy, $freshness, $mkt);

  // Fetch the latest news
  $news_items = $news_fetcher->fetch_latest_news();

  if (is_array($news_items) && count($news_items) > 0) {
      wp_send_json_success($news_items);
  } else {
      wp_send_json_error('No articles fetched or an error occurred.');
  }

  wp_die();
}
add_action('wp_ajax_ainewsposter_fetch_articles', 'ainewsposter_ajax_fetch_articles');

function ainewsposter_ajax_check_for_duplicate_posts() {
  check_ajax_referer('ainewsposter_ajax_nonce', 'nonce');
  // Sanitize and validate the input
  
  $article = isset($_POST['article']) ? $_POST['article'] : null;

  if (is_array($article) && isset($article['url'])) {
      // Sanitize the URL
      $article_url = sanitize_text_field($article['url']);

      // Validate the URL (Example: Check if it's a valid URL format)
      if (!filter_var($article_url, FILTER_VALIDATE_URL)) {
          // If the URL is not valid, send a JSON error
          wp_send_json_error('Invalid URL');
          wp_die();
      }

      // Fetch existing posts with the given meta_value
      $existing_posts = get_posts(array(
          'meta_key' => 'original_news_url',
          'meta_value' => $article_url,
          'post_status' => array('publish', 'draft'),
          'posts_per_page' => 1
      ));

      if (count($existing_posts) > 0) {
          // If existing posts are found, send the ID as a JSON error
          wp_send_json_error($existing_posts[0]->ID);
          wp_die();
      } else {
          // Since $article is an array, it's better to escape each element before outputting.
          $safe_article = array_map('esc_html', $article);
          wp_send_json_success($safe_article);
          wp_die();
      }
  } else {
      // If article is not set or not an array, send a JSON error
      wp_send_json_error('No article data provided');
      wp_die();
  }
}
add_action('wp_ajax_ainewsposter_check_for_duplicate_posts', 'ainewsposter_ajax_check_for_duplicate_posts');

function ainewsposter_ajax_process_article() {
  check_ajax_referer('ainewsposter_ajax_nonce', 'nonce');
  $pagepixels_api_key = get_option('ainewsposter_pagepixels_api_key');
  $article = isset($_POST['article']) ? $_POST['article'] : null;

  if (!$article || !is_array($article)) {
      wp_send_json_error('No article provided or invalid format.');
      wp_die();
  }

  // Sanitize and validate each element in the article array
  $article_url = isset($article['url']) ? sanitize_text_field($article['url']) : null;
  $article_name = isset($article['name']) ? sanitize_text_field($article['name']) : null;
  $article_image = isset($article['image']) ? sanitize_text_field($article['image']) : null;

  // Validate URL
  if (!filter_var($article_url, FILTER_VALIDATE_URL)) {
      wp_send_json_error('Invalid URL provided.');
      wp_die();
  }

  // Proceed with processing
  $article_processor = new AINewsPosterArticleProcessor($pagepixels_api_key, $article_url);
  $content = $article_processor->get_article_body();
  
  if(!$content['success']){
    if($content['error'] && $content['error'] == "usage_limit_exceeded"){
      wp_send_json_error($content['error']);
      wp_die();
    }else{
      wp_send_json_error('No content processed.');
      wp_die();
    }
  } else { 
    if ($content['html']) {
        $processed_article = [
            'title' => esc_html($article_name),
            'content' => esc_html($content['html']),
            'url' => esc_url($article_url),
            'image' => esc_url($article_image)
        ];
        wp_send_json_success($processed_article);
    } else {
        wp_send_json_error('No content processed.');
    }
  }
  wp_die();
}
add_action('wp_ajax_ainewsposter_process_article', 'ainewsposter_ajax_process_article');

function ainewsposter_ajax_generate_article() {
  check_ajax_referer('ainewsposter_ajax_nonce', 'nonce');
  $openai_api_key = get_option('ainewsposter_openai_api_key');
  $openai_model = get_option('ainewsposter_openai_model', 'gpt-3.5-turbo');
  $auto_publish = get_option('ainewsposter_auto_publish', 'no');
  $post_author_config = get_option('ainewsposter_article_author');
  $article = isset($_POST['article']) ? $_POST['article'] : null;

  if (!$article || !is_array($article) || !isset($article['data'])) {
    wp_send_json_error('No article content provided or invalid format.');
    wp_die();
  }
  // Sanitize and validate the article data
  $article_content = isset($article['data']['content']) ? sanitize_text_field($article['data']['content']) : null;
  $article_title = isset($article['data']['title']) ? sanitize_text_field($article['data']['title']) : null;
  $article_url = isset($article['data']['url']) ? esc_url_raw($article['data']['url']) : null;

  // Validate URL
  if (!filter_var($article_url, FILTER_VALIDATE_URL)) {
      wp_send_json_error('Invalid URL provided.');
      wp_die();
  }
  $prompt = get_option('ainewsposter_article_prompt');
  $prompt = $prompt . "\n\n" . $article['data']['content'];
  $article_generator = new AINewsPosterArticleGenerator(
      $openai_api_key, 
      $prompt, 
      $openai_model
  );
  $rewritten_content = $article_generator->generate();

  if ($rewritten_content) {
    $selected_categories = get_option('ainewsposter_article_categories', array());
    $selected_tags = get_option('ainewsposter_article_tags', array());
    $selected_categories = is_array($selected_categories) ? array_map('intval', $selected_categories) : array();
    $selected_tags = is_array($selected_tags) ? array_map('intval', $selected_tags) : array();
    // Check if a post with the same original URL already exists
    $post_data = [
      'post_title'   => esc_html($article_title, ENT_NOQUOTES | ENT_HTML5),
      'post_content' => esc_html($rewritten_content) . "\n\n" . '<p><a href="' . esc_url($article_url) . '" target="_blank">Read the original article</a></p>',
      'post_status' => $auto_publish === 'yes' ? 'publish' : 'draft',
      'post_author' => $post_author_config == '0' ? ainewsposter_get_random_author_id() : $post_author_config,
      'post_category'=> $selected_categories,
      'tags_input'   => $selected_tags, 
    ];
    // Insert new post
    $post_id = wp_insert_post($post_data);
    if ($post_id) {
        // Store the original URL in post meta
        add_post_meta($post_id, 'original_news_url', $article['data']['url']);
        // Set the featured image
        if (isset($article['data']['image']['contentUrl'])) {
          $image_url = $article['data']['image']['contentUrl'];
          $image_set = ainewsposter_set_featured_image_from_url($post_id, $image_url);
        }
        wp_send_json_success(['message' => 'Post created successfully', 'post_id' => $post_id]);
    } else {
        wp_send_json_error('Failed to create post.');
    }
  } else {
    wp_send_json_error('Failed to generate rewritten content.');
  }

  wp_die();
}
add_action('wp_ajax_ainewsposter_generate_article', 'ainewsposter_ajax_generate_article');

function ainewsposter_set_featured_image_from_url($post_id, $image_url) {
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/media.php');

  // Download image to server
  $image_tmp = download_url($image_url);
  if (is_wp_error($image_tmp)) {
      // Handle error
      return false;
  }

  // Prepare an array of post data for the attachment.
  $file_array = array(
      'name' => basename($image_url),
      'tmp_name' => $image_tmp
  );

  // Check for valid image
  $filetype = wp_check_filetype(basename($image_url), null);
  if (!$filetype['type']) {
      @unlink($image_tmp);
      return false;
  }

  // Upload the image
  $attachment_id = media_handle_sideload($file_array, $post_id, 'Image Description');
  
  // Check for handle sideload errors.
  if (is_wp_error($attachment_id)) {
      @unlink($file_array['tmp_name']);
      return false;
  }

  // Set the image as the featured image
  set_post_thumbnail($post_id, $attachment_id);

  return true;
}

function ainewsposter_get_random_author_id() {
  $users = get_users(array('role__in' => array('author', 'administrator'), 'fields' => 'ID'));
  return $users[array_rand($users)];
}

function ainewsposter_enqueue_styles() {
  // Get the URL of the stylesheet
  $css_url = plugin_dir_url( __FILE__ ) . 'css/style.css';
  // Enqueue the stylesheet
  wp_enqueue_style( 'ainewsposter-styles', $css_url );
}
add_action( 'admin_enqueue_scripts', 'ainewsposter_enqueue_styles' );

function ainewsposter_enqueue_admin_scripts() {
  // Custom script to initialize Select2
  wp_enqueue_script('ainewsposter-admin', plugin_dir_url(__FILE__) . 'js/ainewsposter-admin.js', array('jquery', 'select2'));
  wp_enqueue_style('ainewsposter-admin-style', plugin_dir_url(__FILE__) . 'css/ainewsposter-admin.css');

  wp_enqueue_script('ainewsposter-ajax-script', plugin_dir_url(__FILE__) . 'js/ainewsposter-ajax.js', array('jquery'));

  wp_localize_script('ainewsposter-ajax-script', 'ainewsposter_ajax_obj', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ainewsposter_ajax_nonce')
  ));
}
add_action('admin_enqueue_scripts', 'ainewsposter_enqueue_admin_scripts');

function ainewsposter_register_config_settings() {
  //API Keys
  add_option('ainewsposter_bing_api_key', '');
  add_option('ainewsposter_openai_api_key', '');
  add_option('ainewsposter_pagepixels_api_key', '');
  register_setting('ainewsposter_config_group', 'ainewsposter_bing_api_key');
  register_setting('ainewsposter_config_group', 'ainewsposter_openai_api_key');
  register_setting('ainewsposter_config_group', 'ainewsposter_pagepixels_api_key');
  //Microsoft Search API
  add_option('ainewsposter_news_query', 'AI News');
  add_option('ainewsposter_news_language', 'en');
  add_option('ainewsposter_news_count', 3);
  add_option('ainewsposter_news_sortby', 'Date');
  add_option('ainewsposter_news_freshness', 'Day');
  add_option('ainewsposter_news_mkt', 'en-US');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_query');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_language');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_count');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_sortby');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_freshness');
  register_setting('ainewsposter_config_group', 'ainewsposter_news_mkt');
  //OpenAI API
  add_option('ainewsposter_openai_model', 'gpt-3.5-turbo');
  register_setting('ainewsposter_config_group', 'ainewsposter_openai_model');
  //Article Configuration
  add_option('ainewsposter_article_prompt', 'Write a quick blurb about this article containing 2 or more paragraphs');
  add_option('ainewsposter_article_author', '');
  add_option('ainewsposter_auto_publish', 'no');
  register_setting('ainewsposter_config_group', 'ainewsposter_article_prompt');
  register_setting('ainewsposter_config_group', 'ainewsposter_article_author', 'absint');
  register_setting('ainewsposter_config_group', 'ainewsposter_auto_publish');
  // Register settings for Article Categories and Tags
  add_option('ainewsposter_article_categories', array());
  add_option('ainewsposter_article_tags', array());
  register_setting('ainewsposter_config_group', 'ainewsposter_article_categories');
  register_setting('ainewsposter_config_group', 'ainewsposter_article_tags');
}

function ainewsposter_page_dispatcher() {
  ainewsposter_configuration_page();
}

// Set a transient when the plugin is activated
function ainewsposter_activation_redirect() {
  set_transient('ainewsposter-redirect', true, 5);
}
register_activation_hook(__FILE__, 'ainewsposter_activation_redirect');

// Redirect to the settings page on admin_init if the transient is set
function ainewsposter_redirect_to_settings() {
  if (get_transient('ainewsposter-redirect')) {
    delete_transient('ainewsposter-redirect');
    if (!isset($_GET['activate-multi'])) {
      wp_redirect(admin_url('tools.php?page=ainewsposter-setup'));
    }
  }
}
add_action('admin_init', 'ainewsposter_redirect_to_settings');