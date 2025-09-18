<?php

namespace App\Services\Mqtt;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use PhpMqtt\Client\Logger\DebugLogger;

class MqttService
{
    protected $host;
    protected $port;
    protected $clientId;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->host = env('VITE_MQTT_HOST', '127.0.0.1');
        $this->port = 8883;
        $this->clientId = "laravel_mqtt_client_1001";
        $this->username = env('VITE_MQTT_USERNAME');
        $this->password = env('VITE_MQTT_PASSWORD');
    }

    public function publish($topic, $message, $qos = 0, $retain = false)
    {
        $connectionSettings = (new ConnectionSettings)
            ->setUseTls(true)
            ->setTlsVerifyPeer(true)
            ->setTlsVerifyPeerName(true)
            ->setUsername($this->username)
            ->setPassword($this->password);

        $mqtt = new MqttClient(
            $this->host,
            $this->port,
            $this->clientId,
            MqttClient::MQTT_3_1,
            null,
            Log::getLogger()
        );

        try {
            $mqtt->connect($connectionSettings);
            $mqtt->publish($topic, $message, $qos, $retain);
            $mqtt->disconnect();
        } catch (ConnectingToBrokerFailedException $e) {
            logger()->error('MQTT Connection Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
