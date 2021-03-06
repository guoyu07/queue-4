<?php

/**
 * This file is part of the pekkis-queue package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pekkis\Queue\Adapter;

use IronCore\HttpException;
use IronMQ\IronMQ;
use Pekkis\Queue\RuntimeException;

/**
 * IronMQ queue
 */
class IronMQAdapter implements Adapter
{
    /**
     * @var IronMQ
     */
    private $queue;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $expiresIn;

    /**
     * @param string $token
     * @param string $projectId
     * @param string $queueName
     * @param int $timeout
     * @param int $expiresIn
     * @param string $host
     */
    public function __construct($token, $projectId, $queueName, $timeout = 60, $expiresIn = 604800, $host = null)
    {
        if ($timeout < 30) {
            throw new RuntimeException('IronMQ v3 has a minimum timeout of 30');
        }

        $this->queue = new IronMQ(
            array(
                'token' => $token,
                'project_id' => $projectId,
                'host' => $host
            )
        );

        $this->queueName = $queueName;
        $this->timeout = $timeout;
        $this->expiresIn = $expiresIn;
    }

    /**
     * @param string $message
     * @return bool
     */
    public function enqueue($message, $topic)
    {
        $this->queue->postMessage(
            $this->queueName,
            $message,
            array(
                'timeout' => $this->timeout,
                'expires_in' => $this->expiresIn
            )
        );
    }

    public function dequeue()
    {
        $rawMsg = $this->queue->reserveMessage($this->queueName, $this->timeout);
        if (!$rawMsg) {
            return false;
        }

        return array(
            $rawMsg->body,
            $rawMsg->id,
            [
                'reservation_id' => $rawMsg->reservation_id
            ]
        );
    }

    public function purge()
    {
        try {
            $this->queue->clearQueue($this->queueName);
        } catch (HttpException $e) {
            // Queue is not found, so it's as good as purged, no?
            if ($e->getCode() !== 404) {
                throw $e;
            }
            return true;
        }
    }

    public function ack($identifier, $internals)
    {
        $this->queue->deleteMessage($this->queueName, $identifier, $internals['reservation_id']);
    }
}
