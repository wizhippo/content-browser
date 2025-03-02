<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserBundle\Tests\EventListener;

use Exception;
use Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener;
use Netgen\Bundle\ContentBrowserBundle\EventListener\SetIsApiRequestListener;
use Netgen\ContentBrowser\Exceptions\RuntimeException;
use Netgen\ContentBrowser\Tests\Utils\BackwardsCompatibility\CreateEventTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class ExceptionSerializerListenerTest extends TestCase
{
    use CreateEventTrait;

    private ExceptionSerializerListener $eventListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Log\LoggerInterface
     */
    private MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->eventListener = new ExceptionSerializerListener(false, $this->loggerMock);
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::__construct
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::EXCEPTION => ['onException', 5]],
            $this->eventListener::getSubscribedEvents(),
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::logException
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::onException
     */
    public function testOnException(): void
    {
        $exception = new NotFoundHttpException('Some message');

        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = $this->createExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception,
        );

        $this->eventListener->onException($event);

        self::assertInstanceOf(
            JsonResponse::class,
            $event->getResponse(),
        );

        self::assertSame(
            [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
                'status_text' => Response::$statusTexts[$exception->getStatusCode()],
            ],
            json_decode((string) $event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::logException
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::onException
     */
    public function testOnExceptionLogsCriticalError(): void
    {
        $exception = new RuntimeException('Some message');

        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = $this->createExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception,
        );

        $this->loggerMock
            ->expects(self::once())
            ->method('critical');

        $this->eventListener->onException($event);

        self::assertInstanceOf(
            JsonResponse::class,
            $event->getResponse(),
        );

        self::assertSame(
            [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],
            json_decode((string) $event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::logException
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::onException
     */
    public function testOnExceptionWithDebugging(): void
    {
        $exception = new NotFoundHttpException('Some message', new Exception('Previous exception'));

        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = $this->createExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception,
        );

        $this->eventListener = new ExceptionSerializerListener(true, $this->loggerMock);
        $this->eventListener->onException($event);

        self::assertInstanceOf(
            JsonResponse::class,
            $event->getResponse(),
        );

        $data = json_decode((string) $event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertArrayHasKey('code', $data);
        self::assertArrayHasKey('message', $data);
        self::assertArrayHasKey('status_code', $data);
        self::assertArrayHasKey('status_text', $data);
        self::assertArrayHasKey('debug', $data);
        self::assertArrayHasKey('line', $data['debug']);
        self::assertArrayHasKey('file', $data['debug']);
        self::assertArrayHasKey('trace', $data['debug']);

        self::assertSame($exception->getCode(), $data['code']);
        self::assertSame($exception->getMessage(), $data['message']);
        self::assertSame($exception->getStatusCode(), $data['status_code']);
        self::assertSame(Response::$statusTexts[$exception->getStatusCode()], $data['status_text']);
        self::assertSame(__FILE__, $data['debug']['file']);
        self::assertGreaterThan(0, $data['debug']['line']);
        self::assertNotEmpty($data['debug']['trace']);
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::onException
     */
    public function testOnExceptionInSubRequest(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = $this->createExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new NotFoundHttpException('Some message'),
        );

        $this->eventListener->onException($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\ExceptionSerializerListener::onException
     */
    public function testOnExceptionWithNoContentBrowserRequest(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, false);

        $event = $this->createExceptionEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new NotFoundHttpException('Some message'),
        );

        $this->eventListener->onException($event);

        self::assertFalse($event->hasResponse());
    }
}
