<?php
namespace Saki\Play;

use Saki\Game\Area;
use Saki\Game\Round;
use Saki\Util\ArrayList;
use Saki\Util\Utils;
use Saki\Win\WinReport;

/**
 * @package Saki\Play
 */
class RoundSerializer {
    private $role;

    /**
     * @param Role $role
     */
    function __construct(Role $role) {
        $this->role = $role;
    }

    /**
     * @return Role
     */
    function getRole() {
        return $this->role;
    }

    /**
     * @return Round
     */
    function getRound() {
        return $this->getRole()->getRound();
    }

    /**
     * @return array
     */
    function toAllJson() {
        $a = [
            'response' => 'ok',
            'round' => $this->toRoundJson(),
            'areas' => $this->toAreasJson(),
            'relations' => $this->toRelationsJson(),
            'result' => $this->toResultJson(),
        ];
        return $a;
    }

    /**
     * @return array
     */
    function toRoundJson() {
        $round = $this->getRound();
        $prevailing = $round->getPrevailing();
        $a = [
            'prevailingWind' => $prevailing->getStatus()->getPrevailingWind()->__toString(),
            'prevailingWindTurn' => $prevailing->getStatus()->getPrevailingWindTurn(),
            'seatWindTurn' => $prevailing->getSeatWindTurn(),
            'pointSticks' => $round->getRiichiHolder()->getRiichiPointsSticks(),
            'wall' => $round->getWall()->toJson(),
            'phase' => $round->getPhase()->__toString(),
            'current' => $round->getCurrentSeatWind()->__toString(),
        ];
        return $a;
    }

    /**
     * @return array [$areaE, $areaS, $areaW, $areaN]
     */
    function toAreasJson() {
        $areaList = $this->getRound()->getAreaList();
        $toSeatWind = function (Area $area) {
            return $area->getSeatWind()->__toString();
        };
        return $areaList->toMap($toSeatWind, [$this, 'toAreaJson']);
    }

    /**
     * @param Area $area
     * @return array
     */
    function toAreaJson(Area $area) {
        $role = $this->getRole();
        $actor = $area->getSeatWind();
        if ($role->mayExecute($actor)) {
            $commandProvider = $area->getRound()->getProcessor()->getProvider();
            $commandList = $commandProvider->provideAll()
                ->getActorProvided($actor)
                ->select(Utils::getToStringCallback());
        } else {
            $commandList = new ArrayList();
        }

        $handJson = $area->getHand()->toJson($actor, $commandList, $role->mayViewHand($actor));
        $a = [
            'relation' => $role->getRelation($actor)->__toString(),
            'actor' => $actor->__toString(),
            'point' => $area->getPoint(),
            'isReach' => $area->getRiichiStatus()->isRiichi(),
            'discard' => $area->getDiscardDisplay(),
            'wall' => $area->getActorWall()->toJson(),
            'commands' => $commandList->toArray(Utils::getToStringCallback()),
            'public' => $handJson['public'],
            'melded' => $handJson['melded'],
            'target' => $handJson['target'],
        ];
        return $a;
    }

    /**
     * @return array e.g. ['prev' => 'E', 'self' => 'S', 'next' => 'W', 'towards' => 'N']
     */
    function toRelationsJson() {
        $areaList = $this->getRound()->getAreaList();
        $toRelation = function (Area $area) {
            return $this->getRole()->getRelation($area->getSeatWind());
        };
        $toSeatWind = function (Area $area) {
            return $area->getSeatWind()->__toString();
        };
        return $areaList->toMap($toRelation, $toSeatWind);
    }

    /**
     * @return array
     */
    function toResultJson() {
        $round = $this->getRound();
        $phaseState = $round->getPhaseState();

        $a = [
            'isRoundOver' => $phaseState->isRoundOver(),
            'isGameOver' => $phaseState->isGameOver(),
        ];

        if ($a['isRoundOver']) {
            $overPhaseResult = $phaseState->getResult();

            $a['result'] = $overPhaseResult->__toString();
            $a['indicatorWall'] = $round->getWall()->getIndicatorWall()->toJson();
            $a['lastChangeDetail'] = $round->getPointHolder()->getLastChangeDetail();

            if ($overPhaseResult->getResultType()->isWin()) {
                $winReportToJson = function (WinReport $winReport) {
                    return $winReport->toJson();
                };
                $a['winReports'] = $overPhaseResult->getWinReportList()
                    ->toArray($winReportToJson);
            }

            if ($a['isGameOver']) {
                $a['finalScore'] = $phaseState->getFinalScore()->toJson();
            }
        }

        return $a;
    }
}