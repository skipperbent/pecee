<?php
namespace Pecee\Controller;
use Pecee\Router;
use Pecee\SimpleRouter\RouterException;

abstract class ControllerRestful extends Controller {

    protected $_requestMethod;
    protected $_method;

    public function __construct() {

        parent::__construct();

        $this->_requestMethod = ($this->isPostBack()) ? 'POST' : 'GET';
        $this->_method = strtoupper($this->getPost('_method'));
    }

    protected function call($method, array $args = null) {
        $class = get_called_class();
        $args = (is_null($args)) ? array() : $args;

        if(!method_exists($class, $method)) {
            throw new RouterException(sprintf('Restful method "%s" not implemented in class "%s"', $method, $class));
        }

        return call_user_func_array(array($class, $method), $args);
    }

    public function callAction($action, $args = null) {
        $args = func_get_args();
        $args = array_slice($args, 1);
        $args = (is_null($args)) ? array() : $args;
        $actionLower = strtolower($action);
        $actionLower = ($actionLower == Router::METHOD_DEFAULT) ? '' : $actionLower;

        // Delete
        if($this->_method == 'DELETE' && $this->_requestMethod == 'POST') {
            $this->call('destroy', $args);
            die();
        }

        // Update
        if(in_array($this->_method, array('PUT', 'PATCH')) > -1 && $this->_requestMethod == 'POST') {
            $this->call('update', array_merge(array($action), $args));
            die();
        }

        // Edit
        if(isset($args[0]) && strtolower($args[0]) == 'edit' && $this->_requestMethod == 'GET') {
            $this->call('edit', array_merge(array($action), array_slice($args, 1)));
            die();
        }

        // Create
        if($actionLower == 'create' && $this->_method == 'GET') {
            $this->call('create', $args);
            die();
        }

        // Save
        if($this->_requestMethod == 'POST') {
            $this->call('store', $args);
            die();
        }

        // Show
        if($actionLower && $this->_requestMethod == 'GET') {
            $this->call('show', array_merge(array($action), $args));
            die();
        }

        // Index
        $this->call('index');
        die();
    }

}