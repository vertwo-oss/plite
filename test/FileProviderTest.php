<?php



use PHPUnit\Framework\TestCase;
use vertwo\plite\ConfigInterface;
use vertwo\plite\Provider\FileProviderFactory;
use function vertwo\plite\clog;



class TestConfig implements ConfigInterface
{
    function getConfig ()
    {
        return [
            "aws_region"  => "us-east-2",
            "aws_version" => "latest",

            "file_provider_local_root_prefix" => "/Users/srv/",
            "file_provider_local_root_suffix" => "/data",

            "file_provider" => "local",
        ];
    }
}



class FileProviderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEnv ()
    {
        clog("_SERVER", $_SERVER);

        $fileProv = FileProviderFactory::getProvider();

        $fileProv->init([ "bucket" => "test-bucket" ]);
        $entries = $fileProv->ls();
        $dirs    = $fileProv->lsDirs();

        clog("entries", $entries);
        clog("dirs", $dirs);
    }
}
