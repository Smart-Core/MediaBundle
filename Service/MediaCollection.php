<?php

namespace SmartCore\Bundle\MediaBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SmartCore\Bundle\MediaBundle\Entity\Category;
use SmartCore\Bundle\MediaBundle\Entity\Collection;
use SmartCore\Bundle\MediaBundle\Entity\File;
use SmartCore\Bundle\MediaBundle\Entity\FileTransformed;
use SmartCore\Bundle\MediaBundle\Provider\LocalProvider;
use SmartCore\Bundle\MediaBundle\Provider\ProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaCollection extends AbstractCollectionService
{
    use ContainerAwareTrait;

    protected $code;
    protected $title;
    protected $relative_path;

    protected $storage;
    protected $default_filter;
    protected $file_relative_path_pattern;
    protected $filename_pattern;

    /**
     * @param ContainerInterface $container
     * @param int $id
     */
    public function __construct(ContainerInterface $container = null, $id = null)
    {
//        $this->em               = $container->get('doctrine.orm.entity_manager');

        // @todo разные провайдеры.
//        $this->provider = new LocalProvider($container);
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
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param mixed $storage
     *
     * @return $this
     */
    public function setStorage($storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultFilter()
    {
        return $this->default_filter;
    }

    /**
     * @param mixed $default_filter
     *
     * @return $this
     */
    public function setDefaultFilter($default_filter): self
    {
        $this->default_filter = $default_filter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileRelativePathPattern()
    {
        return $this->file_relative_path_pattern;
    }

    /**
     * @param mixed $file_relative_path_pattern
     *
     * @return $this
     */
    public function setFileRelativePathPattern($file_relative_path_pattern): self
    {
        $this->file_relative_path_pattern = $file_relative_path_pattern;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilenamePattern()
    {
        return $this->filename_pattern;
    }

    /**
     * @param mixed $filename_pattern
     *
     * @return $this
     */
    public function setFilenamePattern($filename_pattern): self
    {
        $this->filename_pattern = $filename_pattern;

        return $this;
    }
}
