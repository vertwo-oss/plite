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
use vertwo\plite\Web\Web;
use vertwo\plite\Web\WebUser;
use function vertwo\plite\clog;



class FlatFileAuthModule
{
    const SESSION_KEY_PREFIX = "_plite_user_";
    const SESSION_KEY_ID     = self::SESSION_KEY_PREFIX . "id";
    
    
    /** @var FileProvider */
    private $fp;
    
    
    public static function isSessionValid ()
    {
        return array_key_exists(self::SESSION_KEY_ID, $_SESSION);
    }
    
    
    public static function getID ()
    {
        return $_SESSION[self::SESSION_KEY_ID];
    }
    
    
    /**
     * Initializes bucket containing account info (JS files).
     *
     * @param     $moduleName
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
            clog("user files", $files);
        }
        catch ( Exception $e )
        {
            clog($e);
            throw $e;
        }
        
        foreach ( $files as $file )
        {
            $data = $this->fp->read($file);
            $js   = FJ::js($data);
            
            clog("users ANTE", $users);
            
            $users = array_merge($users, $js);
            
            clog("users POST", $users);
        }
        
        clog("known users", $users);
        
        return $users;
    }
    
    
    /**
     * Authenticates the user with a passowrd.
     *
     * @param $id
     * @param $password
     *
     * @return WebUser
     * @throws Exception
     */
    public function authenticate ( $id, $password )
    {
        $user     = $this->r($id);
        $passhash = $user->get("passhash");
        
        if ( !password_verify($password, $passhash) )
            throw new Exception("Login/Password incorrect.");
        
        ////////////////////////////////////////////////////////////////
        //
        //
        // DANGER
        // WARN
        // MEAT
        // NOTE - At this point, the user is authenticated.
        //        Do session-management here.
        //
        // MEAT --==> Session Management Entry Point <==--
        //
        //
        ////////////////////////////////////////////////////////////////
        
        @Web::nukeSession(); // NOTE - there might not be a session yet, so ignore warning.
        
        session_start();
        session_regenerate_id(true);
        
        $sessionData = $user->getWebSessionData();
        foreach ( $sessionData as $k => $v )
        {
            $sessionKey            = self::SESSION_KEY_PREFIX . $k;
            $_SESSION[$sessionKey] = $v;
        }
        
        return $user;
    }
    
    
    public static function logout ()
    {
        $prelen = strlen(self::SESSION_KEY_PREFIX);
        
        $info = [];
        
        ////////////////////////////////////////////////////////////////
        //
        //
        // DANGER
        // WARN
        // MEAT
        // NOTE - At this point, destroy the session user data.
        //        Do session-management here.
        //
        // MEAT --==> Session Management Entry Point <==--
        //
        //
        ////////////////////////////////////////////////////////////////
        
        Web::nukeSession();
        
        $user = new WebUser($info);
        return $user;
    }
    
    
    /**
     * (C) Create a new user with password.
     *
     * @param            $newUserID
     * @param            $password
     *
     * @return WebUser
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
        
        $user = new WebUser($newUserInfo);
        
        $existing = $this->readAllUserFiles();
        
        if ( array_key_exists($newUserID, $existing) )
            throw new Exception("User [$newUserID] already exists.");
        
        $newUserEntry = [
          $newUserID => $newUserInfo,
        ];
        
        $newdata = FJ::js($newUserEntry) . "\n";
        $newfile = $totime . ".js";
        
        $this->fp->write($newfile, $newdata);
        
        return $user;
    }
    
    
    /**
     * (R) Retrieves an existing user's info.
     *
     * @param $id
     *
     * @return WebUser
     * @throws Exception
     */
    public function r ( $id )
    {
        $existing = $this->readAllUserFiles();
        
        if ( !array_key_exists($id, $existing) )
            throw new Exception("User [$id] doesn't exist.");
        
        return new WebUser($existing[$id]);
    }
    
    
    /**
     * (U) Updates a user's info.
     *
     * @param $id
     * @param $info
     *
     * @return WebUser
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
                $user    = new WebUser($js);
                
                //
                // TODO - See if a "password" is being updated!
                // FIXME
                // DANGER
                //
                
                $data = FJ::js($js) . "\n";
                $this->fp->write($file, $data);
                
                return $user;
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
