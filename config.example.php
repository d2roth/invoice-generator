<?php

require_once('./vendor/autoload.php');

use InvoiceGenerator\Models\Client;
use InvoiceGenerator\Requests\TogglRequest;

$clients = [
  new Client([
    'title' => 'Company Name',
    'toggl_client_id' => 0,
    'frequency' => 'bi-weekly', // Frequency can be 'bi-weekly' or 'weekly' or a DateInterval period
    'period_start_date' => 'May 3, 2024', // The last date that this client was invoiced for
  ]),
];

define( 'TOGGL_WORKSPACE_ID', '' );
define( 'TOGGL_API_KEY',      '' );
define( 'TOGGL_BASE_URL',     'https://track.toggl.com' );


// Initalize Toggl instance
TogglRequest::get_instance([
  'base_url'     => TOGGL_BASE_URL,
  'api_key'      => TOGGL_API_KEY,
  'workspace_id' => TOGGL_WORKSPACE_ID,
]);