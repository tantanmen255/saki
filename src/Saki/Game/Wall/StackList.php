<?php
namespace Saki\Game\Wall;

use Saki\Game\Tile\TileList;
use Saki\Util\ArrayList;
use Saki\Util\Utils;

/**
 * @package Saki\Game\Wall
 */
class StackList extends ArrayList {
    /**
     * @param int $n
     * @return static
     */
    static function fromStackCount(int $n) {
        $generateStack = function () {
            return new Stack();
        };
        return (new static())->fromGenerator($n, $generateStack);
    }

    /**
     * tile list
     * 012345
     * s1 s2 s3
     * 1  3  5
     * 0  2  4
     * @param TileList $tileList
     * @return static
     */
    static function fromTileList(TileList $tileList) {
        self::assertTileListEvenCount($tileList);
        $stackList = static::fromStackCount($tileList->count() / 2);
        $stackList->initByTileList($tileList);
        return $stackList;
    }

    /**
     * @param TileList $tileList
     * @return $this
     */
    function initByTileList(TileList $tileList) {
        $valid = ($tileList->count() == $this->count() * 2);
        if (!$valid) {
            throw new \InvalidArgumentException();
        }

        $chunkList = new ArrayList($tileList->toChunks(2));
        $setChunk = function (Stack $stack, array $chunk) {
            $stack->setTileChunk($chunk);
            return $stack;
        };
        return $this->fromMapping($this, $chunkList, $setChunk);
    }

    /**
     * @return array e.g. [['X', 'X'], ['X', '2s'], ['1s', '2s']]
     */
    function toJson() {
        return $this->toArray(Utils::getMethodCallback('toJson'));
    }

    /**
     * @return TileList
     */
    function toTileList() {
        $selector = function (Stack $stack) {
            return $stack->toTileList();
        };
        return (new TileList())->fromSelectMany($this, $selector);
    }

    /**
     * @param int $diceResult
     * @return StackList[] [$drawStackList, $replaceStackList, $indicatorStackList]
     */
    function toThreeBreak(int $diceResult) {
        if ($this->count() != 68) {
            throw new \LogicException();
        }

        // E       S        W        N
        // 0       1        2        3
        // 0...16, 17...33, 34...50, 51...67
        // e.g. dice 5, last 16, aliveFirst 11,
        //      replace 12...13, indicator 14...18, live 11...0,67...19
        $dealWindIndex = ($diceResult - 1) % 4;
        $last = ($dealWindIndex + 1) * 17 - 1;
        $aliveFirst = $last - $diceResult;
        /** @var StackList $base */
        $base = $this->getCopy()->shiftCyclicLeft($aliveFirst + 1);
        $drawStackList = $base->getCopy()->removeFirst(7);
        $replaceStackList = $base->getCopy()->take(0, 2);
        $indicatorStackList = $base->getCopy()->take(2, 5);
        return [$drawStackList, $replaceStackList, $indicatorStackList];
    }

    private static function assertTileListEvenCount(TileList $tileList) {
        $valid = ($tileList->count() % 2 == 0);
        if (!$valid) {
            throw new \InvalidArgumentException();
        }
    }
}