<?php
// tests/Unit/ClientIpTest.php
//
// GHSA-3hrw-cw8q-qggv / GHSA-p46p-r88v-59mx: the client IP used for
// ALLOWED_SUBNET and MASTER_DELETE_IP must not be choosable by the client.
use PHPUnit\Framework\TestCase;

class ClientIpTest extends TestCase
{
    // a real Cloudflare edge address (104.16.0.0/13)
    private const CF_EDGE = '104.16.1.1';

    private function cfHeaders(string $connectingIp): array
    {
        return [
            'HTTP_CF_CONNECTING_IP' => $connectingIp,
            'HTTP_CF_IPCOUNTRY'     => 'AT',
            'HTTP_CF_RAY'           => '8a1b2c3d4e5f-VIE',
            'HTTP_CF_VISITOR'       => '{"scheme":"https"}',
        ];
    }

    public function testDirectClientCannotSpoofWithCfConnectingIp(): void
    {
        $server = ['REMOTE_ADDR' => '203.0.113.7'] + $this->cfHeaders('10.0.0.1');
        $this->assertSame('203.0.113.7', resolveClientIP($server, ''));
    }

    public function testDirectClientCannotSpoofWithXForwardedFor(): void
    {
        $server = ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '10.0.0.5'];
        $this->assertSame('203.0.113.7', resolveClientIP($server, ''));
    }

    public function testDirectClientCannotSpoofWithClientIp(): void
    {
        $server = ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_CLIENT_IP' => '10.0.0.5'];
        $this->assertSame('203.0.113.7', resolveClientIP($server, ''));
    }

    public function testClientIpHeaderIsIgnoredEvenBehindTrustedProxy(): void
    {
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_CLIENT_IP' => '10.0.0.5'];
        $this->assertSame('172.18.0.2', resolveClientIP($server, '172.18.0.0/16'));
    }

    public function testCloudflareEdgeConnectionUsesCfConnectingIp(): void
    {
        $server = ['REMOTE_ADDR' => self::CF_EDGE] + $this->cfHeaders('198.51.100.20');
        $this->assertSame('198.51.100.20', resolveClientIP($server, ''));
    }

    public function testCloudflareIpv6EdgeConnectionUsesCfConnectingIp(): void
    {
        // 2606:4700::/32 is a Cloudflare range
        $server = ['REMOTE_ADDR' => '2606:4700::1234'] + $this->cfHeaders('2001:db8::99');
        $this->assertSame('2001:db8::99', resolveClientIP($server, ''));
    }

    public function testNonCloudflareIpv6ClientCannotSpoofWithCfHeaders(): void
    {
        $server = ['REMOTE_ADDR' => '2001:db8::7'] + $this->cfHeaders('10.0.0.1');
        $this->assertSame('2001:db8::7', resolveClientIP($server, ''));
    }

    public function testCloudflareEdgeWithGarbageHeaderFallsBackToRemoteAddr(): void
    {
        $server = ['REMOTE_ADDR' => self::CF_EDGE] + $this->cfHeaders('<script>alert(1)</script>');
        $this->assertSame(self::CF_EDGE, resolveClientIP($server, ''));
    }

    public function testTrustedProxyForwardedForIsUsed(): void
    {
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'];
        $this->assertSame('198.51.100.20', resolveClientIP($server, '172.18.0.0/16'));
    }

    public function testTrustedProxyGivenAsPlainIpOnlyTrustsThatAddress(): void
    {
        $xff = ['HTTP_X_FORWARDED_FOR' => '198.51.100.20'];
        $this->assertSame('198.51.100.20', resolveClientIP(['REMOTE_ADDR' => '172.18.0.2'] + $xff, '172.18.0.2'));
        $this->assertSame('172.18.0.3', resolveClientIP(['REMOTE_ADDR' => '172.18.0.3'] + $xff, '172.18.0.2'));
    }

    public function testSpoofedLeftmostForwardedForEntryIsNotTrusted(): void
    {
        // client sent "X-Forwarded-For: 10.0.0.5", the proxy appended the real peer
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_X_FORWARDED_FOR' => '10.0.0.5, 198.51.100.20'];
        $this->assertSame('198.51.100.20', resolveClientIP($server, '172.18.0.0/16'));
    }

    public function testChainOfTrustedProxiesIsSkipped(): void
    {
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20, 192.168.1.1'];
        $this->assertSame('198.51.100.20', resolveClientIP($server, '172.18.0.0/16,192.168.1.1'));
    }

    public function testInvalidForwardedForEntryFallsBackToRemoteAddr(): void
    {
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_X_FORWARDED_FOR' => 'not-an-ip'];
        $this->assertSame('172.18.0.2', resolveClientIP($server, '172.18.0.0/16'));
    }

    public function testCloudflareBehindTrustedProxyUsesCfConnectingIp(): void
    {
        $server = ['REMOTE_ADDR' => '172.18.0.2', 'HTTP_X_FORWARDED_FOR' => self::CF_EDGE]
                + $this->cfHeaders('198.51.100.20');
        $this->assertSame('198.51.100.20', resolveClientIP($server, '172.18.0.0/16'));
    }

    public function testIpv6TrustedProxy(): void
    {
        $server = ['REMOTE_ADDR' => 'fd00::2', 'HTTP_X_FORWARDED_FOR' => '2001:db8::99'];
        $this->assertSame('2001:db8::99', resolveClientIP($server, 'fd00::/8'));
    }

    public function testIpv4ClientDoesNotMatchIpv6TrustedRange(): void
    {
        $server = ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '10.0.0.5'];
        $this->assertSame('203.0.113.7', resolveClientIP($server, '::/0'));
    }

    public function testGetUserIpIgnoresSpoofedHeadersWithoutTrustedProxies(): void
    {
        $backup = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.5';
        try {
            $this->assertSame('203.0.113.7', getUserIP());
        } finally {
            $_SERVER = $backup;
        }
    }
}
