<?php
namespace Saki\RoundResult;

use Saki\Game\Player;
use Saki\Util\ArrayList;
use Saki\Win\WinResult;
use Saki\Win\WinState;

class WinRoundResult extends RoundResult {
    // note: createXXX()'s param check is not fully strict since it seems no harm

    /**
     * @param Player[] $players
     * @param Player $winPlayer
     * @param WinResult $winResult
     * @param int $accumulatedReachCount
     * @param int $seatWindTurn
     * @return WinRoundResult
     */
    static function createWinBySelf(array $players, Player $winPlayer, WinResult $winResult, $accumulatedReachCount, $seatWindTurn) {
        if ($winResult->getWinState() != WinState::create(WinState::WIN_BY_SELF)) {
            throw new \InvalidArgumentException();
        }
        $losePlayers = array_values(array_filter($players, function (Player $player) use ($winPlayer) {
            return $player != $winPlayer;
        }));
        return new self($players, [$winPlayer], [$winResult], $losePlayers, $accumulatedReachCount, $seatWindTurn,
            RoundResultType::create(RoundResultType::WIN_BY_SELF));
    }

    /**
     * @param Player[] $players
     * @param Player $winPlayer
     * @param WinResult $winResult
     * @param Player $losePlayer
     * @param int $accumulatedReachCount
     * @param int $seatWindTurn
     * @return WinRoundResult
     */
    static function createWinByOther(array $players, Player $winPlayer, WinResult $winResult, Player $losePlayer, $accumulatedReachCount, $seatWindTurn) {
        if ($winResult->getWinState() != WinState::create(WinState::WIN_BY_OTHER)) {
            throw new \InvalidArgumentException();
        }
        return new self($players, [$winPlayer], [$winResult], [$losePlayer], $accumulatedReachCount, $seatWindTurn,
            RoundResultType::create(RoundResultType::WIN_BY_OTHER));
    }

    /**
     * @param Player[] $players
     * @param Player[] $winPlayers
     * @param WinResult[] $winResults
     * @param Player $losePlayer
     * @param int $accumulatedReachCount
     * @param int $seatWindTurn
     * @return WinRoundResult
     */
    static function createMultiWinByOther(array $players, array $winPlayers, array $winResults, Player $losePlayer, $accumulatedReachCount, $seatWindTurn) {
        foreach ($winResults as $winResult) {
            if ($winResult->getWinState() != WinState::create(WinState::WIN_BY_OTHER)) {
                throw new \InvalidArgumentException();
            }
        }

        $winPlayerCount = count($winPlayers);
        if (!in_array($winPlayerCount, [2, 3])) {
            throw new \InvalidArgumentException();
        }

        $winTypeValue = $winPlayerCount == 2 ? RoundResultType::DOUBLE_WIN_BY_OTHER : RoundResultType::TRIPLE_WIN_BY_OTHER;
        $winType = RoundResultType::create($winTypeValue);

        return new self($players, $winPlayers, $winResults, [$losePlayer], $accumulatedReachCount, $seatWindTurn, $winType);
    }

    private $players;
    private $winPlayers;
    private $winAnalyzerResults;
    private $losePlayers;
    private $accumulatedReachCount;
    private $seatWindTurn;

    /**
     * @param Player[] $players
     * @param Player[] $winPlayers
     * @param WinResult[] $winResults
     * @param Player[] $losePlayers
     * @param int $accumulatedReachCount
     * @param int $seatWindTurn
     * @param RoundResultType $winType
     */
    function __construct(array $players, array $winPlayers, array $winResults, array $losePlayers, $accumulatedReachCount, $seatWindTurn, RoundResultType $winType) {
        if (!$winType->isWin()) {
            throw new \InvalidArgumentException();
        }

        parent::__construct($players, $winType);
        $this->players = $players;
        $this->winPlayers = $winPlayers;
        $this->winAnalyzerResults = $winResults;
        $this->losePlayers = $losePlayers;
        $this->accumulatedReachCount = $accumulatedReachCount;
        $this->seatWindTurn = $seatWindTurn;
    }

    function getPlayers() {
        return $this->players;
    }

    function getWinPlayers() {
        return $this->winPlayers;
    }

    function getWinAnalyzerResult(Player $player) {
        $k = array_search($player, $this->winPlayers);
        if ($k === false) {
            throw new \InvalidArgumentException();
        }
        return $this->winAnalyzerResults[$k];
    }

    function getLosePlayers() {
        return $this->losePlayers;
    }

    function getAccumulatedReachCount() {
        return $this->accumulatedReachCount;
    }

    function getSeatWindTurn() {
        return $this->seatWindTurn;
    }

    function getPlayerCount() {
        return count($this->getPlayers());
    }

    function getWinPlayerCount() {
        return count($this->getWinPlayers());
    }

    function getLosePlayerCount() {
        return count($this->getLosePlayers());
    }

    function isWinPlayer(Player $player) {
        return array_search($player, $this->getWinPlayers()) !== false;
    }

    function isLosePlayer(Player $player) {
        return array_search($player, $this->getLosePlayers()) !== false;
    }

    function getReachDeltaInt(Player $player) {
        $totalPoint = $this->getAccumulatedReachCount() * 1000;
        if ($this->isWinPlayer($player)) {
            return $totalPoint / $this->getWinPlayerCount();
        } else {
            return 0;
        }
    }

    function getSeatWindTurnDeltaInt(Player $player) {
        // each winPlayer get totalPoint, which was undertaken by each lostPlayer.
        $totalPoint = $this->getSeatWindTurn() * 300;
        if ($this->isWinPlayer($player)) {
            return $totalPoint;
        } elseif ($this->isLosePlayer($player)) {
            return -$totalPoint * $this->getWinPlayerCount() / $this->getLosePlayerCount();
        } else {
            return 0;
        }
    }

    function getTableItemDeltaInt(Player $player) {
        $isWinBySelf = $this->getRoundResultType()->getValue() == RoundResultType::WIN_BY_SELF;
        if ($this->isWinPlayer($player)) {
            $pointItem = $this->getWinAnalyzerResult($player)->getPointItem();
            $receiverIsDealer = $player->getArea()->getSeatWind()->isDealer();
            return $pointItem->getReceivePoint($receiverIsDealer, $isWinBySelf);
        } elseif ($this->isLosePlayer($player)) {
            $totalPayPoint = 0;
            foreach ($this->getWinPlayers() as $winPlayer) {
                $pointItem = $this->getWinAnalyzerResult($winPlayer)->getPointItem();
                $receiverIsDealer = $winPlayer->getArea()->getSeatWind()->isDealer();
                $payerIsDealer = $player->getArea()->getSeatWind()->isDealer();
                $payPoint = -$pointItem->getPayPoint($receiverIsDealer, $isWinBySelf, $payerIsDealer);
                $totalPayPoint += $payPoint;
            }
            return $totalPayPoint;
        } else {
            return 0;
        }
    }

    /**
     * @param Player $player
     * @return PointDelta
     */
    function getPointDeltaInt(Player $player) {
        return $this->getTableItemDeltaInt($player)
        + $this->getReachDeltaInt($player)
        + $this->getSeatWindTurnDeltaInt($player);
    }

    /**
     * @return Player
     */
    function isKeepDealer() {
        $winPlayers = new ArrayList($this->getWinPlayers());
        return $winPlayers->any(function (Player $player) {
            return $player->getArea()->getSeatWind()->isDealer();
        });
    }
}