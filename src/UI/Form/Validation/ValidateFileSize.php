<?php

namespace Pecee\UI\Form\Validation;

class ValidateFileSize extends ValidateFile
{
    public const FORMAT_KB = 0x1;
    public const FORMAT_MB = 0x2;
    public const FORMAT_GB = 0x3;
    public const FORMAT_TB = 0x4;

    protected $error;
    protected $sizeMin;
    protected $sizeMax;
    protected $sizeFormat;

    public function __construct($sizeMax, $sizeMin = null, $sizeFormat = self::FORMAT_KB)
    {
        $this->sizeMin = $sizeMin;
        $this->sizeMax = $sizeMax;
        $this->sizeFormat = $sizeFormat;
    }

    public function validates(): bool
    {
        if ($this->sizeMin !== null && $this->sizeMin >= $this->getInputSize()) {
            $this->error = lang('%s cannot be less than %sKB', $this->input->getName(), $this->sizeMin);

            return false;
        }

        if ($this->sizeMax !== null && $this->sizeMax <= $this->getInputSize()) {
            $this->error = lang('%s cannot be greater than %sKB', $this->input->getName(), $this->sizeMax);

            return false;
        }

        return true;
    }

    protected function getInputSize(): int
    {
        switch ($this->sizeFormat) {
            default:
            case static::FORMAT_KB:
                return $this->input->getSize() / 1024;
            case static::FORMAT_MB:
                return $this->input->getSize() / 1024 / 1024;
            case static::FORMAT_GB:
                return $this->input->getSize() / 1024 / 1024 / 1024;
            case static::FORMAT_TB:
                return $this->input->getSize() / 1024 / 1024 / 1024 / 1024;
        }

    }

    public function getError(): string
    {
        return $this->error;
    }

}