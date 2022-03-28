<?php

require_once 'vendor\autoload.php';

use Emome\SMSClient;

$client = new SMSClient("Account","Password");

$re = $client->send("Message","Phone number");
