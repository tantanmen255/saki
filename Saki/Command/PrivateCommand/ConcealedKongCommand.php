<?php
namespace Saki\Command\PrivateCommand;

use Saki\Command\CommandContext;
use Saki\Command\ParamDeclaration\SelfWindParamDeclaration;
use Saki\Command\ParamDeclaration\TileParamDeclaration;
use Saki\Command\PrivateCommand;
use Saki\Tile\Tile;

class ConcealedKongCommand extends PrivateCommand {
    static function getParamDeclarations() {
        return [SelfWindParamDeclaration::class, TileParamDeclaration::class];
    }

    function __construct(CommandContext $context, Tile $playerSelfWind, Tile $tile) {
        parent::__construct($context, [$playerSelfWind, $tile]);
    }

    /**
     * @return Tile
     */
    function getTile() {
        return $this->getParam(1);
    }

    function matchOtherConditions() {
        $validTile = $this->getActPlayer()->getTileArea()->canConcealedKong($this->getTile());
        return $validTile;
    }

    function executeImpl() {
        $r = $this->getContext()->getRound();
        $r->getTileAreas()->concealedKong($this->getActPlayer(), $this->getTile());
        // stay in private phase
    }
}