<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputFile;

class ValidateFileNotNullOrEmpty extends ValidateFile
{
    protected bool $allowEmpty = false;

    public function validates(): bool
    {
        if (($this->input instanceof InputFile) === false) {
            return false;
        }

        return ($this->input->hasError() === false && trim($this->input->getName()) !== '' && $this->input->getSize() > 0 && $this->input->getError() === 0);
    }

    public function getError(): string
    {
        return lang('%s cannot be empty', [$this->input->getName()]);
    }

}