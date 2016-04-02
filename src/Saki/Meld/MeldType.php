<?php
namespace Saki\Meld;

use Saki\Tile\Tile;
use Saki\Tile\TileList;
use Saki\Util\ArrayList;
use Saki\Util\ClassNameToString;
use Saki\Util\Singleton;

/**
 * A specific pattern for a not empty TileList.
 * @package Saki\Meld
 */
abstract class MeldType extends Singleton {
    use ClassNameToString;

    /**
     * @return int
     */
    abstract function getTileCount();

    /**
     * @param TileList $tileList
     * @return bool
     */
    final function valid(TileList $tileList) {
        return $this->validCount($tileList) && $this->validFaces($tileList);
    }

    final protected function validCount(TileList $tileList) {
        return count($tileList) == $this->getTileCount();
    }

    abstract protected function validFaces(TileList $validCountTileList);

    /**
     * Used in: meld composition analyze.
     * @param Tile $firstTile
     * @return TileList[] possible ordered TileLists begin with $firstTile under this MeldType.
     */
    abstract function getPossibleTileLists(Tile $firstTile);

    final protected function getPossibleTileListsImplByRepeat(Tile $firstTile) {
        $tiles = array_fill(0, $this->getTileCount(), $firstTile);
        return [new TileList($tiles)];
    }

    /**
     * @return bool
     */
    function hasTargetMeldType() {
        return false;
    }

    /**
     * @return WinSetType
     */
    abstract function getWinSetType();
}
