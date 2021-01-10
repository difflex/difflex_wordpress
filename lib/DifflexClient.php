<?php

/*
Copyright Difflex https://difflex.ru
This file is part of the difflex-for-woocommerce plugin created by Difflex.
*/

class DifflexClient {
  private $debug = false;
  private $version = '0.1.1';
  private $tracker_name = 'php-tracker';
  private $endpoint = 'https://tracker.difflex.ru/event';
  private $app_key;
  private $visitor = array();
  private $access_key = null;
  private $cookie_name = 'difflex_uid';

  public function __construct($app_key) {
    $this->app_key = (string) $app_key;
  }

  public function set_debug($value) {
    $this->debug = (bool) $value;
  }

  public function set_endpoint($endpoint) {
    $this->endpoint = (string) $endpoint;
  }

  public function set_visitor($visitor) {
    foreach ($visitor as $key=>$value) $this->visitor[$key] = $value;
    return $this->visitor;
  }

  public function set_access_key($value) {
    $this->access_key = (string) $value;
  }

  public function track($event_name, $properties = []) {
    $this->send($event_name, $properties, $data, $context, $truePerformedAt);
  }

  private function send($event_name, $properties, $data, $context, $truePerformedAt) {
    if (!isset($this->app_key)) return false;

    $cookie_uid = !empty($_COOKIE[$this->cookie_name]) ? stripslashes($_COOKIE[$this->cookie_name]) : null;

    if ($event_name == 'orderUpdate') {
      $cookie_uid = null;
      if (empty($properties['number'])) return false;
    } else {
      if (!isset($cookie_uid) && !isset($this->visitor['uid'])) return false;
    }

    $data = $data ? $data : array();
    $data['appKey'] = $this->app_key;
    $data['name'] = $event_name;
    $data['performedAt'] = time();
    if ($cookie_uid) $data['uuid'] = $cookie_uid;

    $data['clientVersion'] = $this->version;
    $data['clienType'] = 'php-tracker';
    $data['visitor'] = $this->visitor;
    $data['properties'] = $properties;

    if ($this->access_key) $data['accessKey'] = $this->access_key;

    $this->put_log($data);
    $post = http_build_query($data);
    $opts = stream_context_create(array(
      'http' => array(
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post
      )
    ));
    return @file_get_contents("{$this->endpoint}/event", false, $opts);
  }

  private function put_log($post) {
    if (!$this->debug) return true;
    ob_start();
    print_r($post);
    $str_post = ob_get_clean();
    $date = date('Y.m.d H:i:s');
    $row = "{$date}\n{$str_post}\n";
    $filename = dirname(__FILE__) . "/events.log";
    return file_put_contents($filename, $row, FILE_APPEND);
  }
}