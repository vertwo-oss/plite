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



class PliteTemplate
{
    const DEBUG_INIT = true;
    
    
    
    static $TITLE;
    static $APPNAME;
    static $LOGO;
    static $REG_EMAIL;
    static $TOP_PAD;
    static $BGCOLOR;
    static $COPYRIGHT;
    
    static $IS_USING_POWERED_BY_V2;
    
    
    
    /**
     * @return void
     * @throws Exception
     */
    public static function init ()
    {
        try
        {
            Config::init(); // This isn't strictly necessary, but is hygienic.
            
            self::$TITLE                  = Config::get("wl_title");
            self::$APPNAME                = Config::get("wl_name");
            self::$LOGO                   = Config::get("wl_logo");
            self::$REG_EMAIL              = Config::get("wl_reg_email");
            self::$TOP_PAD                = Config::get("wl_logo_padding_top");
            self::$BGCOLOR                = Config::get("wl_bg");
            self::$COPYRIGHT              = Config::get("wl_copyright_notice");
            self::$IS_USING_POWERED_BY_V2 = Config::get("wl_using_powered_by_v2");
        }
        catch ( Exception $e )
        {
            clog($e);
            clog(yel("Could not instantiate PliteTemplate; using DEFAULT values."));
            
            self::$TITLE                  = "Unknown App";
            self::$APPNAME                = "Unknown App";
            self::$LOGO                   = "<img src=\"res/question.png\" alt=\"unknown app\"/>";
            self::$REG_EMAIL              = "interest@example.com";
            self::$TOP_PAD                = "0";
            self::$BGCOLOR                = "#333";
            self::$COPYRIGHT              = "Copyleft";
            self::$IS_USING_POWERED_BY_V2 = false;
            
            throw $e;
        }
        finally
        {
            if ( self::DEBUG_INIT ) clog(self::getMap());
        }
    }
    
    
    
    public static function getMap ()
    {
        return [
          "title"     => self::$TITLE,
          "name"      => self::$APPNAME,
          "logo"      => self::$LOGO,
          "top-pad"   => self::$TOP_PAD,
          "bg"        => self::$BGCOLOR,
          "reg-email" => self::$REG_EMAIL,
          "copyright" => self::$COPYRIGHT,
          "use_pbv2"  => self::$IS_USING_POWERED_BY_V2,
        ];
    }
    
    
    public static function getSolidFooterContents ()
    {
        $copyright = self::$COPYRIGHT;
        $pby       = self::$IS_USING_POWERED_BY_V2
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
