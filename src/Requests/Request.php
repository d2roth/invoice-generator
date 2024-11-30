<?php
namespace InvoiceGenerator\Requests;

class Request {

  private static $instances;
  private $cache_dir;

  function __construct(array $config = []){

    $this->cache_dir = $config['cache_dir'] ?? __DIR__ . '/../../cache/';

    if( !is_dir($this->cache_dir) ){
      @mkdir($this->cache_dir, 0744);
    }
  }

  public static function get_instance(array $config = []): self
  {
    if (!isset(self::$instances[static::class])) {
      self::$instances[static::class] = new static($config);
    }

    return self::$instances[static::class];
  }


  protected function cache( $key, $callback, $timeout = null ){
    if( is_null( $timeout ) ){
      $timeout = defined('CACHE_TTL') ? CACHE_TTL : 500;
    }
    $cache_file = $this->cache_dir . '/' . $key . '.cache';

    // TODO: Test for valid cache creation
    if(!file_exists($cache_file) || filemtime($cache_file) < time() - $timeout ){
      file_put_contents($cache_file, json_encode( $callback() ) );
    }

    return json_decode( file_get_contents( $cache_file ) );
  }

};