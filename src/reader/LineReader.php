<?php

namespace hydra\text\reader;

use hydra\text\StreamTokenizer;

/**
 * 
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
        if ($token["end"])
        {
            if (// $token["token"] && (
                ($this->stream && $this->eof) || 
                (!$this->stream && $this->eot)
            ) 
            {
                $this->emit("line", array (trim($token["token"])));
            }
            // $this->emit("line", array (trim($token["token"])));
        }

        else 
        {
            $this->emit("line", array(trim($token["token"])));
        }

        return $token;
    }

}