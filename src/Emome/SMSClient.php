<?php

namespace Emome;

use http\Client\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SMSClient
{

    private $host = "https://imsp.emome.net:4443/imsp/sms/servlet";
    private $params = [];
    private $account = null;
    private $password = null;
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * Constructor
     *
     * @param string $account
     * @param string $password
     * @return void
     */
    public function __construct(string $account, string $password)
    {
        $this->client = HttpClient::create();

        $this->account = $account;
        $this->password = $password;

        $this->params = array(
            "account"         => $account,
            "password"        => $password,
            "from_addr_type"  => 0,
            "from_addr"       => null,
            "to_addr_type"    => 0,
            "to_addr"         => null,
            "msg_expire_time" => 0,
            "msg_type"        => 0,
            "msg_dcs"         => 0,
            "msg_pclid"       => 0,
            "msg_udhi"        => 1,
            "msg"             => null,
            "dest_port"       => 0
        );
    }

    public function send(string $message, $phoneNumber,$params = array()): ResponseInterface
    {
        $params = array_merge($this->params, $params);

        $params["msg"] = $this->convertMessageByType($message, $params["msg_type"]);
        $params["dest_port"] = $this->getDestPortByType($params["dest_port"], $params["msg_type"]);
        $params["to_addr"] = $this->implodeAddresses($phoneNumber);

        return $this->sendRequest($this->host."/SubmitSM",$params);
    }

    public function parseResponse($responseContent): array
    {
        $responseContent = preg_replace('/<[a-zA-Z\/][^>]*>/', '', $responseContent);
        $responseContent = preg_replace('/[\r\n]*/', '', $responseContent);
        $arr = explode('|', $responseContent);

        return array(
            'to_addr' => $arr[0],
            'code' => intval($arr[1]),
            'message_id' => $arr[2],
            'description' => $arr[3]
        );
    }

    private function implodeAddresses($addresses)
    {
        return is_array($addresses)
            ? implode(",", $addresses)
            : $addresses;
    }

    private function convertMessageByType($msg, $msgType)
    {
        if (in_array($msgType,[0,1]) ) {
            $msg = iconv("UTF-8", "Big5", $msg);
        }
        elseif (in_array($msgType,[2,3])) {
            $msg = mb_convert_encoding($msg, "UTF-16", "UTF-8");
            $str = "";
            $len = strlen($msg);
            for ($i = 0; $i < $len; ++$i) {
                $byte = $str[$i];
                $char = ord($byte);
                $str .= sprintf('%02x', $char);
            }
            $msg = $str;
        }

        return $msg;
    }

    /**
     * @param string $destPort
     * @param string $msgType
     * @return string
     */
    private function getDestPortByType(string $destPort, string $msgType): string
    {
        if ($msgType == 2 || $msgType == 3) {
            return strtoupper(sprintf('%04x', $destPort));
        }

        return $destPort;
    }

    /**
     * @param string $url
     * @return void
     * @throws TransportExceptionInterface
     */
    private function sendRequest(string $url , $params):ResponseInterface
    {
        return $this->client->request('POST',$url , [
            'query' => $params
        ]);
    }
}