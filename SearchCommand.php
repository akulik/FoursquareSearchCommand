<?php namespace App\Command\Foursquare;

use App\Domain\Model\Store\Store;
use App\Infrastructure\Persistance\Domain\Store\StoreRepository;
use Doctrine\ORM\EntityManager;
use App\Command\Foursquare\Data;
use App\Entity;
use App\Service\GoogleGeocoder;
use \GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use Jcroll\FoursquareApiClient\Client\FoursquareClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected static $output;

    protected $googleGeocoder;

    /**
     * ParseCommand constructor.
     * @param GoogleGeocoder $googleGeocoder
     */
    public function __construct(GoogleGeocoder $googleGeocoder)
    {
        $this->googleGeocoder = $googleGeocoder;

        // you *must* call the parent constructor
        parent::__construct();
    }

    /**
     * @param string $line
     */
    protected static function writeln($line)
    {
        if (self::$output) {
            self::$output->writeln($line);
        }
    }

    /**
     * @param string $str
     *
     * @return array
     */
    protected static function convertStringToCoordinatesPair($str)
    {
        if (preg_match('/(\-?\d+(?:\.\d+)?),(\-?\d+(?:\.\d+)?)/', $str, $coordinates)) {
            return [floatval($coordinates[1]), floatval($coordinates[2])];
        } else {
            return [0, 0];
        }
    }

    /**
     * @param array $coordinates
     *
     * @return Data\Coordinates
     */
    protected static function getCoordinatesFromArray($coordinates)
    {
        return new Data\Coordinates($coordinates[0], $coordinates[1]);
    }

    /**
     * @param string $string
     *
     * @return Data\Coordinates
     */
    protected static function getCoordinatesFromString($string)
    {
        return self::getCoordinatesFromArray(self::convertStringToCoordinatesPair($string));
    }

    /**
     * @param InputInterface $input
     *
     * @return Data\Bounds
     */
    protected static function getBoundsFromInput(InputInterface $input)
    {
        return new Data\Bounds(
            self::getCoordinatesFromString($input->getArgument('north-east')),
            self::getCoordinatesFromString($input->getArgument('south-west'))
        );
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected static function getQueryFromInput(InputInterface $input)
    {
        return $input->getOption('query');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected static function getCategoriesFromInput(InputInterface $input)
    {
        return implode(',', $input->getOption('categories'));
    }

    /**
     * @return \Guzzle\Service\Client|FoursquareClient
     */
    protected static function getFoursquareClient()
    {
        return FoursquareClient::factory([
            'client_id' => getenv('FOURSQUARE_CLIENT_ID'),
            'client_secret' => getenv('FOURSQUARE_CLIENT_SECRET')
        ]);
    }

    /**
     * @param Data\Bounds $bounds
     * @param string $query
     * @param string $categories
     *
     * @param $client
     * @return CommandInterface
     */
    protected static function getSearchCommand(Data\Bounds $bounds, $query, $categories, GuzzleClient $client)
    {
        return $client->getCommand(
            'venues/search',
            [
                'ne' => $bounds->northEast->latitude . ',' . $bounds->northEast->longitude,
                'sw' => $bounds->southWest->latitude . ',' . $bounds->southWest->longitude,
                'intent' => 'browse',
                'query' => $query,
                'categoryId' => $categories,
                'limit' => 50
            ]
        );
    }

    /**
     * @param CommandInterface $command
     *
     * @param GuzzleClient $client
     * @return array
     */
    protected static function executeCommand(CommandInterface $command, GuzzleClient $client)
    {
        try {
            return $client->execute($command);
        } catch (\Exception $ex) {
            self::writeln(
                "Failure while executing command: "
                . $ex->getMessage()
            );

            self::writeln("Waiting for 1 second...");
            sleep(1);
            return self::executeCommand($command, $client);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected static function fetchResponseData($data)
    {
        return !empty($data) && isset($data['response'])
            ? $data['response']
            : [];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected static function fetchVenuesData($data)
    {
        return is_array($data) && isset($data['venues'])
            ? $data['venues']
            : [];
    }

    /**
     * @param array $elements
     *
     * @return Data\Venue[]
     */
    protected static function convertArrayToVenues($elements)
    {
        return !is_array($elements) || count($elements) == 0
            ? []
            : array_map(function ($element) {
                return new Data\Venue($element);
            }, $elements);
    }

    /**
     * @param float $from
     * @param float $to
     *
     * @return float
     */
    protected static function calculateDistance($from, $to)
    {
        return $to - $from;
    }

    /**
     * @param float $distance
     *
     * @return float
     */
    protected static function normalizeLatitudeDistance($distance)
    {
        return $distance > 90 ? $distance - 180 : $distance;
    }

    /**
     * @param float $distance
     *
     * @return float
     */
    protected static function normalizeLongitudeDistance($distance)
    {
        return $distance > 180 ? $distance - 360 : $distance;
    }

    /**
     * @param float $from
     * @param float $to
     *
     * @return float
     */
    protected static function calculateLatitudeDistance($from, $to)
    {
        return self::normalizeLatitudeDistance(self::calculateDistance($from, $to));
    }

    /**
     * @param float $from
     * @param float $to
     *
     * @return float
     */
    protected static function calculateLongitudeDistance($from, $to)
    {
        return self::normalizeLongitudeDistance(self::calculateDistance($from, $to));
    }

    /**
     * @param Data\Coordinates $northEast
     * @param Data\Coordinates $southWest
     *
     * @return Data\Coordinates
     */
    protected static function getMiddleCoordinates(Data\Coordinates $northEast, Data\Coordinates $southWest)
    {
        return new Data\Coordinates(
            $northEast->latitude + self::calculateLatitudeDistance($northEast->latitude, $southWest->latitude) / 2,
            $northEast->longitude + self::calculateLongitudeDistance($northEast->longitude, $southWest->longitude) / 2
        );
    }

    /**
     * @param Data\Bounds $bounds
     *
     * @return Data\Bounds[]
     */
    protected static function splitBounds(Data\Bounds $bounds)
    {
        $middleCoordinates = self::getMiddleCoordinates($bounds->northEast, $bounds->southWest);

        return [
            new Data\Bounds(
                new Data\Coordinates($bounds->northEast->latitude, $bounds->northEast->longitude),
                new Data\Coordinates($middleCoordinates->latitude, $middleCoordinates->longitude)
            ),
            new Data\Bounds(
                new Data\Coordinates($bounds->northEast->latitude, $middleCoordinates->longitude),
                new Data\Coordinates($middleCoordinates->latitude, $bounds->southWest->longitude)
            ),
            new Data\Bounds(
                new Data\Coordinates($middleCoordinates->latitude, $bounds->northEast->longitude),
                new Data\Coordinates($bounds->southWest->latitude, $middleCoordinates->longitude)
            ),
            new Data\Bounds(
                new Data\Coordinates($middleCoordinates->latitude, $middleCoordinates->longitude),
                new Data\Coordinates($bounds->southWest->latitude, $bounds->southWest->longitude)
            )
        ];
    }

    /**
     * @param Data\Bounds $bounds
     * @param $query
     * @param $categories
     *
     * @return Data\Venue[]
     */
    protected static function scanBounds(Data\Bounds $bounds, $query, $categories)
    {
        $client = self::getFoursquareClient();

        $venues = self::convertArrayToVenues(
            self::fetchVenuesData(
                self::fetchResponseData(
                    self::executeCommand(
                        self::getSearchCommand($bounds, $query, $categories, $client),
                        $client
                    )
                )
            )
        );

        self::writeln(
            "Bounds [ne: {$bounds->northEast->latitude},{$bounds->northEast->longitude} " .
            "sw: {$bounds->southWest->latitude},{$bounds->southWest->longitude}] = " . count($venues) . " venues"
        );

        return count($venues) == 50
            ? array_merge($venues, self::getVenues(self::splitBounds($bounds), $query, $categories))
            : $venues;
    }

    /**
     * @param Data\Bounds[] $bounds
     * @param string $query
     * @param string $categories
     *
     * @return Data\Venue[]
     */
    protected static function getVenues($bounds, $query, $categories)
    {
        return is_array($bounds)
            ? array_reduce($bounds, function ($carry, Data\Bounds $bounds) use ($query, $categories) {
                return array_merge($carry, self::scanBounds($bounds, $query, $categories));
            }, [])
            : [];
    }

    /**
     * @deprecated
     *
     * @param Data\Venue[] $venues
     *
     * @return Data\Venue[]
     */
    protected static function filterFromDuplicates($venues)
    {
        return array_reduce(
            $venues,
            function ($carry, Data\Venue $newVenue) {
                $found = false;
                array_walk(
                    $carry,
                    function (Data\Venue $filteredVenue) use ($newVenue, &$found) {
                        $found = $found || $filteredVenue->id === $newVenue->id;
                    }
                );

                if (!$found) {
                    $carry[] = $newVenue;
                }

                return $carry;
            },
            []
        );
    }

    /**
     * @param Data\Venue[] $venues
     */
    protected function cacheStoresData($venues)
    {
        global $kernel;

        /** @var EntityManager $entityManager */
        /** @var \Doctrine\ORM\EntityRepository $storeOrmRepository */
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $storeOrmRepository = $entityManager->getRepository(Store::class);
        $storeRepository = new StoreRepository($entityManager);

        foreach ($venues as $venue) {

            $storeName = self::substringName($venue->name);

            try {
                $store = $storeOrmRepository->findOneBy([
                    'name' => $storeName,
                    'latitude' => $venue->location->lat,
                    'longitude' => $venue->location->lng,
                ]);
            } catch (\Exception $exc) {
                continue;
            }

            if (!$store) {
                if (self::isValidFoursquareAddress($venue->location)) {
                    $storeRepository->add(
                        self::createFromFoursquareLocationData($storeName, $venue->location, $storeRepository)
                    );
                } else {
                    $address = $this->googleGeocoder->getFirstAddressByCoordinates(
                        $venue->location->lat,
                        $venue->location->lng
                    );

                    try {
                        $storeRepository->add(
                            self::createFromGoogleLocationData($storeName, $address, $storeRepository)
                        );
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            $entityManager->flush();

            self::writeln(
                $store !== null
                    ? "Venue #{$venue->id} is already in stores #{$store->getId()}"
                    : "Venue #{$venue->id} was added into stores"
            );

            unset($store);
        }
    }


    /**
     * @param $storeName
     * @param Data\Venue\Location $data
     * @param $storeRepository
     * @return Store
     * @internal param Store $store
     */
    public static function createFromFoursquareLocationData(
        string $storeName,
        Data\Venue\Location $data,
        StoreRepository $storeRepository
    ) {
        $store = new Store(
            $storeRepository->generateStoreId(),
            $storeName,
            $data->address,
            $data->city,
            $data->state,
            $data->postalCode,
            $data->lat,
            $data->lng,
            null
        );

        return $store;
    }

    /**
     * @param $storeName
     * @param array $data
     * @param StoreRepository $storeRepository
     * @return Store
     * @internal param Store $store
     */
    public static function createFromGoogleLocationData(
        string $storeName,
        $data,
        StoreRepository $storeRepository
    ) {
        $store = new PhysicalStore(
            $storeRepository->generateStoreId(),
            $data['name'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['latitude'],
            $data['longitude'],
            null
        );

        return $store;
    }

    /**
     * @param $name
     * @return string
     */
    public static function substringName($name)
    {
        return mb_strlen($name, 'utf8') > 60 ? mb_substr($name, 0, 59, 'utf8') . '.' : $name;
    }

    /**
     * @param Data\Venue\Location $data
     * @return bool
     */
    private static function isValidFoursquareAddress(Data\Venue\Location $data)
    {
        return $data->address && $data->city && $data->state && $data->postalCode;
    }

    protected function configure()
    {
        $this
            ->setName('foursquare:search')
            ->addArgument('north-east', InputArgument::REQUIRED, 'North-east point of bounds')
            ->addArgument('south-west', InputArgument::REQUIRED, 'South-west point of bounds')
            ->addOption(
                'categories',
                'c',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'List of categories to be filtered by',
                []
            )
            ->addOption(
                'query',
                null,
                InputOption::VALUE_OPTIONAL,
                'A search term to be applied against venue names.',
                ''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$output = $output;

        $venues = self::getVenues(
            [self::getBoundsFromInput($input)],
            self::getQueryFromInput($input),
            self::getCategoriesFromInput($input)
        );

        $this->saveToDatabase($venues);
    }
}
