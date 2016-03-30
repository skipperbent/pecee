<?php
namespace Pecee\Http\Input;

interface IInputItem {

    public function validates();

    public function addValidation($validation, $placement = null);

    public function getValidationErrors();

}