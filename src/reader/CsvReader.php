<?php

namespace hydra\text\reader;

use hydra\text\StreamTokenizer;

/**
 * 
 * emits (column, row, token, end)
 */
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

            if ($token["end"] && !$this->eof)
            {
                return $token;
            }

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
                        $column = trim("{$text}{$token["token"]}");
                        $token["token"] = $column;
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
                        $text = "{$text}{$token["token"]}\n";
                    }

                    else
                    {
                        // end of column & row
                        $column = trim("{$text}{$token["token"]}");
                        $token["token"] = $column;
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
                
                $column = trim("{$text}{$token["token"]}");
                $token["token"] = $column;

                if ($this->eof)
                {
                    $this->currentRow[] = $column;
                    $this->emit("column", array($column));
                    $this->emit("row", array ($this->currentRow));
                    $this->currentRow = array ();
                }

                break;
            }

        } while (true);

        return $token;
    }

}