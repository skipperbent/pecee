<?php
namespace Pecee\UI\YuiCompressor;

class YuiCompressor
{

    /**
     * @author Simon Sessingø
     * @version 1.0
     *
     * For information about options - or download of the jarFile, please refer to the
     * YUICompressor documentation here:
     *
     * http://developer.yahoo.com/yui/compressor/
     * http://yuilibrary.com/projects/yuicompressor/
     */

    const TYPE_JAVASCRIPT = 'js';
    const TYPE_CSS = 'css';

    protected $jarFile;
    protected $tempDir;
    protected $javaExecutable = 'java';
    protected $types = [
        self::TYPE_JAVASCRIPT,
        self::TYPE_CSS,
    ];

    protected $items = [];

    public function __construct()
    {
        $this->jarFile = $_ENV['framework_path'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'yuicompressor-2.4.8.jar';
        $this->tempDir = sys_get_temp_dir();
    }

    /**
     * Add file to minify
     *
     * @param string $type
     * @param string $file
     * @param array $options
     *
     * @return YuiCompressor
     * @throws YuiCompressorException
     */
    public function addFile($type, $file, $options = [])
    {
        $this->validateType($type);

        if (!is_file($file)) {
            throw new YuiCompressorException('File does not exist: ' . $file);
        }

        $contents = file_get_contents($file);

        return $this->addItem($type, $contents, $options, basename($file), dirname($file));
    }

    /**
     * Add content to minify
     *
     * @param string $type
     * @param string $content
     * @param array $options
     *
     * @return YuiCompressor
     * @throws YuiCompressorException
     */
    public function addContent($type, $content, $options = [])
    {
        $this->validateType($type);

        $content = preg_replace('!/\*.*?\*/!s', '', $content);
        $content = preg_replace('/\n\s*\n/', "\n", $content);

        return $this->addItem($type, $content, $options);
    }

    /**
     * Add new item
     *
     * @param string $type
     * @param string $content
     * @param array $options
     * @param string|null $filename
     * @param string|null $filePath
     *
     * @return self $this
     */
    protected function addItem($type, $content, $options, $filename = null, $filePath = null)
    {
        $item = new YuiCompressorItem();
        $item->type = $type;
        $item->content = $content;
        $item->options = $options;
        $item->filename = $filename;
        $item->filePath = $filePath;
        $this->items[] = $item;

        return $this;
    }

    /**
     * Compress items
     *
     * @param bool $single
     * @return YuiCompressorItem|array
     * @throws YuiCompressorException
     */
    public function minify($single = false)
    {
        if (!is_file($this->jarFile) || !is_dir($this->tempDir) || !is_writable($this->tempDir)) {
            throw new YuiCompressorException('Minify_YuiCompressor : $jarFile must be set or is not a valid ressource.');
        }

        if (!($tmpFile = tempnam($this->tempDir, 'yuic_'))) {
            throw new YuiCompressorException('Minify_YuiCompressor : could not create temp file.');
        }

        if (count($this->items)) {
            /* @var $item YuiCompressorItem */
            foreach ($this->items as $item) {
                file_put_contents($tmpFile, $item->content);
                $output = [];
                exec($this->getCmd($item->options, $item->type, $tmpFile), $output);
                unlink($tmpFile);
                $item->minified = (isset($output[0]) ? $output[0] : '');
                $item->sizeKB = round(strlen($item->content) / 1024, 2);
                $item->minifiedKB = $item->sizeKB - round(strlen($item->minified) / 1024, 2);
                $item->minifiedRatio = round(($item->minifiedKB / $item->sizeKB) * 100);
            }
        }

        return ($single) ? $this->items[count($this->items) - 1] : $this->items;
    }

    protected function validateType($type)
    {
        if (!in_array($type, $this->types)) {
            throw new YuiCompressorException('Unknown type: ' . $type . '. Type must be one of the following: ' . join($this->types, ', '));
        }
    }

    protected function getCmd($userOptions, $type, $tmpFile)
    {
        $options = array_merge([
            'charset'               => 'utf-8',
            'type'                  => $type,
            'nomunge'               => true,
            'preserve-semi'         => true,
            'disable-optimizations' => true,
        ], $userOptions);

        $cmd = $this->javaExecutable . ' -jar ' . escapeshellarg($this->jarFile) . " --type {$type}"
            . (isset($options['charset']) ? " --charset {$options['charset']}" : '')
            . (isset($options['line-break']) && $options['line-break'] >= 0 ? ' --line-break ' . (int)$options['line-break'] : '');

        if ($type === static::TYPE_JAVASCRIPT) {
            foreach (['nomunge', 'preserve-semi', 'disable-optimizations'] as $opt) {
                $cmd .= $options[$opt] ? " --{$opt}" : '';
            }
        }

        return $cmd . ' ' . escapeshellarg($tmpFile);
    }

    public function getJarFile()
    {
        return $this->jarFile;
    }

    public function getTempDir()
    {
        return $this->tempDir;
    }

    public function getJavaExecutable()
    {
        return $this->javaExecutable;
    }

    public function setJarFile($jarFile)
    {
        $this->jarFile = $jarFile;
    }

    public function setTempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    public function setJavaExecutable($javaExecutable)
    {
        $this->javaExecutable = $javaExecutable;
    }

    public function getItems()
    {
        return $this->items;
    }

}