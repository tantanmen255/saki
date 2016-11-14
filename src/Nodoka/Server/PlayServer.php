<?php
namespace Nodoka\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Saki\Play\Play;

/**
 * @package Nodoka\Server
 */
class PlayServer implements MessageComponentInterface {
    private $logEnable;
    private $play;

    function __construct() {
        $this->logEnable = true;
        $this->play = new Play();
    }

    /**
     * @return boolean
     */
    function isLogEnable() {
        return $this->logEnable;
    }

    /**
     * @param boolean $logEnable
     */
    function setLogEnable(bool $logEnable) {
        $this->logEnable = $logEnable;
    }

    /**
     * @return Play
     */
    function getPlay() {
        return $this->play;
    }

    /**
     * @param string $line
     */
    private function log(string $line) {
        if ($this->isLogEnable()) {
            echo date('[Y-m-d h:m:s]') . $line . "\n";
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param array $json
     */
    private function send(ConnectionInterface $conn, array $json) {
        $data = json_encode($json);
        $this->log("Connection {$conn->resourceId} sending: {$data}.");

        $conn->send($data);
    }

    private function notifyAll() {
        $play = $this->getPlay();
        foreach ($play->getUserKeys() as $conn) {
            $this->send($conn, $play->getJson($conn));
        }
    }

    private function fillAIClients() {
        $nTodo = 4; // for debug, prefer fast impl to accurate solution
        while($nTodo-- > 0) {
            $this->getPlay()->join(new AIClient());
        }
    }

    private function clearAIClients() {
        $play = $this->getPlay();
        $clients = $play->getUserKeys();
        foreach ($clients as $client) {
            if ($client instanceof AIClient) {
                $play->leave($client);
            }
        }
    }

    //region MessageComponentInterface impl
    function onOpen(ConnectionInterface $conn) {
        $this->log("Connection {$conn->resourceId} opened.");

        $this->clearAIClients();
        $this->getPlay()->join($conn);
        $this->fillAIClients();

        $this->notifyAll();
    }

    function onClose(ConnectionInterface $conn) {
        $this->log("Connection {$conn->resourceId} closed.");

        $this->clearAIClients();
        $this->getPlay()->leave($conn);
        $this->fillAIClients();

        $this->notifyAll();
    }

    function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("Connection {$conn->resourceId} error: {$e->getMessage()}.");

        $error = [
            'result' => 'error',
            'message' => $e->getMessage(),
        ];
        $this->send($conn, $error);
    }

    function onMessage(ConnectionInterface $from, $msg) {
        $this->log("Connection {$from->resourceId} message: {$msg}.\n");

        // try execute, jump to onError if command invalid
        $this->getPlay()->tryExecute($from, $msg);

        $this->notifyAll();
    }
    //endregion
}