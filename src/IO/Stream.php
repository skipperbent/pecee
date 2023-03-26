<?php

namespace Pecee\IO;

use Carbon\Carbon;

class Stream
{
    protected string $file;
    protected $stream;
    protected int $bufferSize;
    protected int $start = -1;
    protected int $end = -1;
    protected int $size;

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->bufferSize = 4048;
        $this->start = -1;
        $this->end = -1;
    }

    public function start(int $size, string $mime, int $expireDays = 30): void
    {
        if (($this->stream = fopen($this->file, 'rb')) === false) {
            header('HTTP/1.1 400 Invalid Request');
            exit;
        }

        ob_get_clean();
        set_time_limit(0);

        $this->size = $size;
        $this->start = 0;
        $this->end = $this->size - 1;

        response()->headers([
            sprintf('Expires: %s', Carbon::now()->addDays($expireDays)->format('D, d M Y H:i:s \G\M\T')),
            'Content-Type: ' . $mime,
            'Accept-Ranges: 0-' . $this->end,
        ])->cache(basename($this->file));

        if (isset($_SERVER['HTTP_RANGE']) === true) {

            $streamEnd = $this->end;

            [, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                response()->headers([
                    'HTTP/1.1 416 Requested Range Not Satisfiable',
                    sprintf('Content-Range: bytes %s-%s/%s', $this->start, $this->end, $this->size),
                ]);
                exit;
            }
            if ($range === '-') {
                $streamStart = $this->size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $streamStart = $range[0];

                $streamEnd = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $streamEnd;
            }
            $streamEnd = ($streamEnd > $this->end) ? $this->end : $streamEnd;
            if ($streamStart > $streamEnd || $streamStart > $this->size - 1 || $streamEnd >= $this->size) {
                response()->headers([
                    'HTTP/1.1 416 Requested Range Not Satisfiable',
                    sprintf('Content-Range: bytes %s-%s/%s', $this->start, $this->end, $this->size),
                ]);
                exit;
            }
            $this->start = $streamStart;
            $this->end = $streamEnd;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);

            response()->headers([
                'HTTP/1.1 206 Partial Content',
                'Content-Length: ' . $length,
                sprintf('Content-Range: bytes %s-%s/%s', $this->start, $this->end, $this->size),
            ]);

        } else {
            response()->header('Content-Length: ' . $this->size);
        }

        $i = $this->start;

        while (!feof($this->stream) && $i <= $this->end) {

            $bytesToRead = $this->bufferSize;

            if (($i + $bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }

            $data = fread($this->stream, $bytesToRead);

            echo $data;

            flush();

            $i += $bytesToRead;
        }

        fclose($this->stream);
    }

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function setBufferSize(int $size): self
    {
        $this->bufferSize = $size;
        return $this;
    }
}