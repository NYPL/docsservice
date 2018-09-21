<?php
namespace NYPL\Services\Controller;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use NYPL\Starter\Config;
use NYPL\Starter\Controller;

final class SwaggerController extends Controller
{
    /**
     * @param array $docs
     *
     * @throws \NYPL\Starter\APIException
     */
    protected function putDocsInS3(array $docs)
    {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'=> 'us-east-1'
        ]);

        $s3Client->putObject([
            'ACL' => 'public-read',
            'Body' => json_encode($docs),
            'Bucket' => Config::get('DOCS_S3_BUCKET'),
            'Key' => Config::get('DOCS_S3_KEY'),
            'CacheControl' => 'max-age=' . Config::get('DOCS_S3_CACHE_SECONDS'),
            'ContentType' => 'application/json'
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/v0.1/docs",
     *     summary="Get and generate new Platform API documentation",
     *     tags={"docs"},
     *     operationId="getDocs",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="Generic server error",
     *         @SWG\Schema(ref="#/definitions/ErrorResponse")
     *     ),
     *     security={
     *         {
     *             "api_auth": {"openid read:doc"}
     *         }
     *     }
     * )
     * @return \Slim\Http\Response
     * @throws \NYPL\Starter\APIException
     */
    public function getDocs()
    {
        $docs = [
            'swagger' => '2.0',
            'info' => [
                'title' => Config::get('DOCS_INFO_TITLE'),
                'description' => Config::get('DOCS_INFO_DESCRIPTION') . "\n\n*Last generated: " . date('r') . "*",
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
                        'openid' => '',
                        'admin' => '',
                        'login:staff' => '',
                        'read:bib' => '',
                        'write:bib' => '',
                        'read:checkin_request' => '',
                        'write:checkin_request' => '',
                        'read:checkout_request' => '',
                        'write:checkout_request' => '',
                        'read:doc' => '',
                        'read:hold_request' => '',
                        'write:hold_request' => '',
                        'read:item' => '',
                        'write:item' => '',
                        'read:patron' => '',
                        'write:patron' => '',
                        'read:recall_request' => '',
                        'write:recall_request' => '',
                        'read:refile_request' => '',
                        'write:refile_request' => ''
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

        $client = new Client([
            'timeout'  => 10
        ]);

        $addedSpecsUrls = explode(',', Config::get('DOCS_URLS'));

        foreach ($addedSpecsUrls as $addedSpecUrl) {
            $response = $client->get($addedSpecUrl);

            $addedSpecUrl = json_decode((string) $response->getBody(), true);

            if (is_array($addedSpecUrl['paths'])) {
                foreach ($addedSpecUrl['paths'] as $path => $pathArray) {
                    if (!isset($docs['paths'][$path])) {
                        $docs['paths'][$path] = [];
                    }

                    $docs['paths'][$path] = array_merge($docs['paths'][$path], $pathArray);
                }
            }

            if (isset($addedSpecUrl['tags'])) {
                $docs['tags'] = array_merge($docs['tags'], $addedSpecUrl['tags']);
            }

            if (isset($addedSpecUrl['definitions'])) {
                $docs['definitions'] = array_merge($docs['definitions'], $addedSpecUrl['definitions']);
            }
        }

        if (isset($docs['tags'])) {
            array_multisort(
                array_column($docs['tags'], 'description'),
                $docs['tags']
            );

            ksort($docs['paths']);
            ksort($docs['definitions']);
        }

        $this->putDocsInS3($docs);

        return $this->getResponse()->withJson($docs);
    }
}
