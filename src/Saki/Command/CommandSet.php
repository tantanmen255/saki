<?php

namespace Saki\Command;

use Saki\Command\Debug\InitCommand;
use Saki\Command\Debug\MockDeadWallCommand;
use Saki\Command\Debug\MockHandCommand;
use Saki\Command\Debug\MockNextDrawCommand;
use Saki\Command\Debug\MockNextReplaceCommand;
use Saki\Command\Debug\MockWallRemainCommand;
use Saki\Command\Debug\PassAllCommand;
use Saki\Command\Debug\SkipCommand;
use Saki\Command\Debug\SkipToCommand;
use Saki\Command\Debug\ToNextRoundCommand;
use Saki\Command\PrivateCommand\ConcealedKongCommand;
use Saki\Command\PrivateCommand\DiscardCommand;
use Saki\Command\PrivateCommand\ExtendKongCommand;
use Saki\Command\PrivateCommand\NineNineDrawCommand;
use Saki\Command\PrivateCommand\RiichiCommand;
use Saki\Command\PrivateCommand\TsumoCommand;
use Saki\Command\PublicCommand\ChowCommand;
use Saki\Command\PublicCommand\KongCommand;
use Saki\Command\PublicCommand\PungCommand;
use Saki\Command\PublicCommand\RonCommand;
use Saki\Util\ArrayList;
use Saki\Util\ReadonlyArrayList;

/**
 * @package Saki\Command
 */
class CommandSet extends ArrayList {
    use ReadonlyArrayList;
    private static $standardInstance;

    /**
     * @return CommandSet
     */
    static function createStandard() {
        self::$standardInstance = self::$standardInstance ?? new self([
                // private
                DiscardCommand::class,
                ConcealedKongCommand::class,
                ExtendKongCommand::class,
                RiichiCommand::class,
                TsumoCommand::class,
                NineNineDrawCommand::class,
                // public
                ChowCommand::class,
                PungCommand::class,
                KongCommand::class,
                RonCommand::class,
                // debug
                InitCommand::class,
                MockDeadWallCommand::class,
                MockHandCommand::class,
                MockNextDrawCommand::class,
                MockNextReplaceCommand::class,
                MockWallRemainCommand::class,
                PassAllCommand::class,
                SkipCommand::class,
                SkipToCommand::class,
                ToNextRoundCommand::class,
            ]);
        return self::$standardInstance;
    }

    /**
     * @return CommandSet
     */
    function toPlayerCommandSet() {
        $isPlayerCommand = function (string $class) {
            return is_subclass_of($class, PlayerCommand::class);
        };
        return new self(
            $this->toArrayList()->where($isPlayerCommand)->toArray()
        );
    }
}