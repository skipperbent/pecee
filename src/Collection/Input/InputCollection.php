<?php

namespace Pecee\Collection\Input;

use Pecee\Collection\Collection;
use Pecee\UI\Html\Html;

class InputCollection extends Collection
{

    public function addInput(string $name, Html $input, ?string $description = null): self
    {
        $this->add(
            new InputCollectionItem($name, $input, $description)
        );

        return $this;
    }

}