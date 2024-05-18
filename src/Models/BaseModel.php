<?php
namespace InvoiceGenerator\Models;

class BaseModel extends \stdClass{
  function __construct($attributes){
    foreach( $attributes as $key => $value ){
      $this->{$key} = $value;
    }
  }
}