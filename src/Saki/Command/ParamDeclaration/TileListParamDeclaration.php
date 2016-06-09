<?php
namespace Saki\Command\ParamDeclaration;

use Saki\Tile\TileList;

/**
 * @package Saki\Command\ParamDeclaration
 */
class TileListParamDeclaration extends ParamDeclaration {
    //region ParamDeclaration impl
    function toObject() {
        return TileList::fromString($this->getParamString());
    }
    //endregion
}