<?php
namespace freshdeskhelper;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;
use freshdeskhelper\CredentialProvider;

class Helper{
    /**
     * 
     * @return array
     */
    public function freshdeskrequest(string $authString,Client $client,int $page){
        $windowConstant = 7890000;
        $window = time()- $windowConstant;
        $date = substr(date(DateTime::ATOM,$window),0,19).'Z';
        $request = new Request('GET','tickets?updated_since='.$date.'&per_page=100&page='.$page,[
            'Authorization' => ['Basic '. $authString],
        ]);
        
        $response = $client->send($request);
        if ($response->getStatusCode() == 200) {
            $GLOBALS['logger']->info('request success getting page '.$page);
            $newAndMyTickets = json_decode($response->getBody()->getContents(),true);
            if(count($newAndMyTickets)>0){
                return array_merge($newAndMyTickets,Helper::freshdeskrequest($authString,$client,$page+1));
            }else{
                return $newAndMyTickets;
            }
        }else{
            $GLOBALS['logger']->error('failure to request page of tickets '. $response->getStatusCode().PHP_EOL.$response->getReasonPhrase());
            return [];
        }
    }
    
    public function getTickets(){
        $GLOBALS['logger']->info('getting tickets');
        $client = new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => 5.0,
        ]);
        $creds = CredentialProvider::fromini();
        $authString = base64_encode($creds[0].':X');
        $newAndMyTickets = Helper::freshdeskrequest($authString,$client,$page = 1);
        if(count($newAndMyTickets)==0){
            print('failure to retrieve tickets'.PHP_EOL);
        }
        $myTickets = array_filter($newAndMyTickets,function ($ticket) use($creds){
            if($ticket['status'] == 5){
                return false;
            }
            if($ticket['status'] == 4){
                return false;
            }
            return $ticket["responder_id"] == $creds[1];
        });
        return $myTickets;
    }
    public function updateAllNSADates(){
        $client = new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => 5.0,
        ]);
        $creds = CredentialProvider::fromini();
        $today = new DateTime('now');
        $listoftickets = Helper::getTickets();
        $ticketsToUpdate =[];
        $todayNSADate = ['custom_fields'=>['cf_next_scheduled_action'=> date('Y-m-d',$today->getTimestamp())]];
        foreach ($listoftickets as $ticket) {
            $ticketNSA = DateTime::createFromFormat('Y-m-d',$ticket['custom_fields']['cf_next_scheduled_action']);
            if ($ticketNSA < $today) {
                #add id to tickets to update list
                $ticketsToUpdate[$ticket['id']] = $client->requestAsync('PUT','tickets/'.$ticket['id'],[
                    'auth' => [$creds[0],'x', 'basic'],
                    'json' => $todayNSADate
                ]);
            }
        }
        $GLOBALS['logger']->info(count($ticketsToUpdate).' tickets to update NSA');
        $GLOBALS['logger']->info('updating NSA on ticket(s):'.implode(",",array_keys($ticketsToUpdate)));
        
        $results = Promise\unwrap($ticketsToUpdate);
        foreach (array_keys($ticketsToUpdate) as $ticketID) {
            $response = $results[$ticketID];
            #how to get status code from results array?
            if($response->getStatusCode() == 200){
                $GLOBALS['logger']->info('updated ticket:'.$ticketID);
            }else{
                $GLOBALS['logger']->error('failure to update ticket:'.$ticketID.$response->getReasonPhrase());
            }
        }
    }
    public function fixNSADates(string $brokenDateStr){
        #given an NSA date
        #get all ticket ids & closure date filtered to $brokenDate
        #then update tickets in the list via another async request
        $client = new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => 5.0,
        ]);
        $creds = CredentialProvider::fromini();
        $listoftickets = Helper::getTickets();
        $ticketsToUpdate =[];
        $brokenDate = DateTime::createFromFormat('Y-m-d',$brokenDateStr);
        foreach ($listoftickets as $ticket) {
            $ticketNSA = DateTime::createFromFormat('Y-m-d',$ticket['custom_fields']['cf_next_scheduled_action']);
            if ($ticketNSA == $brokenDate) {
                #add id to tickets to update list
                $closureDate = substr($ticket['due_by'],0,10);
                $closureJson = ['custom_fields'=>['cf_next_scheduled_action'=> $closureDate]];
                $ticketsToUpdate[$ticket['id']] = $client->requestAsync('PUT','tickets/'.$ticket['id'],[
                    'auth' => [$creds[0],'x', 'basic'],
                    'json' => $closureJson
                ]);
            }
        }
        $GLOBALS['logger']->info(count($ticketsToUpdate).' tickets to fix NSA to closure date');
        $GLOBALS['logger']->info('updating NSA on ticket(s):'.implode(",",array_keys($ticketsToUpdate)));
        
        $results = Promise\unwrap($ticketsToUpdate);
        foreach (array_keys($ticketsToUpdate) as $ticketID) {
            $response = $results[$ticketID];
            #how to get status code from results array?
            if($response->getStatusCode() == 200){
                $GLOBALS['logger']->info('updated ticket:'.$ticketID);
            }else{
                $GLOBALS['logger']->error('failure to update ticket:'.$ticketID.$response->getReasonPhrase());
            }
        }
    }
    public function displayTickets(array $listoftickets){
        #print ticket summary as symfony console table
        foreach ($listoftickets as $ticket) {
            echo $ticket['subject'] . PHP_EOL;
            echo var_export($ticket).PHP_EOL;
        }
    }
}