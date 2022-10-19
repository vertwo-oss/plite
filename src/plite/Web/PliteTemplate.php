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



namespace vertwo\plite\Web;



use Exception;
use vertwo\plite\Config;
use function vertwo\plite\clog;
use function vertwo\plite\yelulog;



class PliteTemplate
{
    const DEBUG_INIT = false;



    static $string_html_TITLE;
    static $string_APP_NAME;
    static $html_elem_LOGO;
    static $string_REG_EMAIL;
    static $css_value_PADDING_TOP_LOGO;
    static $css_value_BACKGROUND;
    static $string_LONG_COPYRIGHT;

    static $IS_USING_POWERED_BY_V2;



    public static function init ()
    {
        self::$string_html_TITLE          = "Unknown App";
        self::$string_APP_NAME            = "Unknown App";
        self::$html_elem_LOGO             = "<img src=\"res/question.png\" alt=\"unknown app\"/>";
        self::$string_REG_EMAIL           = "interest@example.com";
        self::$css_value_PADDING_TOP_LOGO = "0";
        self::$css_value_BACKGROUND       = "#222";
        self::$string_LONG_COPYRIGHT      = "Copyleft";
        self::$IS_USING_POWERED_BY_V2     = false;

        try
        {
            Config::init(); // This isn't strictly necessary, but is hygienic.
        }
        catch ( Exception $e )
        {
            clog($e);
            yelulog("Could not initialize Config; using DEFAULT values.");

        }

        self::set(self::$string_html_TITLE, "wl_title");
        self::set(self::$string_APP_NAME, "wl_name");
        self::set(self::$html_elem_LOGO, "wl_logo");
        self::set(self::$string_REG_EMAIL, "wl_reg_email");
        self::set(self::$css_value_PADDING_TOP_LOGO, "wl_logo_padding_top");
        self::set(self::$css_value_BACKGROUND, "wl_bg");
        self::set(self::$string_LONG_COPYRIGHT, "wl_copyright_notice");
        self::set(self::$IS_USING_POWERED_BY_V2, "wl_using_powered_by_v2");

        if ( self::DEBUG_INIT ) clog("white-label title", self::$string_html_TITLE);
        if ( self::DEBUG_INIT ) clog("white-label name", self::$string_APP_NAME);
        if ( self::DEBUG_INIT ) clog("white-label logo", self::$html_elem_LOGO);
        if ( self::DEBUG_INIT ) clog("white-label bg", self::$css_value_BACKGROUND);
        if ( self::DEBUG_INIT ) clog("white-label reg-email", self::$string_REG_EMAIL);
        if ( self::DEBUG_INIT ) clog("white-label copyright", self::$string_LONG_COPYRIGHT);
        if ( self::DEBUG_INIT ) clog("white-label use_pbv2", self::$IS_USING_POWERED_BY_V2);
    }



    private static function set ( &$var, $key )
    {
        try
        {
            $val = Config::get($key);
            if ( null !== $val ) $var = $val;
        }
        catch ( Exception $e )
        {
        }
    }



    public static function getSolidFooterContents ()
    {
        $copyright = self::$string_LONG_COPYRIGHT;
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
