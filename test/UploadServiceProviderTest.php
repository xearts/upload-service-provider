<?php
namespace Xearts\Provider\Upload;



use Pimple\Container;

class UploadServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testFileRegistered()
    {
        $_FILES['file'] = array('name' => '', 'error' => '', 'tmp_name' => '');
        $container = new Container();
        $container->register(new UploadServiceProvider(), array(
            'uploads.options' => array(
                'test' => array(
                    'paramname' => 'file',
                    'directory' => '/' . basename(__DIR__),
                ),
            ),
            'upload_dir_path' => dirname(__DIR__),
        ));

        $this->assertInstanceOf('Upload\File', $container['uploads']['test']);
    }
}
