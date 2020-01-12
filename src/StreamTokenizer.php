<?php

namespace hydra\text;

use React\Stream\ReadableResourceStream as StreamReader;

/**
 * Class used to split streams into tokens based on separator text.
 * This class offers a synchronous and an asynchronous interface.
 * 
 * 
 * emits ("token", "error", "end", "pause", "resume")
 */
class StreamTokenizer extends TextTokenizer /* implements Async */
{
    /**
     * 
     */
    protected $bufferSize;

    /**
     * 
     */
    protected $loop;

    /**
     * 
     */
    protected $file;

    /**
     * 
     */
    protected $stream;

    /**
     * 
     */
    protected $reader;

    /**
     * 
     */
    protected $paused;
    
    /**
     * 
     */
    protected $running;

    /**
     * 
     */
    protected $eof;

    /**
     * 
     */
    protected $toAppend = "";

    /**
     * 
     */
    protected $isWin = false;

    /**
     * 
     */
    protected $resourceType = "";

    /**
     * 
     */
    public function __construct ($file = "", $separators = array (" ", "\r", "\n", "\r\n",), $loop = null)
    {

        $this->isWin = (DIRECTORY_SEPARATOR === "\\");

        if ($loop)
        {
            $this->setLoop($loop);
        }

        $type = self::findResourceType($file);
        
        if ($type) 
        {
            switch ($type["type"])
            {
                case "stream":
                    $this->setStream($file);
                    break;
                case "text":
                    $this->setText($file);
                    break;
                case "file":
                default:
                    $this->setFile($file);
                    break;
            }
        }

        $this->setSeparators($separators);

        $this->bufferSize = self::DEFAULT_BUFFER_SIZE;
        $this->paused = false;
        $this->running = false;
        $this->eof = false;
    }

    public function getResourceType ()
    {
        return $this->resourceType;
    }

    public static function findResourceType ($file = "")
    {
        if (!is_resource($file))
        {
            if (is_string($file))
            {
                $len = strlen($file);
                if (5 < $len)
                {
                    $sub = substr($file, 0, 5);
                    $sub = strtolower($sub);
                    $sub = strtolower($sub);
                    if ("text:" === $sub)
                    {
                        $text = (substr($file, 5));
                        return array (
                            "type" => "text",
                            "text" => $text,
                        );
                    }

                    else if ("file:" === $sub)
                    {
                        $file = trim(substr($file, 5));
                        return array (
                            "type" => "file",
                            "file" => $file,
                        );
                    }
                }

                return is_file($file) ?
                    array (
                        "type" => "file",
                        "file" => $file
                    ) : array (
                        "type" => "text",
                        "text" => $file
                    );
            }
            
            return false;
        }

        return array (
            "type" => "stream",
            "file" => $file,
        );
    }

    public function copy ()
    {
        switch ($this->resourceType)
        {
            case "text":
                return new static($this->text, $this->separators, $this->loop);
            case "file":
                return new static($this->file, $this->separators, $this->loop);
            case "stream":
                return new static ($this->stream, $this->separators, $this->loop);
        }
        return false;
    }

    public function setLoop ($loop = null)
    {
        $this->loop = $loop;

        if ($this->isWin)
        {
            // start processing when loop is run
            $this->run();
        }

        else
        {
            if ($this->file)
            {
                // will not work on windows
                $this->stream = fopen("r", $file);
                $this->reader = new StreamReader($this->stream, $this->loop);
                $this->registerListeners($this->reader);
            }

            else if ($this->stream)
            {
                // will not work on windows
                $this->reader = new StreamReader($this->stream, $this->loop);
                $this->registerListeners($this->reader);
            }
        }

        return $this;
    }

    public function getLoop ()
    {
        return $this->loop;
    }

    public function pause ()
    {
        if ($this->loop && $this->reader)
        {
            $this->paused = true;
            $this->reader->pause();
            $this->emit("pause");
        }
        return $this;
    }

    public function resume ()
    {
        if ($this->loop && $this->reader)
        {
            $this->paused = false;
            $this->loop->futureTick(function () {
                $this->run();
            });

            $this->emit("resume");
        }
        return $this;
    }

    public function getBufferSize ()
    {
        return $this->bufferSize;
    }

    public function setBufferSize ($size = 8192)
    {
        $this->bufferSize = $size;
        return $this;
    }

    public function setText ($text = "")
    {
        $this->resourceType = "text";
        return parent::setText($text);
    }

    public function setFile ($file = null)
    {
        if (!is_file($file))
        {
            throw new \InvalidArgumentException();
        }

        $this->resourceType = "file";
        $this->stream = fopen($file, "r");

        if ($this->loop && !$this->isWin)
        {
            $this->reader = new StreamReader($this->stream, $this->loop);
            $this->registerListeners($this->reader);
        }

        $this->file = $file;
        return $this;
    }

    public function getFile ()
    {
        return $this->file;
    }

    public function setStream ($stream = null)
    {
        if (!is_resource($stream))
        {
            throw new \InvalidArgumentException();
        }

        $this->resourceType = "stream";

        if ($this->loop && !$this->isWin)
        {
            $this->reader = new StreamReader($stream, $this->loop);
            $this->registerListeners($this->reader);
        }
        $this->stream = $stream;
        return $this;
    }

    public function eof ()
    {
        return $this->stream ? $this->eof : $this->eot;
    }

    public function getStream ()
    {
        return $this->stream;
    }
    
    public function on ($event, callable $listener)
    {
        if ("end" === $event &&
            $this->eof
        )
        {
            // skip registering the listener
            $listener();
            return;
        }

        return parent::on($event, $listener);
    }

    /**
     * @overridden
     */
    public function nextToken ()
    {
        $token = $this->getNextToken();

        $args = func_get_args();

        if ($token["end"])
        {
            if ($this->stream)
            {
                if ($this->eof)
                {
                    if (!$this->emittedLastToken)
                    {
                        $this->emittedLastToken = true;
                        $this->emit("token", array($token));
                        $this->emit("end");
                    }                    
                }

                else
                {
                    $text = fread($this->stream, $this->bufferSize);
                    if ($text === "")
                    {
                        $this->eof = true;
                    }

                    // not yet at the end - append text and try again
                    $text = "{$token["token"]}{$token["separator"]}{$text}";
                    $this->appendText($text);
                    return $this->nextToken();
                }
            }
            
            else
            {
                // no stream in use
                if (!$this->emittedLastToken)
                {
                    $this->emit("token", array($token));
                    $this->emit("end");
                    $this->emittedLastToken = true;
                }
                
                $this->eof = true;
            }
        }

        else
        {
            $this->emit("token", array($token));
        }

        return $token;
    }

    protected function nextTokenAsync ()
    {
        if ($this->paused)
        {
            return;
        }

        $token = $this->getNextToken();

        if ($token["end"])
        {
            if ($this->eof)
            {
                if (!$this->emittedLastToken)
                {
                    $this->emit("token", array ($token));
                    $this->emit("end");
                    $this->emittedLastToken = true;
                }
                return;
            } 
            
            else {
                $this->toAppend = "{$token["token"]}{$token["separator"]}";
                if (!$this->paused)
                {
                    $this->reader->resume();
                }
                return;
            }
        }

        else
        {
            $this->emit("token", array ($token));
        }

        if (!$this->paused)
        {
            $this->loop->futureTick(function () 
            {
                $this->nextTokenAsync();
            });
        }
    }

    protected function registerListeners ($reader = null)
    {
        // local file system async only works on linux
        // can we call with loop on windows and process
        // semi-async?
        if ($reader)
        {
            $reader->on("data", function ($data) use (&$reader) 
            {
                $reader->pause(); 

                $this->appendText("{$this->toAppend}{$data}");
                $this->toAppend = "";

                // once we have text start processing
                if (!$this->paused)
                {
                    $this->loop->futureTick(function (){
                        $this->nextTokenAsync();
                    });
                }
            });

            $reader->on("end", function () {

                // end of stream
                $this->eof = true;
                $this->reader->close();

                if ($this->toAppend)
                {
                    $this->appendText($this->toAppend);
                    $this->toAppend = "";
                }

                $this->loop->futureTick(function() {
                    $this->nextTokenAsync();
                });
            });

            $reader->on("error", function ($err) {
                $this->emit("error", array($err));
            });

            $reader->on("close", function () {
                $this->eof = true;
            });
        }

        return $this;
    }

    /**
     * 
     */
    public function run ()
    {
        if ($this->paused)
        {
            return;
        }

        if ($this->loop)
        {
            if ($this->isWin)
            {
                if (!$this->eof && !$this->paused)
                {
                    // mimic async
                    $this->loop->futureTick(function () {
                        $this->nextToken();
                        $this->run();
                    });
                }
            }

            else
            {
                if (!$this->stream)
                {
                    // just text - mimic async
                    $this->loop->futureTick(function() {
                        $this->nextToken();
                        $this->run();
                    });
                }

                // else stream will be handled by the rejistered reader
            }
        }

        else
        {
            // no loop? - completely synchronous
            while (!$this->eof)
            {
                $this->nextToken();
            }
        }
    }

    public function asyncTokenize ()
    {
        // return a promise or event callback
    }

    // synchronous
    public function tokenize ()
    {
        $copy = $this->copy();
        $emits = $this->getEmits();
        $tokens = array ();

        for ($i = 0; $i < count($emits); $i++)
        {
            $emit = $emits[$i];
            $copy->on($emits[$i], function ($data) use ($emit, &$tokens) {
                $tokens[] = array ($emit, $data);
            })
        }

        while (!$copy->eof)
        {
            $copy->nextToken();
        }

        return $tokens;
    }

}