<?php



namespace vertwo\plite\Provider;



final class PliteLocalConfig extends PliteConfig
{
    /**
     * @return array - Map of param keys to values.
     */
    final protected function loadInlineConfig () { return parent::getMap(); }
}
