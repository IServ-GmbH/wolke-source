<?php

declare(strict_types=1);

namespace OCA\IServLogin;

use OCP\Http\Client\IClient;

class TokenIntrospection
{
    public function __construct(
        private readonly IClient $client,
        private readonly string $serverDomain
    ) {
    }

    /**
     * @throws IntrospectionFailed
     */
    public function userInformationFromToken(string $token): UserInformation
    {
        if (empty($this->serverDomain) || !is_string($this->serverDomain)) {
            throw new IntrospectionFailed(
                "server domain is not valid: $this->serverDomain"
            );
        }

        try {
            $endpoint = $this->fetchIntrospectionEndpoint();
            return $this->introspect($endpoint, $token);
        } catch (\Exception $e) {
            throw new IntrospectionFailed(
                'introspection failed',
                previous: $e
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function introspect(string $endpoint, string $token): UserInformation
    {
        $responseJson = $this->callIntrospectionEndpoint($endpoint, $token);

        $active = $responseJson['active']
            ?? throw new IntrospectionFailed(
                'active key missing'
            );
        $uuid = $responseJson['user_uuid']
            ?? throw new IntrospectionFailed(
                'user_uuid key missing'
            );
        $scope = $responseJson['scope']
            ?? throw new IntrospectionFailed(
                'scope key missing'
            );

        if (!is_bool($active) || !is_string($uuid) || empty($uuid) || !is_string($scope)) {
            throw new IntrospectionFailed(
                "response data not in expected format: active: $active, uuid: $uuid, scope: $scope"
            );
        }

        return new UserInformation(
            active: $active,
            uuid: $uuid,
            scopes: explode(' ', $scope)
        );
    }

    /**
     * @throws \Exception
     */
    private function fetchIntrospectionEndpoint(): string
    {
        $configUrl = "https://$this->serverDomain/iserv/auth/public/.well-known/openid-configuration";

        $response = $this->client->get($configUrl);
        $content = $response->getBody();
        $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $config['introspection_endpoint']
            ?? throw new IntrospectionFailed(
                'config key "introspection_endpoint" is missing'
            );
    }

    /**
     * @throws \Exception
     */
    public function callIntrospectionEndpoint(string $endpoint, string $token): mixed
    {
        $response = $this->client->post(
            $endpoint,
            [
                'form_params' => [
                    'token' => $token,
                    'token_type_hint' => 'access_token'
                ]
            ]
        );
        $content = $response->getBody();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
