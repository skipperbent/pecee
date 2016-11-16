<?php
namespace Pecee\UI\AssetManager;

class AssetManager {

    protected $scripts = array();
    protected $styles = array();
    protected $scriptsDestination;
    protected $stylesDestination;

    public function __construct() {
        $this->scriptsDestination = env('JS_PATH');
        $this->stylesDestination = env('CSS_PATH');
    }

    public function createScript($filename) {
        $asset = new ScriptAsset($filename);
        $asset->setDestinationPath($this->scriptsDestination);
        $this->scripts[] = $asset;
        return $asset;
    }

    public function createStyle($filename) {
        $asset = new StyleAsset($filename);
        $asset->setDestinationPath($this->stylesDestination);
        $this->styles[] = $asset;
        return $asset;
    }

    public function getScripts() {
        return $this->scripts;
    }

    public function getStyles() {
        return $this->styles;
    }

    public function setScriptsDestination($path) {
        $this->scriptsDestination = $path;
        return $this;
    }

    public function setStylesDestination($path) {
        $this->stylesDestination = $path;
        return $this;
    }

    public function getScriptsDestination() {
        return $this->scriptsDestination;
    }

    public function getStylesDestination() {
        return $this->stylesDestination;
    }

}