<?php
namespace Saki\Win\Waiting;

use Saki\Tile\Tile;
use Saki\Tile\TileList;
use Saki\Util\Immutable;

/**
 * @package Saki\Win\Waiting
 */
class FutureWaiting implements Immutable {
    private $discard;
    private $waiting;

    /**
     * @param Tile $discard
     * @param TileList $waiting
     */
    function __construct(Tile $discard, TileList $waiting) {
        $this->discard = $discard;
        $this->waiting = $waiting->getCopy()->orderByTileID()->lock();
    }

    /**
     * @return Tile
     */
    function getDiscard() {
        return $this->discard;
    }

    /**
     * @return TileList
     */
    function getWaiting() {
        return $this->waiting;
    }
}