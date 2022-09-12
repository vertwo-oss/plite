<?php
/**
 * Copyright (c) 2012-2022 Troy Wu
 * Copyright (c) 2021-2022 Version2 OÜ
 * All rights reserved.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */



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
