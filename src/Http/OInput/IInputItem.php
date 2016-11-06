<?php
namespace Pecee\Http\OInput;

interface IInputItem {

    public function validates();

    public function addValidation($validation, $placement = null);

    public function getValidationErrors();

}