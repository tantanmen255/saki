<?php
namespace Saki\Meld;

use Saki\Tile\Tile;
use Saki\Tile\TileList;
use Saki\Tile\TileSortedList;
use Saki\Tile\TileType;
use Saki\Util\ArrayLikeObject;
use Saki\Util\Singleton;

class MeldList extends ArrayLikeObject {
    static function validString($s) {
        $meldStrings = !empty($s) ? explode(',', $s) : [];
        foreach ($meldStrings as $meldString) {
            if (!Meld::validString($meldString)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $s
     * @return MeldList
     */
    static function fromString($s) {
        if (!static::validString($s)) {
            throw new \InvalidArgumentException("Invalid MeldList string[$s]");
        }
        $meldStrings = !empty($s) ? explode(',', $s) : [];
        $melds = array_map(function ($s) {
            return Meld::fromString($s);
        }, $meldStrings);
        return new static($melds);
    }

    function __construct(array $melds) {
        parent::__construct($melds);
    }

    /**
     * @param int $offset
     * @return Meld
     */
    function offsetGet($offset) {
        return parent::offsetGet($offset);
    }

    function __toString() {
        $meldStrings = array_map(function (Meld $meld) {
            return $meld->__toString();
        }, $this->toArray());
        return implode(',', $meldStrings);
    }

    function toTileList() {
        return $this->toReducedValue(function(TileList $l, Meld $meld) {
            return $l->push($meld->toArray());
        }, TileList::fromString(''));
    }

    function toMeldList() {
        return new MeldList($this->toArray());
    }

    function toFilteredMeldList(callable $filter) {
        return new self($this->toFilteredArray($filter));
    }

    function toFilteredTypesMeldList(array $targetMeldTypes, $concealedFlag = null) {
        return $this->toFilteredMeldList(function (Meld $meld) use ($targetMeldTypes, $concealedFlag) {
            return in_array($meld->getMeldType(), $targetMeldTypes)
            && ($concealedFlag === null || $meld->isConcealed() == $concealedFlag);
        });
    }

    function toConcealed($concealedFlag) {
        return new self($this->toArray(function (Meld $meld) use ($concealedFlag) {
            return $meld->toConcealed($concealedFlag);
        }));
    }

    function isConcealed() {
        return $this->all(function (Meld $meld) {
            return $meld->isConcealed();
        });
    }

    function getHandCount() {
        // each quad introduces 1 extra Tile
        $quadMeldCount = $this->toFilteredTypesMeldList([QuadMeldType::getInstance()])->count();
        $tileCount = $this->toReducedValue(function($tileCount, Meld $meld) {
            return $tileCount + $meld->count();
        },0);
        $n = $tileCount - $quadMeldCount;
        return $n;
    }

    function isCompletePrivateHandCount() {
        return $this->getHandCount() == 14;
    }

    function assertCompletePrivateHandCount() {
        if (!$this->isCompletePrivateHandCount()) {
            throw new \LogicException();
        }
    }

    function tileExist(Tile $tile) {
        return $this->any(function (Meld $meld) use ($tile) {
            return $meld->valueExist($tile);
        });
    }

    // tileSeries

    function isSevenUniquePairs() {
        $this->assertCompletePrivateHandCount();

        $pairs = $this->toFilteredTypesMeldList([PairMeldType::getInstance()])->toArray();
        $isUnique = array_unique($pairs) == $pairs;
        return count($pairs) == 7 && $isUnique;
    }

    function isFourWinSetAndOnePair() {
        $this->assertCompletePrivateHandCount();

        $winSetList = $this->toFilteredMeldList(function (Meld $meld) {
            return $meld->getWinSetType()->isWinSet();
        });
        $pairList = $this->toFilteredTypesMeldList([PairMeldType::getInstance()]);
        return $winSetList->count() == 4 && $pairList->count() == 1;
    }

    function isFourRunAndOnePair() {
        $this->assertCompletePrivateHandCount();

        $runList = $this->toFilteredTypesMeldList([RunMeldType::getInstance()]);
        $pairList = $this->toFilteredTypesMeldList([PairMeldType::getInstance()]);
        return $runList->count() == 4 && $pairList->count() == 1;
    }

    function isFourTripleOrQuadAndOnePair($requireConcealedTripleOrQuad = false) {
        $this->assertCompletePrivateHandCount();

        $tripleOrQuadList = $this->toFilteredTypesMeldList([TripleMeldType::getInstance(), QuadMeldType::getInstance()]);
        $pairList = $this->toFilteredTypesMeldList([PairMeldType::getInstance()]);
        $matchConcealed = !$requireConcealedTripleOrQuad || $tripleOrQuadList->all(function (Meld $meld) {
                return $meld->isConcealed();
            });
        return $tripleOrQuadList->count() == 4 && $pairList->count() == 1 && $matchConcealed;
    }

    // WARNING: be careful about Meld.$concealed , especially for ArrayLikeObject search operations.

    // yaku: run concerned

    function isDoubleRun($isTwoDoubleRun) {
        $this->assertCompletePrivateHandCount();

        $requiredDoubleRunCount = $isTwoDoubleRun ? 2 : 1;

        $runMeldList = $this->toFilteredTypesMeldList([RunMeldType::getInstance()]);

        $uniqueRunMeldList = new MeldList($runMeldList->toArray());
        $uniqueRunMeldList->unique(Meld::getEqualsCallback(false));

        $isDoubleRun = function (Meld $runMeld) use ($runMeldList) {
            return $runMeldList->getEqualValueCount($runMeld, Meld::getEqualsCallback(false)) >= 2;
        };
        return $uniqueRunMeldList->getFilteredValueCount($isDoubleRun) >= $requiredDoubleRunCount;
    }

    function isThreeColorRuns() {
        $this->assertCompletePrivateHandCount();

        $runList = $this->toFilteredTypesMeldList([RunMeldType::getInstance()]);

        $map = []; // [1 => ['s' => true] ...]
        foreach ($runList as $run) {
            $tile = $run[0];
            $map[$tile->getNumber()][$tile->getTileType()->__toString()] = true;
            if (count($map[$tile->getNumber()]) == 3) {
                return true;
            }
        }
        return false; // 0.6s

//        $equals = Meld::getEqualsCallback(false);
//        return $runList->any(function (Meld $meld)use($runList, $equals) {
//            return $runList->valueExist($meld->toAllSuitTypes(), $equals);
//        }); // 1.9s
    }

    function isThreeColorTripleOrQuads() {
        $this->assertCompletePrivateHandCount();

        $tripleOrQuadList = $this->toFilteredTypesMeldList([TripleMeldType::getInstance(), QuadMeldType::getInstance()]);
        $numberTripleOrQuadList = $tripleOrQuadList->toFilteredMeldList(function (Meld $meld) {
            return $meld->isAllSuit();
        });

        $map = []; // [1 => ['s' => true] ...]
        foreach ($numberTripleOrQuadList as $tripleOrQuad) {
            $tile = $tripleOrQuad[0];
            $map[$tile->getNumber()][$tile->getTileType()->__toString()] = true;
            if (count($map[$tile->getNumber()]) == 3) {
                return true;
            }
        }
        return false;
    }

    function isFullStraight() {
        $this->assertCompletePrivateHandCount();

        $targetMeldsArray = new ArrayLikeObject([
            [Meld::fromString('123m'), Meld::fromString('456m'), Meld::fromString('789m')],
            [Meld::fromString('123p'), Meld::fromString('456p'), Meld::fromString('789p')],
            [Meld::fromString('123s'), Meld::fromString('456s'), Meld::fromString('789s')],
        ]);

        return $targetMeldsArray->any(function (array $targetMelds) {
            return $this->valueExist($targetMelds, Meld::getEqualsCallback(false));
        });
    }

    // yaku: triple/quad concerned

    function isValueTiles(Tile $valueTile) {
        $this->assertCompletePrivateHandCount();
        $tripleOrQuadList = $this->toFilteredTypesMeldList([TripleMeldType::getInstance(), QuadMeldType::getInstance()]);

        return $tripleOrQuadList->any(function (Meld $tripleOrQuad) use ($valueTile) {
            return $tripleOrQuad[0] == $valueTile;
        });
    }

    function isPeace() {
        $this->assertCompletePrivateHandCount();
        // all suits
        // 4 runs
        // 1 pair
        // (outside check) concealed
        // (outside check) 2-side-waiting
    }

    function isThreeConcealedTripleOrQuads() {
        $this->assertCompletePrivateHandCount();
        $concealedTripleOrQuadList = $this->toFilteredTypesMeldList(
            [TripleMeldType::getInstance(), QuadMeldType::getInstance()], true);
        return $concealedTripleOrQuadList->count() == 3;
    }

    function isThreeOrFourQuads($isFour) {
        $this->assertCompletePrivateHandCount();

        $quadList = $this->toFilteredTypesMeldList([QuadMeldType::getInstance()]);
        $n = $isFour ? 4 : 3;
        return $quadList->count() == $n;
    }

    // yaku: tile concerned
    function isOutsideHand($isPure) {
        $this->assertCompletePrivateHandCount();

        $runMeldList = $this->toFilteredTypesMeldList([RunMeldType::getInstance()]);
        if ($runMeldList->isEmpty()) {
            return false;
        }

        return $this->all(function (Meld $meld) use ($isPure) {
            return $meld->isAnyTerminalOrHonor($isPure);
        });
    }

    function isAllTerminals() {
        $this->assertCompletePrivateHandCount();

        return $this->all(function (Meld $meld) {
            return $meld->isAllTerminal();
        });
    }

    function isAllHonors() {
        $this->assertCompletePrivateHandCount();

        return $this->all(function (Meld $meld) {
            return $meld->isAllHonor();
        });
    }

    function isAllTerminalsAndHonors() {
        $this->assertCompletePrivateHandCount();

        return $this->all(function (Meld $meld) {
            return $meld->isAllTerminalOrHonor();
        });
    }

    function isThreeDragon($isBig) {
        $this->assertCompletePrivateHandCount();

        $dragonMeldList = $this->toFilteredMeldList(function (Meld $meld) {
            return $meld[0]->getTileType()->isDragon();
        });
        $pairCount = $dragonMeldList->toFilteredTypesMeldList([PairMeldType::getInstance()])->count();
        $tripleOrQuadCount = $dragonMeldList->toFilteredTypesMeldList([TripleMeldType::getInstance(), QuadMeldType::getInstance()])->count();

        list($requiredPairCount, $requiredTripleOrQuadCount) = $isBig ? [0, 3] : [1, 2];
        return $pairCount == $requiredPairCount
        && $tripleOrQuadCount == $requiredTripleOrQuadCount;
    }

    function isFourWinds($isBig) {
        $this->assertCompletePrivateHandCount();

        $windMeldList = $this->toFilteredMeldList(function (Meld $meld) {
            return $meld[0]->getTileType()->isWind();
        });
        $pairCount = $windMeldList->toFilteredTypesMeldList([PairMeldType::getInstance()])->count();
        $tripleOrQuadCount = $windMeldList->toFilteredTypesMeldList([TripleMeldType::getInstance(), QuadMeldType::getInstance()])->count();

        list($requiredPairCount, $requiredTripleOrQuadCount) = $isBig ? [0, 4] : [1, 3];
        return $pairCount == $requiredPairCount
        && $tripleOrQuadCount == $requiredTripleOrQuadCount;
    }
}