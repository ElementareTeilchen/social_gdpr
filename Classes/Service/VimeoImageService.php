<?php
declare(strict_types = 1);
namespace IchHabRecht\SocialGdpr\Service;

use GuzzleHttp\Exception\RequestException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class VimeoImageService
{
    /**
     * @var string
     */
    protected $apiUri = 'https://vimeo.com/api/v2/video/###ID###.json';

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    public function __construct(RequestFactory $requestFactory = null)
    {
        $this->requestFactory = $requestFactory ?: GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function getPreviewImage($id): string
    {
        $filename = GeneralUtility::getFileAbsFileName('typo3temp/assets/tx_socialgdpr/vimeo_' . md5($id) . '.jpg');
        $fileExists = file_exists($filename);

        if (!$fileExists) {
            try {
                $uri = str_replace('###ID###', $id, $this->apiUri);
                $response = $this->requestFactory->request($uri);
                if ($response->getStatusCode() === 200) {
                    $json = json_decode($response->getBody()->getContents(), true);
                    if (!empty($json[0]['thumbnail_large']) || !empty($json[0]['thumbnail_medium']) || !empty($json[0]['thumbnail_small'])) {
                        $thumbnailUri = $json[0]['thumbnail_large'] ?: $json[0]['thumbnail_medium'] ?: $json[0]['thumbnail_small'];
                        $thumbnailResponse = $this->requestFactory->request($thumbnailUri);
                        if ($thumbnailResponse->getStatusCode() === 200) {
                            GeneralUtility::writeFileToTypo3tempDir($filename, $thumbnailResponse->getBody()->getContents());
                            $fileExists = true;
                        }
                    }
                }
            } catch (RequestException $e) {
            }
        }

        return $fileExists ? PathUtility::getAbsoluteWebPath($filename) : '';
    }
}