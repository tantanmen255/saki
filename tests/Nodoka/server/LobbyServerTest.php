<?php

use Nodoka\Server\LobbyServer;
use Nodoka\Server\MockClient;
use Nodoka\Server\User;
use Saki\Game\SeatWind;
use Saki\Play\Participant;

class LobbyServerTest extends \SakiTestCase {
    /** @var LobbyServer */
    private $lobbyServer;
    /** @var MockClient */
    private $client1;
    /** @var MockClient */
    private $client2;
    /** @var MockClient */
    private $client3;
    /** @var MockClient */
    private $client4;
    /** @var MockClient[] */
    private $clients;

    protected function setUp() {
        parent::setUp();
        $server = $this->lobbyServer = new LobbyServer(true);
        $client1 = $this->client1 = new MockClient();
        $client2 = $this->client2 = new MockClient();
        $client3 = $this->client3 = new MockClient();
        $client4 = $this->client4 = new MockClient();
        $clients = $this->clients = [1 => $client1, 2 => $client2, 3 => $client3, 4 => $client4];
        /**
         * @var int $i
         * @var MockClient $client
         */
        foreach ($clients as $i => $client) {
            $server->onOpen($client);
            $server->onMessage($client, "auth client{$i} pw");
            $client->clearReceived();
        }
    }

    function testAuth() {
        $server = $this->lobbyServer;
        $client = $this->client1;
        $server->onMessage($client, 'auth Koromo pw');
        $this->assertEquals('Koromo', $server->getUser($client)->getUsername());
        $this->assertResponseOk([$client]);
    }

    function testMatching() {
        $server = $this->lobbyServer;
        $clients = $this->clients;
        foreach ($clients as $client) {
            $server->onMessage($client, 'join');
        }
        $this->assertResponseOk($clients, 0);
        $this->assertResponseRound($clients, 'E', 1);
    }

    function testPlay() {
        $server = $this->lobbyServer;
        $clients = $this->clients;
        foreach ($clients as $client) {
            $server->onMessage($client, 'join');
            $client->clearReceived();
        }

        $play = $server->getRoom()->getPlay($server->getUser($clients[1]));
        // todo get userKeys in ESWN order
        /** @var Participant $participantE */
        $participantE = $play->getParticipantList(SeatWind::createEast())->getSingle();
        /** @var User $userE */
        $userE = $participantE->getUserKey();
        $clientE = $userE->getConnection();
        $server->onMessage($clientE, 'play mockHand E E');
        $server->onMessage($clientE, 'play discard E E');
        $server->onMessage($clientE, 'play passAll');
        $this->assertResponseRound($clients, 'S');
    }

    /**
     * @param MockClient[] $clients
     * @param int $index
     */
    static function assertResponseOk(array $clients, $index = -1) {
        foreach ($clients as $client) {
            static::assertEquals('ok', $client->getReceived($index)->response);
        }
    }

    /**
     * @param MockClient[] $clients
     * @param string $current
     * @param int $index
     */
    static function assertResponseRound(array $clients, string $current = 'E', $index = -1) {
        foreach ($clients as $client) {
            static::assertTrue(isset($client->getReceived($index)->round), implode("\n", $client->getReceivedHistory()));
            static::assertEquals($current, $client->getReceived($index)->round->current);
        }
    }
}