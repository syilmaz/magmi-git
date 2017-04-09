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

}