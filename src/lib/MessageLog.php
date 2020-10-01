<?php

namespace Appsaloon\Processor\Lib;

/**
 * Class MessageLog
 * @package Appsaloon\Helper
 */
class MessageLog
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