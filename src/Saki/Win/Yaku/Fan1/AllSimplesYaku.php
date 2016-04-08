<?php
namespace Saki\Win\Yaku\Fan1;

use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Yaku;

/**
 * 断么九
 * @package Saki\Win\Yaku
 */
class AllSimplesYaku extends Yaku {
    protected function getConcealedFanCount() {
        return 1;
    }

    protected function getNotConcealedFanCount() {
        return 1;
    }

    protected function getRequiredTileSeries() {
        return [];
    }

    protected function matchOtherConditions(WinSubTarget $subTarget) {
        return $subTarget->getPrivateComplete()->isAllSimple();
    }
}