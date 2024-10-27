<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class AINewsPosterArticleProcessor {
  private $api_key;
  private $article_url;

  public function __construct($api_key,$article_url) {
    $this->api_key = $api_key;
    $this->article_url = $article_url;
  }

  public function get_article_body() {
    $url = 'https://api.pagepixels.com/snap';
    $params = array(
      'url' => $this->article_url,
      'access_token' => $this->api_key,
      'html_only' => 'true'
    );

    $response = wp_remote_get(add_query_arg($params, $url), array(
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      // Handle error; possibly return WP_Error
      return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $html = $data['html'] ?? '';

    if (empty($html)) {
      if($data['error_code'] == "usage_limit_exceeded"){
        $response = [
          "success" => false,
          "error" => $data['error_code']
        ];
        return $response;
      } else {
        return null;
      }
    }

    $response = [
      "success" => true,
      "html" => $this->extract_article_text($html)
    ];
    return $response;
  }

  private function extract_article_text($html) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//script | //style') as $node) {
        $node->parentNode->removeChild($node);
    }

    $bestElement = null;
    $maxScore = 0;

    // Iterate over potential content elements
    $contentNodes = $xpath->query('//article | //section | //main');
    foreach ($contentNodes as $node) {
        if ($this->isLikelyContentNode($node)) {
            $score = $this->calculateContentScore($node);
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestElement = $node;
            }
        }
    }
    
    if ($bestElement) {
        $text = $bestElement->textContent;
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

    return '';
  }

  private function isLikelyContentNode($node) {
      // Implement logic to filter out non-content elements based on id, class, etc.
      $class = $node->getAttribute('class');
      $id = $node->getAttribute('id');

      if (preg_match('/(footer|header|menu|nav|sidebar)/i', $class) || preg_match('/(footer|header|menu|nav|sidebar)/i', $id)) {
          return false;
      }
      return true;
  }

  private function calculateContentScore($node) {
      // Implement a scoring system based on paragraph count, text length, link density, etc.
      $textLength = strlen(trim($node->textContent));
      $paragraphs = $node->getElementsByTagName('p')->length;
      $links = $node->getElementsByTagName('a')->length;

      $linkDensity = $links / ($textLength + 1);
      $score = $paragraphs * 50 - $linkDensity * 50;

      return $score;
  }


}