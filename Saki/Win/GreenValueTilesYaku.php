<?php
namespace Saki\Win;

use Saki\Tile\Tile;

class GreenValueTilesYaku extends ValueTilesYaku {
    function isValueTile(Tile $tile, WinAnalyzerSubTarget $subTarget) {
        return $tile == Tile::fromString('P');
    }
}