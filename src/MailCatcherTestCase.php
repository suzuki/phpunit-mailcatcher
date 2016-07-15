<?php

namespace Suzuki\PHPUnit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;

/**
 * Email TestCase using MailCatcher
 * @see https://mailcatcher.me/
 */
class MailCatcherTestCase extends TestCase
{
    protected $mailCatcherBaseUrl = 'http://localhost:1080';
    protected static $mailCatcher;

    public function __construct()
    {
        parent::__construct();
    }

    protected function setUp()
    {
        self::$mailCatcher = new Client([
            'base_uri' => $this->mailCatcherBaseUrl,
        ]);
        self::cleanMessages();

        parent::setUp();
    }

    /**
     * @param int    $expectedCount
     * @param string $message
     */
    public static function assertMailCount($expectedCount, $message = '')
    {
        $haystack = self::getMessages();
        self::assertThat(
            $haystack,
            new \PHPUnit_Framework_Constraint_Count($expectedCount),
            $message
        );
    }

    /**
     * @param string $expected
     * @param string $message
     */
    public static function assertMailSubject($expected, $message = '')
    {
        $latestMessage = self::getLatestMessage();
        $actual = $latestMessage->subject;

        $constraint = new \PHPUnit_Framework_Constraint_IsIdentical(
            $expected
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * @param string $needle
     * @param string $message
     */
    public static function assertMailPlainBodyContains($needle, $message = '')
    {
        $haystack = self::getLatestPlainBody();
        $ignoreCase = false;

        $constraint = new \PHPUnit_Framework_Constraint_StringContains(
            $needle,
            $ignoreCase
        );

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * @param string $needle
     * @param string $message
     */
    public static function assertMailHtmlBodyContains($needle, $message = '')
    {
        $haystack = self::getLatestHtmlBody();
        $ignoreCase = false;

        $constraint = new \PHPUnit_Framework_Constraint_StringContains(
            $needle,
            $ignoreCase
        );

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * @param string $message
     */
    public static function assertMailPlainBodyEmpty($message = '')
    {
        try {
            $actual = self::getLatestPlainBody();
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                $actual = '';
            }
        }

        static::assertThat($actual, static::isEmpty(), $message);
    }

    /**
     * @param string $message
     */
    public static function assertMailHtmlBodyEmpty($message = '')
    {
        try {
            $actual = self::getLatestHtmlBody();
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                $actual = '';
            }
        }

        static::assertThat($actual, static::isEmpty(), $message);
    }

    /**
     * clean all messages
     */
    protected static function cleanMessages()
    {
        self::$mailCatcher->delete('/messages');
    }

    /**
     * @return stdClass[]
     */
    protected static function getMessages()
    {
        $response = self::$mailCatcher->get('/messages');

        return json_decode($response->getBody()->getContents());
    }

    /**
     * @return stdClass
     */
    protected static function getLatestMessage()
    {
        return self::getMessage(self::getLatestId());
    }

    /**
     * @return string
     */
    protected static function getLatestPlainBody()
    {
        return self::getPlainBody(self::getLatestId());
    }

    /**
     * @return string
     */
    protected static function getLatestHtmlBody()
    {
        return self::getHtmlBody(self::getLatestId());
    }

    /**
     * @param int $id
     * @return stdClass
     */
    protected static function getMessage($id)
    {
        if (!is_int($id)) {
            new \InvalidArgumentException('only numeric');
        }

        $path = sprintf('/messages/%s.json', urlencode($id));
        $response = self::$mailCatcher->get($path);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * @param int $id
     * @return string
     */
    protected static function getPlainBody($id)
    {
        if (!is_int($id)) {
            new \InvalidArgumentException('only numeric');
        }

        $path = sprintf('/messages/%s.plain', urlencode($id));
        $response =self::$mailCatcher->get($path);

        return $response->getBody()->getContents();
    }

    /**
     * @param int $id
     * @return string
     */
    protected static function getHtmlBody($id)
    {
        if (!is_int($id)) {
            new \InvalidArgumentException('only numeric');
        }

        $path = sprintf('/messages/%s.html', urlencode($id));
        $response =self::$mailCatcher->get($path);

        return $response->getBody()->getContents();
    }

    /**
     * @return int
     */
    private static function getLatestId()
    {
        $messages = self::getMessages();
        $ids = array_keys($messages);

        return (int) max($ids);
    }
}
