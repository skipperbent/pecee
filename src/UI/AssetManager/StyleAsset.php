<?php
namespace Pecee\UI\AssetManager;

use Pecee\UI\YuiCompressor\YuiCompressor;

class StyleAsset extends Asset
{

    public function getContentType()
    {
        return 'text/css';
    }

    protected function processFile($file, &$contents)
    {

        $compressor = new YuiCompressor();
        $compressor->addContent(YuiCompressor::TYPE_CSS, $contents);
        $output = $compressor->minify(true);

        if ($output->minified && $output->minified !== '') {
            $contents = $output->minified;
        }

    }

}