<?php
namespace Saki\Command;

class CommandProcessor {
    private $parser;

    function __construct(CommandParser $parser) {
        $this->parser = $parser;
    }

    function getParser() {
        return $this->parser;
    }

    /**
     * @param ...$scripts
     */
    function process(... $scripts) {
        $parser = $this->getParser();

        // todo validate command errors e.x. 'discard E E;' or ' discard E E'
        $script = implode('; ', $scripts);
        $lines = $parser->scriptToLines($script);
        foreach ($lines as $line) {
            $command = $parser->parseLine($line);
            $command->execute();
        }
    }
}