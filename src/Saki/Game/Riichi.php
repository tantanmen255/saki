<?php
namespace Saki\Game;

use Saki\Game\Tile\Tile;

/**
 * @package Saki\Game
 */
class Riichi extends Open {
    //region override Open
    function __construct(Area $area, Tile $openTile) {
        parent::__construct($area, $openTile, true);
    }

    function valid() {
        return parent::valid()
            && $this->validExternal()
            && $this->validWaiting(); // slowest logic last
    }

    /**
     * @return bool
     */
    function validExternal() {
        $area = $this->getArea();
        return $area->getHand()->isConcealed()
            && !$area->getRiichiStatus()->isRiichi()
            && $area->getPoint() >= 1000
            && $area->getRound()->getWall()->getDrawWall()->getRemainTileCount() >= 4;
    }

    /**
     * @return bool
     */
    function validWaiting() {
        $area = $this->getArea();
        $waitingAnalyzer = $area->getRound()->getRule()
            ->getWinAnalyzer()->getWaitingAnalyzer();
        $hand = $area->getHand();
        list($private, $melded, $tile) = [$hand->getPrivate(), $hand->getMelded(), $this->getOpenTile()];
        return $waitingAnalyzer->isWaitingAfterDiscard($private, $melded, $tile);
    }

    function apply() {
        parent::apply();

        $area = $this->getArea();
        $round = $area->getRound();
        $riichiStatus = new RiichiStatus($round->getTurnHolder()->getTurn());
        $round->getRiichiHolder()
            ->setRiichiStatus($area->getSeatWind(), $riichiStatus);
        $round->getPointHolder()
            ->setPointChange($this->getActor(), -1000);
    }
    //endregion
}