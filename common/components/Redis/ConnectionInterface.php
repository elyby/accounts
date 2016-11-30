<?php
namespace common\components\Redis;

interface ConnectionInterface {

    /**
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * @param string $name Command, that should be executed
     * @param array  $params Arguments for this command
     *
     * @return mixed
     */
    public function executeCommand(string $name, array $params = []);

}
