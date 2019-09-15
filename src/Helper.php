<?php
namespace freshdeskhelper;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use freshdeskhelper\CredentialProvider;

class Helper{
   
    public function getTickets(){
        
        $client = new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => 2.0,
        ]);
        $creds = CredentialProvider::fromini();
        $auth_string = base64_encode($creds[0].':X');
        $request = new Request('GET','search/tickets?query="(status:2%20OR%20status:3%20OR%20status:6%20OR%20status:7)%20AND%20agent_id:'.$creds[1].'"',[
            'Authorization' => ['Basic '. $auth_string],
        ]);
        
        $response = $client->send($request);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(),true);
        }
    }
    public function updateAllNSADates(){
        $today = new DateTime('now');
        $listoftickets = Helper::getTickets();
        foreach ($listoftickets['results'] as $ticket) {
            $ticketNSA = DateTime::createFromFormat('Y-m-d',$ticket['custom_fields']['cf_next_scheduled_action']);
            if ($ticketNSA < $today) {
                #add id to tickets to update list
                echo 'ticket needs to be updated ' . $ticket['id'] . PHP_EOL;
            }
        }
    } 
    public function displayTickets(){
        $listoftickets = Helper::getTickets();
        #print ticket summary as symfony console table
        foreach ($listoftickets['results'] as $ticket) {
            echo $ticket['subject'] . PHP_EOL;
            echo var_export($ticket).PHP_EOL;
        }
    }
}