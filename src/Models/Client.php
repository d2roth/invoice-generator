<?php

namespace InvoiceGenerator\Models;
use InvoiceGenerator\Models\BaseModel;
use InvoiceGenerator\Requests\TogglRequest;
use \DateTime;
use \DateInterval;

class Client extends BaseModel {
  
  public $report;
  public $frequency = 'monthly';
  public $last_invoiced_date = 'April 1, 2024';
  public $next_invoice_date;

  function __construct( $attributes ){
    parent::__construct($attributes);
    $this->report = new \stdClass();

    $this->report->start_date = new DateTime($this->last_invoiced_date);

    if( $this->frequency instanceof DateInterval ){
      $this->report->start_date->add(new DateInterval("P1D"));
      $end_date = (clone $this->report->start_date)->add($this->frequency)->sub(new DateInterval("P1D"));
    } else if( $this->frequency == "bi-weekly" ){
      $this->report->start_date->add(new DateInterval("P1D"));
      $end_date = (clone $this->report->start_date)->add(new DateInterval("P2W"))->sub(new DateInterval("P1D"));
    } else { // if( $this->frequency == "monthly" ){
      $end_date = new DateTime( $this->report->start_date->format('Y-m-t') );
    }

    $this->report->end_date   = $end_date;
    $this->last_invoiced_date = (new DateTime($attributes['last_invoiced_date']));
    $this->next_invoice_date  = (clone $end_date)->add(new DateInterval("P1D"));
  }

  public function get_toggl_url(){
    return TogglRequest::get_instance()->get_client_url($this->toggl_client_id); 
  }

  public function get_time_summary(){
    return TogglRequest::get_instance()->request('/reports/api/v3/workspace/{workspace_id}/search/time_entries', 'POST', [
      "start_date" => $this->report->start_date->format('Y-m-d'),
      "client_ids" => [$this->toggl_client_id],
      "end_date"   => $this->report->end_date->format('Y-m-d'),
      "grouped"    => true,
      "page_size"  => 100, // Todo: Handle multiple pages
    ]);
  }
}