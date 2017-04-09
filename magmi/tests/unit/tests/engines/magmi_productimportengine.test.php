<?php
require_once(__DIR__ . "/../../../../inc/magmi_defs.php");
require_once(__DIR__ . "/../../../../inc/magmi_statemanager.php");

require_once MAGMI_INCDIR . '/magmi_config.php';
require_once MAGMI_PLUGIN_DIR . '/inc/magmi_item_processor.php';
require_once MAGMI_ENGINE_DIR . '/magmi_productimportengine.php';

class MagmiProductImportEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetBuiltinPluginClassesOnConstruct()
    {
        $object = new Magmi_ProductImportEngine();

        $this->assertEquals(
            array(
                "itemprocessors" => array(
                    "Magmi_DefaultAttributeItemProcessor"
                )
            ),
            $object->getBuiltinPluginClasses()
        );
    }

    public function testGetEngineInfo()
    {
        $object = new Magmi_ProductImportEngine();
        $this->assertEquals(
            array("name" => "Magmi Product Import Engine", "version" => "1.10", "author" => "dweeves"),
            $object->getEngineInfo()
        );
    }

    public function testGetPluginFamilies()
    {
        $object = new Magmi_ProductImportEngine();
        $this->assertEquals(
            array("datasources", "general", "itemprocessors"),
            $object->getPluginFamilies()
        );
    }

    public function testRegisterAttributeHandler()
    {
        /** @var Magmi_ProductImportEngine|PHPUnit_Framework_MockObject_MockObject $object */
        $object = $this->getMockBuilder(Magmi_ProductImportEngine::class)
            ->setMethods(array('log'))
            ->getMock();

        $object->expects($this->once())
            ->method('log')
            ->with('Invalid registration string (test_input) :MagmiProductImportEngineTest')
            ->willReturnSelf();

        $object->registerAttributeHandler($this, array( "test_input" ));

    }

    public function testRegisterAttributeHandlerShouldSucceed()
    {
        /** @var Magmi_ProductImportEngine|PHPUnit_Framework_MockObject_MockObject $object */
        $object = $this->getMockBuilder(Magmi_ProductImportEngine::class)
            ->setMethods(array('log'))
            ->getMock();

        $object->expects($this->never())
            ->method('log')
            ->willReturnSelf();

        $object->registerAttributeHandler($this, array( "test_input:(test|something)" ));
    }
}