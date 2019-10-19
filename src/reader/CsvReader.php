<?php

namespace hydra\text\reader;

use hydra\text\StreamTokenizer;

class CsvReader extends StreamTokenizer
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
                        $column = "{$text}{$token["token"]}";
                        $token["token"] = $column;
                        $token["length"] = strlen($column);
                        $this->currentRow[] = $column;
                        $this->emit("column", array($column));
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
                        $column = "{$text}{$token["token"]}";
                        $token["token"] = $column;
                        $token["length"] = strlen($column);
                        $this->currentRow[] = $column;
                        $this->emit("column", array($column));
                        $this->emit("row", array ($this->currentRow));
                        $this->currentRow = array ();
                        return $token;
                    }
                    break;
            }

            if ($token["end"])
            {
                // TODO need to find how to get last token to emit "column" etc
                $token["token"] = "{$text},{$token["token"]}";
                $token["length"] = strlen($token["token"]);
                break;
            }

            // $text .= $token["token"];

        } while (true);

        return $token;
    }

    protected function eofToken ($token = array())
    {

    }

}