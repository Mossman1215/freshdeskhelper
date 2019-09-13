<?php
namespace freshdeskhelper;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use freshdeskhelper\CredentialProvider;

class Helper{
   
    public function getTickets(){
        
        $client = new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => 2.0,
        ]);
        $auth_string = base64_encode(CredentialProvider::fromini().':X');
        $request = new Request('GET','search/tickets?query="(status:2%20OR%20status:3%20OR%20status:6%20OR%20status:7)%20AND%20agent_id:22004629654"',[
            'Authorization' => ['Basic '. $auth_string],
        ]);
        
        $response = $client->send($request,['debug' => true]);
        if ($response->getStatusCode() == 200) {
            return $response->getBody()->getContents();
        }
    }

    public function displayTickets(){
        $listoftickets = Helper::getTickets();
        var_dump($listoftickets);
    }
}