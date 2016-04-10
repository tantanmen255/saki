<?php
namespace Saki\Win\Yaku\Fan1;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Yaku;

/**
 * 一盃口
 * @package Saki\Win\Yaku\Fan1
 */
class DoubleRunYaku extends Yaku {
    function getConcealedFan() {
        return 1;
    }

    function getNotConcealedFan() {
        return 0;
    }

    function getRequiredTileSeries() {
        return [];
    }

    protected function matchOther(WinSubTarget $subTarget) {
        return $subTarget->getAllMeldList()->isDoubleRun(false);
    }
}