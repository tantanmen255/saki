<?php
namespace Saki\Game\Wall;

use Saki\Game\Tile\Tile;
use Saki\Util\ArrayList;

/**
 * @package Saki\Game\Wall
 */
class Stack {
    /** @var ArrayList [] or [$bottomTile] or [$topTile, $bottomTile] */
    private $tileList;

    function __construct() {
        $this->tileList = new ArrayList();
    }

    function init() {
        $this->tileList->removeAll();
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->tileList->__toString();
    }

    /**
     * @return ArrayList
     */
    function getTileList() {
        return $this->tileList;
    }

    /**
     * @return int
     */
    function getCount() {
        return $this->tileList->count();
    }

    /**
     * @return bool
     */
    function isEmpty() {
        return $this->tileList->isEmpty();
    }

    /**
     * @param Tile[] $tileChunk
     */
    function setTileChunk(array $tileChunk) {
        $valid = (count($tileChunk) == 2);
        if (!$valid) {
            throw new \InvalidArgumentException();
        }

        // $tileChunk = [$tile0, $tile1]
        // $tileList = [$tile1, $tile0]
        $this->tileList->removeAll()
            ->insertLast($tileChunk);
    }

    /**
     * @param Tile $tile
     */
    function setNextPopTile(Tile $tile) {
        $this->tileList->replaceFirst($tile); // validate
    }

    /**
     * @return Tile
     */
    function popTile() {
        return $this->tileList->shift(); // validate
    }
}