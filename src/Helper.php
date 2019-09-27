<?php
namespace freshdeskhelper;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;

class Helper
{
    /**
     * build a guzzle client on demand
     * @return Client
     */
    public function getClient(int $timeout)
    {
        return new Client([
            'base_uri' => 'https://silverstripe.freshdesk.com/api/v2/',
            'timeout'  => $timeout,
        ]);
    }
    /**
     *
     * @return array
     */
    public function freshdeskSubRequest(string $authString, Client $client, int $page)
    {
        $windowConstant = 7890000;
        $window = time()- $windowConstant;
        $date = substr(date(DateTime::ATOM, $window), 0, 19).'Z';
        $request = new Request('GET', 'tickets?updated_since='.$date.'&per_page=100&page='.$page, [
            'Authorization' => ['Basic '. $authString],
        ]);
        
        $response = $client->send($request);
        if ($response->getStatusCode() == 200) {
            $GLOBALS['logger']->info('request success getting page '.$page);
            $newAndMyTickets = json_decode($response->getBody()->getContents(), true);
            if (count($newAndMyTickets)>0) {
                return array_merge($newAndMyTickets, Helper::freshdeskSubRequest($authString, $client, $page+1));
            } else {
                return $newAndMyTickets;
            }
        } else {
            $GLOBALS['logger']->error('failure to request page of tickets '. $response->getStatusCode().PHP_EOL.$response->getReasonPhrase());
            return [];
        }
    }
    /**
     * get all of the users tickets that are not closed or resolved
     * @return array
     */
    public function getTickets()
    {
        $GLOBALS['logger']->info('getting tickets');
        $client = Helper::getClient(5);
        $creds = CredentialProvider::fromini();
        $authString = base64_encode($creds[0].':X');
        $newAndMyTickets = Helper::freshdeskSubRequest($authString, $client, $page = 1);
        if (count($newAndMyTickets)==0) {
            print('failure to retrieve tickets'.PHP_EOL);
        }
        $myTickets = array_filter($newAndMyTickets, function ($ticket) use ($creds) {
            if ($ticket['status'] == 5) {
                return false;
            }
            if ($ticket['status'] == 4) {
                return false;
            }
            return $ticket["responder_id"] == $creds[1];
        });
        return $myTickets;
    }
    /**
     * updates all NSA dates for tickets assigned to the user obtained in getcredentials
     */
    public function updateAllNSADates($dryrun)
    {
        $client = Helper::getClient(5);
        $creds = CredentialProvider::fromini();
        $today = new DateTime('now');
        $listoftickets = Helper::getTickets();
        $ticketsToUpdate =[];
        $todayNSADate = ['custom_fields'=>['cf_next_scheduled_action'=> date('Y-m-d', $today->getTimestamp())]];
        foreach ($listoftickets as $ticket) {
            $ticketNSA = DateTime::createFromFormat('Y-m-d', $ticket['custom_fields']['cf_next_scheduled_action']);
            if ($ticketNSA < $today) {
                #add id to tickets to update list
                $ticketsToUpdate[$ticket['id']] = $client->requestAsync('PUT', 'tickets/'.$ticket['id'], [
                    'auth' => [$creds[0],'x', 'basic'],
                    'json' => $todayNSADate
                ]);
            }
        }
        $GLOBALS['logger']->info(count($ticketsToUpdate).' tickets to update NSA');
        $GLOBALS['logger']->info('updating NSA on ticket(s):'.implode(",", array_keys($ticketsToUpdate)));
        if ($dryrun) {
            print('updating NSA on ticket(s):'.implode(",", array_keys($ticketsToUpdate)).PHP_EOL);
        } else {
            $results = Promise\unwrap($ticketsToUpdate);
            foreach (array_keys($ticketsToUpdate) as $ticketID) {
                $response = $results[$ticketID];
                #how to get status code from results array?
                if ($response->getStatusCode() == 200) {
                    $GLOBALS['logger']->info('updated ticket:'.$ticketID);
                } else {
                    $GLOBALS['logger']->error('failure to update ticket:'.$ticketID.$response->getReasonPhrase());
                }
            }
        }
    }
    public function displayTickets(array $listoftickets)
    {
        #print ticket summary as symfony console table
        foreach ($listoftickets['results'] as $ticket) {
            echo $ticket['subject'] . PHP_EOL;
            echo var_export($ticket).PHP_EOL;
        }
    }
}
