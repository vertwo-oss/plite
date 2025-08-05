<?php
/**
 * Copyright (c) 2012-2025 Troy Wu
 * Copyright (c) 2021      Version2 OÜ
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



use vertwo\plite\Log;
use vertwo\plite\CommandLine\ConsoleLog;
use vertwo\plite\Web\WebLog;



function red ( $s ) { return Log::TEXT_COLOR_RED . $s . Log::TEXT_COLOR_SUFFIX; }
function yel ( $s ) { return Log::TEXT_COLOR_YELLOW . $s . Log::TEXT_COLOR_SUFFIX; }
function grn ( $s ) { return Log::TEXT_COLOR_GREEN . $s . Log::TEXT_COLOR_SUFFIX; }
function cyn ( $s ) { return Log::TEXT_COLOR_CYAN . $s . Log::TEXT_COLOR_SUFFIX; }
function wht ( $s ) { return Log::TEXT_COLOR_WHITE . $s . Log::TEXT_COLOR_SUFFIX; }


function isCLI () { return !isset($_SERVER["SERVER_PORT"]) && (php_sapi_name() === 'cli'); }
function isWeb () { return !isCLI(); }


if ( isCLI() )
{
    function clog ()
    {
        switch ( func_num_args() )
        {
            case 2:
                ConsoleLog::log(func_get_arg(0), func_get_arg(1));
                break;
            
            default:
                ConsoleLog::log(func_get_arg(0));
                break;
        }
    }
}
else
{
    function clog ()
    {
        switch ( func_num_args() )
        {
            case 2:
                WebLog::log(func_get_arg(0), func_get_arg(1));
                break;
            
            default:
                WebLog::log(func_get_arg(0));
                break;
        }
    }
}
