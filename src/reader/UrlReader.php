<?php

namespace hydra\text\reader;

/**
 * 
 * emits(scheme, host, port, user, pass, path, query, fragment)
 */
class UrlReader extends StreamTokenizer
{

    public function __construct ($file = "", $loop = null)
    {
        parent::__construct($file, array(
            "&", "=", "/", "?", ":", ".", "[", "]", "://"
        ), $loop);
    }

    protected function getNextToken ()
    {

    }

}