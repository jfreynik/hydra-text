<?php

namespace hydra\text\reader;

/**
 * 
 * emits(param)
 */
class UrlReader extends StreamTokenizer
{

    public function __construct ($file = "", $loop = null)
    {
        parent::__construct($file, array(
            "&", "=", "/", "?", ":", ".", "[", "]", "://"
        ), $loop);
    }

}