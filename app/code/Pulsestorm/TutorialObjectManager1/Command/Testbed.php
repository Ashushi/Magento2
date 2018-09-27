<?php
namespace Pulsestorm\TutorialObjectManager1\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pulsestorm\TutorialObjectManager1\Model\Example;

class Testbed extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('ps:tutorial-object-manager-1');
        $this->setDescription('A cli playground for testing commands');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$object = new Example;
		
		// fetches the object manager (return an instance of the Magento object manager.)
		// getObjectManager is a helper method
		// The OM is a special object that Magento uses to instantiate nearly all its objects.
		$manager = $this->getObjectManager();
		
		// passed PHP class name as a string in object manager’s create method. Behind the scenes, the object manager instantiates a Pulsestorm\TutorialObjectManager1\Model\Example.
		
		$object  = $manager->create('Pulsestorm\TutorialObjectManager1\Model\Example');
		
		$message = $object->getHelloMessage();
		
		$output->writeln($message);
    }
}