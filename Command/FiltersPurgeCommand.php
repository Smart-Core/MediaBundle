<?php

namespace SmartCore\Bundle\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use SmartCore\Bundle\MediaBundle\Entity\Collection;
use SmartCore\Bundle\MediaBundle\Entity\FileTransformed;
use SmartCore\Bundle\MediaBundle\Service\MediaCloudService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersPurgeCommand extends Command
{
    protected static $defaultName = 'smart:media:filters:purge';

    protected $em;
    protected $mc;

    /**
     * StatsCommand constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, MediaCloudService $mc)
    {
        parent::__construct();

        $this->em = $em;
        $this->mc = $mc;
    }

    protected function configure()
    {
        $this
            ->setDescription('Purge all filtered images.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;

        $output->writeln('<comment>Truncate FileTransformed Table...</comment>');

        $cmd = $em->getClassMetadata(FileTransformed::class);
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeUpdate($q);
        $connection->query('SET FOREIGN_KEY_CHECKS=1');

        $output->writeln('<comment>Remove files...</comment>');

        foreach ($em->getRepository(Collection::class)->findAll() as $collection) {
            $mc = $this->mc->getCollection($collection->getId());

            $mc->purgeTransformedFiles();
        }
    }
}
