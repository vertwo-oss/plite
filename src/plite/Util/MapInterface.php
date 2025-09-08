<?php



namespace vertwo\plite\Util;



interface MapInterface
{
    public function __construct ( $ar );
    public function dump ( $mesg = false );
    public function has ( $key );
    public function no ( $key );
    public function get ( $key );
    public function getWithPrefix ( $prefix );
    public function matches ( $key, $targetValue );
}
