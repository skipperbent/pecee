<?php
namespace Pecee\UI\Form\Validation;

class ValidateFileMime extends ValidateFile
{
    protected $mimeTypes;

    public function __construct(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function validates()
    {
        return in_array(strtolower($this->input->getType()), $this->mimeTypes, false);
    }

    public function getError()
    {
        return lang('%s is not a valid format', [$this->input->getName()]);
    }

}