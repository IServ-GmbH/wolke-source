<?php

declare(strict_types=1);

namespace OCA\Federation;

use OC\Security\Bruteforce\Backend\IBackend;
use OC\Security\Normalizer\IpAddress;
use Psr\Log\LoggerInterface;

class BruteforceResetter
{
    public function __construct(
        private readonly IBackend $bruteforceBackend,
        private readonly LoggerInterface $logger
    ) {
    }

    public function resetTrustedServerAttempts(string $url): void
    {
        $hostname = parse_url($url, PHP_URL_HOST);
        if ($hostname === false) {
            $this->logger->error('Malformed trusted server url received: ' . $url);
            return;
        }
        $ips = dns_get_record($hostname, DNS_A + DNS_AAAA);
        if ($ips === false) {
            $this->logger->error('DNS lookup for hostname failed: ' . $hostname);
            return;
        }
        foreach ($ips as $ipStr) {
            $ipStr = $ipStr['ip'] ?? $ipStr['ipv6'] ?? null;
            if (!$ipStr) {
                $this->logger->error("Could not resolve IP for trusted server: " . $url);
                continue;
            }
            $ip = new IpAddress($ipStr);
            $this->bruteforceBackend->resetAttempts($ip->getSubnet(), 'federationSharedSecret');
        }
    }
}
