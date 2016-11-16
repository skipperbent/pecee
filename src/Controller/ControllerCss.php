<?php
namespace Pecee\Controller;

use Pecee\Controller\File\FileAbstract;
use Pecee\UI\YuiCompressor\YuiCompressor;

class ControllerCss extends FileAbstract {

    public function getHeader() {
        return 'text/css';
    }

    public function getExtension() {
        return 'css';
    }

    public function getPath() {
        return env('CSS_PATH', 'public/css/');
    }

    public function processContent(&$content) {
        if(env('MINIFY_CSS', false)) {
            $compressor = new YuiCompressor();
            $compressor->addContent($this->getExtension(), $content);
            $output = $compressor->minify(true);

            if ($output->minified && strlen($output->minified)) {
                $content = $output->minified;
            }
        }
    }

}