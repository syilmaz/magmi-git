<?php
use Magento\Framework\App\Bootstrap;

class ProductImportTest extends \PHPUnit\Framework\TestCase
{
    private $storeUrl;
    private $storeAdminUsername;
    private $storeAdminPassword;

    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbName;

    private $magmiUrl;

    /** @var Bootstrap */
    private $magento2Bootstrap;

    /**
     * @before
     */
    public function setupConfig()
    {
        $this->storeUrl = getenv("MAGE2_FAKE_URL");
        $this->storeAdminUsername = getenv("MAGE2_ADMIN_USERNAME");
        $this->storeAdminPassword = getenv("MAGE2_ADMIN_PASSWORD");

        $this->dbHost = getenv("MAGE2_DB_HOST");
        $this->dbUser = getenv("MAGE2_DB_USER");
        $this->dbPass = getenv("MAGE2_DB_PASS") ?: '';
        $this->dbName = getenv("MAGE2_DB_NAME");

        $varsToCheck = array(
            'storeUrl',
            'storeAdminUsername',
            'storeAdminPassword',
            'dbHost',
            'dbUser',
            'dbName'
        );

        foreach ($varsToCheck as $varToCheck) {
            if (strlen($this->{$varToCheck}) == 0) {
                $this->fail($varToCheck . ' variable is not set.');
            }
        }

        $this->magmiUrl = $this->storeUrl . '/pub/magmi/magmi/web';
        $this->setupMagmiSettings();

        // Load Magento 2
        $params = $_SERVER;
        $bootstrap = Bootstrap::create(BP, $params);
        $obj = $bootstrap->getObjectManager();

        /** @var \Magento\Framework\App\State $state */
        $state = $obj->get('Magento\Framework\App\State');
        $state->setAreaCode('adminhtml');

        $this->magento2Bootstrap = $bootstrap;
    }

    /**
     * Import single product
     */
    public function testImportSingleProduct()
    {
        $logFile = 'progress_testImportSingleProduct_' . time() . '.txt';

        //sku,store,attribute_set,price,qty,name,visibility
        //testImportSingleProduct-1,admin,Default,3.99,5,"TestProduct",3
        $client = $this->setCsv('testImportSingleProduct1.csv');

        $response = $client->post($this->magmiUrl . '/magmi_run.php', array(
            'auth' => array( $this->storeAdminUsername, $this->storeAdminPassword ),
            'form_params' => array(
                'ts' => time(),
                'engine' => 'magmi_productimportengine:Magmi_ProductImportEngine',
                'run' => 'import',
                'logfile' => $logFile,
                'profile' => 'integration_tests',
                'mode' => 'create'
            )
        ));

        $this->assertEquals(200, $response->getStatusCode());

        $imported = $this->clientPollUntilDone($client, $logFile);
        $this->assertEquals(true, $imported);


        /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
        $productRepository = $this->magento2Bootstrap
            ->getObjectManager()
            ->get(\Magento\Catalog\Model\ProductRepository::class);

        $product = $productRepository->get('testImportSingleProduct-1');

        $this->assertNotNull($product);
        $this->assertEquals('testImportSingleProduct-1', $product->getSku());
        $this->assertEquals(3.99, $product->getPrice());
        $this->assertEquals("TestProduct", $product->getName());
        $this->assertEquals(3, $product->getVisibility());
    }

    /**
     * Polls until import is done
     * @param \GuzzleHttp\Client $client
     * @param $logFile
     * @return bool
     */
    private function clientPollUntilDone(\GuzzleHttp\Client $client, $logFile)
    {
        $this->printMessage('Polling status of the import');

        $maxAttempts = 5;
        $attempts = 1;
        $polling = true;
        $pollTimeOut = 5000;

        do {
            $response = $client->post($this->magmiUrl . '/magmi_progress.php', array(
                'auth' => array( $this->storeAdminUsername, $this->storeAdminPassword ),
                'form_params' => array(
                    'logfile' => $logFile
                )
            ));

            $contents = $response->getBody()->getContents();

            // Check if errors found is not present
            if (strpos($contents, 'error(s) found') !== false ) {
                $this->printMessage('Import resulted in an error');
                return false;
            }

            if (strpos($contents, 'setProgress(100)') === false) {
                $this->printMessage("Not done yet, waiting $pollTimeOut until trying again. Attempt $attempts of $maxAttempts");

                $attempts++;
                sleep(5);
            } else {
                $this->printMessage('Import status is 100%');
                return true;
            }
        } while ($polling && $attempts < $maxAttempts);

        return false;
    }

    /**
     * Updates the integration test profile and sets the CSV to this one
     * @param $fileName
     * @return \GuzzleHttp\Client
     */
    private function setCsv($fileName)
    {
        $client = new GuzzleHttp\Client(
            array('cookies' => true)
        );

        $response = $client->post($this->magmiUrl . '/magmi_saveprofile.php', array(
            'auth' => array( $this->storeAdminUsername, $this->storeAdminPassword ),
            'form_params' => array(
                'profile' => 'integration_tests',
                'PLUGINS_DATASOURCES:class' => 'Magmi_CSVDataSource',
                'CSV:importmode' => 'local',
                'CSV:basedir' => 'pub/magmi/magmi/tests/integration/data/',
                'CSV:filename' => realpath(dirname(__FILE__) . '/../data/' . $fileName),
                'CSV:separator' => ',',
                'CSV:enclosure' => '"'
            )
        ));

        if ($response->getStatusCode() !== 200) {
            $this->fail('Failed save profile to magmi, status code: ' . $response->getStatusCode());
        }

        return $client;
    }

    /**
     * Configure magmi
     */
    private function setupMagmiSettings()
    {
        $client = new GuzzleHttp\Client(
            array('cookies' => true)
        );

        $options =  array(
            'auth' => array( 'magmi', 'magmi' ),
            'form_params' => array(
                'DATABASE:connectivity' => 'net',
                'DATABASE:host' => $this->dbHost,
                'DATABASE:port' => '3306',
                'DATABASE:resource' => 'default_setup',
                'DATABASE:unix_socket' => '',
                'DATABASE:dbname' => $this->dbName,
                'DATABASE:user' => $this->dbUser,
                'DATABASE:password' => $this->dbPass,
                'MAGENTO:version' => '1.9.x',
                'MAGENTO:basedir' => '../../../../',
                'GLOBAL:step' => '0.5',
                'GLOBAL:multiselect_sep' => '',
                'GLOBAL:dirmask' => '755',
                'GLOBAL:filemask' => '644',
                'GLOBAL:noattsetupdate' => 'off'
            )
        );

        $statusCode = 0;

        try {
            $response = $client->post($this->magmiUrl . '/magmi_saveconfig.php', $options);
            $statusCode = $response->getStatusCode();
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            // It's possible that the database is already set, try to login with store username and password instead
            if ($e->getCode() === 401) {
                $this->printMessage("Failed to use magmi/magmi as username password. Trying store information");

                $options['auth'] = array($this->storeAdminUsername, $this->storeAdminPassword);
                $response = $client->post($this->magmiUrl . '/magmi_saveconfig.php', $options);
                $statusCode = $response->getStatusCode();
            } else {
                $this->assertNull($e);
            }
        }
        
        if ($statusCode !== 200) {
            $this->fail('Failed to save config to magmi, status code: ' . $statusCode);
        }

        $content = file_get_contents(__DIR__.'/../../../conf/magmi.ini');
        $this->assertNotNull($content);

        $this->printMessage('Stored Magmi settings');
        $this->printMessage($content);
    }

    /**
     * Immediately flush message
     * @param $message
     */
    private function printMessage($message) {
        echo $message . PHP_EOL;
        ob_flush();
        flush();
    }

}