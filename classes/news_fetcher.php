<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class AINewsPosterNewsFetcher {
  private $query;
  private $language;
  private $count;
  private $sortBy;
  private $freshness;
  private $mkt;

  public function __construct($api_key, $query, $language = 'en', $count = 30, $sortBy = 'Date', $freshness = 'Day', $mkt = 'en-US') {
    $this->api_key = $api_key;
    $this->query = $query;
    $this->language = $language;
    $this->count = $count;
    $this->sortBy = $sortBy;
    $this->freshness = $freshness;
    $this->mkt = $mkt;
  }

  public function fetch_latest_news() {
    $url = 'https://api.bing.microsoft.com/v7.0/news/search';
    $params = array(
      'q' => $this->query,
      'count' => $this->count,
      'setLang' => $this->language,
      'sortBy' => $this->sortBy,
      'freshness' => $this->freshness,
      'originalImg' => 'true',
      'mkt' => $this->mkt,
      'textFormat' => 'Raw'
    );
    
    $response = wp_remote_get(add_query_arg($params, $url), array(
      'headers' => array(
        'Ocp-Apim-Subscription-Key' => $this->api_key
      )
    ));

    if (is_wp_error($response)) {
      // Handle error; possibly return WP_Error
      return WP_Error($response);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['value'])) {
      return $data['value'];
    }

    return array();
  }
}
