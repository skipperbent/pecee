<?php
namespace Pecee\Http\Input;

class Input {

    /**
     * @var \Pecee\Http\Input\InputCollection
     */
    public $get;

    /**
     * @var \Pecee\Http\Input\InputCollection
     */
    public $post;

    /**
     * @var \Pecee\Http\Input\InputCollection
     */
    public $file;

    public function __construct() {
        $this->setGet();
        $this->setPost();
        $this->setFile();
    }

    public function setGet() {
        $this->get = new InputCollection();

        if(count($_GET)) {
            foreach($_GET as $key => $get) {
                if(!is_array($get)) {
                    $this->get->{$key} = new InputItem($key, $get);
                    continue;
                }

                $output = array();

                foreach($get as $k => $g) {
                    $output[$k] = new InputItem($k, $g);
                }

                $this->get->{$key} = new InputItem($key, $output);
            }
        }
    }

    public function setPost() {
        $this->post = new InputCollection();

        if(count($_POST)) {

            foreach($_POST as $key => $post) {
                if(!is_array($post)) {
                    $this->post->{strtolower($key)} = new InputItem($key, $post);
                    continue;
                }

                $output = array();

                foreach($post as $k=>$p) {
                    $output[$k] = new InputItem($k, $p);
                }

                $this->post->{strtolower($key)} = new InputItem($key, $output);
            }
        }
    }

    public function setFile() {
        $this->file = new InputCollection();

        if(count($_FILES)) {
            foreach($_FILES as $key => $value) {
                // Multiple files
                if(!is_array($value['name'])) {
                    // Strip empty values
                    if($value['error'] != '4') {
                        $file = new InputFile();
                        $file->name = $value['name'];
                        $file->size = $value['size'];
                        $file->type = $value['type'];
                        $file->tmpName = $value['tmp_name'];
                        $file->error = $value['error'];
                        $this->file->{strtolower($key)} = $file;
                    }
                    continue;
                }

                foreach($value['name'] as $k=>$val) {
                    // Strip empty values
                    if($value['error'][$k] != '4') {
                        $file = new InputFile();
                        $file->name = $value['name'][$k];
                        $file->size = $value['size'][$k];
                        $file->type = $value['type'][$k];
                        $file->tmpName = $value['tmp_name'][$k];
                        $file->error = $value['error'][$k];
                        $this->file->{strtolower($key)}[$k] = $file;
                    }
                }
            }
        }
    }

}