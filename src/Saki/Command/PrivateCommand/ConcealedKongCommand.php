<?php
namespace Saki\Command\PrivateCommand;

use Saki\Command\ParamDeclaration\SeatWindParamDeclaration;
use Saki\Command\ParamDeclaration\TileListParamDeclaration;
use Saki\Game\Area;
use Saki\Game\Claim;
use Saki\Game\Meld\KongMeldType;
use Saki\Game\Round;
use Saki\Game\SeatWind;
use Saki\Game\Tile\Tile;
use Saki\Game\Tile\TileList;
use Saki\Util\ArrayList;

/**
 * @package Saki\Command\PrivateCommand\PrivateCommand
 */
class ConcealedKongCommand extends PrivateCommand {
    //region Command impl
    static function getParamDeclarations() {
        return [SeatWindParamDeclaration::class, TileListParamDeclaration::class];
    }

    static function getOtherParamsListRaw(Round $round, SeatWind $actor, Area $actorArea) {
        $private = $actorArea->getHand()->getPrivate();
        $keySelect = function (Tile $tile) {
            return $tile->toFormatString(false);
        };
        $groupFilter = function (ArrayList $group) {
            return $group->count() == 4;
        };
        $tileGroups = $private->toGroups($keySelect, $groupFilter);

        $otherParamsList = new ArrayList($tileGroups);
        return $otherParamsList;
    }
    //endregion

    /**
     * @return TileList
     */
    function getTileList() {
        return $this->getParam(1);
    }

    /**
     * @return Claim
     */
    protected function getClaim() {
        $tiles = $this->getTileList()->toArray();
        return Claim::createConcealedKong(
            $this->getActorArea(),
            $this->getRound()->getTurnHolder()->getTurn(),
            $tiles,
            KongMeldType::create()
        );
    }

    //region PrivateCommand impl
    protected function executablePlayerImpl(Round $round, Area $actorArea) {
        $validCount = $this->getTileList()->count() == 4;
        return $validCount && $this->getClaim()->valid();
    }

    protected function executePlayerImpl(Round $round, Area $actorArea) {
        $this->getClaim()->apply();
        // stay in private phase
    }
    //endregion
}