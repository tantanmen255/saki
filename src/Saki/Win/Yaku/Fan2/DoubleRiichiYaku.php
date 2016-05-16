<?php
namespace Saki\Win\Yaku\Fan2;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Fan1\RiichiYaku;
use Saki\Win\Yaku\Yaku;

class DoubleRiichiYaku extends Yaku {
    function getConcealedFan() {
        return 2;
    }

    function getNotConcealedFan() {
        return 0;
    }

    function getRequiredSeries() {
        return [];
    }

    protected function matchOther(WinSubTarget $subTarget) {
        return $subTarget->getRiichiStatus()->isDoubleRiichi();
    }

    function getExcludedYakus() {
        return [RiichiYaku::create()];
    }
}