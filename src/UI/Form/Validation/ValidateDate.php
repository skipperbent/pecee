<?php
namespace Pecee\UI\Form\Validation;

use Carbon\Carbon;
use Exception;

class ValidateDate extends ValidateInput
{
    protected ?string $format;

    public function __construct(?string $format = null)
    {
        $this->format = $format;
    }

    public function validates(): bool
    {
        try {
            if ($this->format === null) {
                Carbon::parse($this->input->getValue(), 'UTC');
            } else {
                Carbon::createFromFormat($this->format, $this->input->getValue(), 'UTC');
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getError(): string
    {
        return lang('%s is not a valid date', $this->input->getName());
    }

}