<?php
namespace InvoiceGenerator\Models;

use InvoiceGenerator\Requests\TogglRequest;
use DateTime;

class TogglProject extends BaseModel {

  public function get_link(){
    return $this->id ? "https://track.toggl.com/{$this->workspace_id}/projects/{$this->id}/team" : false;
  }

  public function get_summary_link( DateTime $start_date = null, DateTime $end_date = null ){
    if( !is_null( $start_date ) || !is_null ( $end_date ) ){
      $period = "";
      
      if( $start_date ){
        $period .= "/from/" . $start_date->format('Y-m-d');
      }

      if( $end_date ) {
        $period .= "/to/" . $end_date->format('Y-m-d');
      }

    } else {
      $period = "/period/thisWeek";
    }

    return "https://track.toggl.com/reports/summary/{$this->workspace_id}{$period}/projects/{$this->id}";
  }
}