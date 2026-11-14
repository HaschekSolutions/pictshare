<?php
// tests/Unit/IpListTest.php
//
// Entries of ALLOWED_SUBNET / TRUSTED_PROXIES: CIDR ranges, plain IPs (= that
// one address) and hostnames (= every address the name currently resolves to).
use PHPUnit\Framework\TestCase;

class IpListTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . DS . 'pictshare_dnscache_' . bin2hex(random_bytes(6));
        mkdir($this->cacheDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->cacheDir . DS . '*'));
        rmdir($this->cacheDir);
    }

    /** resolver that must never be reached */
    private function noDns(): callable
    {
        return function ($host) {
            $this->fail("unexpected DNS lookup for $host");
        };
    }

    public function testPlainIpv4MatchesOnlyThatAddress(): void
    {
        $this->assertTrue(ipMatchesList('192.168.1.5', '192.168.1.5', $this->noDns()));
        $this->assertFalse(ipMatchesList('192.168.1.6', '192.168.1.5', $this->noDns()));
        $this->assertFalse(ipMatchesList('203.0.113.7', '192.168.1.5', $this->noDns()));
    }

    // the fail-open: ALLOWED_SUBNET=192.168.1.5 used to let everyone in
    public function testAllowedSubnetWithPlainIpRejectsOtherClients(): void
    {
        $this->assertTrue(isIPAllowedBySubnet('192.168.1.5', '192.168.1.5'));
        $this->assertFalse(isIPAllowedBySubnet('203.0.113.7', '192.168.1.5'));
    }

    public function testEmptyAllowedSubnetAllowsEveryone(): void
    {
        $this->assertTrue(isIPAllowedBySubnet('203.0.113.7', ''));
    }

    public function testMasterDeleteIpUnsetMatchesNobody(): void
    {
        $this->assertFalse(isMasterDeleteIP('203.0.113.7', ''));
    }

    public function testMasterDeleteIpPlainAddress(): void
    {
        $this->assertTrue(isMasterDeleteIP('192.0.2.10', '192.0.2.10'));
        $this->assertFalse(isMasterDeleteIP('192.0.2.11', '192.0.2.10'));
    }

    public function testMasterDeleteIpAcceptsRangesAndLists(): void
    {
        $this->assertTrue(isMasterDeleteIP('10.4.5.6', '192.0.2.10, 10.0.0.0/8'));
        $this->assertTrue(isMasterDeleteIP('192.0.2.10', '192.0.2.10, 10.0.0.0/8'));
        $this->assertFalse(isMasterDeleteIP('203.0.113.7', '192.0.2.10, 10.0.0.0/8'));
    }

    public function testMasterDeleteIpWithBrokenEntryMatchesNobody(): void
    {
        $this->assertFalse(isMasterDeleteIP('203.0.113.7', '192.0.2.10/'));
        $this->assertFalse(isMasterDeleteIP('203.0.113.7', '/0'));
    }

    public function testPlainIpv6MatchesOnlyThatAddress(): void
    {
        $this->assertTrue(ipMatchesList('2001:db8::5', '2001:db8::5', $this->noDns()));
        $this->assertFalse(ipMatchesList('2001:db8::6', '2001:db8::5', $this->noDns()));
    }

    public function testCidrRangeStillMatches(): void
    {
        $this->assertTrue(ipMatchesList('10.20.30.40', '10.0.0.0/8', $this->noDns()));
        $this->assertFalse(ipMatchesList('11.0.0.1', '10.0.0.0/8', $this->noDns()));
    }

    public function testMixedListOfRangeAndPlainIp(): void
    {
        $list = '10.0.0.0/8, 192.168.1.5';
        $this->assertTrue(ipMatchesList('10.1.1.1', $list, $this->noDns()));
        $this->assertTrue(ipMatchesList('192.168.1.5', $list, $this->noDns()));
        $this->assertFalse(ipMatchesList('192.168.1.6', $list, $this->noDns()));
    }

    public function testMalformedMaskNeverMatches(): void
    {
        $this->assertFalse(ipMatchesList('10.1.1.1', '10.0.0.0/abc', $this->noDns()));
        $this->assertFalse(ipMatchesList('10.1.1.1', '10.0.0.0/99', $this->noDns()));
        $this->assertFalse(ipMatchesList('10.1.1.1', '10.0.0.0/', $this->noDns()));
    }

    public function testHostnameMatchesEachResolvedAddressExactly(): void
    {
        $resolver = fn($host) => $host === 'office.example.com' ? ['198.51.100.20', '2001:db8::99'] : [];

        $this->assertTrue(ipMatchesList('198.51.100.20', 'office.example.com', $resolver));
        $this->assertTrue(ipMatchesList('2001:db8::99', 'office.example.com', $resolver));
        $this->assertFalse(ipMatchesList('198.51.100.21', 'office.example.com', $resolver));
    }

    public function testUnresolvableHostnameMatchesNobody(): void
    {
        $this->assertFalse(ipMatchesList('198.51.100.20', 'gone.example.com', fn($host) => []));
    }

    public function testHostnameMixedWithRanges(): void
    {
        $resolver = fn($host) => ['198.51.100.20'];
        $list = '10.0.0.0/8,office.example.com';

        $this->assertTrue(ipMatchesList('10.9.9.9', $list, $resolver));
        $this->assertTrue(ipMatchesList('198.51.100.20', $list, $resolver));
        $this->assertFalse(ipMatchesList('203.0.113.7', $list, $resolver));
    }

    public function testHostnameLookupIsCachedWithinTtl(): void
    {
        $calls = 0;
        $lookup = function ($host) use (&$calls) { $calls++; return ['198.51.100.20']; };

        $first  = resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1000);
        $second = resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1299);

        $this->assertSame(['198.51.100.20'], $first);
        $this->assertSame(['198.51.100.20'], $second);
        $this->assertSame(1, $calls);
    }

    public function testHostnameIsLookedUpAgainAfterTtl(): void
    {
        $answers = [['198.51.100.20'], ['198.51.100.77']];
        $lookup = function ($host) use (&$answers) { return array_shift($answers); };

        resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1000);
        $later = resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1301);

        $this->assertSame(['198.51.100.77'], $later);
    }

    public function testFailedRefreshKeepsLastKnownAddresses(): void
    {
        $answers = [['198.51.100.20'], []];
        $lookup = function ($host) use (&$answers) { return array_shift($answers); };

        resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1000);
        $later = resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1301);

        $this->assertSame(['198.51.100.20'], $later);
    }

    public function testFailedRefreshIsRetriedSoonerThanTheFullTtl(): void
    {
        $answers = [['198.51.100.20'], [], ['198.51.100.77']];
        $lookup = function ($host) use (&$answers) { return array_shift($answers); };

        resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1000);
        resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1301); // fails
        $retry = resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1301 + 61);

        $this->assertSame(['198.51.100.77'], $retry);
    }

    public function testLookupResultsThatAreNotIpsAreDropped(): void
    {
        $lookup = fn($host) => ['198.51.100.20', 'not-an-ip', ''];
        $this->assertSame(['198.51.100.20'], resolveHostnameCached('office.example.com', 300, $lookup, $this->cacheDir, 1000));
    }
}
