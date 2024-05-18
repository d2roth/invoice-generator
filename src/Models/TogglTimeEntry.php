<?php
namespace InvoiceGenerator\Models;

class TogglTimeEntry extends BaseModel {

  public $parsed_description;
  public $type = 'other';

  function __construct($time_entry){
    parent::__construct($time_entry);

    $this->parsed_description = new \stdClass();
    $this->parse_description($this->description);
  }

  private function parse_description( $description = ""){

    $types = [
      'issue' => '^(?<description>.*)- Issue #(?<id>\d+)$',
      'ticket' => '^(?<description>.*)- Ticket #(?<id>\d+)$',
    ];
    
    $matched = false;

    foreach( $types as $type => $regex){
      $matched = preg_match( '/' . $regex . '/i', $description, $matches );
      if( $matched ){
        $this->type    = $type;
        break;
      }
    }

    // If we matched and have an array with matched parts save them for easy reference
    if( $matched && is_array( $matches ) ){
      foreach( $matches as $key => $value ){
        // Only save the named groups
        if( is_string($key) ){
          $this->parsed_description->{$key} = trim( $value );
        }
      }
    }
  }

  public function get_link(){
    $links = [
      'issue' => 'https://sentry.io/issues/{id}/',
      'ticket' => 'https://fireside.teamwork.com/desk/tickets/{id}/messages',
    ];

    return isset( $links[$this->type] ) ? str_replace("{id}", $this->parsed_description->id, $links[$this->type]) : false;
  }

  public function get_is_billable(){
    $unbillable_tags = [16010159];
    return !array_intersect($this->tag_ids, $unbillable_tags) || $this->billable;
  }
  public function get_is_invoiced(){
    $invoiced_tags   = [16049406];
    return array_intersect($this->tag_ids, $invoiced_tags);
  }
}