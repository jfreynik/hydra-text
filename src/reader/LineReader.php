<?php

namespace hydra\text\reader;

use hydra\text\StreamTokenizer;

/**
 * Class can be used to process file one line at a time.
 */

class LineReader extends StreamTokenizer
{

    public function __construct ($file = "", $loop = null)
    {
        parent::__construct($file, array ("\n", "\r", "\r\n"), $loop);
    }

    protected function getNextToken ()
    {
        $token = parent::getNextToken();

        // if we have more text or we already emitted the last token
        if (($token["end"] && !$this->eof) || $this->emittedLastToken)
        {
            return $token;
        }

        $this->emit("line", array(trim($token["token"])));        
        return $token;
    }

}