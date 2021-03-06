# CHANGELOG

## 2.0.0

- "type" was renamed to "topic" in all things related.
- Upgraded IronMQ libraries. 3.x queues are REQUIRED.
- Upgraded Symfony deps to support 3.x series.
- Added options (exchange type, routing key) to AMQP adapters.

## 1.0.1

- Fixed an oversight in queue interface and Symfony bridge.

## 1.0

- Throws namespaced exceptions instead of generic RuntimeException ones.
- Handles dequeue errors in processor.
- Fixed segmentation faulting in tests.
- Bumped PHP requirement to 5.5+.
- Updated SQS adapter to use version 3 of the SDK.

## 0.4

- Queue: Symfony specific stuff was moved to SymfonyBridge. If you rely on Symfony events, use
  the EventDispatchingQueue wrapper for queue.
- Queue: There are new features you should consider using (automatic serializing / deserializing).
- Queue: Enqueueable is gone. Enqueue now takes type (topic) and data instead of an Enqueueable and returns a message.
- Processor: All functionality rely on the SymfonyBridge components.
- Processor: new messages, while processing, are not added to the result object and then queued. Queue is passed as a second
  argument instead and your handler must enqueue it's new messages.
