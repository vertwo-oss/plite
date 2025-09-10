<?php
/*
 * Copyright (c) 2012-2025 Troy Wu
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
use vertwo\plite\Config;
use vertwo\plite\Provider\FileProvider;
use vertwo\plite\ConfigClass;
use vertwo\plite\Provider\FileProviderFactory;



class TestConfig implements ConfigClass
{
    function getConfig ()
    {
        return [
          Config::ENV_PLITE_APP_KEY        => "plite",
          Config::ENV_PLITE_LOCAL_ROOT_KEY => "/Users/srv",
          
          "xyz"          => "file",
          "xyz_provider" => "local",
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
        print_r($_SERVER);
        
        /** @var FileProvider $fp */
        $fp = FileProviderFactory::getProvider("xyz");
        
        $fp->init(["bucket" => "test-bucket"]);
        
        $entries = $fp->ls();
        $dirs    = $fp->lsDirs();
        
        print_r($entries);
        
        print_r($dirs);
    }
}
