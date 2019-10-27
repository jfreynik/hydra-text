<?php

namespace hydra\text\reader;

/**
 * The job of a "Reader" is to take data from a text representation and convert 
 * it into a data (data structured) representation.
 *  
 */
interface Reader 
{

    /**
     * Returns the different typs of tokens the reader emits.
     */
    public function getEmits (); 

    /**
     * Loop over all of the text and emit the different tokens found.
     */
    public function run ();

    /**
     * Loops over all of the text and returns a serialized version of the text.
     * If a file or stream is provided to the method, it will write the serialized 
     * data to that stream instead of returning it, this is useful for large files. 
     */
    public function serialize ($file = null);

}