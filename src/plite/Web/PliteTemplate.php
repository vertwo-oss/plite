<?php



namespace vertwo\plite\Web;



use Exception;
use vertwo\plite\Provider\PliteFactory;
use vertwo\plite\Provider\ProviderFactory;
use function vertwo\plite\clog;
use function vertwo\plite\yelulog;



class PliteTemplate
{
    const DEBUG_INIT = true;



    static $string_html_TITLE;
    static $string_APP_NAME;
    static $html_elem_LOGO;
    static $css_value_PADDING_TOP_LOGO;
    static $css_value_BACKGROUND;
    static $string_LONG_COPYRIGHT;

    static $IS_USING_POWERED_BY_V2;



    public static function init ()
    {
        try
        {
            $pf = PliteFactory::loadFactory();

            self::$string_html_TITLE          = $pf->get("wl_title");
            self::$string_APP_NAME            = $pf->get("wl_name");
            self::$html_elem_LOGO             = $pf->get("wl_logo");
            self::$css_value_PADDING_TOP_LOGO = $pf->get("wl_logo_padding_top");
            self::$css_value_BACKGROUND       = $pf->get("wl_bg");
            self::$string_LONG_COPYRIGHT      = $pf->get("wl_copyright_notice");
            self::$IS_USING_POWERED_BY_V2     = $pf->get("wl_using_powered_by_v2");
        }
        catch ( Exception $e )
        {
            yelulog("Could not instantiate PliteFactory; using DEFAULT values.");

            self::$string_html_TITLE          = "Unknown App";
            self::$string_APP_NAME            = "Unknown App";
            self::$html_elem_LOGO             = "<img src=\"res/question.png\" alt=\"unknown app\"/>";
            self::$css_value_PADDING_TOP_LOGO = "92px";
            self::$css_value_BACKGROUND       = "#222";
            self::$string_LONG_COPYRIGHT      = "Copyleft";
            self::$IS_USING_POWERED_BY_V2     = false;
        }

        if ( self::DEBUG_INIT ) clog("white-label title", self::$string_html_TITLE);
        if ( self::DEBUG_INIT ) clog("white-label name", self::$string_APP_NAME);
        if ( self::DEBUG_INIT ) clog("white-label logo", self::$html_elem_LOGO);
        if ( self::DEBUG_INIT ) clog("white-label bg", self::$css_value_BACKGROUND);
        if ( self::DEBUG_INIT ) clog("white-label copyright", self::$string_LONG_COPYRIGHT);
        if ( self::DEBUG_INIT ) clog("white-label use_pbv2", self::$IS_USING_POWERED_BY_V2);
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
