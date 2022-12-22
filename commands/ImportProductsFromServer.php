<?php
namespace OrderProductsXmlGenerator\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OrderProductsXmlGenerator\Repositories\Operations;

class ImportProductsFromServer extends Command
{
    protected function configure()
    {
        $this->setName('orderproductsxmlgenerator:import-products');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Operations::importXMLProductsFromInterne();
        $output->write('Import done!');
    }
}
