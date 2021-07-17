<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Session\Session;

class ValidateSession extends ValidateInput
{
    protected bool $allowEmpty = false;
    protected string $sessionName;

    public function __construct(string $sessionName)
    {
        $this->sessionName = $sessionName;
    }

    public function validates(): bool
    {
        return Session::exists($this->sessionName);
    }

    public function getError(): string
    {
        return lang('%s does not exist', $this->input->getName());
    }

}