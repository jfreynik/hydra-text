<?php

namespace hydra\text\parser;

use hydra\text\StreamTokenizer;

class CsvParser extends StreamTokenizer
{
    protected $currentRow = array ();

    public function __construct ($file = "", $loop = null)
    {
        parent::__construct($file, array (
            ",", "\n", "\r", "\r\n", "\"", "\"\"",
        ), $loop);
    }

    protected function getNextToken ()
    {
        $boolInString = false;
        $text = "";

        do {
            $token = parent::getNextToken();

            switch ($token["separator"])
            {
                case ",":
                    if ($boolInString)
                    {
                        $text = "{$text}{$token["token"]},";
                    }

                    else
                    {
                        // end of column
                        $token["token"] = "{$text}{$token["token"]}";
                        $token["length"] = strlen($token["token"]);
                        $this->currentRow[] = $token;
                        $this->emit("column", array($token));
                        return $token;
                    }
                    break;

                case "\"":
                    if ($boolInString)
                    {
                        $text = "{$text}{$token["token"]}";
                        $boolInString = false;
                    }

                    else
                    {
                        $boolInString = true;
                    }
                    break;

                case "\"\"":
                    if ($boolInString)
                    {
                        $text = "{$text}{$token["token"]}\"";
                    }

                    else
                    {
                        // error
                    }
                    break;

                case "\n":
                case "\r":
                case "\r\n":
                    if ($boolInString)
                    {
                        $text = "{$text}{$token["token"]}\"";
                    }

                    else
                    {
                        // end of column & row
                        $token["token"] = "{$text}{$token["token"]}";
                        $token["length"] = strlen($token["token"]);
                        $this->currentRow[] = $token;
                        $this->emit("column", array($token));
                        $this->emit("row", array ($this->currentRow));
                        return $token;
                    }
                    break;
            }

            if ($token["end"])
            {
                $token["token"] = "{$text},{$token["token"]}";
                $token["length"] = strlen($token["token"]);
                break;
            }

            // $text .= $token["token"];

        } while (true);

        return $token;
    }

}