<?php

namespace Pecee\Model;

use Pecee\Guid;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\File\FileData;
use Pecee\Model\ModelMeta\IModelMetaField;

class ModelFile extends ModelMeta
{
    protected string $table = 'file';

    protected array $columns = [
        'id',
        'filename',
        'original_filename',
        'path',
        'type',
        'bytes',
    ];

    public function __construct($file = null)
    {
        parent::__construct();

        $this->id = Guid::create();
        $this->filename = basename($file);
        $this->path = dirname($file);

        if (is_file($file) === true) {
            $this->type = mime_content_type($file);
            $this->bytes = filesize($file);
        }
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function setOriginalFilename(string $filename): void
    {
        $this->original_filename = $filename;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setBytes(int $bytes): void
    {
        $this->bytes = $bytes;
    }

    protected function onNewDataItem(): IModelMetaField
    {
        $data = new FileData();
        $data->file_id = $this->id;
    }

    protected function fetchData(): ModelCollection
    {
        return FileData::getByIdentifier($this->id);
    }

    public function getFullPath(): string
    {
        return $this->path . $this->filename;
    }


}