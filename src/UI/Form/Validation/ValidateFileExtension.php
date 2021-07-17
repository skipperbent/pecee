<?php
namespace Pecee\UI\Form\Validation;

class ValidateFileExtension extends ValidateFile
{
    protected array $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = array_map('strtolower', $extensions);
    }

    public function validates(): bool
    {
        return in_array(strtolower($this->input->getExtension()), $this->extensions, true);
    }

    public function getError(): string
    {
        return lang('%s is not a valid format', $this->input->getName());
    }

}