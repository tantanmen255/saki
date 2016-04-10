<?php
namespace Saki\Win\Yaku\Yakuman;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Yaku;

class EarthlyWinYaku extends Yaku {
    function getConcealedFanCount() {
        return 13;
    }

    function getNotConcealedFanCount() {
        return 13;
    }

    function getRequiredTileSeries() {
        return [];
    }

    protected function matchOtherConditions(WinSubTarget $subTarget) {
        return $subTarget->isEarthlyWin();
    }
}