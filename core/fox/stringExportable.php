<?php
namespace fox;

/**
 *
 * Interface fox\stringExportable
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

interface stringExportable
{

    public function __toString(): string;

    public function isNull(): bool;
}
?>