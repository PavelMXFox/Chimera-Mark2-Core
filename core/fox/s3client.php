<?php
namespace fox;

/**
 *
 * Class fox\s3client
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class s3client implements objectStorageClient
{

    protected string $endpoint;

    protected string $accessKey;

    protected string $secretKey;

    protected string $regionId = "ru-1";

    protected string $prefix = "";

    protected \Aws\S3\S3Client $s3;

    public function __construct($endpoint = null, $accessKey = null, $secretKey = null, $regionId = null, $prefix = null)
    {
        if (! empty($prefix)) {
            $this->prefix = $prefix;
        }

        if (empty($endpoint)) {
            $endpoint = config::get("s3_endpoint");
            $accessKey = config::get("s3_login");
            $secretKey = config::get("s3_secret");
            $regionId = (empty(config::get("s3_region")) ? "ru-1" : config::get("s3_region"));

            if (empty(config::get("s3_prefix"))) {
                $this->prefix = "";
            } else {
                $this->prefix = config::get("s3_prefix") . "-";
            }
        }

        if (empty($endpoint) || empty($accessKey) || empty($secretKey) || empty($regionId)) {
            throw new \Exception("Credentials can't be empty!");
        }

        $this->s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $regionId,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey
            ]
        ]);
    }

    public function getObject($bucket, $key)
    {
        $res = $this->s3->getObject([
            "Bucket" => $this->prefix . $bucket,
            "Key" => $key
        ]);
        return (string) $res["Body"];
    }

    public function putObject($bucket, $key, $data)
    {
        $this->s3->putObject([
            "Bucket" => $this->prefix . $bucket,
            "Key" => $key,
            "Body" => $data
        ]);
    }

    public function deleteObject($bucket, $key)
    {
        $this->s3->deleteObject([
            "Bucket" => $this->prefix . $bucket,
            "Key" => $key
        ]);
    }

    public function listObjects($bucket)
    {
        return $this->s3->listObjects([
            "Bucket" => $this->prefix . $bucket
        ])["Contents"];
    }

    public function exec($method, $args = [])
    {
        return $this->s3->{$method}($args);
    }

    public function createBucket($bucket)
    {
        return $this->s3->createBucket([
            "Bucket" => $this->prefix . $bucket
        ]);
    }

    public function deleteBucket($bucket)
    {
        return $this->s3->deleteBucket([
            "Bucket" => $this->prefix . $bucket
        ]);
    }
}

?>