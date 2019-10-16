<?php

namespace hydra\text;

use Evenement\EventEmitter;

/**
 * Class used to split strings into tokens based on separator text.
 * 
 * emits ("token", "end")
 */
class TextTokenizer extends EventEmitter implements TokenizerInterface
{

    protected $text;

    protected $separators;

    protected $index;

    protected $length;

    protected $token;

    public function __construct ($text = "", $separators = array (" ", "\r\n", "\r", "\n"))
    {
        $this->setText($text);
        $this->setSeparators($separators);
    }

    public function setText ($text = "")
    {
        $this->text = $text;
        $this->index = 0;
        $this->length = strlen($text);
        return $this;
    }

    public function getText ()
    {
        return $this->text;
    }

    public function addText ($text = "")
    {
        $this->text = "{$this->text}{$text}";
        $this->length += strlen($text);
        return $this;
    }

    /*
    public function prependText ($text = "")
    {
        $this->text = "{$text}{$this->text}";
        $len = strlen($text);
        $this->length += $len;
        $this->index += $len;
        return $this;
    }
    */

    public function setSeparators ($separators = array (" ", "\r\n", "\r", "\n"))
    {
        arsort($separators);
        $this->separators = array ();
        for ($i = 0; $i < count($separators); $i++)
        {
            $this->separators[] = array (
                "text" => $separators[$i],
                "length" => strlen($separators[$i]),
            );
        }
        return $this;
    }

    public function getSeparators ()
    { 
        return $this->separators;
    }

    public function nextToken ($emit = true)
    {
        $token = $this->getNextToken();

        if ($emit)
        {
            if ($token["end"])
            {
                $this->emit("end", array($token));
            }

            else
            {
                $this->emit("token", array($token));
            } 
        }

        return $token;
    }

    protected function getNextToken ()
    {
        if ($this->length <= $this->index)
        {
            return array (
                "separator" => "",
                "token" => "",
                "length" => 0,
                "end" => true,
            );
        }

        $startIndex = $this->index;

        // replaced with substring
        // $token = array ();

        for ($i = $this->index; $i < $this->length; $i++)
        {
            $this->index = $i;
            $char = $this->text[$i];
            $stop = "";

            for ($j = 0; $j < count($this->separators); $j++)
            {
                $tmp = $this->separators[$j];
                $sep = $tmp["text"];
                $len = $tmp["length"];

                if (1 < $len && ($i + $len) <= $this->length)
                {
                    for ($k = 0; $k < $len; $k++)
                    {
                        $sch = $sep[$k];
                        $tch = $this->text[$i + $k];

                        if ($sch === $tch)
                        {
                            if ($k == ($len - 1))
                            {
                                $stop = $tmp;
                                break;
                            }
                            continue;
                        }
                        break;
                    }
                }

                else if ($char === $sep)
                {
                    $stop = $tmp;
                    break;
                }
            }

            if ($stop)
            {
                $token = substr($this->text, 0, $i);

                $this->text = substr($this->text, ($i + $stop["length"]));
                $this->length = $this->length - ($i + $stop["length"]);
                $this->index = 0;

                return array (
                    "separator" => $stop["text"],
                    "token" => $token,
                    "length" => $this->length,
                    "end" => ($this->length === 0),
                );
            }

            /* replaced with substring above
            else
            {
                $token[] = $char;
            }
            */
        }

        // we hit the end of the text block
        $token = $this->text;
        $this->text = "";
        $this->length = 0;
        $this->index = 0;

        return array (
            "separator" => "",
            "token" => $token,
            "length" => 0,
            "end" => true
        );

    }

}