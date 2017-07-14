<?php
namespace NYPL\Services\Controller;

use GuzzleHttp\Client;
use NYPL\Starter\APILogger;
use NYPL\Starter\Config;
use NYPL\Starter\Controller;

final class SwaggerController extends Controller
{
    public function getDocs()
    {
        $client = new Client([
            'timeout'  => 5
        ]);

        $baseSpec = [
            'swagger' => '2.0',
            'info' => [
                'title' => Config::get('DOCS_INFO_TITLE'),
                'description' => Config::get('DOCS_INFO_DESCRIPTION'),
                'version' => Config::get('DOCS_INFO_VERSION')
            ],
            'host' => Config::get('DOCS_HOST'),
            'basePath' => Config::get('DOCS_BASE_PATH'),
            'schemes' => ['https'],
            'securityDefinitions' => [
                'api_auth' => [
                    'type' => 'oauth2',
                    'flow' => 'accessCode',
                    'authorizationUrl' => 'https://isso.nypl.org/oauth/authorize',
                    'tokenUrl' => 'https://isso.nypl.org/oauth/token',
                    'scopes' => [
                        'openid offline_access api' => 'General API access',
                        'openid offline_access api patron:read' => 'Patron specific API access',
                        'openid offline_access api staff:read' => 'Staff specific API access'
                    ]
                ]
            ],
            'externalDocs' => [
                'description' => Config::get('DOCS_EXTERNAL_DESCRIPTION'),
                'url' => Config::get('DOCS_EXTERNAL_URL')
            ],
            'paths' => [],
            'tags' => [],
            'definitions' => [],
        ];

        $addedSpecsUrls = explode(',', Config::get('DOCS_URLS'));

        foreach ($addedSpecsUrls as $addedSpecUrl) {
            try {
                $response = $client->get($addedSpecUrl);

                $addedSpecUrl = json_decode((string) $response->getBody(), true);

                if (isset($addedSpecUrl['paths'])) {
                    $baseSpec['paths'] = array_merge($baseSpec['paths'], $addedSpecUrl['paths']);
                }

                if (isset($addedSpecUrl['tags'])) {
                    $baseSpec['tags'] = array_merge($baseSpec['tags'], $addedSpecUrl['tags']);
                }

                if (isset($addedSpecUrl['definitions'])) {
                    $baseSpec['definitions'] = array_merge($baseSpec['definitions'], $addedSpecUrl['definitions']);
                }
            } catch (\Exception $exception) {
                APILogger::addError(
                    'Unable to retrieve Swagger specification from ' . $addedSpecUrl . ': ' . $exception->getMessage()
                );
            }
        }

        if (isset($baseSpec['tags'])) {
            array_multisort(
                array_column($baseSpec['tags'], 'description'),
                $baseSpec['tags']
            );

            ksort($baseSpec['paths']);
            ksort($baseSpec['definitions']);
        }

        return $this->getResponse()->withJson($baseSpec);
    }
}
