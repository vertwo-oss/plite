<?php
/**
 * Copyright (c) 2012-2020 Troy Wu
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



namespace vertwo\plite\Util;



interface CSVisitor
{
    /**
     * Called when header line has been tokenized.
     *
     * Handle columns headers (column names).  This is called BEFORE parse().
     *
     * @param array $headers - Column headers.
     */
    function onHeaders ( $headers );



    /**
     * Called when a new line of text is encountered (before tokenization).
     *
     * @param int    $lineIndex - Index of line, inclusive of all lines.
     * @param string $line      - Raw line.
     */
    function onLine ( $lineIndex, $line );



    /**
     * MEAT - "Main" method, called for each CSV line tokenized.
     *
     * @param int      $lineIndex - Index of line, inclusive of all lines.
     * @param string[] $tokens    - Array of comma-separated tokens.
     */
    function onTokens ( $lineIndex, $tokens );



    /**
     * Called when CSV file is finished.
     *
     * @param $lineCount - Total number of lines, including possible-header line.
     */
    function onFinish ( $lineCount );

}
