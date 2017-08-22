<?php
namespace Pecee\Model;

use Pecee\Guid;
use Pecee\Model\File\FileData;

class ModelFile extends ModelData
{
    protected $dataPrimary = 'file_id';
    protected $table = 'file';

    protected $columns = [
        'id',
        'filename',
        'original_filename',
        'path',
        'type',
        'bytes',
    ];

    public function __construct($file)
    {
        parent::__construct();

        $this->id = Guid::create();
        $this->filename = basename($file);
        $this->path = dirname($file);

        if (is_file($file)) {
            $this->type = mime_content_type($file);
            $this->bytes = filesize($file);
        }
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function setOriginalFilename($filename)
    {
        $this->original_filename = $filename;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setBytes($bytes)
    {
        $this->bytes = $bytes;
    }

    protected function getDataClass()
    {
        return FileData::class;
    }

    protected function fetchData()
    {
        return FileData::getByIdentifier($this->id);
    }

    public function getFullPath()
    {
        return $this->path . $this->Filename;
    }

}