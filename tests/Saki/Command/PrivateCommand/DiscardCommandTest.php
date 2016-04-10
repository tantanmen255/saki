<?php

use Saki\Command\CommandContext;
use Saki\Command\PrivateCommand\DiscardCommand;
use Saki\Game\Round;
use Saki\Game\RoundPhase;

class DiscardCommandTest extends PHPUnit_Framework_TestCase {
    function testAll() {
        $r = new Round();
        $pro = $r->getProcessor();
        $pro->process('mockHand E 123456789m12344s');
//        $r->getTileAreas()->debugReplaceHand($r->getTurnManager()->getCurrentPlayer(), TileList::fromString('123456789m12344s'));

        $context = new CommandContext($r);
        $invalidCommand = DiscardCommand::fromString($context, 'discard E 9p');
        $this->assertFalse($invalidCommand->executable());

        $validCommand = DiscardCommand::fromString($context, 'discard E 4s');
        $this->assertTrue($validCommand->executable());

        $validCommand->execute();
        $this->assertEquals(RoundPhase::getPublicInstance(), $r->getPhaseState()->getRoundPhase());
    }
}