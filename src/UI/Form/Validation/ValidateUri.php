<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Url;

class ValidateUri extends ValidateInput
{
    const TYPE_ABSOLUTE = 0x1;
    const TYPE_RELATIVE = 0x2;
    const TYPE_BOTH = 0x3;

    protected $error;

    public function validates($type = self::TYPE_ABSOLUTE)
    {
        if($type === self::TYPE_BOTH) {
            return (Url::isValidRelative($this->input->getValue()) && Url::isValid($this->input->getValue()));
        }

        if($type === self::TYPE_RELATIVE) {
            return Url::isValidRelative($this->input->getValue());
        }

        return Url::isValid($this->input->getValue());
    }

    public function getError()
    {
        return lang('%s is not a valid url', $this->input->getName());
    }

}