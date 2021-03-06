<?php
namespace Saki\Game\Meld;

use Saki\Game\Tile\Tile;
use Saki\Game\Tile\TileList;
use Saki\Util\Singleton;
use Saki\Util\Utils;

/**
 * A specific pattern for a not empty TileList.
 * @package Saki\Game\Meld
 */
abstract class MeldType extends Singleton {
    /**
     * @return string
     */
    function __toString() {
        // A\B\XXClass -> XXClass
        return Utils::strLastPart(get_called_class());
    }

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

    /**
     * @param TileList $tileList
     * @return bool
     */
    final protected function validCount(TileList $tileList) {
        return count($tileList) == $this->getTileCount();
    }

    /**
     * @param TileList $validCountTileList
     * @return bool
     */
    abstract protected function validFaces(TileList $validCountTileList);

    /**
     * @param TileList $sourceTileList
     * @return TileList[] Returns [[$beginTileList, $remainTileList]...] if success, [] otherwise.
     *                    Note that WeakChow may return multiple possible cuts.
     */
    function getPossibleCuts(TileList $sourceTileList) {
        if ($sourceTileList->isEmpty()) {
            return [];
        }

        $firstTile = $sourceTileList[0];
        $meldTileLists = $this->getPossibleTileLists($firstTile);

        $cuts = [];
        foreach ($meldTileLists as $meldTileList) {
            $twoCut = $sourceTileList->toTwoCut($meldTileList->toArray());
            if ($twoCut !== false) {
                $cuts[] = $twoCut;
            }
        }
        return $cuts;
    }

    /**
     * Used in: meld composition analyze.
     * @param Tile $firstTile
     * @return TileList[] possible ordered TileLists begin with $firstTile under this MeldType.
     */
    abstract protected function getPossibleTileLists(Tile $firstTile);

    /**
     * @param Tile $firstTile
     * @return TileList[]
     */
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

