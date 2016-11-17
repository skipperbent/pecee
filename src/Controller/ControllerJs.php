<?php
namespace Pecee\Controller;

use Pecee\Controller\File\FileAbstract;
use Pecee\UI\YuiCompressor\YuiCompressor;

class ControllerJs extends FileAbstract {

    public function getHeader() {
        return 'application/javascript';
    }

    public function getExtension() {
        return 'js';
    }

    public function getPath() {
        return env('JS_PATH', 'www/js/');
    }

    public function processContent(&$content) {
        if(env('MINIFY_JS', false)) {
            $compressor = new YuiCompressor();
            $compressor->addContent($this->getExtension(), $content);
            $output = $compressor->minify(true);

            if ($output->minified && strlen($output->minified)) {
                $content = $output->minified;
            }
        }
    }

}