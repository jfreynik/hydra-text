<?php

namespace hydra\text;

use React\Stream\ReadableResourceStream as StreamReader;

/**
 * Class used to split streams into tokens based on separator text.
 * This class offers a synchronous and an asynchronous interface.
 * 
 * emits ("token", "error", "end")
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

    public function __construct ($file = "", $separators = array (" ", "\r", "\n", "\n\r",), $loop = null)
    {
        if ($loop)
        {
            $this->setLoop($loop);
        }

        if (is_string($file))
        {
            $this->setFile($file);
        }

        else
        {
            $this->setStream($file);
        }

        $this->paused = false;
        $this->running = false;
        $this->eof = false;
    }

    public function setLoop ($loop = null)
    {
        $this->loop = $loop;

        if ($this->file)
        {
            $this->stream = fopen("r", $file);
            $this->reader = new StreamReader($this->stream, $this->loop);
            $this->registerListeners($this->reader);
        }

        else if ($this->stream)
        {
            $this->reader = new StreamReader($this->stream, $this->loop);
            $this->registerListeners($this->reader);
        }

        return $this;
    }

    public function getLoop ()
    {
        return $this->loop;
    }

    public function pause ()
    {
        if ($this->loop)
        {
            $this->paused = true;
            if ($this->reader)
            {
                $this->reader->pause();
            }
        }
        return $this;
    }

    public function resume ()
    {
        if ($this->loop)
        {
            $this->paused = false;
            if ($this->reader)
            {
                $this->reader->resume();
                $this->loop->addFutureTick(function () {
                    $this->processText();
                });
            }
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
        if ($this->loop)
        {
            $this->stream = fopen("r", $file);
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
        if ($this->loop)
        {
            $this->reader = new StreamReader($stream, $this->loop);
            $this->registerListeners($this->reader);
        }
        $this->stream = $stream;
        return $this;
    }

    public function getStream ()
    {
        return $this->stream;
    }

    /*
    public function addText ($text = "")
    {
        parent::addText($text);
        if ($this->loop)
        {

        }
        return $this;
    }
    */

    // not async
    public function nextToken ($emit = true)
    {
        $token = $this->getNextToken();

        if ($token["end"])
        {

        }

    }

    protected function processText ()
    {
        /*
        if ($this->running)
        {
            // only process tokens one at a time
            return;
        }

        $this->running = true;
        */

        $token = $this->getNextToken();
        $continue = true;

        if ($token["end"])
        {
            // see if the reader is still readable
            if ($this->reader->isReadable())
            {
                $this->addText($token["text"]);
                if (!$this->paused)
                {
                    $continue = false;
                    $this->reader->resume();
                }
            }

            else 
            {
                $this->emit("end", array ($token));
            }
        }

        else
        {
            $this->emit("token", array ($token));
        }

        if (!$this->paused && $continue)
        {
            $this->loop->addFutureTick(function () 
            {
                $this->processText();
            });
        }
    }

    protected function registerListeners ($reader = null)
    {
        if ($reader)
        {
            $reader->on("data", function ($data) use (&$reader, &$length) 
            {
                $reader->pause(); 
                $this->addText($data);

                // once we have text start processing
                if (!$this->paused)
                {
                    $this->processText();
                }
            });

            $reader->on("end", function () {
                // end of stream
                $this->eof = true;
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

}