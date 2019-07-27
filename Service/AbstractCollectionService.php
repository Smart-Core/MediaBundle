<?php

declare(strict_types=1);

namespace SmartCore\Bundle\MediaBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use SmartCore\Bundle\MediaBundle\Entity\Category;
use SmartCore\Bundle\MediaBundle\Entity\File;
use SmartCore\Bundle\MediaBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractCollectionService
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var GeneratorService
     */
    protected $generator;

    /**
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * AbstractCollectionService constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->generator = new GeneratorService();
    }

    /**
     * @param ProviderInterface $provider
     *
     * @return $this
     */
    public function setProvider(ProviderInterface $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return $this
     */
    public function setEntityManager(EntityManagerInterface $em): self
    {
        $this->em = $em;

        return $this;
    }

    /**
     * Получить ссылку на файл.
     *
     * @param int         $id
     * @param string|null $filter
     *
     * @return string|null
     */
    public function get($id, $filter = null)
    {
        return $this->provider->get($id, $filter, $this->collection->getDefaultFilter());
    }

    /**
     * @param UploadedFile $file
     * @param Category|int $category
     * @param array $tags
     *
     * @return int - ID файла в коллекции.
     */
    public function upload(\Symfony\Component\HttpFoundation\File\File $uploadedFile, $category = null, array $tags = null)
    {
        // @todo проверку на доступность загруженного файла
        // могут быть проблеммы, если в настройках сервера указан маленький upload_max_filesize и/или post_max_size
        $file = new File($uploadedFile);
        $file
            ->setCollection($this->collection)
            ->setFilename($this->generator->generateFileName($file))
            ->setRelativePath($this->generator->generateFilePath($file))
        ;

        $newFile = $this->provider->upload($file);

        $this->em->persist($file);
        $this->em->flush();

        return $file->getId();
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function remove($id)
    {
        if (empty($id)) {
            return;
        }

        $this->provider->remove($id);

        $file = $this->em->find(File::class, $id);

        if (!empty($file)) {
            $this->em->remove($file);
            $this->em->flush($file);
        }

        return true;
    }

    /**
     * @param int    $id
     * @param string $filter
     *
     * @return mixed|null
     */
    public function generateTransformedFile(int $id, $filter)
    {
        return $this->provider->generateTransformedFile($id, $filter);
    }

    /**
     * @return bool
     */
    public function purgeTransformedFiles()
    {
        return $this->provider->purgeTransformedFiles($this->collection);
    }

    /**
     * Получить список файлов.
     *
     * @param int|null $categoryId
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return File[]|null
     */
    public function findBy($categoryId = null, array $orderBy = null, $limit = null, $offset = null)
    {
        // @todo
    }
}
