<?php
namespace Saki\Win\Yaku\Fan6;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Fan3\HalfFlushYaku;
use Saki\Win\Yaku\Yaku;

class FullFlushYaku extends Yaku {
    function getConcealedFan() {
        return 6;
    }

    function getNotConcealedFan() {
        return 5;
    }

    function getRequiredTileSeries() {
        return [];
    }

    protected function matchOther(WinSubTarget $subTarget) {
        return $subTarget->getPrivateComplete()->isFlush(true);
    }

    function getExcludedYakus() {
        return [HalfFlushYaku::create()];
    }
}