<?php

namespace SimpleS3\Commands;

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
