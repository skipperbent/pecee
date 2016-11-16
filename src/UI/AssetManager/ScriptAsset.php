<?php
namespace Pecee\UI\AssetManager;

use Pecee\UI\YuiCompressor\YuiCompressor;

class ScriptAsset extends Asset {

    public function getContentType() {
        return 'application/javascript';
    }

    protected function processFile($file, &$contents) {

        $compressor = new YuiCompressor();
        $compressor->addContent(YuiCompressor::TYPE_JAVASCRIPT, $contents);
        $output = $compressor->minify(true);

        if ($output->minified && strlen($output->minified)) {
            $contents = $output->minified;
        }

    }
}