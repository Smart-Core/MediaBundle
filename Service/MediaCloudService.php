<?php

namespace SmartCore\Bundle\MediaBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use SmartCore\Bundle\MediaBundle\Entity\Collection;
use SmartCore\Bundle\MediaBundle\Entity\File;
use SmartCore\Bundle\MediaBundle\Entity\Storage;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaCloudService
{
    use ContainerAwareTrait;

    protected $config;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MediaCollection[]
     */
    protected $collections;

    /**
     * @var MediaStorage[]
     */
    protected $storages;

    /**
     * MediaCloudService constructor.
     *
     * @param ContainerInterface     $container
     * @param EntityManagerInterface $em
     * @param array                  $config
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em, array $config)
    {
        $this->config = $config;

        // storages
        foreach ($config['storages'] as $name => $val) {
            $s = new MediaStorage();
            $s->setCode($val['code'])
                ->setTitle($val['title'])
                ->setRelativePath($val['relative_path'])
                ->setProvider($val['provider'])
                ->setArguments($val['arguments'])
            ;

            $this->storages[$val['code']] = $s;
        }

        $dbStorages = $em->getRepository(Storage::class)->findAll();
        foreach ($dbStorages as $dbStorage) {
            if (isset($this->storages[$dbStorage->getCode()])) {
                throw new \Exception('Storage with code "'.$dbStorage->getCode().'" is already exist');
            }

            $s = new MediaStorage();
            $s->setCode($dbStorage->getCode())
                ->setTitle($dbStorage->getTitle())
                ->setRelativePath($dbStorage->getRelativePath())
                ->setProvider($dbStorage->getProvider())
                ->setArguments($dbStorage->getArguments())
            ;

            $this->storages[$dbStorage->getCode()] = $s;
        }

        // collections
        foreach ($config['collections'] as $name => $val) {
            $c = new MediaCollection();
            $c->setCode($val['code'])
                ->setTitle($val['title'])
                ->setRelativePath($val['relative_path'])
                ->setFilenamePattern($val['filename_pattern'])
                ->setFileRelativePathPattern($val['file_relative_path_pattern'])
                ->setStorage($this->storages[$val['storage']])
            ;

            $this->collections[$val['code']] = $c;
        }

        $dbCollections = $em->getRepository(Collection::class)->findAll();
        foreach ($dbCollections as $dbCollection) {
            if (isset($this->collections[$dbCollection->getCode()])) {
                throw new \Exception('Collection with code "'.$dbCollection->getCode().'" is already exist');
            }

            $c = new MediaCollection();
            $c->setCode($dbCollection->getCode())
                ->setTitle($dbCollection->getTitle())
                ->setRelativePath($dbCollection->getRelativePath())
                ->setFilenamePattern($dbCollection->getFilenamePattern())
                ->setFileRelativePathPattern($dbCollection->getFileRelativePathPattern())
                ->setStorage($this->storages[$dbCollection->getStorage()->getCode()])
            ;

            $this->collections[$dbCollection->getCode()] = $c;
        }

//        dump($this->storages);
//        dump($this->collections);

        $this->container = $container;
        $this->em        = $em;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * @param int|null $id
     *
     * @return MediaCollection
     *
     * @todo если не задан id, и коллекций больше 1, то выкидывать исключение.
     */
    public function getCollection($code = null)
    {
        return $this->collections[$code];
    }

    /**
     * Получить ссылку на файл.
     *
     * @param int $id
     * @param string $filter
     *
     * @return string|null
     *
     * @todo кеширование.
     */
    public function getFileUrl($id, $filter = null)
    {
        if (!is_numeric($id)) {
            return null;
        }

        /** @var File $file */
        $file = $this->em->getRepository(File::class)->find($id);

        if (empty($file)) {
            return null;
        }

        return $this->getCollection($file->getCollection()->getId())->get($id, $filter);
    }

    /**
     * @param int    $id
     * @param string $filter
     *
     * @return mixed|null
     */
    public function generateTransformedFile(int $id, $filter)
    {
        if (!is_numeric($id)) {
            return null;
        }

        /** @var File $file */
        $file = $this->em->getRepository(File::class)->find($id);

        if (empty($file)) {
            return null;
        }

        return $this->getCollection($file->getCollection()->getId())->generateTransformedFile($id, $filter);
    }

    public function createCollection()
    {
        // @todo
    }

    public function removeCollection()
    {
        // @todo
    }

    /**
     * @return MediaCollection[]|array
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    public function getStoragesList()
    {
        // @todo
    }

    public function createStorage()
    {
        // @todo
    }

    public function removeStorage()
    {
        // @todo
    }

    public function updateStorage()
    {
        // @todo
    }
}
