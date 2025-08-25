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



namespace vertwo\plite\Web;



use Exception;
use vertwo\plite\Config;
use vertwo\plite\FJ;



class PliteWebConfig
{
    const DEBUG_INIT = true;
    
    private static $map = [
      "title"     => null,
      "appname"   => null,
      "logo"      => null,
      "top_pad"   => null,
      "bg"        => null,
      "reg_email" => null,
      "copyright" => null,
      "use_pbv2"  => null,
    ];
    
    
    public static function has ( $key ) { return null !== self::$map["wl_" . $key]; }
    public static function get ( $key ) { return self::$map["wl_" . $key]; }
    public static function set ( $key, $val ) { self::$map["wl_" . $key] = $val; }
    
    
    /**
     * @return void
     * @throws Exception
     */
    public static function init ()
    {
        try
        {
            Config::init(); // This isn't strictly necessary, but is hygienic.
            
            foreach ( array_keys(self::$map) as $key )
            {
                $val = Config::get("wl_" . $key);
                self::set($key, $val);
            }
        }
        catch ( Exception $e )
        {
            clog($e);
            clog(yel("Could not instantiate PliteTemplate; using DEFAULT values."));
            
            $defaultConfigs = [
              "title"     => "Unknown App",
              "appname"   => "Unknown App",
              "logo"      => "<img src=\"res/question.png\" alt=\"unknown app\"/>",
              "reg_email" => "interest@example.com",
              "top_pad"   => "0",
              "bg"        => "#444",
              "copyright" => "Copyleft",
              "use_pbv2"  => false,
            ];
            
            foreach ( $defaultConfigs as $key => $val )
                self::set($key, $val);
            
            throw $e;
        }
        finally
        {
            if ( self::DEBUG_INIT ) clog(self::getMap());
        }
    }
    
    
    
    public static function getMap () { return FJ::deepCopy(self::$map); }
    
    
    public static function getSolidFooterContents ()
    {
        $copyright = self::get("copyright");
        $pby       = self::get("use_pbv2")
          ? '<p>Powered by <span class="v2">Version2</span></p>'
          : "";
        
        $footerContents = <<<EOF
    <div>
        $pby
    </div>
    <div>
        <p>$copyright</p>
    </div>

EOF;
        
        return $footerContents;
    }
}
