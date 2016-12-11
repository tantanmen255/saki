<?php
namespace Saki\Game\Meld;

use Saki\Game\Claim;
use Saki\Game\Tile\Tile;
use Saki\Game\Tile\TileList;
use Saki\Util\Immutable;
use Saki\Util\ReadonlyArrayList;
use Saki\Util\Utils;
use Saki\Win\Waiting\WaitingType;

/**
 * A not empty TileList under one MeldType.
 * @package Saki\Game\Meld
 */
class Meld extends TileList implements Immutable {
    use ReadonlyArrayList;
    private static $meldTypeAnalyzer;

    /**
     * @return MeldTypeAnalyzer
     */
    static function getMeldTypeAnalyzer() {
        self::$meldTypeAnalyzer = self::$meldTypeAnalyzer ?? new MeldTypeAnalyzer([
                // hand win set
                RunMeldType::create(),
                TripleMeldType::create(),
                // declare win set
                QuadMeldType::create(),
                // pair
                PairMeldType::create(),
                // weak
                WeakPairMeldType::create(),
                WeakRunMeldType::create(),
                // special
                ThirteenOrphanMeldType::create(),
            ]);
        return self::$meldTypeAnalyzer;
    }

    /**
     * @param string $s
     * @return bool
     */
    static function validString(string $s) {
        // note: it's hard to implement by regex here since various MeldType exist.
        try {
            static::fromString($s);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $s
     * @return Meld
     */
    static function fromString(string $s) {
        $regex = sprintf('/^%s|(\(%s\))$/', TileList::REGEX_NOT_EMPTY_LIST, TileList::REGEX_NOT_EMPTY_LIST);
        if (preg_match($regex, $s) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid $s[%s] for Meld.', $s));
        }
        $concealed = $s[0] === '(';
        $tileListString = $concealed ? substr($s, 1, strlen($s) - 2) : $s;
        $tileList = TileList::fromString($tileListString); // validate
        return new static($tileList->toArray(), null, $concealed); // validate
    }

    /**
     * @param bool $compareConcealed
     * @param bool $compareIsRedDora
     * @return \Closure
     */
    static function getCompareKeySelector(bool $compareConcealed, bool $compareIsRedDora = false) {
        return function (Meld $meld) use ($compareConcealed, $compareIsRedDora) {
            return $meld->getCompareKey($compareConcealed, $compareIsRedDora);
        };
    }

    /**
     * @param Tile[] $tiles
     * @param MeldType|null $meldType
     * @param bool $concealed
     * @return bool
     */
    static function valid(array $tiles, MeldType $meldType = null, bool $concealed = false) {
        try {
            new self($tiles, $meldType, $concealed);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    private $meldType;
    private $concealed;
    private $claim;

    /**
     * @param Tile[] $tiles
     * @param MeldType|null $meldType
     * @param bool $concealed
     * @param Claim $claim
     */
    function __construct(array $tiles, MeldType $meldType = null, bool $concealed = false,
                         Claim $claim = null) {
        $l = (new TileList($tiles))->orderByTileID();
        $actualMeldType = $meldType ?? self::getMeldTypeAnalyzer()->analyzeMeldType($l); // validate
        if (!$actualMeldType->valid($l)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid $meldType[%s] for $tiles[%s].', $meldType, implode(',', $tiles))
            );
        }

        parent::__construct($l->toArray());
        $this->meldType = $actualMeldType;
        $this->concealed = $concealed;
        $this->claim = $claim;
    }

    function getCopy() {
        return new Meld($this->toArray(), $this->meldType, $this->concealed);
    }

    /**
     * @param bool $compareConcealed
     * @param bool $compareIsRedDora
     * @return string
     */
    function getCompareKey(bool $compareConcealed, bool $compareIsRedDora = false) {
        $concealedKey = $compareConcealed ? ($this->isConcealed() ? 'true' : 'false') : 'skip';
        $getTileKey = $compareIsRedDora ? Tile::getPrioritySelector() : Tile::getIgnoreRedPrioritySelector();
        $compareKey = $this->toArrayList($getTileKey)
            ->insertLast($concealedKey)
            ->toFormatString(',');
        return $compareKey;
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->toSortedString(true);
    }

    /**
     * @param bool $hide
     * @return \string[]
     */
    function toJson(bool $hide = false) {
        // ignore $hide
        $a = $this->toArray(Utils::getToStringCallback());

        if ($this->isRun() || $this->isTriple() || $this->isQuad(false)) {
            $fromIndex = $this->fromRelation->toFromIndex($this->count());
            $a[$fromIndex] = '-' . $a[$fromIndex];
            if ($this->isExtendKong) {
                $secondFromIndex = $this->fromRelation->toSecondFromIndex($this->count());
                $a[$secondFromIndex] = '-' . $a[$secondFromIndex];
            }
        } elseif ($this->isQuad(true)) {
            $a[0] = $a[3] = 'O';
        }

        return $a;
    }

    /**
     * @param bool $considerConcealed
     * @return string
     */
    function toSortedString(bool $considerConcealed) {
        $s = parent::__toString();
        return $considerConcealed && $this->isConcealed() ? "($s)" : $s;
    }

    /**
     * @return TileList
     */
    function toTileList() {
        return new TileList($this->toArray());
    }

    /**
     * @param bool|null $concealedFlag
     * @return Meld
     */
    function toConcealed(bool $concealedFlag = null) {
        return $this->matchConcealed($concealedFlag) ? $this :
            new Meld($this->toArray(), $this->getMeldType(), $concealedFlag);
    }

    /**
     * @return MeldType|WeakMeldType
     */
    function getMeldType() {
        return $this->meldType;
    }

    /**
     * @return bool
     */
    function isConcealed() {
        return $this->concealed;
    }

    /**
     * @param bool|null $concealedFlag
     * @return bool
     */
    function matchConcealed(bool $concealedFlag = null) {
        return $concealedFlag === null || $this->isConcealed() === $concealedFlag;
    }

    /**
     * @return Claim|null
     */
    function getClaim() {
        return $this->claim;
    }

//region MeldType delegates
    /**
     * @return bool
     */
    function isPair() {
        return $this->getMeldType() instanceof PairMeldType;
    }

    /**
     * @return bool
     */
    function isRun() {
        return $this->getMeldType() instanceof RunMeldType;
    }

    /**
     * @param bool $concealedFlag
     * @return bool
     */
    function isTriple(bool $concealedFlag = null) {
        return $this->getMeldType() instanceof TripleMeldType && $this->matchConcealed($concealedFlag);
    }

    /**
     * @param bool $concealedFlag
     * @return bool
     */
    function isQuad(bool $concealedFlag = null) {
        return $this->getMeldType() instanceof QuadMeldType && $this->matchConcealed($concealedFlag);
    }

    /**
     * @param bool $concealedFlag
     * @return bool
     */
    function isTripleOrQuad(bool $concealedFlag = null) {
        return $this->isTriple($concealedFlag) || $this->isQuad($concealedFlag);
    }

    /**
     * @return bool
     */
    function isWeakPair() {
        return $this->getMeldType() instanceof WeakPairMeldType;
    }

    /**
     * @return bool
     */
    function isWeakRun() {
        return $this->getMeldType() instanceof WeakRunMeldType;
    }

    /**
     * @return bool
     */
    function isThirteenOrphan() {
        return $this->getMeldType() instanceof ThirteenOrphanMeldType;
    }

    /**
     * @return WinSetType
     */
    function getWinSetType() {
        return $this->getMeldType()->getWinSetType();
    }
//endregion

//region target of weak meld type
    /**
     * @param Tile $waitingTile
     * @return bool
     */
    function canToWeakMeld(Tile $waitingTile) {
        if (!$this->valueExist($waitingTile)) {
            return false;
        }

        $weakMeldTileList = $this->toTileList()->remove($waitingTile);
        $weakMeldType = $this->getMeldTypeAnalyzer()->analyzeMeldType($weakMeldTileList, true);
        if ($weakMeldType === false) {
            return false;
        }

        $weakMeld = new Meld($weakMeldTileList->toArray(), $weakMeldType, $this->isConcealed());
        return $weakMeld->canToTargetMeld($waitingTile, $this->getMeldType());
    }

    /**
     * @param Tile $waitingTile
     * @return Meld
     */
    function toWeakMeld(Tile $waitingTile) {
        if (!$this->canToWeakMeld($waitingTile)) {
            throw new \InvalidArgumentException();
        }

        $weakMeldTileList = $this->toTileList()->remove($waitingTile);
        $weakMeld = new Meld($weakMeldTileList->toArray(), null, $this->isConcealed());
        return $weakMeld;
    }

    /**
     * @param Tile $waitingTile
     * @return WaitingType
     */
    function getWeakMeldWaitingType(Tile $waitingTile) {
        return $this->canToWeakMeld($waitingTile)
            ? $this->toWeakMeld($waitingTile)->getWaitingType()
            : WaitingType::create(WaitingType::NOT_WAITING);
    }
//endregion

//region weak meld type
    /**
     * @param Tile $newTile
     * @param MeldType|null $targetMeldType
     * @return bool
     */
    function canToTargetMeld(Tile $newTile, MeldType $targetMeldType = null) {
        if (!$this->getMeldType()->hasTargetMeldType()) {
            return false;
        }

        if ($targetMeldType !== null
            && $targetMeldType != $this->getMeldType()->getTargetMeldType()
        ) {
            return false;
        }

        $waitingTileList = $this->getMeldType()->getWaiting($this);
        return $waitingTileList->valueExist($newTile);
    }

    /**
     * @param Tile $newTile
     * @param MeldType|null $targetMeldType
     * @param bool|null $concealedFlag
     * @param Claim $claim
     * @return Meld
     */
    function toTargetMeld(Tile $newTile, MeldType $targetMeldType = null, bool $concealedFlag = null,
                          Claim $claim = null) {
        if (!$this->canToTargetMeld($newTile, $targetMeldType)) {
            throw new \InvalidArgumentException();
        }

        $targetTileList = $this->toTileList()->insertLast($newTile)->orderByTileID();
        $actualTargetMeldType = $targetMeldType ?? $this->getMeldType()->getTargetMeldType();
        $targetConcealed = $concealedFlag ?? $this->isConcealed();
        return new Meld($targetTileList->toArray(), $actualTargetMeldType, $targetConcealed, $claim);
    }

    /**
     * @return TileList
     */
    function getWaiting() {
        return $this->getMeldType()->getWaiting($this);
    }

    /**
     * @return WaitingType
     */
    function getWaitingType() {
        return $this->getMeldType()->getWaitingType($this);
    }
//endregion
}

