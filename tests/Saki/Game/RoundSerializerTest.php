<?php

use Saki\Game\RoundSerializer;

class RoundSerializerTest extends \SakiTestCase {
    function testJson() {
        $r = $this->getInitRound();
        $a = RoundSerializer::create()->toJsonArray($r);

        // todo
        $this->assertCount(4, $a['areas']);
    }
}