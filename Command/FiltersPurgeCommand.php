<?php

namespace SmartCore\Bundle\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersPurgeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smart:media:filters:purge')
            ->setDescription('Purge all filtered images.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $filters = $this->getContainer()->get('liip_imagine.filter.configuration')->all();

        $output->writeln('<comment>Truncate FileTransformed Table...</comment>');

        $cmd = $em->getClassMetadata('SmartMediaBundle:FileTransformed');
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeUpdate($q);
        $connection->query('SET FOREIGN_KEY_CHECKS=1');

        $output->writeln('<comment>Remove files...</comment>');

        foreach ($filters as $name => $filter) {
            dump($name);
        }

        foreach ($em->getRepository('SmartMediaBundle:Collection')->findAll() as $collection) {
            $mc = $this->getContainer()->get('smart_media')->getCollection($collection->getId());

            $mc->purgeTransformedFiles();
        }
    }
}
