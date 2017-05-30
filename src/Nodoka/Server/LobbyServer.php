<?php

namespace Nodoka\server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Saki\Play\Play;
use Saki\Util\ArrayList;
use Saki\Util\Utils;

class LobbyServer implements MessageComponentInterface {
    private $debugError;
    private $tableList;
    private $authorizedUsers;
    private $lostConnectionUsers;

    function __construct($debugError = false) {
        $this->debugError = $debugError;
        $this->tableList = new TableList(1);
        $this->authorizedUsers = new \SplObjectStorage();
        $this->lostConnectionUsers = new \SplObjectStorage();
    }

    /**
     * @return bool
     */
    function isDebugError() {
        return $this->debugError;
    }

    function send(ConnectionInterface $conn, array $json) {
        $data = json_encode($json);
        $conn->send($data);
    }

    function onOpen(ConnectionInterface $conn) {
        // waiting auth
    }

    function onClose(ConnectionInterface $conn) {
        if (!$this->isAuthorized($conn)) {
            return;
        }

        $user = $this->getAuthorizedUser($conn);
        if ($this->getTableList()->inTable($user->getId())) {
            $table = $this->getTableList()->getTable($user->getId());
            if ($table->isStarted()) {
                // keep table playing and expect king's return
            } else {
                // leave table since lost connection
                $table->leave($user);
            }
        } else {
            // not in table, do nothing
        }

        $user->setConn(null);
        unset($this->authorizedUsers[$conn]);
    }

    function onError(ConnectionInterface $conn, \Exception $e) {
        if ($this->isDebugError()) {
            throw $e;
        } else {
            echo "onError: " . $e->getMessage() . "\n";
        }
    }

    function onMessage(ConnectionInterface $from, $msg) {
        try {
            $tokens = explode(' ', $msg);
            if (empty($tokens)) {
                throw new \InvalidArgumentException("Invalid message $msg");
            }

            $cmd = array_shift($tokens);
            $params = $tokens;
            $firstParam = ($cmd == 'auth' ? $from : $this->getAuthorizedUser($from));
            array_unshift($params, $firstParam);

            if (!$this->validCommand($cmd)) {
                throw new \InvalidArgumentException("Invalid message $msg");
            }

            call_user_func_array([$this, $cmd], $params);
        } catch (\Exception $e) {
            $this->onError($from, $e);
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @return bool
     */
    function isAuthorized(ConnectionInterface $conn) {
        return isset($this->authorizedUsers[$conn]);
    }

    /**
     * @param ConnectionInterface $conn
     * @return User
     */
    function getAuthorizedUser(ConnectionInterface $conn) {
        if (!$this->isAuthorized($conn)) {
            throw new \LogicException("user not exist for $conn");
        }
        return $this->authorizedUsers[$conn];
    }

    /**
     * @return TableList
     */
    function getTableList() {
        return $this->tableList;
    }

    /**
     * @param $command
     * @return bool
     */
    function validCommand($command) {
        return in_array($command, [
            'auth',
            'tableInfoList',
            'tableJoin', 'tableLeave', 'tableReady', 'tableUnready',
            'tablePlay'
        ]);
    }

    /**
     * @param ConnectionInterface $conn
     * @param $username
     * @throws \Exception
     */
    function auth(ConnectionInterface $conn, $username) {
        // todo secure auth
        $userId = $username;
        $authOk = true;
        if (!$authOk) {
            throw new \Exception("user[$username] auth failed.");
        }

        $tableList = $this->getTableList();
        if ($tableList->inTable($userId)) {
            $user = $tableList->getUser($userId);
        } else {
            $user = new User($username);
        }

        $user->setConn($conn);
        $this->authorizedUsers[$conn] = $user;
    }

    /**
     * @param User $user
     */
    function tableInfoList(User $user) {
        $json = $this->getTableList()->toJson();
        $this->send($user->getConn(), $json);
    }

    /**
     * @param User $user
     * @param int $tableId
     */
    function tableJoin(User $user, $tableId) {
        $this->tableList->getTableById($tableId)->join($user);
    }

    /**
     * @param User $user
     */
    function tableLeave(User $user) {
        $this->getTableList()->getTable($user->getId())->leave($user);
    }

    /**
     * @param User $user
     */
    function tableReady(User $user) {
        $this->getTableList()->getTable($user->getId())->ready($user);
    }

    /**
     * @param User $user
     */
    function tableUnready(User $user) {
        $this->getTableList()->getTable($user->getId())->unready($user);
    }

    /**
     * @param User $user
     * @param string[] ...$roundCommandTokens
     */
    function tablePlay(User $user, ...$roundCommandTokens) {
        $commandLine = implode(' ', $roundCommandTokens);
        $this->getTableList()->getTable($user->getId())->getPlay()->tryExecute($user, $commandLine);
    }
}

/**
 * @package Nodoka\server
 */
class User {
    private $username;
    private $conn;

    function __construct(string $username) {
        $this->username = $username;
        $this->conn = NullClient::create();
    }

    /**
     * @return string
     */
    function __toString() {
        return "user {$this->getId()}";
    }

    /**
     * @return int|string
     */
    function getId() {
        return $this->username;
    }

    /**
     * @return string
     */
    function getUsername() {
        return $this->username;
    }

    /**
     * @return bool
     */
    function isConnected() {
        return $this->conn !== NullClient::create();
    }

    /**
     * @return ConnectionInterface
     */
    function getConn() {
        return $this->conn;
    }

    /**
     * @param ConnectionInterface|null $conn
     */
    function setConn($conn = null) {
        $this->conn = $conn ?? NullClient::create();
    }
}

/**
 * @package Nodoka\server
 */
class TableList {
    private $tableList;

    /**
     * @param int $tableCount
     */
    function __construct(int $tableCount) {
        $idToTable = function ($id) {
            return new Table($id);
        };
        $this->tableList = (new ArrayList(range(0, $tableCount - 1)))
            ->select($idToTable);
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->tableList->__toString();
    }

    /**
     * @return array
     */
    function toJson() {
        return $this->tableList->toArray(Utils::getMethodCallback('toJson'));
    }

    /**
     * @param int $id
     * @return Table
     */
    function getTableById($id) {
        return $this->tableList[$id];
    }

    /**
     * @param $userId
     * @return bool|User
     */
    function getInTableUserOrFalse($userId) {
        $result = $this->search($userId);
        return $result ? $result['user'] : false;
    }

    /**
     * @param $userId
     * @return bool
     */
    function inTable($userId) {
        return $this->search($userId) !== false;
    }

    function getUser($userId) {
        $result = $this->search($userId);
        if ($result === false) {
            throw new \InvalidArgumentException();
        }
        return $result['user'];
    }

    /**
     * @param $userId
     * @return mixed
     */
    function getTable($userId) {
        $result = $this->search($userId);
        if ($result === false) {
            throw new \InvalidArgumentException();
        }
        return $result['table'];
    }

    /**
     * @param $userId
     * @return array|bool
     */
    private function search($userId) {
        /** @var Table $table */
        foreach ($this->tableList as $table) {
            $user = $table->getInTableUserOrFalse($userId);
            if ($user !== false) {
                return ['user' => $user, 'table' => $table];
            }
        }
        return false;
    }
}

/**
 * @package Nodoka\server
 */
class Table {
    private $id;
    private $tableUserList;
    private $play;

    /**
     * @param int $id
     */
    function __construct(int $id) {
        $this->id = $id;
        $this->tableUserList = new ArrayList();
        $this->play = null;
    }

    /**
     * @return string
     */
    function __toString() {
        return "table {$this->getId()}";
    }

    /**
     * @return array
     */
    function toJson() {
        return [
            'id' => $this->getId(),
            'tableUserList' => $this->tableUserList->toArray(Utils::getMethodCallback('toJson')),
            'isStarted' => $this->isStarted()
        ];
    }

    /**
     * @return int
     */
    function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    function getSeatCount() {
        return 4;
    }

    /**
     * @return int
     */
    function getUserCount() {
        return $this->tableUserList->count();
    }

    /**
     * @return bool
     */
    function isFull() {
        return $this->getUserCount() == $this->getSeatCount();
    }

    /**
     * @return int
     */
    function getReadyCount() {
        return $this->tableUserList->getCount(
            Utils::getMethodCallback('isReady'));
    }

    /**
     * @return bool
     */
    function isFullReady() {
        return $this->getReadyCount() == $this->getSeatCount();
    }

    /**
     * @return bool
     */
    function isStarted() {
        return isset($this->play);
    }

    function assertNotStarted() {
        if ($this->isStarted()) {
            throw new \LogicException("[$this] is started.");
        }
    }

    /**
     * @param $userId
     * @return User
     */
    function getInTableUserOrFalse($userId) {
        $match = function (TableUser $user) use ($userId) {
            return $user->getUser()->getId() == $userId;
        };
        $tableUser = $this->tableUserList->getSingleOrDefault($match, false);
        return $tableUser !== false ? $tableUser->getUser() : false;
    }

    /**
     * @param User $user
     * @return TableUser
     */
    private function getTableUser(User $user) {
        $match = function (TableUser $tableUser) use ($user) {
            return $tableUser->getUser() === $user;
        };
        return $this->tableUserList->getSingle($match);
    }

    /**
     * @param User $user
     */
    function join(User $user) {
        $this->assertNotStarted();
        if ($this->isFull()) {
            throw new \LogicException("[$this] is full.");
        }
        $this->tableUserList->insertLast(new TableUser($user));
    }

    /**
     * @param User $user
     */
    function leave(User $user) {
        $this->assertNotStarted();
        $this->tableUserList->remove($this->getTableUser($user)); // validate exist
    }

    /**
     * @param User $user
     */
    function ready(User $user) {
        $this->assertNotStarted();
        $this->getTableUser($user)->setReady(true); // validate exist

        if ($this->isFullReady()) {
            $this->start();
        }
    }

    /**
     * @param User $user
     */
    function unready(User $user) {
        $this->assertNotStarted();
        $this->getTableUser($user)->setReady(false); // validate exist
    }

    function allUnready() {
        $this->assertNotStarted();
        $unready = function (TableUser $tableUser) {
            $tableUser->setReady(false);
        };
        $this->tableUserList->walk($unready);
    }

    function kickLostConnections() {
        if ($this->isStarted()) {
            return;
        }

        $isLostConnection = function (TableUser $tableUser) {
            return !$tableUser->getUser()->isConnected();
        };
        $this->tableUserList
            ->toArrayList()->where($isLostConnection)
            ->walk([$this, 'leave']);
    }

    function start() {
        if (!$this->isFullReady()) {
            throw new \LogicException("[$this] is not full ready.");
        }

        $play = new Play();
        $randomIndexes = (new ArrayList(range(0, $this->getUserCount() - 1)))->shuffle();
        foreach ($randomIndexes as $index) {
            $user = $this->tableUserList[$index]->getUser();
            $play->join($user);
        }
        $this->play = $play;
    }

    /**
     * @return Play
     */
    function getPlay() {
        if (!$this->isStarted()) {
            throw new \LogicException("[$this] is not started.");
        }
        return $this->play;
    }

    function finish() {
        if (!$this->isStarted()) {
            throw new \LogicException("[$this] is not started.");
        }
        $this->play = null;
        $this->allUnready();
        $this->kickLostConnections(); // todo move into server?
    }
}

/**
 * @package Nodoka\server
 */
class TableUser {
    private $user;
    private $ready;

    /**
     * @param $user
     */
    function __construct(User $user) {
        $this->user = $user;
        $this->ready = false;
    }

    /**
     * @return array
     */
    function toJson() {
        return [
            'username' => $this->getUser()->getUsername(),
            'ready' => $this->isReady()
        ];
    }

    /**
     * @return User
     */
    function getUser() {
        return $this->user;
    }

    /**
     * @return bool
     */
    function isReady() {
        return $this->ready;
    }

    /**
     * @param bool $ready
     */
    function setReady(bool $ready) {
        $this->ready = $ready;
    }
}