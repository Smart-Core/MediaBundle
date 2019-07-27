<?php

declare(strict_types=1);

namespace SmartCore\Bundle\MediaBundle\Service;

class MediaStorage
{
    protected $code;
    protected $title;
    protected $relative_path;

    protected $provider;
    protected $arguments;

    /**
     * Constructor.
     */
    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     *
     * @return $this
     */
    public function setCode($code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return $this
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRelativePath()
    {
        return $this->relative_path;
    }

    /**
     * @param mixed $relative_path
     *
     * @return $this
     */
    public function setRelativePath($relative_path): self
    {
        $this->relative_path = $relative_path;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $provider
     *
     * @return $this
     */
    public function setProvider($provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $arguments
     *
     * @return $this
     */
    public function setArguments($arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }
}
