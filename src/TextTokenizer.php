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

    protected $text = "";

    protected $separators = array ();

    protected $index = 0;

    protected $length = 0;

    protected $eot = false;

    // - needed?
    protected $token;

    public function __construct ($text = "", $separators = array (" ", "\r\n", "\r", "\n"))
    {
        $this->setText($text);
        $this->setSeparators($separators);
    }

    public function setText ($text = "")
    {
        $this->eot = false;
        $this->text = $text;
        $this->index = 0;
        $this->length = strlen($text);
        return $this;
    }

    public function getText ()
    {
        return $this->text;
    }

    public function appendText ($text = "")
    {
        $this->eot = false;
        $this->text = "{$this->text}{$text}";
        $this->length += strlen($text);
        return $this;
    }

    // is this a needed function?
    public function prependText ($text = "")
    {
        $this->text = "{$text}{$this->text}";
        $len = strlen($text);
        $this->length += $len;
        $this->index += $len;
        return $this;
    }

    public function setSeparators ($separators = array (" ", "\r\n", "\r", "\n"))
    {
        usort($separators, function ($a, $b) {
            $la = strlen($a);
            $lb = strlen($b);
            if ($la != $lb)
            {
                return $lb < $la ? -1 : 1;
            }
            else if ($a === $b)
            {
                return 0;
            }

            return ($b < $a) ? -1 : 1; 
        });
        $this->separators = array ();
        for ($i = 0; $i < count($separators); $i++)
        {
            $this->separators[] = array (
                "text" => $separators[$i],
                "length" => strlen($separators[$i]),
            );
        }

        // var_dump($this->separators);
        // exit;

        return $this;
    }

    public function getSeparators ()
    { 
        return $this->separators;
    }

    public function nextToken ()
    {
        $token = $this->getNextToken();

        if ($token["end"])
        {
            if (!$this->eot)
            {
                $this->emit("token", array($token));
                $this->eot = true;
            }
            $this->emit("end");
        }

        else
        {
            $this->emit("token", array($token));
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
                                break 2;
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