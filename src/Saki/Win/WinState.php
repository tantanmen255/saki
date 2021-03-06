<?php
namespace Saki\Win;

use Saki\Util\ComparablePriority;
use Saki\Util\Enum;

/**
 * @package Saki\Win
 */
class WinState extends Enum {
    //region ComparablePriority impl
    use ComparablePriority;

    function getPriority() {
        $m = [
            self::FURITEN_FALSE_WIN => 6,
            self::WIN_BY_SELF => 5,
            self::WIN_BY_OTHER => 4,
            self::NO_YAKU_FALSE_WIN => 3,
            self::WAITING_BUY_NOT_WIN => 2,
            self::NOT_WIN => 1,
        ];
        return $m[$this->getValue()];
    }
    //endregion

    /**
     * @param bool $isPrivate
     * @return static
     */
    static function getTsumoOrOther(bool $isPrivate) {
        $v = $isPrivate ? self::WIN_BY_SELF : self::WIN_BY_OTHER;
        return self::create($v);
    }

    const NOT_WIN = 1; // なし
    const WAITING_BUY_NOT_WIN = 2; // 聴牌
    const FURITEN_FALSE_WIN = 3; // 振り聴
    const NO_YAKU_FALSE_WIN = 4; // 役なし
    const WIN_BY_SELF = 5; // ツモ
    const WIN_BY_OTHER = 6; // ロン

    /**
     * @return bool
     */
    function isWaiting() {
        return $this->getValue() != self::NOT_WIN;
    }

    /**
     * @return bool
     */
    function isTrueWin() {
        $targetValues = [self::WIN_BY_SELF, self::WIN_BY_OTHER];
        return in_array($this->getValue(), $targetValues);
    }

    /**
     * @return bool
     */
    function isFalseWin() {
        $targetValues = [self::FURITEN_FALSE_WIN, self::NO_YAKU_FALSE_WIN];
        return in_array($this->getValue(), $targetValues);
    }

    /**
     * @return bool
     */
    function isTrueOrFalseWin() {
        return $this->isTrueWin() || $this->isFalseWin();
    }
}