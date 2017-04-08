<?php
class ProductImportTest extends \PHPUnit\Framework\TestCase
{

    public function testGet()
    {

        echo getenv("MAGE2_FAKE_URL");

        $client = new GuzzleHttp\Client();
        echo $client->get(getenv("MAGE2_FAKE_URL"))
            ->getBody();
    }


}