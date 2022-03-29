<?php

require_once 'vendor\autoload.php';

use Emome\SMSClient;

$client = new SMSClient("Account","Password");

$response = $client->send("Message","Phone");

$responseArr = $client->parseResponse($response->getContent());