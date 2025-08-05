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



namespace vertwo\plite\CommandLine;



use ErrorException;



abstract class CLI extends BoringCLI
{
    const DEBUG_ARGS  = false;
    const DEBUG_CLASS = false;
    
    
    /**
     * @throws ErrorException
     */
    public function failOnWarnings ()
    {
        set_error_handler(
          function ( $errNo, $errStr, $errFile, $errLine ) use ( &$previous ) {
              $msg = "$errStr in $errFile on line $errLine";
              if ( $errNo == E_NOTICE || $errNo == E_WARNING )
              {
                  throw new ErrorException($msg, $errNo);
              }
              else
              {
                  clog("warn|notice", $msg);
              }
          }
        );
    }
    
    
    public function dump ()
    {
        clog("argc: " . $this->argc, $this->argv);
        
        $shortOpts = $this->getShortOpts();
        $longOpts  = $this->getLongOpts();
        
        clog("short-opts", $shortOpts);
        clog(" long-opts", $longOpts);
        
        clog("options", $this->opts);
        
        clog("optind (remaining): $this->optind", $this->remaining);
    }
    
    
    public static function run ()
    {
        $class = get_called_class();
        if ( self::DEBUG_CLASS ) clog("get_called_class", $class);
        
        /** @var CLI $cli */
        $cli = new $class();
        $cli->main();
    }
}
