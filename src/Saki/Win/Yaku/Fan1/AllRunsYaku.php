<?php
namespace Saki\Win\Yaku\Fan1;

use Saki\Win\TileSeries;
use Saki\Win\WaitingType;
use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\Yaku;

/**
 * 平和
 * @package Saki\Win\Yaku
 */
class AllRunsYaku extends Yaku {
    function getConcealedFan() {
        return 1;
    }

    function getNotConcealedFan() {
        return 0;
    }

    function getRequiredTileSeries() {
        return [
            TileSeries::create(TileSeries::FOUR_WIN_SET_AND_ONE_PAIR)
        ];
    }

    protected function matchOther(WinSubTarget $subTarget) {
        /**
         * https://ja.wikipedia.org/wiki/%E5%B9%B3%E5%92%8C_(%E9%BA%BB%E9%9B%80)
         * 平和の成立条件は以下の4つである。
         * - 門前であること。すなわちチーをしてはいけない（下の条件2によりポンやカンは不可能である）。
         * - 符のつかない面子で手牌が構成されていること。すなわち4面子すべてが順子であること。
         * - 符のつかない対子が雀頭であること、すなわち役牌が雀頭の時は平和にならない。
         * - 符のつかない待ち、すなわち辺張待ち・嵌張待ち・単騎待ちではなく、両面待ちであること。
         */
        $isFourRunAndOnePair = $subTarget->getAllMeldList()->isFourRunAndOnePair();

        $waitingType = TileSeries::create(TileSeries::FOUR_WIN_SET_AND_ONE_PAIR)->getWaitingType(
            $subTarget->getAllMeldList(), $subTarget->getTileOfTargetTile(), $subTarget->getDeclaredMeldList()
        );
        return $isFourRunAndOnePair
        && $subTarget->getPrivateComplete()->isAllSuit()
        && ($waitingType == WaitingType::create(WaitingType::TWO_SIDE_RUN_WAITING));
    }
}

