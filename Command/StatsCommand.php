<?php

namespace SmartCore\Bundle\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smart:media:stats')
            ->setDescription('Show media cloud statistics.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $style = new TableStyle();
        $style
            ->setVerticalBorderChar(' ')
            ->setCrossingChar(' ')
        ;

        $table = new Table($output);
        $table
            ->setHeaders(['id', 'Collection', 'Default storage', 'Files', 'Original size', 'Filters size', 'Summary size'])
            ->setStyle($style)
        ;

        foreach ($em->getRepository('SmartMediaBundle:Collection')->findAll() as $collection) {
            $size = round($em->getRepository('SmartMediaBundle:File')->summarySize($collection) / 1024 / 1024, 2);
            $filtersSize = round($em->getRepository('SmartMediaBundle:FileTransformed')->summarySize($collection) / 1024 / 1024, 2);
            $sum = $size + $filtersSize;

            $table->addRow([
                $collection->getId(),
                $collection->getTitle(),
                $collection->getDefaultStorage()->getTitle(),
                $em->getRepository('SmartMediaBundle:File')->count($collection),
                $size.' MB',
                $filtersSize.' MB',
                '<comment>'.$sum.'</comment> MB',
            ]);
        }

        $table->render();
    }
}
