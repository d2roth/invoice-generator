<?php
require_once('./vendor/autoload.php');
require_once('config.php');

use InvoiceGenerator\Requests\TogglRequest;
use InvoiceGenerator\Models\TogglTimeEntry;
use InvoiceGenerator\Models\TogglProject;

$projects = TogglRequest::get_instance()->request('/api/v9/workspaces/{workspace_id}/projects');
foreach( $projects as $k => $project ){
  unset( $projects[$k] );
  $projects[$project->id] = new TogglProject($project);
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Invoice Generation</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
  <style>
    .invoiced {
      -webkit-touch-callout: none; /* iOS Safari */
      -webkit-user-select: none; /* Safari */
       -khtml-user-select: none; /* Konqueror HTML */
         -moz-user-select: none; /* Old versions of Firefox */
          -ms-user-select: none; /* Internet Explorer/Edge */
              user-select: none; /* Non-prefixed version, currently
                                    supported by Chrome, Edge, Opera and Firefox */
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="row">
      <?php foreach( $clients as $index => $client ):?>
        <form class="col-6 mt-3" id="client-<?= $index;?>">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <?= htmlspecialchars($client->title);?>
                <small class="d-block">Reporting Period: <?= $client->report->start_date->format('F j, Y');?> - <?= $client->report->end_date->format('F j, Y');?></small>
                <small class="d-block">Last Invoice: <?= $client->last_invoiced_date->format('F j, Y');?></small>
                <small class="d-block">Next Invoice: <?= $client->next_invoice_date->format('F j, Y');?></small>
              </div>
              <a class="btn btn-primary" href="<?= htmlspecialchars($client->get_toggl_url());?>" target="_blank">View project</a>
            </div>
            <div class="card-body"><?php
              $time_entries = $client->get_time_summary();
              $groups = [];

              foreach( $time_entries as $entry => $details ):
                $time_entry = new TogglTimeEntry($details);

                // // If this time entry was already invoiced don't show it
                // if( $time_entry->get_is_invoiced() ){
                //   continue;
                // }

                $key = $time_entry->parsed_description->id ?? $time_entry->project_id;

                if( !isset( $groups[$time_entry->type][$key] ) ){
                  $groups[$time_entry->type][$key] = (object)[
                    'description' => $time_entry->type == "other" ? '' : $time_entry->description,
                    'link' => $time_entry->get_link() ?? "#",
                    'items' => [],
                    'total_seconds' => 0,
                    'billable_seconds' => 0,
                    'unbillable_seconds' => 0,
                  ];
                }

                // Only add our time if not already invoiced
                if( !$time_entry->get_is_invoiced() ){
                  foreach( $time_entry->time_entries as $t ){
                    $billable_seconds = $time_entry->get_is_billable() ? $t->seconds : 0;
                    $unbillable_seconds = !$time_entry->get_is_billable() ? $t->seconds : 0;

                    $groups[$time_entry->type][$key]->total_seconds += $billable_seconds + $unbillable_seconds;
                    $groups[$time_entry->type][$key]->billable_seconds += $billable_seconds;
                    $groups[$time_entry->type][$key]->unbillable_seconds += $unbillable_seconds;
                  }
                }

                $groups[$time_entry->type][$key]->items[] = $time_entry;
              endforeach;?>
              <?php foreach( $groups as $type => $items ):?>
                <details open>
                  <summary><strong><?= htmlspecialchars(ucwords($type));?></strong></summary>
                  <ul>
                    <?php foreach( $items as $id => $details ):
                      $project = $details->items[0]->project_id && isset( $projects[$details->items[0]->project_id] ) ? $projects[$details->items[0]->project_id] : false;
                      ?>
                      <li>
                        <?php if( $project ):?>
                          <a href="<?= $project->get_summary_link($client->report->start_date, $client->report->end_date);?>" target="_blank">
                            <?= htmlspecialchars($project->name);?>
                          </a>
                        <?php endif;?>
                        <?php if( $details->link ):?>
                          - 
                          <a href="<?= $details->link;?>" target="_blank">
                            <?= htmlspecialchars($details->description);?>
                          </a>
                        <?php else:?>
                          <?= htmlspecialchars($details->description);?>
                        <?php endif;?>
                        <br>
                        <?php foreach( $details->items as $i ):?>
                          <label class="<?= $i->get_is_invoiced() ? "invoiced" : "";?>">-
                            <input
                              type="checkbox"
                              class="form_control invoiced_time_entry"
                              value="<?= implode( " ", array_map( function( $b ) { return $b->id; }, $i->time_entries ) );?>"
                              name="invoiced_time_entry[]"
                              form="client-<?= $index;?>"
                              <?= $i->get_is_invoiced() ? "disabled checked" : "";?>
                              autocomplete="off"
                              >
                            <?php if( !$i->get_is_billable() ):?>
                              [Unbillable]
                            <?php endif;?>
                            <?= htmlspecialchars($i->parsed_description->description ?? $i->description);?>
                            <br>
                          </label>
                        <?php endforeach;?>
                        <div>
                          Billable: <?= round( ( $details->billable_seconds / 60 / 60 ), 2 )  . ' hours';?><br>
                          Unbillable: <?= round( ( $details->unbillable_seconds / 60 / 60 ), 2 )  . ' hours';?><br>
                          Total: <?= round( ( $details->total_seconds / 60 / 60 ), 2 )  . ' hours';?>
                        </div>
                      </li>
                    <?php endforeach;?>
                  </ul>
                </details>
              <?php endforeach;?>
            </div>
            <div class="card-footer">
              <button class="mark-invoiced btn btn-outline-primary" form="client-<?= $index;?>">Mark Selected as Invoiced</button>
            </div>
          </div>
        </form>
      <?php endforeach;?>
    </div>
  </div>
  
  <script>
    document.querySelectorAll('.mark-invoiced').forEach(btn => {
      btn.addEventListener('click', function(e){
        e.preventDefault();
        console.log( e.target.form.querySelectorAll('.invoiced_time_entry:checked') );

        fetch('mark_invoiced.php', {
          method: 'POST',
          body: new FormData(e.target.form)
        })
          .then(response => response.json())
          .then(response => {
            console.log(response)
            if( response.response.success.length > 0 ){
              response.response.success.forEach(function(id){
                document.querySelectorAll(`input[value~="${id}"].invoiced_time_entry`).forEach(function(input){
                  input.checked = true;
                  input.disabled = true;
                })
              })
            }

            if( response.response.failure.length > 0 ){
              response.response.failure.forEach(function(id){
                document.querySelectorAll(`input[value~="${id}"].invoiced_time_entry`).forEach(function(input){
                  input.checked = false;
                  input.parentElement.title = "This failed to be updated";
                })
              })
            }
          })
          .catch(err => console.error(err));
        // console.log(e.target.dataset.id);
      })
    });
  </script>
  <!-- JS, Popper.js, and jQuery -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
</body>
</html>