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



namespace vertwo\plite;



abstract class PLI extends CLI
{
    /**
     * This provides a list of the short options, in classic getopts() format.
     *
     * @return string - Classic getopt() spec (with colons); empty string ok.
     */
    abstract protected function getOtherShortOpts ();
    /**
     * This provides an array of the long options, in getopts() format.
     *
     * @return array - getopt() spec (with colons); empty array is ok.
     */
    abstract protected function getOtherLongOpts ();



    /**
     * This provides a list of the short options, in classic getopts() format.
     *
     * @return string - Classic getopt() spec (with colons); empty string ok.
     */
    final protected function getShortOpts ()
    {
        $pliShortOpts = "r:";
        return $this->getOtherShortOpts() . $pliShortOpts;
    }



    /**
     * This provides an array of the long options, in getopts() format.
     *
     *    "file_provider_local_root_prefix" : "/Users/srv/",
     * "file_provider_local_root_suffix" : "/data",
     *
     * @return array - getopt() spec (with colons); empty array is ok.
     */
    final protected function getLongOpts ()
    {
        $pliLongOpts = [
            self::req("--root-dir"),
        ];
        return array_merge($this->getOtherLongOpts(), $pliLongOpts);
    }
}
