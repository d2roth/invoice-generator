<?php
namespace InvoiceGenerator\Requests;
use InvoiceGenerator\Requests\Request;

class TogglRequest extends Request{
  private $base_url;
  private $api_key;
  private $workspace_id;

  function __construct(array $config = []) {
    parent::__construct($config);
    $this->base_url     = $config['base_url'] ?? "";
    $this->api_key      = $config['api_key'] ?? "";
    $this->workspace_id = $config['workspace_id'] ?? "";
  }

  public function get_client_url(int $client_id){
    return sprintf(
      "%s/reports/summary/%s/clients/%s/period/thisMonth",
      $this->base_url,
      $this->workspace_id,
      $client_id
    );
  }

  public function request( $path, $method = "GET", $args = "" ){
    $parameters = [
      '{workspace_id}' => $this->workspace_id,
    ];

    $path = str_replace(array_keys($parameters), array_values($parameters), $path);

    $results = $this->cache(md5($path . json_encode( $args )), function() use ($path, $method, $args){

      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_URL => $this->base_url . $path,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
          "Authorization: Basic " . base64_encode($this->api_key . ":api_token"),
          "Content-Type: application/json",
          // "User-Agent: insomnia/2023.5.8"
        ],
      ]);

      if( !empty( $args ) ){
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($args));
      }

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        return (object)["curl_error" => $err];
      } else {
        if( json_decode($response) === null ){
          return $response;
        }
        return json_decode($response);
      }
    });

    return $results;
  }
}