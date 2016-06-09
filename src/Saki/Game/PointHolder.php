<?php
namespace Saki\Game;

use Saki\Win\Point\PointList;

/**
 * @package Saki\Game
 */
class PointHolder {
    // immutable
    private $pointSetting;
    // variable
    /** @var int[] */
    private $pointMap;

    /**
     * @param PointSetting $pointSetting
     */
    function __construct(PointSetting $pointSetting) {
        $this->pointSetting = $pointSetting;
        $this->init();
    }

    function init() {
        $this->pointMap = $this->pointSetting->getInitialPointMap();
    }

    /**
     * @param SeatWind $seatWind
     * @return int
     */
    function getPoint(SeatWind $seatWind) {
        return $this->pointMap[$seatWind->__toString()];
    }

    /**
     * @param SeatWind $seatWind
     * @param int $point
     */
    function setPoint(SeatWind $seatWind, int $point) {
        $this->pointMap[$seatWind->__toString()] = $point;
    }

    /**
     * @param SeatWind $seatWind
     * @param int $pointChange
     */
    function setPointChange(SeatWind $seatWind, int $pointChange) {
        $point = $this->getPoint($seatWind) + $pointChange;
        $this->setPoint($seatWind, $point);
    }

    /**
     * @return PointList
     */
    function getPointList() {
        return PointList::fromPointMap($this->pointMap);
    }

    /**
     * @param int[] $pointChangeMap
     */
    function applyPointChangeMap(array $pointChangeMap) {
        foreach ($pointChangeMap as $seatWindString => $pointChange) {
            $seatWind = SeatWind::fromString($seatWindString);
            $this->setPointChange($seatWind, $pointChange);
        }
    }
}