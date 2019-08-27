<?php

namespace SmartCore\Bundle\MediaBundle\Provider;

use Doctrine\ORM\EntityManager;
use SmartCore\Bundle\MediaBundle\Entity\Collection;
use SmartCore\Bundle\MediaBundle\Entity\File;
use SmartCore\Bundle\MediaBundle\Entity\FileTransformed;
use SmartCore\Bundle\MediaBundle\Service\GeneratorService;
use SmartCore\Bundle\MediaBundle\Service\MediaCollection;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class LocalProvider implements ProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $source_dir;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var GeneratorService
     */
    protected $generator;

    /**
     * @var Request
     */
    protected $request;

    /** @var MediaCollection */
    protected $mediaCollection;

    /**
     * LocalProvider constructor.
     *
     * @param ContainerInterface $container
     * @param array              $arguments
     */
    public function __construct(ContainerInterface $container, array $arguments = [])
    {
        if (isset($arguments['source_dir'])) {
            $this->source_dir = $arguments['source_dir'];
        } else {
            $this->source_dir = "%kernel.project_dir%/public/"; // @todo
        }

        $this->container    = $container;
        $this->em           = $container->get('doctrine.orm.entity_manager');
        $this->generator    = $container->get('smart_media.generator');
        $this->request      = $container->get('request_stack')->getCurrentRequest();
    }

    /**
     * Получить ссылку на файл.
     *
     * @param int $id
     * @param string|null $filter
     * @param string|null default_filter
     *
     * @return string|null
     */
    public function get($id, $filter = null, $default_filter = '200x200')
    {
        if (empty($filter)) {
            $filter = null;
        }

        if (null === $id) {
            return null;
        }

        /** @var File $file */
        $file = $this->em->find(File::class, $id);

        if (null === $file) {
            return null;
        }

        if ($file and $file->isMimeType('png')) {
            $runtimeConfig['format'] = 'png';
        }

        try {
            $this->container->get('liip_imagine.filter.configuration')->get($filter);
//            $this->container->get('smart_imagine_configuration')->get($filter);
        } catch (\RuntimeException $e) {
            if ($filter !== 'orig') {
                try {
                    $this->container->get('liip_imagine.filter.configuration')->get($default_filter);
//                    $this->container->get('smart_imagine_configuration')->get($default_filter);

                    $filter = $default_filter;
                } catch (\RuntimeException $e) {
                    $filter = null;
                }
            } else {
                $filter = null;
            }
        }

        $basePath = $this->request ? $this->request->getBasePath() : '';
        $ending   = '';

        if ($filter) {
            $fileTransformed = $this->em->getRepository(FileTransformed::class)->findOneBy(['file' => $file, 'filter' => $filter]);

            if (isset($runtimeConfig['format'])) {
                $ending = '.'.$runtimeConfig['format'];
            } else {
                $ending = '.'.$this->container->get('liip_imagine.filter.configuration')->get($filter)['format'];
//                $ending = '.'.$this->container->get('smart_imagine_configuration')->get($filter)['format'];
            }

            if (null === $fileTransformed) {
                //$ending .= '?id='.$file->getId();
                return $basePath.
                    $file->getStorage()->getRelativePath(). // @todo !!!
                    $file->getCollection()->getRelativePath(). // @todo !!!
                    '/'.$filter.'/img.php?id='.$file->getId()
                ;
            }
        }

        $transformedImagePathInfo = pathinfo($basePath.$file->getFullRelativeUrl($filter));

        if (empty($ending)) {
            $ending = '.'.$transformedImagePathInfo['extension'];
        }

        return $transformedImagePathInfo['dirname'].'/'.$transformedImagePathInfo['filename'].$ending;
    }

    /**
     * @param int    $id
     * @param string $filter
     *
     * @return null|mixed
     */
    public function generateTransformedFile(int $id, $filter)
    {
        $file = $this->em->find(File::class, $id);

        if (null === $file) {
            return null;
        }

        $runtimeConfig = [];

        if ($file and $file->isMimeType('png')) {
            $runtimeConfig['format'] = 'png';
        }

        $fileTransformed = $this->em->getRepository(FileTransformed::class)->findOneBy(['file' => $file, 'filter' => $filter]);

//        if (null === $fileTransformed) {
            $imagine = $this->container->get('liip_imagine.binary.loader.default');
            $imagineFilterManager = $this->container->get('liip_imagine.filter.manager');

            if ($file->isMimeType('image/jpeg') or $file->isMimeType('image/png') or $file->isMimeType('image/gif')) {
                // dummy
            } else {
                echo 'Unsupported image format';

                return null;
            }

            $originalImage = $imagine->find($file->getFullRelativeUrl());

            if (empty($this->request)) {
                $webDir = $this->container->getParameter('kernel.project_dir').'/public'.$this->generator->generateRelativePath($file, $filter);
            } else {
                $webDir = dirname($this->request->server->get('SCRIPT_FILENAME')).$this->generator->generateRelativePath($file, $filter);
            }

            if (!is_dir($webDir) and false === @mkdir($webDir, 0777, true)) {
                throw new \RuntimeException(sprintf("Unable to create the %s directory.\n", $webDir));
            }

            if (isset($runtimeConfig['format'])) {
                $ending = '.'.$runtimeConfig['format'];
            } else {
                $ending = '.'.$this->container->get('liip_imagine.filter.configuration')->get($filter)['format'];
            }

            $transformedImagePathInfo = pathinfo($webDir.'/'.$file->getFilename());
            $transformedImagePath = $transformedImagePathInfo['dirname'].'/'.$transformedImagePathInfo['filename'].$ending;
            $transformedImage = $imagineFilterManager->applyFilter($originalImage, $filter, $runtimeConfig)->getContent();

            file_put_contents($transformedImagePath, $transformedImage);

            if (null === $fileTransformed) {
                $fileTransformed = new FileTransformed();
                $fileTransformed
                    ->setFile($file)
                    ->setFilter($filter)
                    ->setSize((new \SplFileInfo($transformedImagePath))->getSize())
                ;

                $this->em->persist($fileTransformed);
                $this->em->flush($fileTransformed);
            }

            return $transformedImage;
//        }

//        return null;
    }
    
    /**
     * @param File $file
     *
     * @return \Symfony\Component\HttpFoundation\File\File|void
     *
     * @throws \RuntimeException
     */
    public function upload(File $file, $relative_path)
    {
        /*
        if (empty($this->request)) {
            //$webDir = $this->container->getParameter('kernel.project_dir').'/public'.$file->getFullRelativePath();
        } else {
            $webDir = dirname($this->request->server->get('SCRIPT_FILENAME')).$file->getFullRelativePath();
        }
        */
        $webDir = $this->source_dir.$relative_path;

//        dump($webDir);

        if (!is_dir($webDir) and false === @mkdir($webDir, 0777, true)) {
            throw new \RuntimeException(sprintf("Unable to create the %s directory.\n", $webDir));
        }

//        dump($file->getFilename());
//        die;

        $newFile = $file->getUploadedFile()->move($webDir, $file->getFilename());

        // @todo настройка качества сжатия и условное уменьшение т.е. если картинка больше заданных размеров.
        // @todo возможность использовать Imagick, если доступен.
        // @todo поддержку PNG
        /*
        if (strpos($newFile->getMimeType(), 'jpeg') !== false) {
            $img = imagecreatefromjpeg($newFile->getPathname());
            imagejpeg($img, $newFile->getPathname(), 90);
            imagedestroy($img);

            clearstatcache();

            $file->setSize($newFile->getSize());
        }
        */
        return $newFile;
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @todo качественную обработку ошибок.
     */
    public function remove($id)
    {
        $filesTransformed = $this->em->getRepository(FileTransformed::class)->findBy(['file' => $id]);

        /** @var FileTransformed $fileTransformed */
        foreach ($filesTransformed as $fileTransformed) {
            if (empty($this->request)) {
                $fullPath = $this->container->getParameter('kernel.project_dir').'/public'.$fileTransformed->getFullRelativeUrl();
            } else {
                $fullPath = dirname($this->request->server->get('SCRIPT_FILENAME')).$fileTransformed->getFullRelativeUrl();
            }

            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Удаление оригинала.
        if (!empty($fileTransformed) and $fileTransformed instanceof FileTransformed) {
            if (empty($this->request)) {
                $fullPath = $this->container->getParameter('kernel.project_dir').'/public'.$fileTransformed->getFile()->getFullRelativeUrl();
            } else {
                $fullPath = dirname($this->request->server->get('SCRIPT_FILENAME')).$fileTransformed->getFile()->getFullRelativeUrl();
            }

            return @unlink($fullPath);
        }

        return true;
    }

    /**
     * @param Collection $collection
     *
     * @return bool
     */
    public function purgeTransformedFiles(Collection $collection)
    {
        foreach ($this->container->get('liip_imagine.filter.configuration')->all() as $filter_name => $filter) {
            $dir = getcwd().'/public'.$collection->getStorage()->getRelativePath().$collection->getRelativePath().'/'.$filter_name;

            if (is_dir($dir)) {
                foreach(new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path
                ) {
                    $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
                }

                rmdir($dir);
            }
        }

        return true;
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

    /**
     * @return MediaCollection
     */
    public function getMediaCollection(): MediaCollection
    {
        return $this->mediaCollection;
    }

    /**
     * @param MediaCollection $mediaCollection
     *
     * @return $this
     */
    public function setMediaCollection(MediaCollection $mediaCollection): self
    {
        $this->mediaCollection = $mediaCollection;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceDir(): string
    {
        return $this->source_dir;
    }
}
