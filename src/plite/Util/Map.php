<?php



namespace vertwo\plite\Util;



use vertwo\plite\FJ;



class Map implements MapInterface
{
    protected $ar = [];
    
    
    public function __construct ( $ar )
    {
        $this->ar = FJ::deepCopy($ar);
    }
    
    
    public function dump ( $mesg = false )
    {
        clog($mesg, $this->ar);
    }
    
    
    public function has ( $key )
    {
        return array_key_exists($key, $this->ar);
    }
    public function no ( $key ) { return !$this->has($key); }
    
    
    public function get ( $key )
    {
        return $this->has($key) ? $this->ar[$key] : null;
    }
    
    
    public function getWithPrefix ( $prefix )
    {
        $keys   = array_keys($this->ar);
        $prelen = strlen($prefix);
        $submap = [];
        
        foreach ( $keys as $key )
        {
            if ( 0 == strncasecmp($prefix, $key, $prelen) )
            {
                $submap[$key] = self::$PARAMS[$key];
            }
        }
        
        return new Map($submap);
    }
    
    
    public function matches ( $key, $targetValue )
    {
        return $this->has($key) && $targetValue === $this->get($key);
    }
}
