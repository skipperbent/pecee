<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputFile;

class ValidateFileMime extends ValidateFile
{

    protected $mimeTypes;

    public function __construct(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function validates()
    {

        if (!($this->input instanceof InputFile)) {
            return true;
        }

        return (in_array(strtolower($this->input->getType()), $this->mimeTypes));
    }

    public function getError()
    {
        return lang('%s is not a valid format', [$this->input->getName()]);
    }

}