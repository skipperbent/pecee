<?php
namespace Pecee\UI\Html;

use Pecee\Http\Middleware\BaseCsrfVerifier;

class HtmlForm extends Html {

    const ENCTYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';
    const ENCTYPE_FORM_DATA = 'multipart/form-data';
    const ENCTYPE_TEXT_PLAIN = 'text/plain';

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    public function __construct($name, $method = self::METHOD_POST, $action = null, $enctype = self::ENCTYPE_APPLICATION_URLENCODED) {
        parent::__construct('form');

        $this->closingType = self::CLOSE_TYPE_NONE;

        $this->name($name);
        $this->enctype($enctype);
        $this->method($method);
        $this->action((($action === null) ? url() : $action));

        // Add csrf token
        if(strtolower($method) !== 'get') {
            $this->addItem(new HtmlInput(BaseCsrfVerifier::POST_KEY, 'hidden', csrf_token()));
        }
    }

    public function name($name) {
        return $this->attr('name', $name);
    }

    public function method($method) {
        if($method !== self::METHOD_GET) {
            return $this->addAttribute('method', $method);
        }
        return $this;
    }

    public function enctype($enctype) {
        if($enctype !== self::ENCTYPE_APPLICATION_URLENCODED) {
            return $this->addAttribute('enctype', $enctype);
        }
        return $this;
    }

    public function action($action) {
        return $this->attr('action', $action);
    }

}