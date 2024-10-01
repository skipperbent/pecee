<?php

namespace Pecee\UI\Form\Validation;

class ValidateFileMime extends ValidateFile
{
    protected array $mimeTypes;

    public function __construct(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function validates(): bool
    {
        return in_array(strtolower($this->input->getType()), array_map('strtolower', $this->mimeTypes), true);
    }

    public function getError(): string
    {
        return lang('%s is not a valid format', [$this->input->getName()]);
    }

}