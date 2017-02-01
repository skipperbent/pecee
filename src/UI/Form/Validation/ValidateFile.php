<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Http\Input\InputFile;

/**
 * Class ValidateFile
 *
 * @property InputFile $input
 * @method InputFile getInput
 */
abstract class ValidateFile extends ValidateInput
{
    /**
     * Validate both custom validation and build-in validation (like empty values and framework specific stuff).
     */
    public function runValidation()
    {
        if(($this->allowEmpty === true && ($this->input instanceof InputFile) === false) || ($this->input instanceof InputFile && $this->input->getError() === 4)) {
            return true;
        }

        return $this->validates();
    }

}