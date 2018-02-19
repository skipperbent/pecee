<?php

namespace Pecee\UI\Form\Validation;

class ValidateFileSize extends ValidateFile
{
    const FORMAT_KB = 0x1;
    const FORMAT_MB = 0x2;
    const FORMAT_GB = 0x3;
    const FORMAT_TB = 0x4;

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

    public function validates()
    {
        if ($this->sizeMin !== null && $this->sizeMin >= $this->input->getSize()) {
            $this->error = lang('%s cannot be less than %sKB', $this->input->getName(), $this->getInputSize());

            return false;
        }

        if ($this->sizeMax !== null && $this->sizeMax <= $this->input->getSize()) {
            $this->error = lang('%s cannot be greater than %sKB', $this->input->getName(), $this->getInputSize());

            return false;
        }

        return true;
    }

    protected function getInputSize()
    {

        switch ($this->sizeFormat) {
            default:
            case static::FORMAT_KB:
                return $this->input->getSize() * 1024;
            case static::FORMAT_MB:
                return $this->input->getSize() * 1024 * 1024;
            case static::FORMAT_GB:
                return $this->input->getSize() * 1024 * 1024 * 1024;
            case static::FORMAT_TB:
                return $this->input->getSize() * 1024 * 1024 * 1024 * 1024;
        }

    }

    public function getError()
    {
        return $this->error;
    }

}