<?php

namespace SmartCore\Bundle\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use SmartCore\Bundle\MediaBundle\Entity\Collection;
use SmartCore\Bundle\MediaBundle\Entity\File;
use SmartCore\Bundle\MediaBundle\Entity\FileTransformed;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends Command
{
    protected static $defaultName = 'smart:media:stats';

    protected $em;

    /**
     * StatsCommand constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Show media cloud statistics.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;

        $style = new TableStyle();
        /*
        $style
            ->setVerticalBorderChars('', ' ')
            ->setCrossingChars(' ')
        ;
        */

        $table = new Table($output);
        $table
            ->setHeaders(['id', 'Collection', 'Default storage', 'Files', 'Original size', 'Filters size', 'Summary size'])
            ->setStyle($style)
        ;

        $totalSize = 0;

        foreach ($em->getRepository(Collection::class)->findAll() as $collection) {
            $size = round($em->getRepository(File::class)->summarySize($collection) / 1024 / 1024, 2);
            $filtersSize = round($em->getRepository(FileTransformed::class)->summarySize($collection) / 1024 / 1024, 2);
            $sum = $size + $filtersSize;

            $totalSize += $sum;

            $table->addRow([
                $collection->getId(),
                $collection->getTitle(),
                $collection->getDefaultStorage()->getTitle(),
                $em->getRepository(File::class)->countByCollection($collection),
                $size.' MB',
                $filtersSize.' MB',
                '<comment>'.$sum.'</comment> MB',
            ]);
        }

        $table->render();

        $output->writeln('Total size: '.$totalSize.' MB');
    }
}
