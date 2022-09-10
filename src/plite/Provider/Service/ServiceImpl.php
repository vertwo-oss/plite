<?php



namespace vertwo\plite\Provider\Service;



use vertwo\plite\Provider\PliteFactory;



interface ServiceImpl
{
    /**
     * Returns a value if 'get' is semantically well-defined.
     *
     * In the case of AWS Secrets Manager, get() just retrieves a single secret.
     * In the case of S3, get() would retrieve the client.
     *
     * @param PliteFactory $pf
     * @param mixed        $params
     *
     * @return mixed
     */
    function get ( $pf, $params );
}
