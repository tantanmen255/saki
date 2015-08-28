<?php
namespace Saki\Game;

use Saki\Meld\Meld;
use Saki\Meld\MeldList;
use Saki\Meld\QuadMeldType;
use Saki\Meld\RunMeldType;
use Saki\Meld\TripleMeldType;
use Saki\Tile\Tile;
use Saki\Tile\TileList;
use Saki\Tile\TileSortedList;

class PlayerArea {
    private $handTileSortedList;
    private $discardedTileList;
    private $declaredMeldList;
    private $candidateTile;
    private $isReach;

    function __construct(TileSortedList $onHandTileSortedList = null, Tile $candidateTile = null, MeldList $declaredMeldList = null) {
        $this->init($onHandTileSortedList, $candidateTile, $declaredMeldList);
    }

    function init(TileSortedList $onHandTileSortedList = null, Tile $candidateTile = null, MeldList $declaredMeldList = null) {
        $this->handTileSortedList = $onHandTileSortedList ?: TileSortedList::fromString('');
        $this->discardedTileList = TileList::fromString('');
        $this->declaredMeldList = $declaredMeldList ?: new MeldList([]);
        $this->candidateTile = $candidateTile ?: null;
        $this->isReach = false;
    }

    /**
     * @return TileSortedList
     */
    function getHandTileSortedList() {
        return $this->handTileSortedList;
    }

    /**
     * @return TileList
     */
    function getDiscardedTileList() {
        return $this->discardedTileList;
    }

    /**
     * @return MeldList
     */
    function getDeclaredMeldList() {
        return $this->declaredMeldList;
    }

    /**
     * @return bool
     */
    function hasCandidateTile() {
        return $this->candidateTile !== null;
    }

    /**
     * @return Tile
     */
    function getCandidateTile() {
        if (!$this->hasCandidateTile()) {
            throw new \BadMethodCallException('Candidate tile not existed.');
        }
        return $this->candidateTile;
    }

    /**
     * @param Tile $tile
     */
    function setCandidateTile(Tile $tile) {
        if ($tile === null) {
            throw new \InvalidArgumentException();
        }
        $this->candidateTile = $tile;
    }

    /**
     * @return Tile
     */
    function removeCandidateTile() {
        if (!$this->hasCandidateTile()) {
            throw new \BadMethodCallException();
        }
        $ret = $this->candidateTile;
        $this->candidateTile = null;
        return $ret;
    }

    function isReach() {
        return $this->isReach;
    }

    function setIsReach($isReach) {
        $this->isReach = $isReach;
    }

    function reach(Tile $selfTile) {
        if ($this->isReach) {
            throw new \InvalidArgumentException();
        }
        $this->discard($selfTile);
        $this->isReach = true;
    }

    /**
     * @param \Saki\Tile\Tile|Tile[] $otherTileOrTiles
     */
    function drawInit($otherTileOrTiles) {
        // always valid
        $this->getHandTileSortedList()->push($otherTileOrTiles);
    }

    function draw(Tile $newTile) {
        // always valid
        $this->getHandTileSortedList()->push($newTile);
        $this->setCandidateTile($newTile);
    }

    function drawReplacement(Tile $newTile) {
        // always valid
        $this->getHandTileSortedList()->push($newTile);
        $this->setCandidateTile($newTile);
    }

    function canDiscard(Tile $selfTile) {
        return $this->getHandTileSortedList()->valueExist($selfTile);
    }

    function discard(Tile $selfTile) {
        $this->getHandTileSortedList()->removeByValue($selfTile); // valid test
        $this->getDiscardedTileList()->push($selfTile);
        if ($this->hasCandidateTile()) {
            $this->removeCandidateTile();
        }
    }

    function canKongBySelf(Tile $selfTile) {
        return $this->getHandTileSortedList()->valueExist([$selfTile, $selfTile, $selfTile, $selfTile]);
    }

    function kongBySelf(Tile $selfTile) {
        $this->getHandTileSortedList()->removeByValue([$selfTile, $selfTile, $selfTile, $selfTile]); // valid test
        $meld = new Meld(new TileList([$selfTile, $selfTile, $selfTile, $selfTile]), QuadMeldType::getInstance(), false);
        $this->getDeclaredMeldList()->push($meld);
        if ($this->hasCandidateTile() && $this->getCandidateTile() == $selfTile) {
            $this->removeCandidateTile();
        }
        return $meld;
    }

    function canPlusKongBySelf(Tile $selfTile) {
        return $this->getDeclaredMeldList()->canPlusKong($selfTile) && $this->getHandTileSortedList()->valueExist($selfTile);
    }

    /**
     * @param Tile $selfTile
     * @return Meld
     */
    function plusKongBySelf(Tile $selfTile) {
        if (!$this->canPlusKongBySelf($selfTile)) {
            throw new \InvalidArgumentException();
        }

        $newMeld = $this->getDeclaredMeldList()->plusKong($selfTile, false);
        $this->getHandTileSortedList()->removeByValue($selfTile);
        if ($this->hasCandidateTile() && $this->getCandidateTile() == $selfTile) {
            $this->removeCandidateTile();
        }
        return $newMeld;
    }

    function canChowByOther(Tile $otherTile, Tile $selfTile1, Tile $selfTile2) {
        return RunMeldType::getInstance()->valid(new TileList([$otherTile, $selfTile1, $selfTile2]))
        && $this->getHandTileSortedList()->valueExist([$selfTile1, $selfTile2]);
    }

    function chowByOther(Tile $otherTile, Tile $selfTile1, Tile $selfTile2) {
        $meld = new Meld(new TileList([$otherTile, $selfTile1, $selfTile2]), RunMeldType::getInstance()); // valid test before remove
        $this->getHandTileSortedList()->removeByValue([$selfTile1, $selfTile2]); // valid test
        $this->getDeclaredMeldList()->push($meld);
        return $meld;
    }

    function canPongByOther(Tile $otherTile) {
        return $this->getHandTileSortedList()->valueExist([$otherTile, $otherTile]);
    }

    function pongByOther(Tile $otherTile) {
        $this->getHandTileSortedList()->removeByValue([$otherTile, $otherTile]); // valid test
        $meld = new Meld(new TileList([$otherTile, $otherTile, $otherTile]), TripleMeldType::getInstance());
        $this->getDeclaredMeldList()->push($meld);
        return $meld;
    }

    function canKongByOther(Tile $otherTile) {
        $this->getHandTileSortedList()->valueExist([$otherTile, $otherTile, $otherTile]);
    }

    function kongByOther(Tile $otherTile) {
        $this->getHandTileSortedList()->removeByValue([$otherTile, $otherTile, $otherTile]); // valid test
        $meld = new Meld(new TileList([$otherTile, $otherTile, $otherTile, $otherTile]), QuadMeldType::getInstance());
        $this->getDeclaredMeldList()->push($meld);
        return $meld;
    }

    function canPlusKongByOther(Tile $otherTile) {
        return $this->getDeclaredMeldList()->canPlusKong($otherTile);
    }

    function plusKongByOther(Tile $otherTile) {
        $meld = $this->getDeclaredMeldList()->plusKong($otherTile, true); // valid test
        return $meld;
    }
}