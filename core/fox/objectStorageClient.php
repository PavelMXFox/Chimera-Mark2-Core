<?php
namespace fox;

/**
 *
 * Interface fox\objectStorageClient
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

interface objectStorageClient
{

    public function getObject($bucket, $key);

    public function putObject($bucket, $key, $data);

    public function deleteObject($bucket, $key);

    public function listObjects($bucket);

    public function exec($method, $args = []);

    public function createBucket($bucket);

    public function deleteBucket($bucket);

    public function headBucket($bucket);

    public function headObject($bucket, $key);
}
?>