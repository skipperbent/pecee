<?php

namespace Pecee\Collection\Input;

use Pecee\UI\Html\Html;

class InputCollectionItem
{

    protected string $name;
    protected Html $input;
    protected ?string $description = null;

    public function __construct(string $name, Html $input, ?string $description = null)
    {
        $this->name = $name;
        $this->input = $input;
        $this->description = $description;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setInput(Html $input): self
    {
        $this->input = $input;
        return $this;
    }

    public function getInput(): Html
    {
        return $this->input;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

}