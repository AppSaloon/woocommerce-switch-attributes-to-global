<?php

namespace appsaloon\wcstga\lib;

/**
 * Class Message_Log
 * @package appsaloon\wcstga\lib
 */
class Message_Log
{
    /**
     * @var array
     */
    private $messages = array();

    /**
     * @param string $message
     */
    public function add_message(string $message)
    {
        $this->messages[] = $message;
    }

    /**
     * @return array
     */
    public function get_messages(): array
    {
        return $this->messages;
    }
}