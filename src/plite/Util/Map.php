<?php



namespace vertwo\plite\Util;



use Countable;
use Iterator;
use vertwo\plite\FJ;
use function vertwo\plite\clog;



class Map implements MapInterface, Iterator, Countable
{
    protected $ar = [];
    
    
    public function __construct ( $ar )
    {
        $this->ar = FJ::deepCopy($ar);
    }
    
    
    public function array () { return FJ::deepCopy($this->ar); }
    
    
    public function has ( $key )
    {
        return array_key_exists($key, $this->ar);
    }
    public function no ( $key ) { return !$this->has($key); }
    
    
    public function get ( $key )
    {
        return $this->has($key) ? $this->ar[$key] : null;
    }
    
    
    public function keys () { return array_keys($this->ar); }
    
    
    public function getWithPrefix ( $prefix )
    {
        $keys   = array_keys($this->ar);
        $prelen = strlen($prefix);
        $submap = [];
        
        foreach ( $keys as $key )
        {
            if ( 0 == strncasecmp($prefix, $key, $prelen) )
            {
                $submap[$key] = $this->ar[$key];
            }
        }
        
        return new Map($submap);
    }
    
    
    public function matches ( $key, $targetValue )
    {
        return $this->has($key) && $targetValue === $this->get($key);
    }
    
    
    
    
    private $cur = 0;
    
    public function current ()
    {
        return $this->ar[$this->key()];
    }
    public function next ()
    {
        ++$this->cur;
    }
    public function key ()
    {
        $keys = array_keys($this->ar);
        return $keys[$this->cur];
    }
    public function valid ()
    {
        return $this->cur < $this->count();
    }
    public function rewind ()
    {
        $cur = 0;
    }
    public function count ()
    {
        return count($this->ar);
    }
}
