<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class AINewsPosterArticleGenerator {
  private $api_key;
  private $prompt;
  private $model;

  public function __construct($api_key, $prompt, $model = 'gpt-3.5-turbo-instruct') {
    $this->api_key = $api_key;
    $this->prompt = $prompt;
    $this->model = $model;
  }

  public function generate() {
    $url = 'https://api.openai.com/v1/chat/completions';
    $body = array(
      'model' => $this->model,
      'messages' => array(
        array('role' => 'user', 'content' => $this->prompt)
      )
    );

    $response = wp_remote_post($url, array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->api_key
      ),
      'body' => wp_json_encode($body),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['choices']) && is_array($data['choices']) && !empty($data['choices'])) {
      $last_choice = end($data['choices']);
      if (isset($last_choice['message']['content'])) {
        return trim($last_choice['message']['content']);
      }
    }

    return '';
  }
}
