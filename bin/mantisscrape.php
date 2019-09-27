#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use freshdeskhelper\CredentialProvider;
use Goutte\Client;

// makes a real request to an external site
$client = new Client();
$crawler = $client->request('GET', 'https://www.cwp.govt.nz/service-desk/requests/desk/my_view_page.php');

// select the form and fill in some values
$form = $crawler->selectButton('LDAPMFALoginForm_LoginForm_action_dologin')->form();
$creds = CredentialProvider::fromini();
$form['Login'] = $creds[2];
$form['Password'] = $creds[3];

// submits the given form
$crawler = $client->submit($form); #loads us with a valid session
#check where we are (should be /Security/LoginForm/mfa/verify/totp)
#https://dash.cwp.govt.nz/Security/LoginForm/mfa/verify/totp
$cookies = $client->getCookieJar();
$request = $client->getClient()->request('GET', 'https://dash.cwp.govt.nz/Security/LoginForm/mfa/verify/totp');
foreach ($cookies as $key => $value) {
   $request->addCookie($key, $value);
}
$response = $request->send(); // Send created request to server
$data = $response->json(); // Returns PHP Array
print(var_export($data).PHP_EOL);
#get json data from this page (securityID)
#POST to mfa endpoint with security ID in URL
#https://dash.cwp.govt.nz/Security/LoginForm/mfa/verify/totp?SecurityID=dec7feb26388a8ecfc93ef1c0d293e91858a754a
#'{"code":"237131"}'
#then we have a valid session cookie and can view service-desk
#get https://www.cwp.govt.nz/service-desk/requests/desk/view_all_bug_page.php?filter=76005
#browse over dom to get buglist table
//print($resp->getContent());
// select the form and fill in some values
// $form = $crawler->selectButton('Log in')->form();
// $form['login'] = 'symfonyfan';
// $form['password'] = 'anypass';

// submit that form
// $crawler = $client->submit($form);

// $browser->submitForm('Sign in', ['login' => '...', 'password' => '...']);
// $openPullRequests = trim($browser->clickLink('Pull requests')->filter(
//     '.table-list-header-toggle a:nth-child(1)'
// )->text());
