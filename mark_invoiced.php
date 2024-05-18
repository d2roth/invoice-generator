<?php

require './vendor/autoload.php';
require 'config.php';

use InvoiceGenerator\Requests\TogglRequest;
/*
{
  "success": [
    3414870445
  ],
  "failure": []
}
*/
// {
//   "invoiced_time_entry": [
//     "3409628582"
//   ]
// }

$invoiced_time_entries = [];
foreach( $_POST['invoiced_time_entry'] as $invoiced_group_ids ){
  foreach( explode(" ", $invoiced_group_ids) as $id ){
    if( !empty( $id ) && intval( $id ) ){
      $invoiced_time_entries[] = intval( $id );
    }
  }
}
// $invoiced_time_entries = array_map( 'intval', $_POST['invoiced_time_entry'] );

$response = TogglRequest::get_instance()->request(
  '/api/v9/workspaces/{workspace_id}/time_entries/' . implode( ",", $invoiced_time_entries ),
  'PATCH',
  [[
    "op" => "add",
    "path" => "/tags",
    "value" => ["!Invoiced"]
  ]]
);
echo json_encode(['response' => $response, 'ids' => $invoiced_time_entries ]);