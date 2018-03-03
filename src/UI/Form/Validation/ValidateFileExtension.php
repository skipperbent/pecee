<?php
namespace Pecee\UI\Form\Validation;

class ValidateFileExtension extends ValidateFile
{
    protected $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function validates()
    {
        return \in_array($this->input->getExtension(), array_map('strtolower', $this->extensions), false);
    }

    public function getError()
    {
        return lang('%s is not a valid format', $this->input->getName());
    }

}