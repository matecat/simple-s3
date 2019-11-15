<?php
/**
 *  This file is part of the Simple S3 package.
 *
 * (c) Mauro Cassani<https://github.com/mauretto78>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Matecat\SimpleS3\Commands;

interface CommandHandlerInterface
{
    /**
     * @param array $params
     *
     * @return mixed
     */
    public function handle($params = []);

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = []);
}
