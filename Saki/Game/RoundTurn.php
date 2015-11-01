<?php
namespace Saki\Game;

use Saki\Tile\Tile;
use Saki\Util\Utils;

class RoundTurn {
    private $globalTurn;
    private $selfWind;

    function __construct($globalTurn, Tile $selfWind) {
        $valid = $globalTurn >= 1 && $selfWind->isWind();
        if (!$valid) {
            throw new \InvalidArgumentException();
        }

        $this->globalTurn = $globalTurn;
        $this->selfWind = $selfWind;
    }

    function __toString() {
        return sprintf('RoundTurn %s%s', $this->globalTurn, $this->selfWind);
    }

    function equal(RoundTurn $other) {
        return $this->compare($other) == 0;
    }

    function compare(RoundTurn $other) {
        $globalTurnDiff = Utils::sgn($this->globalTurn, $other->globalTurn);
        if ($globalTurnDiff != 0) {
            return $globalTurnDiff;
        }

        $selfWindDiff = $this->selfWind->getWindOffset($other->selfWind);
        return $selfWindDiff;
    }

    function getGlobalTurn() {
        return $this->globalTurn;
    }

    function getSelfWind() {
        return $this->selfWind;
    }

    /**
     * @return float
     */
    function getFloatGlobalTurn() {
        return $this->getGlobalTurn() + 0.25 * $this->getSelfWind()->getWindOffset(Tile::fromString('E'));
    }

    /**
     * @param RoundTurn $priorRoundTurn
     * @return float past float global turn in format like 0.25, 0.5, 0.75, 1.0, 1.25 etc.
     */
    function getPastFloatGlobalTurn(RoundTurn $priorRoundTurn) {
        $result = $this->getFloatGlobalTurn() - $priorRoundTurn->getFloatGlobalTurn();
        if ($result <= 0) {
            throw new \InvalidArgumentException();
        }
        return $result;
    }
}