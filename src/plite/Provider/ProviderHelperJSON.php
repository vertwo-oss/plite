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



namespace vertwo\plite\Provider;



use vertwo\plite\Ball;
use vertwo\plite\FJ;
use vertwo\plite\Provider\Exception\ProviderMergeException;



class ProviderHelperJSON
{
    /**
     * Merge (i.e., update-new-values) single datum.
     *
     * Merge differs from update in that the old record is not simple REPLACED.
     *
     * NOTE - Using Ball::merge, update fields in existing record where specified;
     *        leave existing fields alone (do not delete).
     *
     * If editing the datum causes the ID to change, caller of edit() must
     * provide new ID.  By default, edit() assumes ID does not change, and
     * simply edits the record.  If 'newID' is provided, then the old record
     * is deleted, and a new record created.
     *
     * @param array       $origData     - JSON object [ id => [record] ]
     * @param string      $existingID   - Existing ID
     * @param Ball        $newBall      - New data.
     * @param string      $newBallDelim - Delimiter for data.
     * @param string|bool $newID        - New ID (if editing changes ID)
     *
     * @return array - JSON object, MERGED [ id => [record] ]
     *
     * @throws ProviderMergeException
     */
    public static function merge ( $origData, $existingID, $newBall, $newBallDelim = Ball::DEFAULT_DELIM, $newID = false )
    {
        if ( !array_key_exists($existingID, $origData) ) throw new ProviderMergeException();

        $mergedData = FJ::deepCopy($origData);

        //
        // Gather existing record, and merge with new record.
        //
        $existing   = $mergedData[$existingID];
        $mergedBall = new Ball($existing, $newBallDelim);

        $mergedBall->mergeBall($newBall);
        $datum = $mergedBall->data();

        //
        // NOTE - On the off chance that editing changes the ID,
        //  1. Remove old ID.
        //  2. Create object with new ID.
        //

        if ( $newID !== false ) // Unset old ID, and add new ID.
        {
            unset($mergedData[$existingID]);
            $mergedData[$newID] = $datum;
        }

        else // Otherwise, just replace old ID.
        {
            $mergedData[$existingID] = $datum;
        }

        //$json = FJ::jsPrettyEncode($mergedData);
        //$this->saveData($json);
        //return $mergedBall;

        return $mergedData;
    }
}
