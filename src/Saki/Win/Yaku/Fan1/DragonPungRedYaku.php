<?php
namespace Saki\Win\Yaku\Fan1;

use Saki\Game\Tile\Tile;
use Saki\Win\WinSubTarget;
use Saki\Win\Yaku\AbstractValueTilesYaku;

/**
 * 役牌 中
 * @package Saki\Win\Yaku\Fan1
 */
class DragonPungRedYaku extends AbstractValueTilesYaku {
    function getValueTile(WinSubTarget $subTarget) {
        return Tile::fromString('C');
    }
}