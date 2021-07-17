<?php

namespace Pecee\UI\Form\Validation;

class ValidateFileSize extends ValidateFile
{
    public const FORMAT_KB = 'format_kb';
    public const FORMAT_MB = 'format_mb';
    public const FORMAT_GB = 'format_gb';
    public const FORMAT_TB = 'format_tb';

    protected string $error = '';
    protected int $sizeMax;
    protected ?int $sizeMin;
    protected string $sizeFormat;

    public function __construct(int $sizeMax, ?int $sizeMin = null, string $sizeFormat = self::FORMAT_KB)
    {
        $this->sizeMax = $sizeMax;
        $this->sizeMin = $sizeMin;
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