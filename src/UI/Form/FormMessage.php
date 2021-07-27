<?php

namespace Pecee\UI\Form;

class FormMessage
{

    protected ?string $name = '';
    protected ?string $index = '';
    protected ?string $message = '';
    protected ?string $placement = '';

    /**
     * Get name assosiated with the element (if any)
     * @return string $name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the index (if any)
     * @return string $index
     */
    public function getIndex(): ?string
    {
        return $this->index;
    }

    /**
     * Get message
     * @return string $message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get name (if any)
     * @param string $name
     * @return static
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set index
     * @param string $index
     * @return static
     */
    public function setIndex(?string $index = null): self
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Set message
     * @param string $message
     * @return static
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string $placement
     */
    public function getPlacement(): string
    {
        return $this->placement;
    }

    /**
     * @param string $placement
     * return static
     */
    public function setPlacement(string $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

}