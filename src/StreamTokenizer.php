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

    public function __construct ($file = "", $separators = array (" ", "\r", "\n", "\r\n",), $loop = null)
    {

        $this->isWin = (DIRECTORY_SEPARATOR === "\\");

        if ($loop)
        {
            $this->setLoop($loop);
        }

        $type = $this->getFileType($file);
        if ("stream" == $type["type"])
        {
            $this->setStream($type["file"]);
        }

        else if ("text" == $type["type"])
        {
            $this->setText($type["text"]);
        } 
        
        else
        {
            $this->setFile($type["file"]);
        }

        $this->setSeparators($separators);

        $this->bufferSize = self::DEFAULT_BUFFER_SIZE;
        $this->paused = false;
        $this->running = false;
        $this->eof = false;
    }

    public function copy ($copy = null)
    {
        if ($copy)
        {
            $loop = $copy->getLoop();
            return $copy;
        }
        
        $loop = $this->getLoop();

        // $copy = new 
        return $copy;
    }

    public function setLoop ($loop = null)
    {
        $this->loop = $loop;

        if ($this->isWin)
        {
            $loop->futureTick(function () {
                $this->run();
            });
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

    public function setFile ($file = null)
    {
        if (!is_file($file))
        {
            throw new \InvalidArgumentException();
        }

        $this->stream = fopen($file, "r");

        if ($this->loop && !$this->isWin)
        {
            $this->reader = new StreamReader($this->stream, $this->loop);
            $this->registerListeners($this->reader);
        }

        $this->file = $file;
        return $this;
    }

    public function getFileType ($file = "")
    {
        if (is_resource($file))
        {
            return array (
                "type" => "stream",
                "file" => $file,
            );
        }

        else if (is_string($file))
        {
            if (5 < strlen($file))
            {
                $sub = substr($file, 0, 5);
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
            if (!is_file($file))
            {
                return array (
                    "type" => "text",
                    "text" => $file,
                );
            }

            else
            {
                return array (
                    "type" => "file",
                    "file" => $file,
                );
            }
        }

        return array (
            "type" => "error",
            "message" => "type not usable in stream tokenizer",
        );
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
        /*
        if (isset($args[0])) {
            var_dump($this->eof);
            exit;
        }
        */

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
                
                /*
                if ($this->toAppend)
                {
                    $this->appendText($this->toAppend);
                    $this->toAppend = "";
                }
                */

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
     * Synchronous / Asynchronous method for processing text.
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
                    $this->nextToken();
                    $this->loop->futureTick(function () {
                        $this->run();
                    });
                }
            }

            else
            {
                // real async
                $this->nextTokenAsync();
            }
        }

        else
        {
            // synchronous
            while (!$this->eof)
            {
                $this->nextToken();
            }
        }
    }

}