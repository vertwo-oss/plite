<?php
/*
 * Copyright (c) 2025      Troy Wu
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



namespace vertwo\plite\Modules;



use Exception;
use vertwo\plite\FJ;
use vertwo\plite\Provider\FileProvider;
use vertwo\plite\Provider\FileProviderFactory;
use vertwo\plite\Util\PrecTime;
use function vertwo\plite\clog;



class FlatFileAuthModule
{
    /** @var FileProvider */
    private $fp;
    
    
    /**
     * Initializes bucket containing account info (JS files).
     *
     * @param $moduleName
     *
     * @throws Exception
     */
    public function __construct ( $moduleName )
    {
        $this->fp = FileProviderFactory::getProvider($moduleName);
        $this->fp->init(["bucket" => "accounts"]);
    }
    
    
    /**
     * Load all users from all JS files.
     *
     * @return array
     * @throws Exception
     */
    private function readAllUserFiles ()
    {
        $users = [];
        
        try
        {
            $files = $this->fp->lsFiles();
        }
        catch ( Exception $e )
        {
            clog($e);
            throw $e;
        }
        
        foreach ( $files as $file )
        {
            $data  = $this->fp->read($file);
            $js    = FJ::js($data);
            $users = array_merge($users, $js);
        }
        
        return $users;
    }
    
    
    /**
     * Authenticates the user with a passowrd.
     *
     * @param $id
     * @param $password
     *
     * @return bool
     * @throws Exception
     */
    public function authenticate ( $id, $password )
    {
        $info     = $this->r($id);
        $passhash = $info["passhash"];
        
        if ( !password_verify($password, $passhash) )
            throw new Exception("Login/Password incorrect.");
    }
    
    
    /**
     * (C) Create a new user with password.
     *
     * @param            $newUserID
     * @param            $password
     *
     * @return void
     * @throws Exception
     */
    public function c ( $newUserID, $password )
    {
        $totime = FJ::totime();
        
        $passhash  = password_hash($password, PASSWORD_BCRYPT);
        $now       = new PrecTime();
        $timestamp = $now->frac();
        
        $newUserInfo = [
          "id"       => $newUserID,
          "passhash" => $passhash,
          "ctime"    => $timestamp,
        ];
        
        $existing = $this->readAllUserFiles();
        
        if ( array_key_exists($newUserID, $existing) )
            throw new Exception("User [$newUserID] already exists.");
        
        $existing[$newUserID] = $newUserInfo;
        
        $data = FJ::js($existing) . "\n";
        $this->fp->write($totime . ".js", $data);
    }
    
    
    /**
     * (R) Retrieves an existing user's info.
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception
     */
    public function r ( $id )
    {
        $existing = $this->readAllUserFiles();
        
        if ( !array_key_exists($id, $existing) )
            throw new Exception("User [$id] doesn't exist.");
        
        return $existing[$id];
    }
    
    
    /**
     * (U) Updates a user's info.
     *
     * @param $id
     * @param $info
     *
     * @return mixed
     * @throws Exception
     */
    public function u ( $id, $info )
    {
        $files = $this->fp->lsFiles();
        foreach ( $files as $file )
        {
            $data = $this->fp->read($file);
            $js   = FJ::js($data);
            if ( array_key_exists($id, $js) )
            {
                $js[$id] = $info;
                
                //
                // TODO - See if a "password" is being updated!
                // FIXME
                // DANGER
                //
                
                $data = FJ::js($js) . "\n";
                $this->fp->write($file, $data);
                
                return $id;
            }
        }
        
        throw new Exception("User [$id] not found.");
    }
    
    
    /**
     * (D) Deletes a user.
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception
     */
    public function d ( $id )
    {
        $files = $this->fp->lsFiles();
        foreach ( $files as $file )
        {
            $data = $this->fp->read($file);
            $js   = FJ::js($data);
            if ( array_key_exists($id, $js) )
            {
                unset($js[$id]);
                
                $data = FJ::js($js) . "\n";
                $this->fp->write($file, $data);
                
                return $id;
            }
        }
        
        throw new Exception("User [$id] not found.");
    }
}
