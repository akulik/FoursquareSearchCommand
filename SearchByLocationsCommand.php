<?php namespace App\Command\Foursquare;

use Doctrine\ORM\EntityManager;
use App\Exception\GoogleApiOverLimitException;
use App\Service\GoogleGeocoder;
use Guzzle\Service\Command\CommandInterface;
use Jcroll\FoursquareApiClient\Client\FoursquareClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchByLocationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('foursquare:parse:locations')
            ->addOption(
                'store',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A store name.',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $searchLocationRepository = $entityManager->getRepository('AggregatorBundle:SearchLocation');
        $searchLocations = $searchLocationRepository->findAll();

        $command = $this->getApplication()->find('foursquare:search');

        foreach ($input->getOption('store') as $store) {
            foreach ($searchLocations as $location) {
                if ($location->isEnabled()) {
                    $arguments = array(
                        'command' => 'foursquare:parse',
                        'north-east' => $location->getNorthEast(),
                        'south-west' => $location->getSouthWest(),
                        '--query' => $store,
                    );

                    $greetInput = new ArrayInput($arguments);
                    $command->run($greetInput, $output);
                }
            }
        }
    }
}
