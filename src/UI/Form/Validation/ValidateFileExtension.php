<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputFile;
use Pecee\IO\File;

class ValidateFileExtension extends ValidateFile
{

    protected $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function validates()
    {

        if (!($this->input instanceof InputFile)) {
            return true;
        }

        return (in_array(File::getExtension($this->input->getName()), $this->extensions));
    }

    public function getError()
    {
        return lang('%s is not a valid format', $this->input->getName());
    }

}