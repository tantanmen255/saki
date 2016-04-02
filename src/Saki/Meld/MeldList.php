<?php
namespace Saki\Meld;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Saki\Tile\Tile;
use Saki\Tile\TileList;
use Saki\Util\ArrayList;
use Saki\Util\Utils;

/**
 * A sequence of Meld.
 * @package Saki\Meld
 */
class MeldList extends ArrayList {
    /**
     * @param string $s
     * @return bool
     */
    static function validString(string $s) {
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

    protected static function getPredicate(array $meldTypes, bool $concealedFlag = null) {
        return function (Meld $meld) use ($meldTypes, $concealedFlag) {
            return in_array($meld->getMeldType(), $meldTypes)
            && $meld->matchConcealed($concealedFlag);
        };
    }

    /**
     * @return TileList
     */
    function toTileList() {
        return (new TileList())->fromSelectMany($this, function (Meld $meld) {
            return $meld->toArray();
        });
    }

    /**
     * @param array $targetMeldTypes
     * @param bool|null $concealedFlag
     * @return MeldList
     */
    function toFiltered(array $targetMeldTypes, bool $concealedFlag = null) {
        return $this->getCopy()->where($this->getPredicate($targetMeldTypes, $concealedFlag));
    }

    /**
     * @param bool $concealedFlag
     * @return $this
     */
    function toConcealed(bool $concealedFlag) {
        return (new self)->fromSelect($this, function (Meld $meld) use ($concealedFlag) {
            return $meld->toConcealed($concealedFlag);
        });
    }

    /**
     * @return bool
     */
    function isConcealed() {
        return $this->isAll(function (Meld $meld) {
            return $meld->isConcealed();
        });
    }

    /**
     * @return int
     */
    function getNormalizedHandCount() {
        // note: each quad introduces 1 extra Tile
        $tileCount = $this->getSum(function (Meld $meld) {
            return $meld->count();
        });
        $quadMeldCount = $this->toFiltered([QuadMeldType::getInstance()])->count();
        $n = $tileCount - $quadMeldCount;
        return $n;
    }

    /**
     * @return bool
     */
    function isCompletePrivateHandCount() {
        return $this->getNormalizedHandCount() == 14;
    }

    protected function assertCompletePrivateHandCount() {
        if (!$this->isCompletePrivateHandCount()) {
            throw new \LogicException();
        }
    }

    /**
     * @param Tile $tile
     * @return bool
     */
    function tileExist(Tile $tile) {
        return $this->isAny(function (Meld $meld) use ($tile) {
            return $meld->valueExist($tile);
        });
    }

    //region tileSeries
    /**
     * @return bool
     */
    function isSevenUniquePairs() {
        $this->assertCompletePrivateHandCount();
        $uniquePairCount = $this->toFiltered([PairMeldType::getInstance()])->distinct()->count();
        return $uniquePairCount == 7;
    }

    /**
     * @return bool
     */
    function isFourWinSetAndOnePair() {
        $this->assertCompletePrivateHandCount();
        $winSetCount = $this->getCount(function (Meld $meld) {
            return $meld->getWinSetType()->isWinSet();
        });
        $pairCount = $this->getCount($this->getPredicate([PairMeldType::getInstance()]));
        return [$winSetCount, $pairCount] == [4, 1];
    }

    /**
     * @return bool
     */
    function isFourRunAndOnePair() {
        $this->assertCompletePrivateHandCount();
        $runCount = $this->getCount($this->getPredicate([RunMeldType::getInstance()]));
        $pairCount = $this->getCount($this->getPredicate([PairMeldType::getInstance()]));
        return [$runCount, $pairCount] == [4, 1];
    }

    /**
     * @param bool|false $requireConcealedTripleOrQuad
     * @return bool
     */
    function isFourTripleOrQuadAndOnePair(bool $requireConcealedTripleOrQuad = false) {
        $this->assertCompletePrivateHandCount();

        $concealedFlag = $requireConcealedTripleOrQuad ? true : null;
        $isRequiredTripleOrQuad = $this->getPredicate([TripleMeldType::getInstance(), QuadMeldType::getInstance()], $concealedFlag);

        $tripleOrQuadCount = $this->getCount($isRequiredTripleOrQuad);
        $pairCount = $this->getCount($this->getPredicate([PairMeldType::getInstance()]));
        return [$tripleOrQuadCount, $pairCount] == [4, 1];
    }
    //endregion

    // WARNING: be careful about compare of Tile.isRedDora, Meld.isConcealed.

    //region yaku: run, three color, thirteen orphan
    /**
     * @param bool $isTwoDoubleRun
     * @return bool
     */
    function isDoubleRun(bool $isTwoDoubleRun) {
        $this->assertCompletePrivateHandCount();

        $requiredDoubleRunCount = $isTwoDoubleRun ? 2 : 1;

        $runMeldList = $this->toFiltered([RunMeldType::getInstance()]);
        $keySelector = function (Meld $runMeld) {
            $considerConcealed = false;
            return $runMeld->toFormatString($considerConcealed);
        };
        $counts = $runMeldList->getCounts($keySelector); // ['123s' => 2 ...]
        $doubleRunCount = (new ArrayList(array_values($counts)))->getCount(function (int $n) {
            return $n >= 2;
        });

        return $doubleRunCount >= $requiredDoubleRunCount;
    }

    /**
     * @return bool
     */
    function isFullStraight() {
        $this->assertCompletePrivateHandCount();
        $targetMeldsList = new ArrayList([
            [Meld::fromString('123m'), Meld::fromString('456m'), Meld::fromString('789m')],
            [Meld::fromString('123p'), Meld::fromString('456p'), Meld::fromString('789p')],
            [Meld::fromString('123s'), Meld::fromString('456s'), Meld::fromString('789s')],
        ]);
        $existInThis = function (array $targetMelds) {
            return $this->valueExist($targetMelds, Meld::getEqual(false));
        };
        return $targetMeldsList->isAny($existInThis);
    }

    /**
     * @return bool
     */
    function isThreeColorRuns() {
        $this->assertCompletePrivateHandCount();
        $runList = $this->toFiltered([RunMeldType::getInstance()]);
        return $runList->isThreeColorSuits();
    }

    /**
     * @return bool
     */
    function isThreeColorTripleOrQuads() {
        $this->assertCompletePrivateHandCount();
        $suitTripleOrQuadList = $this->toFiltered([TripleMeldType::getInstance(), QuadMeldType::getInstance()])
            ->where(function (Meld $meld) {
                return $meld->isAllSuit();
            });
        return $suitTripleOrQuadList->isThreeColorSuits();
    }

    protected function isThreeColorSuits() {
        $map = []; // [1 => ['s' => true] ...]
        foreach ($this as $tripleOrQuad) {
            /** @var Tile $firstTile */
            $fistTile = $tripleOrQuad[0];
            $number = $fistTile->getNumber();
            $tileTypeString = $fistTile->getTileType()->__toString();
            $map[$number][$tileTypeString] = true;
            if (count($map[$fistTile->getNumber()]) == 3) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool $requirePairWaiting
     * @param Tile|null $targetTile
     * @return bool
     */
    function isThirteenOrphan(bool $requirePairWaiting, Tile $targetTile = null) {
        $this->assertCompletePrivateHandCount();

        $valid = !$requirePairWaiting || $targetTile !== null;
        if (!$valid) {
            throw new \InvalidArgumentException();
        }

        if ($this->count() != 1) {
            return false;
        }

        /** @var Meld $meld */
        $meld = $this[0];
        return $meld->isThirteenOrphan()
        && (!$requirePairWaiting || $meld->getCount(Utils::toPredicate($targetTile)) == 2);
    }
    //endregion

    //region yaku: triple and quad
    /**
     * @param Tile $valueTile
     * @return bool
     */
    function isValueTiles(Tile $valueTile) {
        $this->assertCompletePrivateHandCount();
        $tripleOrQuadList = $this->toFiltered([TripleMeldType::getInstance(), QuadMeldType::getInstance()]);
        $isValueMeld = function (Meld $tripleOrQuad) use ($valueTile) {
            /** @var Tile $firstTile */
            $firstTile = $tripleOrQuad[0];
            return $firstTile->equalTo($valueTile, false);
        };
        return $tripleOrQuadList->isAny($isValueMeld);
    }

    /**
     * @return bool
     */
    function isThreeConcealedTripleOrQuads() {
        $this->assertCompletePrivateHandCount();
        $isConcealedTripleOrQuad = $this->getPredicate([TripleMeldType::getInstance(), QuadMeldType::getInstance()], true);
        $concealedTripleOrQuadCount = $this->getCount($isConcealedTripleOrQuad);
        return $concealedTripleOrQuadCount == 3;
    }

    /**
     * @param bool $isFour
     * @return bool
     */
    function isThreeOrFourQuads(bool $isFour) {
        $this->assertCompletePrivateHandCount();
        $n = $isFour ? 4 : 3;
        $quadCount = $this->getCount($this->getPredicate([QuadMeldType::getInstance()]));
        return $quadCount == $n;
    }

    // yaku: tile concerned
    /**
     * @param bool $isPure
     * @return bool
     */
    function isOutsideHand(bool $isPure) {
        $this->assertCompletePrivateHandCount();

        $hasRun = $this->isAny($this->getPredicate([RunMeldType::getInstance()]));
        if (!$hasRun) {
            return false;
        }

        $isOutsideMeld = function (Meld $meld) use ($isPure) {
            return $isPure ? $meld->isAnyTerminal() : $meld->isAnyTerminalOrHonor();
        };
        return $this->isAll($isOutsideMeld);
    }

    /**
     * @return bool
     */
    function isAllTerminals() {
        $this->assertCompletePrivateHandCount();
        $isAllTerminalMeld = function (Meld $meld) {
            return $meld->isAllTerminal();
        };
        return $this->isAll($isAllTerminalMeld);
    }

    /**
     * @return bool
     */
    function isAllHonors() {
        $this->assertCompletePrivateHandCount();
        $isAllHonorMeld = function (Meld $meld) {
            return $meld->isAllHonor();
        };
        return $this->isAll($isAllHonorMeld);
    }

    /**
     * @return bool
     */
    function isAllTerminalsAndHonors() {
        $this->assertCompletePrivateHandCount();
        $isAllTerminalOrHonorMeld = function (Meld $meld) {
            return $meld->isAllTerminalOrHonor();
        };
        return $this->isAll($isAllTerminalOrHonorMeld);
    }

    /**
     * @param bool $isBig
     * @return bool
     */
    function isThreeDragon(bool $isBig) {
        $this->assertCompletePrivateHandCount();
        $dragonMeldList = $this->getCopy()->where(function (Meld $meld) {
            return $meld[0]->getTileType()->isDragon();
        });
        $pairCount = $dragonMeldList->getCount($this->getPredicate([PairMeldType::getInstance()]));
        $tripleOrQuadCount = $dragonMeldList->getCount($this->getPredicate([TripleMeldType::getInstance(), QuadMeldType::getInstance()]));
        return [$pairCount, $tripleOrQuadCount] == ($isBig ? [0, 3] : [1, 2]);
    }

    /**
     * @param bool $isBig
     * @return bool
     */
    function isFourWinds(bool $isBig) {
        $this->assertCompletePrivateHandCount();
        $windMeldList = $this->where(function (Meld $meld) {
            return $meld[0]->getTileType()->isWind();
        });
        $pairCount = $windMeldList->getCount($this->getPredicate([PairMeldType::getInstance()]));
        $tripleOrQuadCount = $windMeldList->getCount($this->getPredicate([TripleMeldType::getInstance(), QuadMeldType::getInstance()]));
        return [$pairCount, $tripleOrQuadCount] == ($isBig ? [0, 4] : [1, 3]);
    }
    //endregion
}