<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Mail\Model;

use Aws\SesV2\SesV2Client;

/**
 * Amazon SES v2 SendEmail with the raw MIME message. Credentials: the AWS
 * SDK's default chain (the EC2 instance role on the server).
 */
class Ses
{
    /** @var callable(array): SesV2Client */
    private $clientFactory;

    public function __construct(
        private readonly Config $config,
        ?callable $clientFactory = null
    ) {
        $this->clientFactory = $clientFactory ?? static fn (array $args) => new SesV2Client($args);
    }

    /**
     * @param array{to: string[], cc: string[], bcc: string[]} $recipients
     * @return string SES message id
     */
    public function send(string $raw, string $from, array $recipients): string
    {
        $client = ($this->clientFactory)([
            'version' => 'latest',
            'region' => $this->config->sesRegion(),
            // A page waiting on SES (Send Through the Queue off) waits this long at most.
            'http' => ['connect_timeout' => 5, 'timeout' => 20],
            'retries' => 1,
        ]);
        $args = [
            'FromEmailAddress' => $from,
            'Destination' => array_filter([
                'ToAddresses' => $recipients['to'],
                'CcAddresses' => $recipients['cc'],
                'BccAddresses' => $recipients['bcc'],
            ]),
            'Content' => ['Raw' => ['Data' => $raw]],
        ];
        if ($this->config->sesConfigurationSet() !== '') {
            $args['ConfigurationSetName'] = $this->config->sesConfigurationSet();
        }

        return (string) $client->sendEmail($args)->get('MessageId');
    }
}
