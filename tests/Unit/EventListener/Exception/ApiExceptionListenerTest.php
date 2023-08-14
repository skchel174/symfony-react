<?php

declare(strict_types=1);

namespace Test\Unit\EventListener\Exception;

use App\Event\ExceptionEvent;
use App\EventListener\Exception\ApiExceptionListener;
use App\Service\ExceptionMetadataResolver\ExceptionMetadata;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiExceptionListenerTest extends TestCase
{
    public function testCreateDebugHiddenResponse(): void
    {
        $e = new RuntimeException('Test exception');

        $content = json_encode([
            'status' => $statusCode = 500,
            'detail' => Response::$statusTexts[$statusCode],
            'message' => $e->getMessage(),
            'class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ], JsonResponse::DEFAULT_ENCODING_OPTIONS);

        $event = $this->createExceptionEvent($e, $statusCode, true, $content);

        $eventListener = new ApiExceptionListener(true);
        $eventListener($event);
    }

    public function testCreateDebugNotHiddenResponse(): void
    {
        $e = new RuntimeException('Test exception');

        $content = json_encode([
            'status' => $statusCode = 500,
            'detail' => $e->getMessage(),
            'class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ], JsonResponse::DEFAULT_ENCODING_OPTIONS);

        $event = $this->createExceptionEvent($e, $statusCode, false, $content);

        $eventListener = new ApiExceptionListener(true);
        $eventListener($event);
    }

    public function testCreateNotDebugResponse(): void
    {
        $e = new RuntimeException('Test exception');

        $content = json_encode([
            'status' => $statusCode = 500,
            'detail' => $e->getMessage(),
        ], JsonResponse::DEFAULT_ENCODING_OPTIONS);

        $event = $this->createExceptionEvent($e, $statusCode, false, $content);

        $eventListener = new ApiExceptionListener(false);
        $eventListener($event);
    }

    public function testWhenJsonFormatNotPreferred(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getPreferredFormat')
            ->willReturn('html');

        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->never())
            ->method('getThrowable');
        $event->expects($this->never())
            ->method('getMetadata');
        $event->expects($this->never())
            ->method('setResponse');

        $eventListener = new ApiExceptionListener(true);
        $eventListener($event);
    }

    private function createExceptionEvent(Throwable $e, int $statusCode, bool $isHidden, string $content): ExceptionEvent
    {
        $metadata = $this->createMock(ExceptionMetadata::class);
        $metadata->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($statusCode);
        $metadata->expects($this->once())
            ->method('isHidden')
            ->willReturn($isHidden);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getPreferredFormat')
            ->willReturn('json');

        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($e);
        $event->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function (Response $response) use ($statusCode, $content) {
                return $response instanceof JsonResponse
                    && $response->getStatusCode() === $statusCode
                    && $response->getContent() === $content;
            }));

        return $event;
    }
}
