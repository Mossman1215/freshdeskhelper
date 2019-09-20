#!/usr/bin/env php
<?php
$autoloaded = false;
$paths = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;

        break;
    }
}
if (false === $autoloaded) {
    throw new Exception('Please run composer install to set up project dependencies');
}
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
$browser = new HttpBrowser(HttpClient::create());
$crawl = $browser->request('GET', 'https://cwp.govt.nz/service-desk');
foreach ($crawl->children() as $nodeID => $domNode) {
    print(var_export($domNode->childNodes).PHP_EOL);
}

$resp = $browser->getResponse();
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
