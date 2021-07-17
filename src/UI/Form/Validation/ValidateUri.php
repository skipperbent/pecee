<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Url;

class ValidateUri extends ValidateInput
{
    public const TYPE_ABSOLUTE = 'absolute';
    public const TYPE_RELATIVE = 'relative';
    public const TYPE_BOTH = 'both';

    protected string $type;

    public function __construct(string $type = self::TYPE_ABSOLUTE)
    {
        $this->type = $type;
    }

    public function validates(): bool
    {
        if($this->type === self::TYPE_BOTH) {
            return (Url::isValidRelative($this->input->getValue()) && Url::isValid($this->input->getValue()));
        }

        if($this->type === self::TYPE_RELATIVE) {
            return Url::isValidRelative($this->input->getValue());
        }

        return Url::isValid($this->input->getValue());
    }

    public function getError(): string
    {
        return lang('%s is not a valid url', $this->input->getName());
    }

}