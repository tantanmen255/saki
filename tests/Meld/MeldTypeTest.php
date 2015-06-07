<?php

use Saki\TileList;

class MeldTypeTest extends PHPUnit_Framework_TestCase {

    function testSingle() {
        $mt = \Saki\Meld\SingleMeldType::getInstance();
        $this->assertTrue($mt->valid(TileList::fromString('1m', false)));
        $this->assertFalse($mt->valid(TileList::fromString('11m', false)));
    }

    function testEyes() {
        $mt = \Saki\Meld\EyesMeldType::getInstance();
        $this->assertTrue($mt->valid(TileList::fromString('11m', false)));
        $this->assertFalse($mt->valid(TileList::fromString('111m', false)));
    }

    function testSequence() {
        $mt = \Saki\Meld\SequenceMeldType::getInstance();
        $this->assertTrue($mt->valid(TileList::fromString('123m', false)));
        $this->assertTrue($mt->valid(TileList::fromString('132m', false)));
        $this->assertTrue($mt->valid(TileList::fromString('213m', false)));
        $this->assertTrue($mt->valid(TileList::fromString('231m', false)));
        $this->assertTrue($mt->valid(TileList::fromString('312m', false)));
        $this->assertTrue($mt->valid(TileList::fromString('321m', false)));
        $this->assertFalse($mt->valid(TileList::fromString('12m3s', false)));
        $this->assertFalse($mt->valid(TileList::fromString('121m', false)));
    }

    function testTriplet() {
        $mt = \Saki\Meld\TripletMeldType::getInstance();
        $this->assertTrue($mt->valid(TileList::fromString('111m', false)));
        $this->assertFalse($mt->valid(TileList::fromString('11m1s', false)));
        $this->assertFalse($mt->valid(TileList::fromString('11m', false)));
    }
}