<?php

namespace hydra\text\parser;

/**
 * More complex tokenizer that allows
 */
interface ParserInterface
{

    const MODE_TEXT = 1;

    const MODE_STREAM = 2;

    const MODE_FILE = 3;

    public function setLoop ($loop = null);

    public function getLoop ();

    public function setText ($text = "");

    public function getText ();

    public function setStream ($stream = null);

    public function getStream ();

    public function setFile ($file = "");

    public function getFile ();

}