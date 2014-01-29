<?php

/**
 * This file is part of the pekkis-queue package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pekkis\Queue\Adapter;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use Pekkis\Queue\Message;

class PhpAMQPAdapter implements Adapter
{
    /**
     *
     * @var AMQPChannel
     */
    private $channel;

    private $exchange;

    private $queue;

    /**
     * @var array
     */
    private $connectionOptions = array();

    public function __construct($host, $port, $user, $pass, $vhost, $exchange, $queue)
    {
        $this->exchange = $exchange;
        $this->queue = $queue;

        $this->connectionOptions = array(
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
            'vhost' => $vhost,
        );
    }

    /**
     * Connects to AMQP and gets channel
     *
     * @return AMQPChannel
     */
    private function getChannel()
    {
        if (!$this->channel) {
            $conn = new AMQPConnection(
                $this->connectionOptions['host'],
                $this->connectionOptions['port'],
                $this->connectionOptions['user'],
                $this->connectionOptions['pass'],
                $this->connectionOptions['vhost']
            );
            $ch = $conn->channel();

            $ch->queue_declare($this->queue, false, true, false, false);
            $ch->exchange_declare($this->exchange, 'direct', false, true, false);
            $ch->queue_bind($this->queue, $this->exchange, 'xi_filelib');
            $this->channel = $ch;
        }

        return $this->channel;
    }

    public function enqueue(Message $msg)
    {
        $msg = new AMQPMessage(
            json_encode($msg->toArray()),
            array('content_type' => 'application/json', 'delivery-mode' => 1)
        );
        $this->getChannel()->basic_publish($msg, $this->exchange, 'xi_filelib', false, false);
    }

    public function dequeue()
    {
        $msg = $this->getChannel()->basic_get($this->queue);

        if (!$msg) {
            return null;
        }

        $message = Message::fromArray(json_decode($msg->body, true));
        $message->setIdentifier($msg->delivery_info['delivery_tag']);

        return $message;
    }

    public function purge()
    {
        return $this->getChannel()->queue_purge($this->queue);
    }

    public function ack(Message $message)
    {
        $this->getChannel()->basic_ack($message->getIdentifier());
    }
}