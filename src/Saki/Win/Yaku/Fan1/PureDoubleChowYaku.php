<?php
namespace Saki\Win\Yaku\Fan1;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Yaku;

/**
 * 一盃口
 * @package Saki\Win\Yaku\Fan1
 */
class PureDoubleChowYaku extends Yaku {
    function getConcealedFan() {
        return 1;
    }

    function getNotConcealedFan() {
        return 0;
    }

    function getRequiredSeries() {
        return [];
    }

    protected function matchOther(WinSubTarget $subTarget) {
        return $subTarget->getAllMeldList()->isDoubleChow(false);
    }
}