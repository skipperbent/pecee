<?php
namespace Pecee\UI\Form\Validation;

use Pecee\Session\Session;

class ValidateSession extends ValidateInput
{
    protected $allowEmpty = false;
    protected $sessionName;

    public function __construct($sessionName)
    {
        $this->sessionName = $sessionName;
    }

    public function validates()
    {
        return ((bool)Session::exists($this->sessionName));
    }

    public function getError()
    {
        return lang('%s does not exist', $this->input->getName());
    }

}