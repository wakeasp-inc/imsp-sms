# Wakeasp SMS Client 

## 中華電信Emome API 

Example

```
use Emome\SMSClient;

$client = new SMSClient("Account","Password");

$response = $client->send("Message","Phone");

$responseArr = $client->parseResponse($response->getContent());
```
