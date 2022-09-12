<?php



namespace vertwo\plite\Provider;



interface ProviderFactory
{
    /**
     * @return string
     */
    static function getProviderType ();



    /**
     * @return mixed
     */
    static function getProvider ();
}
