<?php

namespace hydra\text;

interface TokenizerInterface 
{

    const DEFAULT_BUFFER_SIZE = 8192;

    /**
     * 
     */
    public function getSeparators ();

    /**
     * 
     */
    public function setSeparators ($separators = array());
    
    /**
     * 
     */
    public function getText ();

    /**
     * 
     */
    public function addText ($text = "");

    /*
     * 
     * /
    public function prependText ($text ="");
    */

    /*
     * 
     * /
    public function ignore ();
    */

    /**
     * 
     */
    public function setText ($text = "");

    /**
     * 
     */
    public function nextToken ($emit = true);
    
}