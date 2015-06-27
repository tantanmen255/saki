<?php

namespace Saki\Game;

use Saki\Tile;
use Saki\Util\Enum;
use Saki\Command\Command;
use Saki\Command\DiscardCommand;

class ZimoCommand extends Command {
    function execute() {

    }
}

class PassCommand extends Command {
    function execute() {

    }
}

class ChowCommand extends Command {
    function execute() {

    }
}

class PongCommand extends Command {
    function execute() {

    }
}

class KangCommand extends Command {
    function execute() {

    }
}

class RonCommand extends Command {
    function execute() {

    }
}

class Round {
    private $wall;
    private $playerList;
    private $dealerPlayer;

    private $playerAreas;
    private $turnManager;
    private $roundPhase;

    function __construct(Wall $wall, PlayerList $playerList, $dealerPlayer) {
        $this->wall = $wall;
        $this->playerList = $playerList;
        $this->dealerPlayer = $dealerPlayer;

        $this->toInitPhase();
    }

    /**
     * @return Wall
     */
    function getWall() {
        return $this->wall;
    }

    /**
     * @return PlayerList
     */
    function getPlayerList() {
        return $this->playerList;
    }

    /**
     * @return Player
     */
    function getDealerPlayer() {
        return $this->dealerPlayer;
    }

    /**
     * @return PlayerArea[]
     */
    function getPlayerAreas() {
        return $this->playerAreas;
    }

    /**
     * @return TurnManager
     */
    protected function getTurnManager() {
        return $this->turnManager;
    }

    /**
     * @return Player
     */
    function getPrevPlayer() {
        return $this->getTurnManager()->getPrevPlayer();
    }

    /**
     * @return Player
     */
    function getCurrentPlayer() {
        return $this->getTurnManager()->getCurrentPlayer();
    }

    /**
     * @return Player
     */
    function getNextPlayer() {
        return $this->getTurnManager()->getNextPlayer();
    }

    /**
     * @return Player
     */
    function getNextNextPlayer() {
        return $this->getTurnManager()->getNextNextPlayer();
    }

    /**
     * @param Player $player
     * @param bool $addTurn
     */
    function setCurrentPlayer(Player $player, $addTurn) {
        $this->getTurnManager()->toPlayer($player, $addTurn);
    }

    /**
     * @param $player
     * @return PlayerArea
     */
    function getPlayerArea($player) {
        return $this->getPlayerAreas()[$this->getPlayerList()->valueToIndex($player)];
    }

    /**
     * @return PlayerArea
     */
    function getCurrentPlayerArea() {
        return $this->getPlayerArea($this->getCurrentPlayer());
    }

    /**
     * @return RoundPhase
     */
    function getRoundPhase() {
        return $this->roundPhase;
    }

    protected function setRoundPhase(RoundPhase $roundPhase) {
        $this->roundPhase = $roundPhase;
    }

    protected function toInitPhase() {
        $this->roundPhase = RoundPhase::getInstance(RoundPhase::INIT_PHASE);

        // init fields
        $this->playerAreas = array_map(function () {
            return new PlayerArea();
        }, range(1, count($this->getPlayerList())));
        $this->turnManager = new TurnManager($this->getPlayerList()->toArray(), $this->getDealerPlayer(), 0);

        // each player draw initial tiles
        $playerCount = count($this->getPlayerList());
        $drawTileCounts = [4,4,4,1];
        foreach ($drawTileCounts as $drawTileCount) {
            for ($cnt = 0; $cnt < $playerCount; ++$cnt) {
                $this->drawInit($this->getCurrentPlayer(), $drawTileCount);
                $this->setCurrentPlayer($this->getNextPlayer(), false);
            }
        }

        // go to dealer player's private phase
        $this->toPrivatePhase($this->getDealerPlayer(), true);
    }

    /**
     * @param Player $player
     * @param bool $drawTile
     */
    protected function toPrivatePhase(Player $player, $drawTile) {
        if ($this->getWall()->getRemainTileCount() == 0 && $drawTile) {
            $this->toOverPhase();
        } else {
            $this->setRoundPhase(RoundPhase::getInstance(RoundPhase::PRIVATE_PHASE));
            $this->setCurrentPlayer($player, true);
            if ($drawTile) {
                $this->draw($player);
            }
        }
    }

    protected function toPublicPhase() {
        $this->setRoundPhase(RoundPhase::getInstance(RoundPhase::PUBLIC_PHASE));
    }

    protected function toOverPhase() {
        $this->setRoundPhase(RoundPhase::getInstance(RoundPhase::OVER_PHASE));
        // todo draw/win
    }

    protected function drawInit(Player $player, $drawTileCount) {
        $this->getPlayerArea($player)->drawInit($this->getWall()->pop($drawTileCount));
    }

    protected function draw(Player $player) {
        $this->getPlayerArea($player)->draw($this->getWall()->pop());
    }

    protected function drawReplacement(Player $player) {
        $this->getPlayerArea($player)->draw($this->getWall()->shift());
    }

    function discard(Player $player, Tile $selfTile) {
        // valid: private phase, currentPlayer
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PRIVATE_PHASE && $player == $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // do
        $this->getCurrentPlayerArea()->discard($selfTile);
        // switch phase
        $this->toPublicPhase();
    }

    function kongBySelf(Player $player, Tile $selfTile) {
        // valid: private phase, currentPlayer
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PRIVATE_PHASE && $player == $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // do
        $this->getPlayerArea($player)->kongBySelf($selfTile);
        $this->drawReplacement($player);
        // stay in private phase
    }

    function plusKongBySelf(Player $player, Tile $selfTile) {
        // valid: private phase, currentPlayer
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PRIVATE_PHASE && $player == $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // do
        $this->getPlayerArea($player)->plusKongBySelf($selfTile);
        $this->drawReplacement($player);
        // stay in private phase
    }

    function winBySelf(Player $player) {
        /// private phase, currentPlayer
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PRIVATE_PHASE && $player == $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // do todo
        $yakuList = null;

        // phase
        $this->toOverPhase();
    }

    function chowByOther(Player $player, Tile $tile1, Tile $tile2) {
        // valid: public phase, next player
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PUBLIC_PHASE && $player == $this->getNextPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // execute
        $currentPlayerArea = $this->getCurrentPlayerArea();
        $playerArea = $this->getPlayerArea($player);
        $targetTile = $currentPlayerArea->getDiscardedTileList()->getLast(); // test valid
        $playerArea->chowByOther($targetTile, $tile1, $tile2); // test valid
        $currentPlayerArea->getDiscardedTileList()->pop();
        // switch phase
        $this->toPrivatePhase($player, false);
    }

    function pongByOther(Player $player) {
        // valid: public phase, non-current player
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PUBLIC_PHASE && $player != $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // execute
        $currentPlayerArea = $this->getCurrentPlayerArea();
        $playerArea = $this->getPlayerArea($player);
        $targetTile = $currentPlayerArea->getDiscardedTileList()->getLast(); // test valid
        $playerArea->pongByOther($targetTile); // test valid
        $currentPlayerArea->getDiscardedTileList()->pop();
        // switch phase
        $this->toPrivatePhase($player, false);
    }

    function kongByOther(Player $player) {
        // valid: public phase, non-current player
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PUBLIC_PHASE && $player != $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // execute
        $currentPlayerArea = $this->getCurrentPlayerArea();
        $playerArea = $this->getPlayerArea($player);
        $targetTile = $currentPlayerArea->getDiscardedTileList()->getLast(); // test valid
        $playerArea->kongByOther($targetTile); // test valid
        $this->drawReplacement($player);
        $currentPlayerArea->getDiscardedTileList()->pop();
        // switch phase
        $this->toPrivatePhase($player, false);
    }

    function plusKongByOther(Player $player) {
        // valid: public phase, non-current player
        $valid = $this->getRoundPhase()->getValue() == RoundPhase::PUBLIC_PHASE && $player != $this->getCurrentPlayer();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
        // execute
        $currentPlayerArea = $this->getCurrentPlayerArea();
        $playerArea = $this->getPlayerArea($player);
        $targetTile = $currentPlayerArea->getDiscardedTileList()->getLast(); // test valid
        $playerArea->plusKongByOther($targetTile);
        $this->drawReplacement($player);
        $currentPlayerArea->getDiscardedTileList()->pop();
        // switch phase
        $this->toPrivatePhase($player, false);
    }

    function winByOther(Player $player) {
        // public

        // do

        // phase
        $this->toOverPhase();
    }
}

class CommandProcessor {

    private $publicPhaseCommandPoller;

    function toPublicPhase() {
        $this->getPublicPhaseCommandPoller()->init($this->getCandidateCommands());
        $this->wonderIfPollerDecided();
    }

    protected function getPublicPhaseCommandPoller() {
        if ($this->publicPhaseCommandPoller === null) {
            $this->publicPhaseCommandPoller = new PublicPhaseCommandPoller([]);
        }
        return $this->publicPhaseCommandPoller;
    }

    protected function wonderIfPollerDecided() {
        $poller = $this->getPublicPhaseCommandPoller();;
        if ($poller->decided()) {
            $todoCommands = $poller->getDecidedCommands();
            if (!empty($todoCommands)) {
                foreach ($todoCommands as $todoCommand) {
                    $todoCommand->execute();
                }
            } else { // no decided commands
                $this->toPrivatePhase($this->getNextPlayer(), true);
            }
        } else { // candidate commands exist
            // waiting commands decided
        }
    }

    function acceptCommand(Command $command) {
        switch ($this->getRoundPhase()->getValue()) {
            case RoundPhase::PRIVATE_PHASE:
                $command->execute();
                break;
            case RoundPhase::PUBLIC_PHASE:
                $this->getPublicPhaseCommandPoller()->acceptCommand($command);
                $this->wonderIfPollerDecided();
                break;
            default:
                throw new \LogicException();
        }
    }

    /**
     * @return Command[]
     */
    function getCandidateCommands() {
        $candidateCommands = [];

        switch ($this->getRoundPhase()->getValue()) {
            case RoundPhase::PRIVATE_PHASE:
                $currentPlayer = $this->getCurrentPlayer();
                $currentPlayerArea = $this->getPlayerArea($currentPlayer);
                foreach ($currentPlayerArea->getOnHandTileSortedList() as $onHandTile) {
                    $candidateCommands[] = new DiscardCommand($this, $currentPlayer, $onHandTile);
                }
                if ($currentPlayerArea->hasCandidateTile()) {
                    $candidateCommands[] = new DiscardCommand($this, $currentPlayer, $currentPlayerArea->getCandidateTile());
                }
                $candidateCommands = array_unique($candidateCommands);
                break;
            case RoundPhase::PUBLIC_PHASE:
                // nextPlayer chow

                // non-currentPlayer pong/kang/ron
                break;
            case RoundPhase::OVER_PHASE:
                break;
            default:
                throw new \LogicException();
        }

        return $candidateCommands;
    }

    function getCandidateCommand(Player $player) {
        return array_values(array_filter($this->getCandidateCommands(), function (Command $v) use ($player) {
            return $v->getPlayer() == $player;
        }));
    }
}