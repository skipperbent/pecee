<?php
namespace Pecee\Http\Input;

interface IInputItem {

    public function validates();

    public function addValidation($validation);

    public function getValidationErrors();

}