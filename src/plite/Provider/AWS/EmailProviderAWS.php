<?php
/*
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



namespace vertwo\plite\Provider\AWS;



use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use vertwo\plite\Provider\EmailProvider;
use function vertwo\plite\clog;
use function vertwo\plite\red;



class EmailProviderAWS implements EmailProvider
{
    const CHARSET = 'UTF-8';



    /** @var SesClient */
    private $ses;
    private $fromEmail;
    private $fromName;



    public function __construct ( $params )
    {
        $this->ses       = $params['ses'];
        $this->fromEmail = $params['from-email'];
        $this->fromName  = $params['from-name'];
    }



    public function init ()
    {
        // Nothing happens here.
    }



    public function sendEmail ( $params )
    {
        try
        {
            $this->sendEmailSES($params);
            return true;
        }
        catch ( AwsException $e )
        {
            clog($e);
            clog(red("Could not send email"));
        }

        return false;
    }



    /**
     * @param array $params - Map of email params
     *                      to-email,
     *                      to-name,
     *                      subject,
     *                      body
     *                      alt
     *
     * @return boolean - Was email sent?
     */
    private function sendEmailSES ( $params )
    {
        // TODO: Implement sendEmail() method.

        $fromEmail = $this->fromEmail;
        $fromName  = $this->fromName;

        $toEmail  = $params['to-email'];
        $toName   = $params['to-name'];
        $subject  = $params['subject'];
        $bodyHTML = $params['body'];
        $bodyText = $params['alt'];

        $toEmails = [ $toEmail ];

        $result = $this->ses->sendEmail(
            [
                'Destination'      => [
                    'ToAddresses' => $toEmails,
                ],
                'ReplyToAddresses' => [ $fromEmail ],
                'Source'           => $fromEmail,
                'Message'          => [
                    'Body'    => [
                        'Html' => [
                            'Charset' => self::CHARSET,
                            'Data'    => $bodyHTML,
                        ],
                        'Text' => [
                            'Charset' => self::CHARSET,
                            'Data'    => $bodyText,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => self::CHARSET,
                        'Data'    => $subject,
                    ],
                ],
                // If you aren't using a configuration set, comment or delete the
                // following line
                //'ConfigurationSetName' => $configuration_set,
            ]
        );

        $messageId = $result['MessageId'];
        clog("Email sent - Message ID", $messageId);

        return true;
    }
}
